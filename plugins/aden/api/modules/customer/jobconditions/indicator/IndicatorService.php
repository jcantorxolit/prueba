<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Indicator;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use DB;
use Wgroup\SystemParameter\SystemParameter;

class IndicatorService extends BaseService
{
    public function getDatesEvaluationsByEmployees($customerId, $employeeId)
    {
        $result = DB::table('wg_customer_job_condition as jc')
            ->join('wg_customer_employee as ce', function ($join) use ($customerId, $employeeId) {
                $join->on('ce.id', '=', 'jc.customer_employee_id');
                $join->where('ce.customer_id', $customerId);
                $join->where('ce.employee_id', $employeeId);
            })
            ->join('wg_customer_job_condition_self_evaluation as eval', 'eval.job_condition_id', '=', 'jc.id')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_job_conditions_location', 'location')), function ($join) {
                $join->on('eval.location', '=', 'location.value');
            })
            ->where('jc.customer_id', $customerId)
            ->where('eval.fully_answered', 1)
            ->groupBy('eval.id', 'eval.registration_date', 'location.id')
            ->orderBy('eval.registration_date')
            ->orderBy('location.id')
            ->select(
                'eval.id as evaluationId',
                DB::raw("DATE_FORMAT(eval.registration_date, '%d/%m/%Y') as date"),
                'location.*')
            ->get();

        $data = collect();
        foreach ($result as $item) {
            if ($data->contains('date', $item->date)) {
                continue;
            }

            $temp = new \stdClass();
            $temp->date = $item->date;
            $temp->locations = $result->where('date', $item->date)->values();
            $data[] = $temp;
        }

        return $data;
    }

    // Filters

    public static function getYearToGeneralIndicator($customerId)
    {
        return DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition as jc', 'eval.job_condition_id', '=', 'jc.id')
            ->where('jc.customer_id', $customerId)
            ->where('eval.fully_answered', true)
            ->orderBy('eval.registration_date', 'desc')
            ->select(DB::raw("YEAR(eval.registration_date) as year"))
            ->distinct()
            ->get();
    }

    public static function getLocationToGeneralIndicator($customerId, $year)
    {
        return DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition as jc', 'eval.job_condition_id', '=', 'jc.id')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_customer_job_conditions_location', 'sp')), function ($join) {
                $join->on('eval.location', '=', 'sp.value');
            })
            ->where('jc.customer_id', $customerId)
            ->where('eval.fully_answered', true)
            ->whereRaw("YEAR(eval.registration_date) = $year")
            ->orderBy('sp.item')
            ->select('sp.*')
            ->distinct()
            ->get();
    }

    public function getIndicatorByEvaluation($evaluationId)
    {
        $subquery = DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition_self_evaluation_answers as ans', function ($join) {
                $join->on('ans.self_evaluation_id', '=', 'eval.id');
                $join->whereRaw('ans.initial = 0');
            })
            ->join('wg_customer_job_condition_classification_questions as cq', 'cq.question_id', '=', 'ans.question_id')
            ->join('wg_customer_job_condition_classification as subcla', 'subcla.id', '=', 'cq.classification_id')
            ->join('wg_customer_job_condition_classification as cla', 'cla.id', '=', 'subcla.parent_id')
            ->leftjoin('wg_customer_job_condition_self_evaluation_answer_interventions as i', 'i.self_evaluation_answer_id', '=', 'ans.id')
            ->where('eval.id', $evaluationId)
            ->groupBy('cla.id', 'ans.id')
            ->select('eval.id as evalId', 'eval.risk', 'eval.risk_initial',
                DB::raw("CASE WHEN eval.risk <= 60 THEN 'danger'
                             WHEN eval.risk >= 61 AND eval.risk <= 80 THEN 'warning'
                             ELSE 'success'
                        END AS typeLevelRisk"
                ),
                DB::raw("CASE WHEN eval.risk_initial <= 60 THEN 'danger'
                              WHEN eval.risk_initial > 60 AND eval.risk_initial <= 80 THEN 'warning'
                              ELSE 'success'
                         END AS typeLevelRiskInitial"
                ),
                'cla.id as classificationId', 'cla.name as classification',
                DB::raw("COUNT(ans.id) AS totalAnswers"),
                DB::raw("SUM(IF(ans.answer = 'JCA001', 1, NULL)) AS compliance"),
                DB::raw("SUM(IF(ans.answer = 'JCA002', 1, NULL)) AS fails"),
                DB::raw("SUM(IF(ans.answer = 'JCA003', 1, NULL)) AS noApply"),
                DB::raw("SUM(IF(i.is_closed = 0, 1, NULL)) AS intervention_opens"),
                DB::raw("SUM(IF(i.is_closed = 1, 1, NULL)) AS intervention_closed"),
                DB::raw("SUM(i.budget) AS budget")
            );

        $results = DB::table(DB::raw("({$subquery->toSql()}) as c"))
            ->mergeBindings($subquery)
            ->groupBy('c.classificationId')
            ->select(
                'evalId', 'risk', 'risk_initial', 'typeLevelRisk', 'typeLevelRiskInitial', 'classificationId', 'classification',
                DB::raw("ROUND( (count(compliance) + count(noApply)) / count(totalAnswers) * 100) AS avgClassification"),
                DB::raw("CASE WHEN ROUND( (count(compliance) + count(noApply)) / count(totalAnswers) * 100) <= 60 THEN 'Alto'
                              WHEN ROUND( (count(compliance) + count(noApply)) / count(totalAnswers) * 100) > 61 AND
                                   ROUND( (count(compliance) + count(noApply)) / count(totalAnswers) * 100) <= 80 THEN 'Medio'
                              ELSE 'Bajo'
                         END AS levelRisk"),
                DB::raw("CASE WHEN ROUND((COUNT(compliance) + COUNT(noApply)) / COUNT(totalAnswers) * 100) <= 60 THEN 'danger'
                              WHEN ROUND((COUNT(compliance) + COUNT(noApply)) / COUNT(totalAnswers) * 100) > 61 AND
                                   ROUND((COUNT(compliance) + COUNT(noApply)) / COUNT(totalAnswers) * 100) <= 80 THEN 'warning'
                              ELSE 'success'
                         END AS classificationLabelRisk"),
                DB::raw("count(compliance) as compliance"),
                DB::raw("count(fails) as fails"),
                DB::raw("count(noApply) as noApply"),
                DB::raw("COALESCE(SUM(intervention_opens), 0) as intervention_opens"),
                DB::raw("COALESCE(SUM(intervention_closed), 0) as intervention_closed"),
                DB::raw("COALESCE(SUM(budget), 0) as budget")
            )
            ->get();

        $data = [];
        if (!$results->isEmpty()) {
            $evaluation = new \stdClass();
            $evaluation->id = $results[0]->evalId;
            $evaluation->risk = $results[0]->risk;
            $evaluation->typeLevelRisk = $results[0]->typeLevelRisk;
            $evaluation->risk_initial = $results[0]->risk_initial;
            $evaluation->typeLevelRiskInitial = $results[0]->typeLevelRiskInitial;
            $evaluation->classifications = $results;
            $data = $evaluation;
        }

        return $data;
    }

    /*Indicador general */
    public static function getTotalRiskLevel($criteria)
    {
        $subquery = DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition as jc', 'eval.job_condition_id', '=', 'jc.id')
            ->where("jc.customer_id", $criteria->customerId)
            ->whereRaw("YEAR(eval.registration_date) = $criteria->year")
            ->when($criteria->location, function ($query) use ($criteria) {
                $query->whereRaw("eval.location = '$criteria->location'");
            })
            ->where('eval.fully_answered', true)
            ->select(
                DB::raw("CASE WHEN eval.risk <= 60 THEN 'Alto' WHEN eval.risk > 60 AND eval.risk <= 80 THEN 'Medio' ELSE 'Bajo' END AS levelRisk"),
                'eval.id as evalId'
            );

        $data = DB::table(DB::raw("({$subquery->toSql()}) as stats"))
            ->mergeBindings($subquery)
            ->select(
                DB::raw("count( DISTINCT if(levelRisk = 'Alto',  evalId, null)) AS highPriority"),
                DB::raw("count( DISTINCT if(levelRisk = 'Medio', evalId, null)) AS mediumPriority"),
                DB::raw("count( DISTINCT if(levelRisk = 'Bajo',  evalId, null)) AS lowPriority"),
                DB::raw("count(*) AS quantity"), 'evalId'
            )->first();

        $data->highPriorityPercent = $data->quantity > 0 ? round($data->highPriority * 100) / $data->quantity : 0;
        $data->mediumPriorityPercent = $data->quantity > 0 ? round($data->mediumPriority * 100) / $data->quantity : 0;
        $data->lowPriorityPercent = $data->quantity > 0 ? round($data->lowPriority * 100) / $data->quantity : 0;
        return $data;
    }

    public function getChartPieJobConditionsInterventionStats($customerId, $year = null, $location = null)
    {
        $data = DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition_self_evaluation_answers as answer', 'eval.id', '=', 'answer.self_evaluation_id')
            ->join('wg_customer_job_condition as jc', 'eval.job_condition_id', '=', 'jc.id')
            ->join('wg_customer_job_condition_self_evaluation_answer_interventions as intervention', 'answer.id', '=', 'intervention.self_evaluation_answer_id')
            ->where("jc.customer_id", $customerId)
            ->whereRaw("YEAR(eval.registration_date) = $year")
            ->when($location, function ($query) use ($location) {
                $query->whereRaw("eval.location = '$location'");
            })
            ->select(
                DB::raw("SUM(CASE WHEN intervention.is_closed = 0 THEN 1 ELSE 0 END) AS opened"),
                DB::raw("SUM(CASE WHEN intervention.is_closed = 1 THEN 1 ELSE 0 END) AS closed"),
                DB::raw("SUM(intervention.budget) as budget")
            )->first();

        $chart = [
            [
                "label" => "{$data->opened} Abiertos",
                "value" => $data->opened,
                "color" => '#68bc47',
            ],
            [
                "label" => "{$data->closed} Cerrados",
                "value" => $data->closed,
                "color" => '#6f8896',
            ],
        ];

        $response = new \stdClass();
        $response->chartPie = $this->chart->getChartPie(json_decode(json_encode($chart)));
        $response->percent = $data->closed ? round($data->closed * 100 / ($data->closed + $data->opened)) : 0;
        $response->budget = $data->budget;
        return $response;
    }

    public function getRiskLevel($customerId, $year, $location)
    {
        return DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition as jc', 'eval.job_condition_id', '=', 'jc.id')
            ->where("jc.customer_id", $customerId)
            ->whereRaw("YEAR(eval.registration_date) = '$year' ")
            ->when($location, function ($query) use ($location) {
                $query->whereRaw("eval.location = '$location'");
            })
            ->where('eval.fully_answered', true)
            ->select(
                DB::raw("CASE WHEN eval.risk_initial <= 60 THEN 'Alto' WHEN eval.risk_initial > 60 AND eval.risk_initial <= 80 THEN 'Medio' ELSE 'Bajo' END AS levelRisk"),
                'eval.id as self_evaluation_id',
                'eval.registration_date'
            );
    }

    public function getDataLevelRiskByMonth($customerId, $year, $location)
    {
        $subquery = $this->getRiskLevel($customerId, $year, $location);
        $query = DB::table(DB::raw("({$subquery->toSql()}) as stats"))
            ->mergeBindings($subquery)
            ->groupBy(DB::raw("MONTH(registration_date)"))
            ->select(
                DB::raw("MONTH(registration_date) AS month"),
                DB::raw("count( DISTINCT if(levelRisk = 'Alto',  self_evaluation_id, null)) AS high"),
                DB::raw("count( DISTINCT if(levelRisk = 'Medio', self_evaluation_id, null)) AS medium"),
                DB::raw("count( DISTINCT if(levelRisk = 'Bajo',  self_evaluation_id, null)) AS low")
            );

        $data = DB::table('system_parameters')
            ->leftjoin(DB::raw("({$query->toSql()}) as i"), function ($join) {
                $join->on('i.month', '=', 'system_parameters.value');
            })
            ->mergeBindings($query)
            ->select(
                "system_parameters.item AS label",
                DB::raw("coalesce(i.high, 0) AS high"),
                DB::raw("coalesce(i.medium, 0) AS medium"),
                DB::raw("coalesce(i.low, 0) AS low")
            )
            ->where("system_parameters.group", 'month')
            ->orderBy('system_parameters.id')
            ->get();

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Alto', 'field' => 'high', 'color' => '#D43F3A'],
                ['label' => 'Medio', 'field' => 'medium', 'color' => '#EEA236'],
                ['label' => 'Bajo', 'field' => 'low', 'color' => '#5CB85C'],
            ],
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getSubqueryLevelRisk($indicator, $order, $customerId, $year, $location = null)
    {
        $subquery = $this->getRiskLevel($customerId, $year, $location);
        return DB::table(DB::raw("({$subquery->toSql()}) as stats"))
            ->mergeBindings($subquery)
            ->groupBy(DB::raw("MONTH(registration_date)"))
            ->select(
                DB::raw("MONTH(registration_date) AS period"),
                DB::raw("'$indicator' AS indicator"),
                DB::raw("$order AS `order`"),
                DB::raw("COUNT(DISTINCT IF(levelRisk = '$indicator', self_evaluation_id, NULL)) AS value")
            );
    }

    public function getDataComplianceByPeriod($customerId, $years, $location = null)
    {
        $subquery = DB::table('wg_customer_job_condition_self_evaluation_tracking as h')
            ->where('h.customer_id', $customerId)
            ->whereIn(DB::raw('YEAR(h.date_evaluation)'), $years)
            ->whereIn(DB::raw('YEAR(h.created_at)'), $years)
            ->when($location, function ($query) use ($location) {
                $query->where("h.location", $location);
            })
            ->groupBy(DB::raw('YEAR(created_at)'),
                DB::raw('MONTH(created_at)'),
                'self_evaluation_id'
            )
            ->select(
                DB::raw('YEAR(created_at) AS year'),
                DB::raw('MONTH(created_at) AS month'),
                'self_evaluation_id', 'risk'
            );

        if (in_array(date('Y'), $years)) {
            $subquery->union($this->getQueryCurrentMonthToComplianceByMonthIndicator($customerId, $location));
        }

        $query = DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('year', 'month')
            ->select('year', 'month', DB::raw("round(sum(risk) / count(self_evaluation_id), 2) AS value"));

        $data = DB::table(DB::raw("({$query->toSql()}) as i"))
            ->mergeBindings($query)
            ->groupBy('i.year')
            ->orderBy('i.year', 'desc')
            ->select(
                'year as label',
                DB::raw("sum(case when `month` = 1 then `value` else 0 end) as JAN"),
                DB::raw("sum(case when `month` = 2 then `value` else 0 end) as FEB"),
                DB::raw("sum(case when `month` = 3 then `value` else 0 end) as MAR"),
                DB::raw("sum(case when `month` = 4 then `value` else 0 end) as APR"),
                DB::raw("sum(case when `month` = 5 then `value` else 0 end) as MAY"),
                DB::raw("sum(case when `month` = 6 then `value` else 0 end) as JUN"),
                DB::raw("sum(case when `month` = 7 then `value` else 0 end) as JUL"),
                DB::raw("sum(case when `month` = 8 then `value` else 0 end) as AUG"),
                DB::raw("sum(case when `month` = 9 then `value` else 0 end) as SEP"),
                DB::raw("sum(case when `month` = 10 then `value` else 0 end) as OCT"),
                DB::raw("sum(case when `month` = 11 then `value` else 0 end) as NOV"),
                DB::raw("sum(case when `month` = 12 then `value` else 0 end) as `DEC`")
            )
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartLine($data, $config);
    }

    private function getQueryCurrentMonthToComplianceByMonthIndicator($customerId, $location)
    {
        return DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition as jc', 'eval.job_condition_id', '=', 'jc.id')
            ->where("jc.customer_id", $customerId)
            ->whereRaw("YEAR(eval.registration_date) = year(now())")
            ->where('eval.fully_answered', true)
            ->when($location, function ($query) use ($location) {
                $query->whereRaw("eval.location = '$location'");
            })
            ->groupBy('eval.id')
            ->select(
                DB::raw('year(now()) AS year'),
                DB::raw('month(now()) AS month'),
                'eval.id as self_evaluation_id', 'risk'
            );
    }

    //Report in excel

    public function getInfoExportExcel($criteria)
    {
        $qAgentUser = CustomerModel::getRelatedAgentAndUser('agent_user');
        $query = DB::table("wg_customer_job_condition AS jc")
            ->join("wg_customer_employee AS ce", "ce.id", "=", "jc.customer_employee_id")
            ->join("wg_employee as employee", "ce.employee_id", "=", "employee.id")
            ->join("wg_customer_job_condition_self_evaluation as jcse", "jcse.job_condition_id", "=", "jc.id")
            ->join("wg_customer_job_condition_self_evaluation_answers as jcsea", function ($join) {
                $join->on("jcsea.self_evaluation_id", "=", "jcse.id");
                $join->where("jcsea.initial", 0);
            })
            ->join("wg_customer_job_condition_questions as qu", "jcsea.question_id", "=", "qu.id")
            ->join("wg_customer_job_condition_classification_questions as jccq", "jccq.question_id", "=", "qu.id")
            ->join("wg_customer_job_condition_classification as subcla", "jccq.classification_id", "=", "subcla.id")
            ->join("wg_customer_job_condition_classification as cla", "subcla.parent_id", "=", "cla.id")
            ->leftjoin("wg_customer_job_condition_self_evaluation_answer_interventions as jcseai", "jcseai.self_evaluation_answer_id", "=", "jcsea.id")
            ->leftjoin(DB::raw("({$qAgentUser})"), function ($join) {
                $join->on('agent_user.customer_id', '=', 'jc.customer_id');
                $join->on('agent_user.type', '=', 'jcseai.responsible_type');
                $join->on('agent_user.id', '=', 'jcseai.responsible_id');
            })
            ->where('jc.customer_id', $criteria->customerId)
            ->where('jcsea.answer', '=', 'JCA002')
            ->when($criteria->location, function ($query) use ($criteria) {
                $query->where('jcse.location', $criteria->location);
            })
            ->orderBy('employee.fullName')
            ->select('jcse.registration_date',
                'employee.fullName',
                DB::raw("CONCAT(UPPER(cla.name), ' / ', UPPER(subcla.name)) AS `condition`"),
                'qu.name as question',
                'jcseai.name as interventionPlan',
                'jcseai.description',
                'agent_user.name AS responsibleName',
                'jcseai.budget',
                'jcseai.execution_date',
                DB::raw("
                    CASE WHEN jcseai.id IS NOT NULL AND jcseai.is_closed = 0 THEN 'Abierto'
                    WHEN jcseai.id IS NOT NULL AND jcseai.is_closed = 1 THEN 'Cerrado'
                    ELSE '' END AS status"),
                'jcseai.files_name as evidence'
            );


        if (isset($criteria->typeIndicator) && $criteria->typeIndicator == 'general') {
            $query->whereRaw("YEAR(jcse.registration_date) = $criteria->year");
        } else {
            $query->whereRaw("DATE_FORMAT(jcse.registration_date, '%d/%m/%Y') = '$criteria->date'")
                ->where('employee.id', $criteria->employeeId);
        }

        $data = $query->get();

        if (count($data) > 0) {
            $heading = [];
            if (isset($criteria->typeIndicator) && $criteria->typeIndicator == 'general') {
                $heading['FECHA DE LA AUTOEVALUACIÓN'] = 'registration_date';
            }

            $heading["NOMBRE DEL EMPLEADO"] = "fullName";
            $heading["NOMBRE DE LA CATEGORÍA/CONDICIÓN"] = "condition";
            $heading["PREGUNTA DE LA CONDICIÓN RESPONDIDA COMO NO CUMPLE"] = "question";
            $heading["NOMBRE DEL PLAN DE INTERVENCIÓN"] = "interventionPlan";
            $heading["DESCRIPCIÓN DEL PLAN DE INTERVENCIÓN"] = "description";
            $heading["RESPONSABLE DEL PLAN DE INTERVENCIÓN"] = 'responsibleName';
            $heading["PRESUPUESTO DEL PLAN DE INTERVENCIÓN"] = "budget";
            $heading["FECHA DE EJECUCIÓN DEL PLAN DE INTERVENCIÓN"] = "execution_date";
            $heading["ESTADO DEL PLAN DE INTERVENCIÓN (ABIERTO/CERRADO)"] = "status";
            $heading["NOMBRES DE LOS ARCHIVOS ADJUNTOS SEPARADOS POR COMA"] = "evidence";

            return ExportHelper::headings($data, $heading);
        }

        return null;
    }
}
