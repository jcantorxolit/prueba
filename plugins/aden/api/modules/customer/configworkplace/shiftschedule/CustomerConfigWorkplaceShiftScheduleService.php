<?php

namespace AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeModel;
use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;

class CustomerConfigWorkplaceShiftScheduleService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getExportData($criteria)
    {
        $customerId = isset($criteria->customerId) ? $criteria->customerId : null;

        $qEmployee = DB::table('wg_customer_config_workplace_shift_schedule_detail_employee')
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.id', '=', 'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->select(
                'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id',
                'wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id',
                'wg_customer_config_workplace_shift_schedule_detail_employee.customer_id',
                'wg_customer_config_workplace_shift_schedule_detail_employee.created_at',
                'wg_customer_config_workplace_shift_schedule_detail_employee.status'
            )
            ->where('wg_customer_config_workplace_shift_schedule.id', $criteria->id)
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.status', ['S', 'C']);

        $query = DB::table('wg_customer_config_workplace_shift_schedule')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_schedule.customer_workplace_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id', '=', 'wg_customer_config_workplace_shift_schedule.id');
            })
            ->leftjoin(DB::raw("({$qEmployee->toSql()}) AS wg_customer_config_workplace_shift_schedule_detail_employee"), function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id', '=', 'wg_customer_config_workplace_shift_schedule_detail.id');
            })
            ->mergeBindings($qEmployee)
            ->leftjoin('wg_customer_employee', function ($join) {
                //$join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->leftjoin('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_workplace_shift_employee_status')), function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.status', '=', 'customer_workplace_shift_employee_status.value');
            })
            ->leftjoin(DB::raw(CustomerEmployeeModel::getRelationInfoDetailByCustomer('employee_info_detail',  $customerId)), function ($join) {
                $join->on('wg_employee.id', '=', 'employee_info_detail.entityId');
            })

            ->select(
                "employee_document_type.item AS documentType",
                "wg_employee.documentNumber",
                "wg_employee.fullName",
                "wg_customer_config_workplace_shift_schedule_detail_employee.created_at AS registerDate",
                "wg_customer_config_workplace_shift_schedule_detail.description",
                "wg_customer_config_workplace_shift_schedule.start_date",
                "wg_customer_config_workplace_shift_schedule.end_date",
                'wg_customer_config_workplace_shift_schedule_detail.start_time',
                'wg_customer_config_workplace_shift_schedule_detail.end_time',
                'wg_customer_config_workplace_shift_schedule_detail.hours',
                "employee_info_detail.telephone",
                "employee_info_detail.email",
                "wg_employee.fullName",
                "wg_customer_config_workplace.name AS workplace",
                DB::raw("CONCAT_WS(' - ', wg_customer_config_workplace_shift_schedule_detail.start_time, wg_customer_config_workplace_shift_schedule_detail.end_time) AS shiftTime"),
                DB::raw("DATEDIFF(wg_customer_config_workplace_shift_schedule.end_date, wg_customer_config_workplace_shift_schedule.start_date) + 1 AS scheduled_days")
            )
            ->where('wg_customer_config_workplace_shift_schedule.id', $criteria->id)
            ->where('wg_customer_config_workplace_shift_schedule_detail.is_deleted', 0);

        $data = $query->get();

        $heading = [
            "CENTRO DE TRABAJO" => "workplace",
            "DÍAS PROGRAMADOS" => "scheduled_days",
            "HORA INICIO" => "start_time",
            "HORA FIN" => "end_time",
            "CANT. HORAS PROGRAMADAS" => "hours",
            "NOMBRE EMPLEADO" => "fullName",
            "IDENTIFICACIÓN EMPLEADO" => "documentNumber",
            "TEL. EMPLEADO" => "telephone",
            "EMAIL EMPLEADO" => "email",
        ];

        return ExportHelper::headings($data, $heading);
    }

    public function getItemList($id)
    {
        $query = DB::table('wg_customer_config_workplace_shift_schedule_detail')
            ->leftjoin('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->select(
                'wg_customer_config_workplace_shift_schedule_detail.id',
                'wg_customer_config_workplace_shift_schedule_detail.start_time',
                'wg_customer_config_workplace_shift_schedule_detail.end_time',
                'wg_customer_config_workplace_shift_schedule_detail.hours',
                'wg_customer_config_workplace_shift_schedule_detail.qty_employee',
                'wg_customer_config_workplace_shift_schedule_detail.description',
                'wg_customer_config_workplace_shift_schedule_detail.is_automatic_used',
                'wg_customer_config_workplace_shift_schedule_detail.is_night_shift'
            )
            ->where('wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id', $id)
            ->where('wg_customer_config_workplace_shift_schedule_detail.is_deleted', 0);

        $employeeList = $this->getSelectedEmployeeCountGrouped($id);

        $selectedEmployeeCountGruped = (new Collection($employeeList));

        return (new Collection($query->get()))->map(function ($item, $index) use ($selectedEmployeeCountGruped) {
          
            $entityDetail = $selectedEmployeeCountGruped->filter(function($detail) use ($item) {
                return $item->id == $detail->id;
            })->first();

            $qtySelectedEmployee = $entityDetail ? $entityDetail->aggregated : 0;

            return [
                'id' => $item->id,
                'startTime' => $item->start_time ? Carbon::createFromFormat('H:i:s', $item->start_time)->subDay() : null,
                'endTime' => $item->end_time ? Carbon::createFromFormat('H:i:s', $item->end_time)->subDay() : null,
                'hours' => $item->hours,
                'qtyEmployee' => $item->qty_employee,
                'description' => $item->description,
                'isAutomaticUsed' => $item->is_automatic_used == 1,
                'isNightShift' => $item->is_night_shift == 1,
                'index' => $index,
                'isOpen' => $index == 0,
                'qtySelectedEmployee' => $qtySelectedEmployee,
                'qtyAvailableEmployee' => $item->qty_employee - $qtySelectedEmployee
            ];
        });
    }

    public function getSelectedEmployeeCountGrouped($id)
    {
        return DB::table('wg_customer_config_workplace_shift_schedule_detail_employee')
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.id', '=', 'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->where('wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id', $id)
            ->where('wg_customer_config_workplace_shift_schedule_detail.is_deleted', 0)
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.status', ['S', 'C'])
            ->select(
                DB::raw('COUNT(*) AS aggregated'),
                'wg_customer_config_workplace_shift_schedule_detail.id'
            )
            ->groupBy(
                'wg_customer_config_workplace_shift_schedule_detail.id'
            )
            ->get();
     }

    public function getEmailData($criteria)
    {
        $customerId = isset($criteria->customerId) ? $criteria->customerId : null;

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
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_workplace_shift_employee_status')), function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.status', '=', 'customer_workplace_shift_employee_status.value');
            })
            ->leftjoin(DB::raw(CustomerEmployeeModel::getRelationInfoDetailByCustomer('employee_info_detail',  $customerId)), function ($join) {
                $join->on('wg_employee.id', '=', 'employee_info_detail.entityId');
            })
            ->select(
                "employee_document_type.item AS documentType",
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
            ->where('wg_customer_config_workplace_shift_schedule.id', $criteria->id)
            ->where('wg_customer_config_workplace_shift_schedule_detail.is_deleted', 0)            
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.status', ['S', 'C']);

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

    public function getSelectedEmployeeCount($id)
    {
        return DB::table('wg_customer_config_workplace_shift_schedule_detail_employee')
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.id', '=', 'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->where('wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id', $id)
            ->where('wg_customer_config_workplace_shift_schedule_detail.is_deleted', 0)
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.status', ['S', 'C'])
            ->count();
    }
}
