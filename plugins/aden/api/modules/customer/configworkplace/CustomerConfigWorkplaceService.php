<?php

namespace AdeN\Api\Modules\Customer\ConfigWorkplace;

use AdeN\Api\Classes\BaseService;
use DB;
use Illuminate\Support\Collection;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;

class CustomerConfigWorkplaceService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList($criteria)
    {
        $query = DB::table('wg_customer_config_workplace')
            ->select(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.name',
                DB::raw('1 AS canExecuteBulkOperation')
            )
            ->where('wg_customer_config_workplace.customer_id', $criteria->customerId)
            ->whereIn('wg_customer_config_workplace.status', ['Activo', 'En progreso']);

        if (isset($criteria->isFullyConfigured)) {
            $query->where('wg_customer_config_workplace.is_fully_configured', $criteria->isFullyConfigured);
        }

        return $query->get();
    }

    public function getWithProcessList($criteria)
    {
        return DB::table('wg_customer_config_workplace')
            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_express_workplace_status')), function ($join) {
                $join->on('matrix_express_workplace_status.value', '=', 'wg_customer_config_workplace.status');
            })
            ->select(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.name',
                DB::raw("CASE WHEN wg_customer_config_workplace.is_fully_configured = 1 THEN matrix_express_workplace_status.item ELSE 'En Proceso' END AS status")
            )
            ->where('wg_customer_config_workplace.customer_id', $criteria->customerId)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('wg_customer_config_workplace.is_fully_configured', '1');
                    $query->where('wg_customer_config_workplace.status', '=', 'Activo', 'or');
                });
                //$query->where('wg_customer_config_workplace.status', '=', 'Activo', 'or');
            })
            ->groupBy(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.customer_id'
            )
            ->orderBy('wg_customer_config_workplace.name')
            ->get();
    }

    public function getWithShiftList($criteria)
    {
        return DB::table('wg_customer_config_workplace')
            ->leftjoin('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_express_workplace_status')), function ($join) {
                $join->on('matrix_express_workplace_status.value', '=', 'wg_customer_config_workplace.status');
            })
            ->select(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.name',
                'wg_customer_config_workplace.qty_eligible_employee AS qtyEligibleEmployee',
                'wg_customer_config_workplace.max_shift_employee AS maxShiftEmployee',
                DB::raw("CASE WHEN wg_customer_config_workplace.is_shift_fully_configured = 1 THEN matrix_express_workplace_status.item ELSE 'En Proceso' END AS status")
            )
            ->where('wg_customer_config_workplace.customer_id', $criteria->customerId)
            ->where('is_shift_configured', 1)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('wg_customer_config_workplace.is_shift_fully_configured', '1');
                    $query->where('wg_customer_config_workplace.status', '=', 'Activo', 'or');
                });
            })
            ->groupBy(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.customer_id'
            )
            ->orderBy('wg_customer_config_workplace.name')
            ->get();
    }

    public function findWorkplaceWithShift($id)
    {
        return DB::table('wg_customer_config_workplace')
            ->leftjoin('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_express_workplace_status')), function ($join) {
                $join->on('matrix_express_workplace_status.value', '=', 'wg_customer_config_workplace.status');
            })
            ->select(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.name',
                'wg_customer_config_workplace.qty_eligible_employee AS qtyEligibleEmployee',
                'wg_customer_config_workplace.max_shift_employee AS maxShiftEmployee',
                DB::raw("CASE WHEN wg_customer_config_workplace.is_shift_fully_configured = 1 THEN matrix_express_workplace_status.item ELSE 'En Proceso' END AS status")
            )
            ->where('wg_customer_config_workplace.id', $id)
            ->where('is_shift_configured', 1)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('wg_customer_config_workplace.is_shift_fully_configured', '1');
                    $query->where('wg_customer_config_workplace.status', '=', 'Activo', 'or');
                });
            })
            ->groupBy(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.customer_id'
            )
            ->orderBy('wg_customer_config_workplace.name')
            ->first();
    }

    public function getDataToCopy($criteria)
    {
        $query = DB::table('wg_customer_config_workplace')
            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->join('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
                $join->on('wg_customer_config_job_express_relation.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->join('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.id', '=', 'wg_customer_config_job_express_relation.customer_job_express_id');
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
            })
            ->join('wg_customer_config_activity_express_relation', function ($join) {
                $join->on('wg_customer_config_activity_express_relation.customer_job_express_relation_id', '=', 'wg_customer_config_job_express_relation.id');
                $join->on('wg_customer_config_activity_express_relation.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
            })
            ->join('wg_customer_config_activity_express', function ($join) {
                $join->on('wg_customer_config_activity_express.id', '=', 'wg_customer_config_activity_express_relation.customer_activity_express_id');
                $join->on('wg_customer_config_activity_express.customer_id', '=', 'wg_customer_config_activity_express_relation.customer_id');
            })
            ->select(
                'wg_customer_config_workplace.id',
                'wg_customer_config_workplace.name',
                'wg_customer_config_workplace.customer_id',
                'wg_customer_config_workplace.country_id',
                'wg_customer_config_workplace.state_id',
                'wg_customer_config_workplace.city_id',
                'wg_customer_config_workplace.economic_activity_id',
                'wg_customer_config_workplace.employee_direct',
                'wg_customer_config_workplace.employee_contractor',
                'wg_customer_config_workplace.employee_mision',
                'wg_customer_config_workplace.status',
                'wg_customer_config_process_express.id AS process_express_id',
                'wg_customer_config_job_express.id AS job_express_id',
                'wg_customer_config_activity_express.id AS activity_express_id',
                'wg_customer_config_activity_express_relation.is_routine',
                DB::raw("? AS module")
            )
            ->addBinding($criteria->module, "select")
            ->where('wg_customer_config_workplace.id', $criteria->workplace->id);

        $collection = new Collection($query->get());

        $workplace = $collection->groupBy('id')->map(function ($items) {
            $process = new Collection($items);

            $item = $process->first();
            $workplace = new \stdClass();
            $workplace->id = 0;
            $workplace->customerId = $item->customer_id;
            $workplace->name = "{$item->name} (Copia)";
            $workplace->country = $item->country_id;
            $workplace->state = $item->state_id;
            $workplace->city = $item->city_id;
            $workplace->status = $item->status;
            $workplace->economicActivity = $item->economic_activity_id;
            $workplace->employeeDirect = $item->employee_direct;
            $workplace->employeeContractor = $item->employee_contractor;
            $workplace->employeeMision = $item->employee_mision;
            $workplace->module = $item->module;

            $workplace->processList = $process->groupBy('process_express_id')->map(function ($items, $key) {

                $jobs = new Collection($items);

                $item = $jobs->first();
                $process = new \stdClass();
                $process->id = 0;
                $process->customerId = $item->customer_id;
                $process->processExpressId = $item->process_express_id;
                $process->module = $item->module;

                $process->jobList = $jobs->groupBy('job_express_id')->filter(function ($item) use ($key) {
                    return $item[0]->process_express_id == $key;
                })->map(function ($items, $key) {

                    $activities = new Collection($items);

                    $item = $activities->first();
                    $job = new \stdClass();
                    $job->id = 0;
                    $job->customerId = $item->customer_id;
                    $job->jobExpressId = $item->job_express_id;
                    $job->module = $item->module;

                    $job->activityList = $activities->filter(function ($item) use ($key) {
                        return $item->job_express_id == $key;
                    })->map(function ($item, $key) {
                        $activity = new \stdClass();
                        $activity->id = 0;
                        $activity->customerId = $item->customer_id;
                        $activity->activityExpressId = $item->activity_express_id;
                        $activity->isRoutine = (string) $item->is_routine;

                        return $activity;
                    });

                    return $job;
                });

                return $process;
            });

            return $workplace;
        })->first();

        return $workplace;
    }

    public function findActivityRelationAfterDelete($id)
    {
        return DB::table('wg_customer_config_workplace')
            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->join('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
                $join->on('wg_customer_config_job_express_relation.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->join('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.id', '=', 'wg_customer_config_job_express_relation.customer_job_express_id');
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
            })
            ->leftjoin('wg_customer_config_activity_express_relation', function ($join) {
                $join->on('wg_customer_config_activity_express_relation.customer_job_express_relation_id', '=', 'wg_customer_config_job_express_relation.id');
                $join->on('wg_customer_config_activity_express_relation.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
            })
            ->leftjoin('wg_customer_config_activity_express', function ($join) {
                $join->on('wg_customer_config_activity_express.id', '=', 'wg_customer_config_activity_express_relation.customer_activity_express_id');
                $join->on('wg_customer_config_activity_express.customer_id', '=', 'wg_customer_config_activity_express_relation.customer_id');
            })
            ->select(
                'wg_customer_config_workplace.customer_id AS customerId',
                'wg_customer_config_workplace.id AS customerWorkplaceId',
                'wg_customer_config_process_express_relation.id AS processExpressRelationId',
                'wg_customer_config_job_express_relation.id AS jobExpressRelationId'
            )
            ->where('wg_customer_config_job_express_relation.id', $id)
            ->first();
    }

    public function findJobRelationAfterDelete($id)
    {
        return DB::table('wg_customer_config_workplace')
            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->leftjoin('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
                $join->on('wg_customer_config_job_express_relation.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->leftjoin('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.id', '=', 'wg_customer_config_job_express_relation.customer_job_express_id');
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
            })
            ->select(
                'wg_customer_config_workplace.customer_id AS customerId',
                'wg_customer_config_workplace.id AS customerWorkplaceId',
                'wg_customer_config_process_express_relation.id AS processExpressRelationId'
            )
            ->where('wg_customer_config_process_express_relation.id', $id)
            ->first();
    }

    public function findProcessRelationAfterDelete($id)
    {
        return DB::table('wg_customer_config_workplace')
            ->leftjoin('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->leftjoin('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->select(
                'wg_customer_config_workplace.customer_id AS customerId',
                'wg_customer_config_workplace.id AS customerWorkplaceId'
            )
            ->where('wg_customer_config_workplace.id', $id)
            ->first();
    }

    public function findProcessRelation($id)
    {
        return DB::table('wg_customer_config_workplace')
            ->join('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->select(
                'wg_customer_config_workplace.customer_id AS customerId',
                'wg_customer_config_workplace.id AS customerWorkplaceId'
            )
            ->where('wg_customer_config_process_express_relation.id', $id)
            ->first();
    }

    public function updateIsFullyConfigured($customerId, $userId)
    {
        $qProcessRelation = DB::table('wg_customer_config_process_express_relation')
            ->select(
                'wg_customer_config_process_express_relation.customer_workplace_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_process_express_relation.is_fully_configured THEN 1 ELSE 0 END) qtyProcessFullyConfigured'),
                DB::raw('COUNT(*) qtyProcess')
            )
            ->groupBy('wg_customer_config_process_express_relation.customer_workplace_id');

        $query = DB::table('wg_customer_config_workplace')
            ->leftjoin('wg_customer_config_process_express_relation', function ($join) {
                $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->leftjoin(DB::raw("({$qProcessRelation->toSql()}) AS wg_customer_config_process_express_relation_group"), function ($join) {
                $join->on('wg_customer_config_process_express_relation_group.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->mergeBindings($qProcessRelation)
            ->select(
                'wg_customer_config_workplace.id',
                DB::raw('SUM(CASE WHEN wg_customer_config_process_express_relation.customer_workplace_id IS NOT NULL THEN 1 ELSE 0 END) qty'),
                DB::raw('CASE WHEN wg_customer_config_process_express_relation_group.qtyProcess = wg_customer_config_process_express_relation_group.qtyProcessFullyConfigured AND wg_customer_config_process_express_relation_group.qtyProcessFullyConfigured > 0 THEN 1 ELSE 0 END is_workplace_fully_configured')
            )
            ->where('wg_customer_config_workplace.customer_id', $customerId)
            ->whereNotNull('wg_customer_config_process_express_relation_group.customer_workplace_id')
            ->groupBy('wg_customer_config_workplace.id');


        return DB::table('wg_customer_config_workplace')
            ->leftjoin(DB::raw("({$query->toSql()}) AS wg_customer_config_workplace_fully_configured"), function ($join) {
                $join->on('wg_customer_config_workplace_fully_configured.id', '=', 'wg_customer_config_workplace.id');
            })
            ->mergeBindings($query)
            ->where('wg_customer_config_workplace.customer_id', $customerId)
            ->update([
                'wg_customer_config_workplace.is_fully_configured' => DB::raw('CASE WHEN wg_customer_config_workplace_fully_configured.is_workplace_fully_configured IS NOT NULL 
                    THEN wg_customer_config_workplace_fully_configured.is_workplace_fully_configured ELSE 0 END'),
                'wg_customer_config_workplace.updated_by' => DB::raw($userId),
                'wg_customer_config_workplace.updated_at' => DB::raw('NOW()')
            ]);
    }

    public function findElegibleEmployee($criteria)
    {
        DB::table('wg_customer_covid_bolivar')
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_bolivar.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
            })
            ->join('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->where('wg_customer_employee.customer_id', $criteria->customerId)
            ->where('wg_customer_covid_bolivar.customer_id', $criteria->customerId)
            ->update([
                'wg_customer_covid_bolivar.age' => DB::raw("CASE WHEN wg_employee.age = 0 THEN TIMESTAMPDIFF( YEAR, wg_employee.birthdate, CURDATE()  ) else wg_employee.age END"),
                'wg_customer_covid_bolivar.gender' => DB::raw("wg_employee.gender")
            ]);

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
            ->whereRaw("wg_customer_covid_bolivar.customer_id = " . $criteria->customerId);

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
            ->whereRaw("wg_customer_covid_bolivar.customer_id = " . $criteria->customerId);

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
            ->whereRaw("wg_customer_covid_bolivar.customer_id = " . $criteria->customerId)
            ->whereRaw("wg_customer_config_workplace.customer_id = " . $criteria->customerId);

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
            ->whereRaw("wg_customer_covid_bolivar.customer_id = " . $criteria->customerId)
            ->whereRaw("wg_customer_config_workplace.customer_id = " . $criteria->customerId);

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
                DB::raw("COUNT(*) total"),
                DB::raw("SUM(CASE WHEN not_elegible.customer_employee_id then 1 else 0 end) qtyNotElegible"),
                DB::raw("COUNT(*) - SUM(CASE WHEN not_elegible.customer_employee_id then 1 else 0 end) qtyElegible")
            )
            ->where("wg_customer_covid_bolivar.customer_id", $criteria->customerId)
            ->groupBy(
                'wg_customer_covid_bolivar.customer_id',
                'wg_customer_covid_bolivar.customer_workplace_id'
            );

        if (isset($criteria->customerWorkplaceId)) {
            $q1->whereRaw('wg_customer_config_workplace.id = ' . $criteria->customerWorkplaceId);
            $q2->whereRaw('wg_customer_config_workplace.id = ' . $criteria->customerWorkplaceId);
            $query->whereRaw('wg_customer_covid_bolivar.customer_workplace_id = ' . $criteria->customerWorkplaceId);
        }

        $data = $query->first();

        return $data ? $data->qtyElegible : 0;
    }
}
