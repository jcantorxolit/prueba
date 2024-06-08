<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Indicator;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use Carbon\Carbon;
use DB;

class IndicatorRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new IndicatorModel());

        $this->service = new IndicatorService();
    }

    public function getDatesEvaluationsByEmployees($customerId, $employeeId)
    {
        return $this->service->getDatesEvaluationsByEmployees($customerId, $employeeId);
    }

    public function getIndicatorByEvaluation($evaluationId)
    {
        return $this->service->getIndicatorByEvaluation($evaluationId);
    }

    public function getChartPieJobConditionsInterventionStats($customerId, $year = null, $location = null)
    {
        return $this->service->getChartPieJobConditionsInterventionStats($customerId, $year, $location);
    }

    public function getDataComplianceByPeriod($customerId, $years, $location = null)
    {
        return $this->service->getDataComplianceByPeriod($customerId, $years, $location);
    }

    public function getDataLevelRiskByMonth($customerId, $year, $location = null)
    {
        return $this->service->getDataLevelRiskByMonth($customerId, $year, $location);
    }

    /*Indicator general*/
    public function getLevelRisksByMonthsList($criteria, $customFilters)
    {
        $customerId = CriteriaHelper::getMandatoryFilter($customFilters, "customerId")->value ?? null;
        $year = CriteriaHelper::getMandatoryFilter($customFilters, "year")->value ?? null;
        $location = CriteriaHelper::getMandatoryFilter($customFilters, "location")->value ?? null;

        $high = $this->service->getSubqueryLevelRisk('Alto', 1, $customerId, $year, $location);
        $middle = $this->service->getSubqueryLevelRisk('Medio', 2, $customerId, $year, $location);
        $low = $this->service->getSubqueryLevelRisk('Bajo', 3, $customerId, $year, $location);

        $high->union($middle)->union($low);

        $query = DB::table(DB::raw("({$high->toSql()}) as i"))
            ->mergeBindings($high)
            ->groupBy('indicator')
            ->orderBy('order', 'desc')
            ->select(
                'indicator',
                DB::raw("sum(case when `period` = 1 then `value` else 0 end) as `JAN`"),
                DB::raw("sum(case when `period` = 2 then `value` else 0 end) as `FEB`"),
                DB::raw("sum(case when `period` = 3 then `value` else 0 end) as `MAR`"),
                DB::raw("sum(case when `period` = 4 then `value` else 0 end) as `APR`"),
                DB::raw("sum(case when `period` = 5 then `value` else 0 end) as `MAY`"),
                DB::raw("sum(case when `period` = 6 then `value` else 0 end) as `JUN`"),
                DB::raw("sum(case when `period` = 7 then `value` else 0 end) as `JUL`"),
                DB::raw("sum(case when `period` = 8 then `value` else 0 end) as `AUG`"),
                DB::raw("sum(case when `period` = 9 then `value` else 0 end) as `SEP`"),
                DB::raw("sum(case when `period` = 10 then `value` else 0 end) as `OCT`"),
                DB::raw("sum(case when `period` = 11 then `value` else 0 end) as `NOV`"),
                DB::raw("sum(case when `period` = 12 then `value` else 0 end) as `DEC`")
            );

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function getInterventions($criteria)
    {
        $year = CriteriaHelper::getMandatoryFilter($criteria, "year")->value ?? null;

        $this->setColumns([
            'classification' => 'cla.name as classification',
            'question' => 'qu.name as question',
            'totalPlans' => DB::raw("COUNT(DISTINCT inter.id) AS totalPlans"),
            'id' => "eval.id as id",
            'customerId' => 'jc.customer_id',
            'classificationId' => 'cla.id as classificationId',
            'questionId' => 'qu.id as questionId',
            'year' => DB::raw('YEAR(eval.registration_date) as year'),
            'location' => 'eval.location',
        ]);

        $query = DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition as jc', 'eval.job_condition_id', '=', 'jc.id')
            ->join('wg_customer_job_condition_self_evaluation_answers as answer', 'answer.self_evaluation_id', '=', 'eval.id')
            ->join('wg_customer_job_condition_self_evaluation_answer_interventions as inter', 'inter.self_evaluation_answer_id', '=', 'answer.id')
            ->join('wg_customer_job_condition_questions as qu', 'qu.id', '=', 'answer.question_id')
            ->join('wg_customer_job_condition_classification_questions as cq', 'cq.question_id', '=', 'qu.id')
            ->join('wg_customer_job_condition_classification as subcla', 'subcla.id', '=', 'cq.classification_id')
            ->join('wg_customer_job_condition_classification as cla', 'cla.id', '=', 'subcla.parent_id')
            ->where('inter.is_closed', 0)
            ->whereRaw("YEAR(eval.registration_date) = '$year' ")
            ->groupBy('cla.id', 'qu.id');

        $this->parseCriteria($criteria);
        $this->applyCriteria($query, $criteria);

        $query = $this->query($query);
        return $this->get($query, $criteria);
    }

    public function getInterventionsByResponsibles($criteria)
    {
        $year = CriteriaHelper::getMandatoryFilter($criteria, "year")->value ?? null;

        $this->setColumns([
            'name' => 'agent_user.name as name',
            'email' => 'agent_user.email as email',
            'assignedPlans' => DB::raw("COUNT(DISTINCT inter.id) AS assignedPlans"),
            'id' => "eval.id as id",
            'customerId' => 'jc.customer_id',
            'year' => DB::raw('YEAR(eval.registration_date) as year'),
            'location' => 'eval.location',
        ]);

        $qAgentUser = CustomerModel::getRelatedAgentAndUser('agent_user');

        $query = DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition as jc', 'eval.job_condition_id', '=', 'jc.id')
            ->join('wg_customer_job_condition_self_evaluation_answers as answer', 'answer.self_evaluation_id', '=', 'eval.id')
            ->join('wg_customer_job_condition_self_evaluation_answer_interventions as inter', 'inter.self_evaluation_answer_id', '=', 'answer.id')
            ->join(DB::raw("({$qAgentUser})"), function ($join) {
                $join->on('agent_user.customer_id', '=', 'jc.customer_id');
                $join->on('agent_user.type', '=', 'inter.responsible_type');
                $join->on('agent_user.id', '=', 'inter.responsible_id');
            })
            ->where('inter.is_closed', 0)
            ->whereRaw("YEAR(eval.registration_date) = '$year' ")
            ->groupBy('agent_user.name');

        $this->parseCriteria($criteria);
        $this->applyCriteria($query, $criteria);

        $query = $this->query($query);
        return $this->get($query, $criteria);
    }

    public function getInterventionsByQuestionsHistorical($criteria)
    {
        $this->setColumns([
            'id' => "inter.id as id",
            'customerId' => "jc.customer_id as customerId",
            'classificationId' => "subcla.parent_id as classificationId",
            'questionId' => 'ans.question_id as questionId',
            'employee' => 'e.fullName as employee',
            'intervention' => 'inter.name as intervention',
            'responsibleName' => 'agent_user.name as responsibleName',
            'date' => 'inter.execution_date as date',
        ]);

        $qAgentUser = CustomerModel::getRelatedAgentAndUser('agent_user');

        $query = DB::table('wg_customer_job_condition_self_evaluation_answer_interventions as inter')
            ->join('wg_customer_job_condition_self_evaluation_answers as ans', 'ans.id', '=', 'inter.self_evaluation_answer_id')
            ->join('wg_customer_job_condition_self_evaluation as eval', 'eval.id', '=', 'ans.self_evaluation_id')
            ->join('wg_customer_job_condition as jc', 'jc.id', '=', 'eval.job_condition_id')
            ->join('wg_customer_employee as we', 'we.id', '=', 'jc.customer_employee_id')
            ->join('wg_employee as e', 'e.id', '=', 'we.employee_id')
            ->join('wg_customer_job_condition_classification_questions as cq', 'cq.question_id', '=', 'ans.question_id')
            ->join('wg_customer_job_condition_classification as subcla', 'subcla.id', '=', 'cq.classification_id')
            ->join(DB::raw("({$qAgentUser})"), function ($join) {
                $join->on('agent_user.customer_id', '=', 'jc.customer_id');
                $join->on('agent_user.type', '=', 'inter.responsible_type');
                $join->on('agent_user.id', '=', 'inter.responsible_id');
            })
            ->where('inter.is_closed', 0)
            ->groupBy('inter.id');


        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    //Report excel general indicator
    public function getInfoExportExcel($criteria)
    {
        $data = $this->service->getInfoExportExcel($criteria);
        if (!empty($data)) {
            $name = $criteria->typeIndicator = 'general' ? 'REPORTE INDICADORES GENERAL' : 'REPORTE INDICADORES POR EMPLEADO';
            $filename = $name . Carbon::now()->timestamp;
            ExportHelper::excel($filename, 'INDICADORES', $data);
        }

        throw new \Exception('No data');
    }

}
