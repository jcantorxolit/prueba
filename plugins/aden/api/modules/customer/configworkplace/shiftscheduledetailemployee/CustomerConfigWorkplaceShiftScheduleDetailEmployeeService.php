<?php

namespace AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeModel;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Collection;
use Log;

class CustomerConfigWorkplaceShiftScheduleDetailEmployeeService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getEmailData($criteria)
    {
        $customerId = isset($criteria->customerId) ? $criteria->customerId : null;
        $status = isset($criteria->status) ? $criteria->status : ['S', 'C'];

        $query = DB::table('wg_customer_config_workplace_shift_schedule_detail_employee')
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.id', '=', 'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_schedule.customer_workplace_id');
            })
            ->join('wg_customer_employee', function ($join) {
                //$join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin(DB::raw(CustomerEmployeeModel::getRelationInfoDetailByCustomer('employee_info_detail',  $customerId)), function ($join) {
                $join->on('wg_employee.id', '=', 'employee_info_detail.entityId');
            })
            ->select(
                "wg_employee.documentNumber",
                "wg_employee.fullName",
                "wg_customer_config_workplace_shift_schedule_detail_employee.created_at AS registerDate",
                "wg_customer_config_workplace_shift_schedule_detail.description",
                "wg_customer_config_workplace_shift_schedule.start_date",
                "wg_customer_config_workplace_shift_schedule.end_date",
                'wg_customer_config_workplace_shift_schedule_detail.start_time',
                'wg_customer_config_workplace_shift_schedule_detail.end_time',
                "employee_info_detail.email",
                "wg_employee.fullName",
                "wg_customer_config_workplace.name AS workplace",
                DB::raw("CONCAT_WS(' - ', wg_customer_config_workplace_shift_schedule_detail.start_time, wg_customer_config_workplace_shift_schedule_detail.end_time) AS shiftTime")
            )
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id', $criteria->employeeId)
            ->where('wg_customer_config_workplace_shift_schedule_detail.is_deleted', 0)
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.status', $status);

        if (isset($criteria->id)) {
            $query->where('wg_customer_config_workplace_shift_schedule_detail_employee.id', $criteria->id);
        }

        if (isset($criteria->customerWorkplaceShiftScheduleDetailId)) {
            $query->where('wg_customer_config_workplace_shift_schedule_detail.id', $criteria->customerWorkplaceShiftScheduleDetailId);
        }

        return (new Collection($query->get()))->map(function ($item, $index) {
            $result = new \stdClass();
            $result->startTime = $item->start_time ? Carbon::createFromFormat('H:i:s', $item->start_time) : null;
            $result->endTime = $item->end_time ? Carbon::createFromFormat('H:i:s', $item->end_time) : null;
            $result->startDate = $item->start_date ? Carbon::parse($item->start_date) : null;
            $result->endDate = $item->end_date ? Carbon::parse($item->end_date) : null;
            $result->subject = $item->workplace;
            $result->email = $item->email;
            $result->name = $item->fullName;

            return $result;
        })->toArray();
    }
}
