<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigJobActivity;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use AdeN\Api\Modules\Customer\ConfigJobProcess\CustomerConfigJobRepository;
use AdeN\Api\Modules\Customer\ConfigActivityProcess\CustomerConfigActivityProcessRepository;

class CustomerConfigJobActivityRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigJobActivityModel());

        $this->service = new CustomerConfigJobActivityService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity.id",
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "macroprocess" => "wg_customer_config_macro_process.name AS macroprocess",
            "process" => "wg_customer_config_process.name AS process",
            "job" => "wg_customer_config_job_data.name AS job",
            "activity" => "wg_customer_config_activity.name AS activity",
            "updatedby" => "users.name AS updatedBy",
            "updatedAt" => "wg_customer_config_job_activity.updated_at AS updatedAt",
            "customerId" => "wg_customer_config_workplace.customer_id"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

		/* Example relation*/
        $query->join("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_config_job_activity.job_id', '=', 'wg_customer_config_job.id');

        })->join("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');

        })->join("wg_customer_config_activity_process", function ($join) {
            $join->on('wg_customer_config_job_activity.activity_id', '=', 'wg_customer_config_activity_process.id');

        })->join("wg_customer_config_activity", function ($join) {
            $join->on('wg_customer_config_activity_process.activity_id', '=', 'wg_customer_config_activity.id');

        })->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_workplace.id');
            $join->on('wg_customer_config_activity_process.workplace_id', '=', 'wg_customer_config_workplace.id');

        })->join("wg_customer_config_macro_process", function ($join) {
            $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            $join->on('wg_customer_config_activity_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');

        })->join("wg_customer_config_process", function ($join) {
            $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
            $join->on('wg_customer_config_activity_process.process_id', '=', 'wg_customer_config_process.id');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job_activity.updatedby', '=', 'users.id');

        });



        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["recordsTotal"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["recordsFiltered"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function canInsert($entity)
    {
        $repositoryJobProcess = new CustomerConfigJobRepository();
        $repositoryActivityProcess = new CustomerConfigActivityProcessRepository();

        $entity->jobProcess = $repositoryJobProcess->findOrCreate($entity);
        $entity->activityProcess = $repositoryActivityProcess->findOrCreate($entity);

        if (!$entity->id) {
            return !$this->model->where('job_id', $entity->jobProcess->id)
                ->where('activity_id', $entity->activityProcess->id)
                ->count() > 0;
        } else {
            $entityModel = $this->find($entity->id);
            $entityToCompare = $this->model->where('job_id', $entity->jobProcess->id)
                ->where('activity_id', $entity->activityProcess->id)
                ->first();

            if ($entityToCompare !== null && $entityModel !== null) {
                return $entityModel->id == $entityToCompare->id;
            }

            if ($entityModel->job_id != $entity->jobProcess->id) {
                if ($this->model->where('job_id', $entityModel->job_id)->count() < 2) {
                    $repositoryJobProcess->delete($entityModel->job_id);
                }
            }

            if ($entityModel->activity_id != $entity->activityProcess->id) {
                if ($this->model->where('activity_id', $entityModel->activity_id)->count() < 2) {
                    $repositoryActivityProcess->delete($entityModel->activity_id);
                }
            }
        }

        return true;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->job_id = $entity->jobProcess ? $entity->jobProcess->id : null;
        $entityModel->activity_id = $entity->activityProcess ? $entity->activityProcess->id : null;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entityModel;
    }

    public function batch($entity)
    {
        $validateMessages = [];

        foreach ($entity->activityList as $activity) {
            $entity->activity = $activity;
            $entity->isRoutine = $activity->isRoutine;
            if ($this->canInsert($entity)) {
                $this->insertOrUpdate($entity);
                $validateMessages["sucess"] = true;
            } else {
                $validateMessages["error"][] = sprintf("%s, %s, %s, %s, %s. No es posible guardar esta información, ya existe un registro con iguales caracteristicas \n", $entity->workplace->name, $entity->macroprocess->name, $entity->process->name, $entity->job->name, $entity->activity->name);
            }
        }

        $this->service->bulkInsertCriticalActivity();

        return $validateMessages;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->hazardRelation()->delete();
        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();
            $repositoryJob = new CustomerConfigJobRepository();
            $repositoryActivity = new CustomerConfigActivityProcessRepository();

            $jobProcess = $repositoryJob->parseModelWithRelations($model->getJobProcess());
            $activityProcess = $repositoryActivity->parseModelWithRelations($model->getActivityProcess());

            $entity->id = $model->id;
            $entity->customerId = $jobProcess && $jobProcess->workplace ? $jobProcess->workplace->customer_id : null;
            $entity->workplace = $jobProcess && $jobProcess->workplace ? $jobProcess->workplace : null;
            $entity->macroprocess = $jobProcess && $jobProcess->macroprocess ? $jobProcess->macroprocess : null;
            $entity->process = $jobProcess && $jobProcess->process ? $jobProcess->process : null;
            $entity->job = $jobProcess && $jobProcess->job ? $jobProcess->job : null;
            $entity->activityList[] = [
                "id" => $activityProcess->activity->id,
                "name" => $activityProcess->activity->name,
                "isRoutine" => $activityProcess->isRoutine == 1,
            ];

            return $entity;
        } else {
            return null;
        }
    }

    public function allList($customerId)
    {
        return $this->service->allList($customerId);
    }

    public function findOne($id)
    {
        return $this->service->findOne($id);
    }
}
