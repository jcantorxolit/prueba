<?php

namespace AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeModel;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Log;
use Str;


class CustomerConfigWorkplaceShiftScheduleDetailService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getAvailableEmployeeList($criteria, $customerId)
    {
        $qNonAvailable = $this->prepareNonAvailableEmployeeQuery($customerId);

        $qElegibleEmployee = $this->prepareElegibleEmployeeQuery($customerId);

        $query = DB::table('wg_customer_config_workplace_shift_schedule_detail')
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_schedule.customer_workplace_id');
            })
            ->join('wg_customer_covid_bolivar', function ($join) {
                $join->on('wg_customer_covid_bolivar.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })            
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_bolivar.customer_employee_id');
            })
            ->join('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->join(DB::raw("({$qElegibleEmployee->toSql()}) AS workplace_shift_elegible_employee"), function ($join) {
                $join->on('workplace_shift_elegible_employee.customer_employee_id', '=', 'wg_customer_employee.id');
                $join->on('workplace_shift_elegible_employee.customer_id', '=', 'wg_customer_employee.customer_id');
                $join->on('workplace_shift_elegible_employee.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->mergeBindings($qElegibleEmployee)
            ->leftjoin(DB::raw("({$qNonAvailable->toSql()}) AS wg_customer_config_workplace_shift_schedule_detail_employee"), function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id', '=', 'wg_customer_employee.id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_id', '=', 'wg_customer_config_workplace_shift_schedule.id');
            })
            ->mergeBindings($qNonAvailable)
            ->select(
                'wg_customer_config_workplace_shift_schedule_detail.id as customer_workplace_shift_schedule_detail_id',
                'wg_customer_employee.id as customer_employee_id',
                'wg_customer_config_workplace.customer_id'
            )
            ->whereNull('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id')
            ->where('wg_customer_config_workplace_shift_schedule_detail.id', $criteria->id)
            ->groupBy(
                'wg_customer_employee.id',
                'wg_customer_config_workplace_shift_schedule.customer_workplace_id',
                'wg_customer_config_workplace.customer_id'
            )
            ->orderBy('wg_employee.documentNumber')
            ->take($criteria->qtyEmployee);

        return (new Collection($query->get()))->map(function ($item) {
            return CmsHelper::parseToStdClass([
                'id' => 0,
                'customerWorkplaceShiftScheduleDetailId' => $item->customer_workplace_shift_schedule_detail_id,
                'customerEmployeeId' => $item->customer_employee_id,
                'customerId' => $item->customer_id,
            ]);
        });
    }

    public function prepareNonAvailableEmployeeQuery($customerId)
    {
        return DB::table('wg_customer_config_workplace_shift_schedule_detail_employee')
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.id', '=', 'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_schedule.customer_workplace_id');
            })
            ->select(
                'wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id',
                'wg_customer_config_workplace_shift_schedule.customer_workplace_id',
                'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id'
            )
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.status', ['S', 'C'])
            ->where('wg_customer_config_workplace_shift_schedule_detail_employee.customer_id', $customerId);
    }

    public function prepareElegibleEmployeeQuery($customerId)
    {
        $qPositive = DB::table('wg_customer_covid_bolivar')
            ->join('wg_customer_covid_bolivar_daily', function ($join) {
                $join->on('wg_customer_covid_bolivar_daily.customer_covid_bolivar_id', '=', 'wg_customer_covid_bolivar.id');
            })
            ->select(
                DB::raw("'P014' AS covid_bolivar_question_code"),
                DB::raw("1 AS is_active"),
                'customer_covid_bolivar_id'
            )
            ->whereRaw("wg_customer_covid_bolivar_daily.registration_date BETWEEN DATE_ADD(NOW(), INTERVAL -20 DAY) AND NOW()")
            ->whereRaw("wg_customer_covid_bolivar_daily.diagnostic_exam_covid = 'P'")
            ->whereRaw("wg_customer_covid_bolivar_daily.has_exam_covid = 1")
            ->whereRaw("wg_customer_covid_bolivar.customer_id = " . $customerId);

        $q1 = DB::table('wg_customer_covid_bolivar')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_covid_bolivar.customer_workplace_id');
                $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
            })
            ->select(
                'wg_customer_covid_bolivar.customer_employee_id',
                'wg_customer_covid_bolivar.customer_id',
                'wg_customer_covid_bolivar.customer_workplace_id'
            )
            ->whereRaw("wg_customer_covid_bolivar.age > wg_customer_config_workplace.max_age")
            ->whereRaw("wg_customer_covid_bolivar.customer_id = " . $customerId);

        $q2 = DB::table('wg_customer_config_workplace_shift_condition')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_condition.customer_workplace_id');
            })
            ->join('wg_customer_covid_bolivar_question', function ($join) {
                $join->on('wg_customer_covid_bolivar_question.covid_bolivar_question_code', '=', 'wg_customer_config_workplace_shift_condition.covid_bolivar_question_code');
                $join->on('wg_customer_covid_bolivar_question.is_active', '=', 'wg_customer_config_workplace_shift_condition.is_active');
            })
            ->join('wg_customer_covid_bolivar', function ($join) {
                $join->on('wg_customer_covid_bolivar.id', '=', 'wg_customer_covid_bolivar_question.customer_covid_bolivar_id');
            })
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_bolivar.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
                //$join->on('wg_customer_employee.workPlace', '=', 'wg_customer_covid_bolivar.customer_workplace_id');
                $join->on('wg_customer_config_workplace_shift_condition.customer_workplace_id', '=', 'wg_customer_covid_bolivar.customer_workplace_id');
            })
            ->select(
                'wg_customer_covid_bolivar.customer_employee_id',
                'wg_customer_covid_bolivar.customer_id',
                'wg_customer_covid_bolivar.customer_workplace_id'
            )
            ->whereRaw("wg_customer_config_workplace_shift_condition.is_active = 1")
            ->whereRaw("wg_customer_config_workplace.customer_id = " . $customerId)
            ->whereRaw("wg_customer_covid_bolivar.customer_id  = " . $customerId);

        $q3 = DB::table('wg_customer_config_workplace_shift_condition')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_condition.customer_workplace_id');
            })
            ->join(DB::raw("({$qPositive->toSql()}) AS wg_customer_covid_bolivar_question"), function ($join) {
                $join->on('wg_customer_covid_bolivar_question.covid_bolivar_question_code', '=', 'wg_customer_config_workplace_shift_condition.covid_bolivar_question_code');
                $join->on('wg_customer_covid_bolivar_question.is_active', '=', 'wg_customer_config_workplace_shift_condition.is_active');
            })
            ->mergeBindings($qPositive)
            ->join('wg_customer_covid_bolivar', function ($join) {
                $join->on('wg_customer_covid_bolivar.id', '=', 'wg_customer_covid_bolivar_question.customer_covid_bolivar_id');
            })
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_bolivar.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
                //$join->on('wg_customer_employee.workPlace', '=', 'wg_customer_covid_bolivar.customer_workplace_id');
                $join->on('wg_customer_config_workplace_shift_condition.customer_workplace_id', '=', 'wg_customer_covid_bolivar.customer_workplace_id');
            })
            ->select(
                'wg_customer_covid_bolivar.customer_employee_id',
                'wg_customer_covid_bolivar.customer_id',
                'wg_customer_covid_bolivar.customer_workplace_id'
            )
            ->whereRaw("wg_customer_config_workplace_shift_condition.is_active = 1")
            ->whereRaw("wg_customer_covid_bolivar.customer_id = " . $customerId)
            ->whereRaw("wg_customer_config_workplace.customer_id = " . $customerId);

        $q1->union($q2)->union($q3);

        $qUnion = DB::table(DB::raw("({$q1->toSql()}) as union_not_elegible"))
            ->mergeBindings($q1)
            ->select(
                'customer_employee_id',
                'customer_id',
                'customer_workplace_id'
            )
            ->groupBy(
                'customer_employee_id',
                'customer_id',
                'customer_workplace_id'
            );

        $query = DB::table('wg_customer_covid_bolivar')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_covid_bolivar.customer_workplace_id');
                $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
            })
            ->leftjoin(DB::raw("({$qUnion->toSql()}) AS not_elegible"), function ($join) {
                $join->on('not_elegible.customer_employee_id', '=', 'wg_customer_covid_bolivar.customer_employee_id');
                $join->on('not_elegible.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
                $join->on('not_elegible.customer_workplace_id', '=', 'wg_customer_covid_bolivar.customer_workplace_id');
            })
            ->mergeBindings($qUnion)
            ->select(
                'wg_customer_covid_bolivar.customer_id',
                'wg_customer_covid_bolivar.customer_workplace_id',
                'wg_customer_covid_bolivar.customer_employee_id'
            )
            ->whereNull('not_elegible.customer_employee_id')
            ->where("wg_customer_covid_bolivar.customer_id", $customerId);

        return $query;
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
            ->where('wg_customer_config_workplace_shift_schedule_detail.id', $id)
            ->where('wg_customer_config_workplace_shift_schedule_detail.is_deleted', 0)
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.status', ['S', 'C'])
            ->count();
    }
}
