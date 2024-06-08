<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Intervention;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\Customer\JobConditions\Evaluation\EvaluationModel;
use AdeN\Api\Modules\Customer\JobConditions\Evaluation\EvaluationService;
use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionEvaluationAnswerModel;
use Carbon\Carbon;use October\Rain\Database\Model;

class InterventionRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new InterventionModel());

        $this->service = new InterventionService();
    }

    public function getQuestions($evaluationId, $classificationId, $isHistorical)
    {
        return $this->service->getQuestion($evaluationId, $classificationId, $isHistorical);
    }

    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->interventionId))) {
            $entityModel = $this->model->newInstance();
        }

        $authUser = $this->getAuthUser();
        $userId = $authUser ? $authUser->id : 1;

        $entityModel->self_evaluation_answer_id = empty($entity->selfEvaluationAnswerId) ? $entityModel->self_evaluation_answer_id : $entity->selfEvaluationAnswerId;
        $entityModel->name = $entity->name;
        $entityModel->description = $entity->description;
        $entityModel->responsible_type = $entity->responsible->type ?? null;
        $entityModel->responsible_id = $entity->responsible->id ?? null;
        $entityModel->budget = $entity->budget;
        $entityModel->execution_date = Carbon::createFromFormat("d/m/Y", $entity->executionDate);
        $entityModel->is_closed = $entity->isClosed;
        $entityModel->updated_by = $userId;

        if (empty($entityModel->id)) {
            $entityModel->is_historical = false;
            $entityModel->created_by = $userId;
        }

        if ($entityModel->is_closed) {
            $entityModel->closed_at = Carbon::now('America/Bogota');
            $entityModel->closed_by = $userId;
        }

        $entityModel->save();

        if ($entityModel->is_closed) {
            $this->updateStateEvaluation($entityModel->self_evaluation_answer_id);
        }

        $entity->interventionId = $entityModel->id;
        return $entity;
    }

    private function updateStateEvaluation($answerId)
    {
        $questionHasOpenInterventions = $this->service->questionHasOpenInterventions($answerId);
        if ($questionHasOpenInterventions) {
            return;
        }

        // change to historical
        InterventionModel::query()
            ->where('self_evaluation_answer_id', $answerId)
            ->where('is_historical', false)
            ->where('is_closed', true)
            ->update([
                'is_historical' => true,
            ]);

        $answer = JobConditionEvaluationAnswerModel::find($answerId);

        $this->updateAnswerToComply($answer);

        $evaluationHasOpenedInterventions = $this->service->evaluationHasOpenedInterventions($answer->self_evaluation_id);
        if (!$evaluationHasOpenedInterventions) {
            $evaluation = EvaluationModel::find($answer->self_evaluation_id);
            $evaluation->state = false;
            $evaluation->save();
        }
    }

    private function updateAnswerToComply($answer)
    {
        $answer->answer = JobConditionEvaluationAnswerModel::COMPLY;
        $answer->save();

        $evaluationService = new EvaluationService();
        $evaluationService->updateRisk($answer->self_evaluation_id, false);
    }


    public function updateFiles($model, $fileInfo)
    {
        $newFile = new \stdClass();
        $newFile->default = true;
        $newFile->url = $fileInfo['path'];
        $newFile->name = $fileInfo['file'];
        $newFile->date = Carbon::now("America/Bogota");
        $newFile->id = $fileInfo['id'];

        $filesInfo = null;
        if (!empty($model->files_info)) {
            $filesInfo = json_decode($model->files_info) ?: [];
        }

        $filesInfo[] = $newFile;

        $model->files_info = json_encode($filesInfo);
        $model->files_name = implode(',', array_map(function ($item) {
            return $item->name;
        }, $filesInfo));

        $model->save();
    }

    public function closeIntervention($interventionId) {
        $authUser = $this->getAuthUser();

        $intervention = InterventionModel::findOrFail($interventionId);
        $intervention->is_closed = true;
        $intervention->closed_at = Carbon::now('America/Bogota');
        $intervention->closed_by = $authUser ? $authUser->id : 1;
        $intervention->save();

        $this->updateStateEvaluation($intervention->self_evaluation_answer_id);
    }


    public function parseModelWithRelations(InterventionModel $model) {
        $model->executionDate = Carbon::parse($model->execuion_date)->format('d/m/Y');
        $model->isClosed = $model->is_closed;
        $model->isClosedOriginal = $model->is_closed;
        $model->files = $this->service->getFiles($model->files_info);
        $model->responsible = $model->getResponsible();
        return $model;
    }

}
