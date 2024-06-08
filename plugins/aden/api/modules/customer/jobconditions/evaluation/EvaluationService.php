<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Evaluation;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Modules\Customer\JobConditions\Intervention\InterventionModel;use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionEvaluationAnswerModel;use DB;

class EvaluationService extends BaseService
{
    public function updateEvaluationAfterAnswer($evaluationId) {
        $percent = $this->getStats($evaluationId)->percent;

        $evaluation = EvaluationModel::find($evaluationId);
        $evaluation->fully_answered = $percent == 100;
        $evaluation->save();
    }

    public function updateRisk($evaluationId, $updateInitialRisk)
    {
        $evaluation = EvaluationModel::find($evaluationId);

        if ($evaluation->fully_answered) {
            $evaluation->risk = $this->getRisk($evaluationId);

            if ($updateInitialRisk) {
                $evaluation->risk_initial = $evaluation->risk;
            }

            $evaluation->save();
        }
    }

    public function getRisk($evaluationId)
    {
        return DB::table('wg_customer_job_condition_classification_questions as cq')
            ->join('wg_customer_job_condition_self_evaluation as eval', 'eval.work_model', '=', 'cq.work_model')
            ->leftjoin('wg_customer_job_condition_self_evaluation_answers as ans', function ($join) {
                $join->on('ans.self_evaluation_id', '=', 'eval.id');
                $join->on('ans.question_id', '=', 'cq.question_id');
                $join->where('ans.initial', 0);
                $join->whereIn('ans.answer', ['JCA001', 'JCA003']);
            })
            ->where('eval.id', $evaluationId)
            ->select(DB::raw("ROUND(COUNT(ans.id) / COUNT(cq.question_id) * 100, 2) AS percent"))
            ->first()
            ->percent;
    }

    public function getStats($evaluationId)
    {
        $result = DB::table('wg_customer_job_condition_classification_questions as cq')
            ->join('wg_customer_job_condition_self_evaluation as eval', 'eval.work_model', '=', 'cq.work_model')
            ->leftjoin('wg_customer_job_condition_self_evaluation_answers as ans', function ($join) {
                $join->on('ans.self_evaluation_id', '=', 'eval.id');
                $join->on('ans.question_id', '=', 'cq.question_id');
                $join->where('ans.initial', 0);
                $join->whereNotNull('ans.answer');
            })
            ->where('eval.id', $evaluationId)
            ->select(
                DB::raw('count(cq.question_id) as countQuestions'),
                DB::raw('count(ans.id) as countAnswers'),
                DB::raw('COALESCE(ROUND(count(ans.id) / count(cq.question_id) * 100, 2), 0) as percent')
            )->first();

        $result->percent = (int) $result->percent;
        return $result;
    }


    public function evaluationIsFullyAnswered($evaluationId){
        return EvaluationModel::whereId($evaluationId)->where('fully_answered', true)->exists();
    }


    public function hasOpenedInterventions($evaluationId) {
        return DB::table('wg_customer_job_condition_self_evaluation_answers as ans')
            ->join('wg_customer_job_condition_self_evaluation_answer_interventions as inter', 'inter.self_evaluation_answer_id', '=', 'ans.id')
            ->where('ans.self_evaluation_id', $evaluationId)
            ->where('inter.is_closed', false)
            ->exists();
    }

}
