<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Jobcondition;

use DB;
use AdeN\Api\Classes\BaseService;
use System\Models\Parameters;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\JobConditions\Evaluation\EvaluationModel;

class JobConditionService extends BaseService
{

    public function getJobList($customerId)
    {
        return DB::table('wg_customer_config_job_data')

            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_job_data.customer_id');
            })
            ->select(
                'wg_customer_config_job_data.id',
                'wg_customer_config_job_data.name'
            )
            ->where('wg_customer_config_job_data.customer_id', $customerId)
            ->where('wg_customer_config_job_data.status', '=', 'Activo')
            ->orderBy('wg_customer_config_job_data.name')
            ->get()
            ->toArray();
    }

    public function getOccupationByEmployee($jobConditionId)
    {
        return DB::table('wg_customer_job_condition as jc')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'jc.customer_employee_id')
            ->join('wg_customer_config_job as job', 'job.id', '=', 'ce.job')
            ->join('wg_customer_config_job_data as oc', 'oc.id', '=', 'job.job_id')
            ->where('jc.id', $jobConditionId)
            ->select('oc.*')
            ->first();
    }

    public function getJobConditionByCurrentUser($userId)
    {
        $result = DB::table('wg_customer_user as cu')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'cu.customer_employee_id')
            ->join('wg_employee as e', 'e.id', '=', 'ce.employee_id')
            ->join(DB::raw(SystemParameter::getRelationTable('gender')), function ($join) {
                $join->on('e.gender', '=', 'gender.value');
            })
            ->leftjoin('wg_customer_job_condition as jc', 'jc.customer_employee_id', '=', 'ce.id')
            ->where('cu.user_id', $userId)
            ->select(
                'jc.id as jobConditionId',
                'ce.employee_id as id',
                'ce.customer_id as customerId',
                'e.documentType',
                'e.documentNumber',
                'e.fullName',
                'e.gender'
            )
            ->first();

        $entity = new \stdClass();

        if (empty($result)) {
            return $entity;
        }

        $employee = new \stdClass();
        $employee->id = $result->id;
        $employee->customerId = $result->customerId;
        $employee->documentType = $this->getParameterByValue($result->documentType, "employee_document_type");
        $employee->documentNumber = $result->documentNumber;
        $employee->fullName = $result->fullName;
        $employee->gender = $this->getParameterByValue($result->gender, "gender");

        $entity->employee = $employee;
        $entity->jobConditionId = $result->jobConditionId;
        return $entity;
    }

    public function getClassifications($evaluationId)
    {
        $classifications =  DB::table('wg_customer_job_condition_classification_questions as cq')
            ->join('wg_customer_job_condition_classification as subcla', function ($join) {
                $join->on('subcla.id', '=', 'cq.classification_id');
                $join->where('subcla.is_active', 1);
            })
            ->join('wg_customer_job_condition_classification as cla', function ($join) {
                $join->on('cla.id', '=', 'subcla.parent_id');
                $join->where('subcla.is_active', 1);
            })
            ->join('wg_customer_job_condition_questions as q', function ($join) {
                $join->on('q.id', '=', 'cq.question_id');
                $join->where('q.is_active', 1);
            })
            ->join('wg_customer_job_condition_self_evaluation as eval', function ($join) use ($evaluationId) {
                $join->where('eval.id', $evaluationId);
                $join->on('eval.work_model', '=', 'cq.work_model');
            })
            ->leftjoin('wg_customer_job_condition_self_evaluation_answers as ans', function ($join) {
                $join->on('ans.self_evaluation_id', '=', 'eval.id');
                $join->on('ans.question_id', '=', 'q.id');
                $join->where('ans.initial', 0);
                $join->whereNotNull('ans.answer');
            })
            ->groupBy('cla.id', 'cla.order')
            ->orderBy('cla.order')
            ->select(
                'cla.id', 'cla.name',
                DB::raw("CASE WHEN count(DISTINCT ans.id) = 0 THEN 'pending'
                              WHEN count(DISTINCT ans.id) < count(DISTINCT q.id) THEN 'inProcess'
                              WHEN  count(DISTINCT q.id) = count(DISTINCT ans.id) THEN 'complete'
                          END AS answered")
            )->get();



        if (!empty($classifications)) {
            // marcar la primera pendiente como en proceso
            for ($i = 1; $i < count($classifications); $i++) {
                if ($classifications[$i]->answered == 'pending' && $classifications[$i-1]->answered == 'complete') {
                    $classifications[$i]->answered = 'inProcess';
                    break;
                }
            }
        }

        return $classifications;
    }

    public function getPeriods($jobConditionId)
    {
        return EvaluationModel::query()
            ->where('job_condition_id', $jobConditionId)
            ->orderBy('registration_date', 'desc')
            ->select(DB::raw("DATE_FORMAT(registration_date, '%Y%m') AS date"))
            ->distinct()
            ->get();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public function getIdentificationType()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("employee_document_type")->select("item as NOMBRE")->get()->toArray();
    }

    public function getWorkModel()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("wg_customer_job_conditions_work_model")->select("item as NOMBRE")->get()->toArray();
    }

    public function getWorkLocation()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("wg_customer_job_conditions_location")->select("item as NOMBRE")->get()->toArray();
    }



}
