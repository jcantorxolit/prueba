<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Evaluation;

use DB;
use Log;
use Carbon\Carbon;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Classes\BaseRepository;
use Wgroup\SystemParameter\SystemParameter;

use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionWorkplaceModel;
use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionEvaluationAnswerModel;
use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionEvaluationEvidenceModel;

class EvaluationRepository extends BaseRepository
{
    protected $service;

    public function __construct() {
        parent::__construct(new EvaluationModel());

        $this->service = new EvaluationService();
    }

    public function allEvaluations($criteria, $customFilters)
    {
        $period = CriteriaHelper::getMandatoryFilter($customFilters, "period")->value ?? null;

        $this->setColumns([
            "jobConditionId" => "wg_customer_job_condition_self_evaluation.job_condition_id",
            "date" => DB::raw("DATE_FORMAT(registration_date, '%Y-%m-%d') as date"),
            "workmodel" => "workmodel.item as workmodel",
            "location" => "location.item as location",
            "occupation" => "job.name as occupation",
            "workplace" => "cw.name as workplace",
            "risk" => DB::raw("CASE WHEN risk <= 60 THEN 'Alto'
            WHEN risk >= 61 AND risk <= 80 THEN 'Medio'
            WHEN risk >= 81 THEN 'Bajo' END AS risk "),
            "state" => DB::raw("if(wg_customer_job_condition_self_evaluation.state = 1, 'Abierto', 'Cerrado') AS state"),
            "id" => "wg_customer_job_condition_self_evaluation.id as id",
            "createdBy" => "wg_customer_job_condition_self_evaluation.created_by as createdBy"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query()
            ->join('wg_customer_job_condition as c', 'c.id', '=', 'wg_customer_job_condition_self_evaluation.job_condition_id')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'c.customer_employee_id')
            ->join('wg_customer_job_condition_workplace as cw', 'cw.id', '=', 'wg_customer_job_condition_self_evaluation.workplace_id')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_job_conditions_work_model', 'workmodel')), function ($join) {
                $join->on('wg_customer_job_condition_self_evaluation.work_model', '=', 'workmodel.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_job_conditions_location', 'location')), function ($join) {
                $join->on('wg_customer_job_condition_self_evaluation.location', '=', 'location.value');
            })
            ->leftjoin('wg_customer_config_job_data as job', 'job.id', '=', 'wg_customer_job_condition_self_evaluation.occupationId')
            ->when($period, function ($query) use ($period) {
                $query->whereRaw(DB::raw("DATE_FORMAT(registration_date, '%Y%m') = $period"));
            });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
        }

        $authUser = $this->getAuthUser();
        $userId = $authUser ? $authUser->id : 1;

        $workplaceId = $this->saveWorkplace($entity->workplace);

        $entityModel->job_condition_id = $entity->jobConditionId;
        $entityModel->registration_date = Carbon::createFromFormat("d/m/Y", $entity->date);
        $entityModel->work_model = $entity->workModel->value ?? null;
        $entityModel->location = $entity->location->value ?? null;
        $entityModel->workplace_id = $workplaceId;
        $entityModel->occupationId = $entity->occupation->id;
        $entityModel->state = $entity->state;

        if (empty($entityModel->id)) {
            $entityModel->created_by = $userId;
            $entityModel->fully_answered = 0;
        } else {
            $entityModel->updated_by =  $userId;
        }

        $entityModel->save();

        return $this->parseModelWithRelations($entityModel);
    }

    public function canSave($entity){
        if (empty($entity->location->value)) {
            throw new \Exception('La locación es requerida.');
        }

        if (empty($entity->id)) {
            $this->canCreate($entity);
        } else {
            $this->canUpdate($entity);
        }
    }


    private function canCreate($entity) {
        // al crear auto evaluaciones abiertas, validar que no tenga otra abierta para el mismo lugar de trabajo
        if ($entity->state) {
            $existsInSameLocation = $this->existOpenEvaluationInSameLocation($entity->jobConditionId, $entity->location->value ?? null, $entity->id);
            if ($existsInSameLocation) {
                throw new \Exception('Un lugar de trabajo no puede tener mas de una evaluación abierta.');
            }
        }
    }

    private function canUpdate($entity) {
        // al cerrar evaluación, validar que no tenga intervenciones abiertas
        if (!$entity->state) {
            $hasInterventions = $this->service->hasOpenedInterventions($entity->id);
            if ($hasInterventions) {
                throw new \Exception('No se puede cerrar la evaluación porque tiene planes de intervención abiertos');
            }
        }
    }


    public function saveWorkplace($name) {
        $workplace = JobConditionWorkplaceModel::where('name', $name)->first();
        if (empty($workplace)) {
            $workplace = new JobConditionWorkplaceModel();
            $workplace->name = $name;
            $workplace->save();
        }

        return $workplace->id;
    }


    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            $model = (object)$model;
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->jobConditionId = $model->job_condition_id;
            $entity->date = Carbon::parse($model->registration_date)->format('d/m/Y');
            $entity->workModel = $model->getWorkModel();
            $entity->location = $model->getLocation();
            $entity->workplace = $model->getWorkplace();
            $entity->occupation = $model->getOccupation();
            $entity->state = $model->state;
            $entity->createdBy = $model->created_by;
            return $entity;
        } else {
            return null;
        }
    }


    public function getQuestions($classificationId, $evaluationId) {
        $result = DB::table('wg_customer_job_condition_classification_questions as cq')
            ->join('wg_customer_job_condition_classification as subcla', function($join) {
                $join->on('subcla.id', '=', 'cq.classification_id');
                $join->where('subcla.is_active', 1);
            })
            ->join('wg_customer_job_condition_classification as cla', function($join) {
                $join->on('cla.id', '=', 'subcla.parent_id');
                $join->where('subcla.is_active', 1);
            })
            ->join('wg_customer_job_condition_questions as q', function($join) {
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
            })
            ->where('cla.id', $classificationId)
            ->orderBy('subcla.order')
            ->orderBy('q.order')
            ->select(
                'subcla.id as subClassificationId', 'subcla.name as subClassification',
                'q.id', 'q.name', 'q.order', 'ans.answer', 'ans.id as answerId'
            )->get();


        $subClassifications = $result->groupBy('subClassificationId');
        $data = [];
        foreach ($subClassifications as $subClassification) {
            if (empty($subClassification[0])) {
                continue;
            }

            $temp = new \stdClass();
            $temp->id = $subClassification[0]->subClassificationId;
            $temp->name = $subClassification[0]->subClassification;
            $temp->questions = $subClassification;
            $data[] = $temp;
        }

        return $data;
    }


    public function insertOrUpdateAnswers($entity)
    {
        try {
            DB::beginTransaction();

            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser->id : 1;
            $evaluationIsFullyAnswered = $this->service->evaluationIsFullyAnswered($entity->evaluationId);

            $updateInitialRisk = false;
            foreach ($entity->questionList as $subClassification) {
                foreach ($subClassification->questions as $question) {
                    if (!$evaluationIsFullyAnswered) {
                        $this->saveAnswer($question, $entity->evaluationId, 1, $userId);
                        $updateInitialRisk = true;
                    }

                    $this->saveAnswer($question, $entity->evaluationId, 0, $userId);
                }
            }

            $this->service->updateEvaluationAfterAnswer($entity->evaluationId);
            $this->service->updateRisk($entity->evaluationId, $updateInitialRisk);

            DB::commit();

        } catch (\Exception $exception) {
            DB::rollBack();
            Log::error($exception);
            throw $exception;
        }
    }

    private function saveAnswer($question, $evaluationId, $initial, $userId) {
        if ($initial) {
            $answer  = JobConditionEvaluationAnswerModel::where('self_evaluation_id', $evaluationId)
                ->where('question_id', $question->id)
                ->where('initial', '1')
                ->first();

            if (empty($answer)) {
                $answer = new JobConditionEvaluationAnswerModel();
                $answer->self_evaluation_id = $evaluationId;
                $answer->question_id = $question->id;
                $answer->initial = '1';
            }

        } else {
            $answer = JobConditionEvaluationAnswerModel::findOrNew($question->answerId);
        }

        $answer->self_evaluation_id = $evaluationId;
        $answer->question_id = $question->id;
        $answer->answer =  $question->answer;
        $answer->initial = $initial;

        if (empty($answer->id)) {
            $answer->created_by = $userId;
        } else {
            $answer->updated_by =  $userId;
        }

        $answer->save();
    }


    public function getStats($evaluationId) {
        return $this->service->getStats($evaluationId);
    }


    public function getEvidences($evaluationId, $classificationId) {
        $allPhotos = [];
        $evidences = JobConditionEvaluationEvidenceModel::where('self_evaluation_id', $evaluationId)->where('classification_id', $classificationId)->first();

        if ($evidences) {
            foreach ($evidences->photos as $photo) {
                $photoTmp = new \stdClass;
                $photoTmp->url = $photo->getTemporaryUrl();
                $photoTmp->id = $photo->id;
                $allPhotos[] = $photoTmp;
            }
        }

        return $allPhotos;
    }


    public function exportEvidencesZip($evaluationId, $classificationId)
    {
        $entity = JobConditionEvaluationEvidenceModel::where('self_evaluation_id', $evaluationId)->where('classification_id', $classificationId)->first();

        $filename = 'Evidencias_Condicones_Puesto_Trabajo_' . Carbon::now()->timestamp . '.zip';

        $photos = $entity->photos;
        $photos->map(function($photo) {
            $photo->fullPath = $photo->getTemporaryUrl();
            $photo->filename = $photo->getDiskPath();
        });

        ExportHelper::zipDownload($filename, $photos);
    }

    private function existOpenEvaluationInSameLocation($jobConditionId, $location, $evaluationId = null) {
        return $this->model
            ->where('job_condition_id', $jobConditionId)
            ->where('state', 1)
            ->where('location', $location)
            ->when($evaluationId, function($query) use ($evaluationId) {
                $query->where('id', '<>', $evaluationId);
            })
            ->exists();
    }

}
