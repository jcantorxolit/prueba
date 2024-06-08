<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Intervention;

use DB;
use AdeN\Api\Classes\BaseService;

use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionEvaluationAnswerModel;


class InterventionService extends BaseService
{

    public function getQuestion($evaluationId, $classificationId, $isHistorical) {
        $data = collect();

        $qAgentUser = CustomerModel::getRelatedAgentAndUser('agent_user');

        $query = DB::table('wg_customer_job_condition_self_evaluation_answers as ans')
            ->join('wg_customer_job_condition_self_evaluation as eval', 'eval.id', '=', 'ans.self_evaluation_id')
            ->join('wg_customer_job_condition as jc', 'jc.id', '=', 'eval.job_condition_id')
            ->join('wg_customer_job_condition_questions as q', 'q.id', '=', 'ans.question_id')
            ->join('wg_customer_job_condition_classification_questions as cq', 'cq.question_id', '=', 'q.id')
            ->join('wg_customer_job_condition_classification as subcla', 'subcla.id', '=', 'cq.classification_id')
            ->join('wg_customer_job_condition_classification as cla', 'cla.id', '=', 'subcla.parent_id')
            ->leftjoin('wg_customer_job_condition_self_evaluation_answer_interventions as i', function($join) use ($isHistorical) {
                $join->on('i.self_evaluation_answer_id', '=', 'ans.id');
                $join->where('i.is_historical', $isHistorical);
            })
            ->leftjoin(DB::raw("({$qAgentUser})"), function ($join) {
                $join->on('agent_user.customer_id', '=', 'jc.customer_id');
                $join->on('agent_user.type', '=', 'i.responsible_type');
                $join->on('agent_user.id', '=', 'i.responsible_id');
            })
            ->when($isHistorical, function ($query) use ($isHistorical) {
                $query->where('i.is_historical', $isHistorical);
            })
            ->where('ans.self_evaluation_id', $evaluationId)
            ->where('subcla.parent_id', $classificationId)
            ->where('ans.initial', 0)
            ->select(
                'ans.id AS selfEvaluationAnswerId',
                'eval.state as stateEvaluation',
                'ans.question_id as questionId',
                'q.name as question',
                'cla.name AS classification',
                'i.id AS interventionId',
                'i.name',
                'i.description',
                'i.responsible_id',
                'i.budget',
                DB::raw("DATE_FORMAT(i.execution_date, '%d/%m/%Y') as executionDate"),
                'i.is_closed AS isClosed',
                'i.is_closed AS isClosedOriginal',
                'i.is_historical AS isHistorical',
                'i.files_info',
                'agent_user.id AS responsibleId',
                'agent_user.type AS responsibleType',
                'agent_user.name AS responsibleName',
                'agent_user.email AS responsibleEmail'
            );

        if (!$isHistorical) {
            $query->where('ans.answer', JobConditionEvaluationAnswerModel::NOCOMPLY);
        }

        $results = $query->get();

        if (!empty($results)) {
            foreach ($results as $result) {
                if ($data->contains('selfEvaluationAnswerId', $result->selfEvaluationAnswerId)) {
                    continue;
                }

                $temp = new \stdClass();
                $temp->selfEvaluationAnswerId = $result->selfEvaluationAnswerId;
                $temp->stateEvaluation = $result->stateEvaluation;
                $temp->questionId = $result->questionId;
                $temp->question = $result->question;
                $temp->classification = $result->classification;
                $temp->interventions = $results
                    ->where('selfEvaluationAnswerId', $result->selfEvaluationAnswerId)
                    ->filter(function($item) {
                        return !empty($item->interventionId);
                    })
                    ->map(function($intervention) {
                        $intervention->files = $this->getFiles($intervention->files_info);
                        $intervention->responsible = [
                            'id' => $intervention->responsibleId,
                            'type' => $intervention->responsibleType,
                            'name' => $intervention->responsibleName,
                            'email' => $intervention->responsibleEmail
                        ];

                        return $intervention;
                    })
                    ->values();

                $data->push($temp);
            }
        }

        return $data;
    }


    public function questionHasOpenInterventions($answerId) {
        return InterventionModel::query()
            ->where('self_evaluation_answer_id', $answerId)
            ->where('is_historical', false)
            ->where('is_closed', false)
            ->exists();
    }

    public function evaluationHasOpenedInterventions($evaluationId) {
        return DB::table('wg_customer_job_condition_self_evaluation as eval')
            ->join('wg_customer_job_condition_self_evaluation_answers as ans2', 'ans2.self_evaluation_id', '=', 'eval.id')
            ->join('wg_customer_job_condition_self_evaluation_answer_interventions as i', 'i.self_evaluation_answer_id', '=', 'ans2.id')
            ->where('eval.id', $evaluationId)
            ->where('i.is_closed', false)
            ->exists();
    }


    public function getFiles($filesInfo) {
        $result = [];
        if (!empty($filesInfo)) {
            $result = json_decode($filesInfo) ?: [];
        }

        return $result;
    }

}
