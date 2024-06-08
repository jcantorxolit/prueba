<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigActivityProcess;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigActivityProcessRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigActivityProcessModel());

        $this->service = new CustomerConfigActivityProcessService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_activity_process.id",
            "activityId" => "wg_customer_config_activity_process.activity_id",
            "workplaceId" => "wg_customer_config_activity_process.workplace_id",
            "macroProcessId" => "wg_customer_config_activity_process.macro_process_id",
            "processId" => "wg_customer_config_activity_process.process_id",
            "isroutine" => "wg_customer_config_activity_process.isRoutine",
            "createdby" => "wg_customer_config_activity_process.createdBy",
            "updatedby" => "wg_customer_config_activity_process.updatedBy",
            "createdAt" => "wg_customer_config_activity_process.created_at",
            "updatedAt" => "wg_customer_config_activity_process.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

		/* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_activity_process.parent_id', '=', 'tableParent.id');
		}
         */


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

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->workplace_id = $entity->workplace ? $entity->workplace->id : null;
        $entityModel->macro_process_id = $entity->macroprocess ? $entity->macroprocess->id : null;
        $entityModel->process_id = $entity->process ? $entity->process->id : null;
        $entityModel->activity_id = $entity->activity ? $entity->activity->id : null;
        $entityModel->isRoutine = $entity->isRoutine;

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

    public function updateRoutine($entity)
    {
        Log::info('updateRoutine');

        $authUser = $this->getAuthUser();

        if (($entityModel = $this->find($entity->id))) {
            $entityModel->isRoutine = $entity->isRoutine;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entityModel;
    }

    public function findOrCreate($model)
    {
        $entity = $this->model->where('workplace_id', $model->workplace->id)
            ->where('macro_process_id', $model->macroprocess->id)
            ->where('process_id', $model->process->id)
            ->where('activity_id', $model->activity->id)
            ->first();

        if ($entity == null) {
            $entity = new \stdClass();
            $entity->id = 0;
            $entity->customerId = $model->customerId;
            $entity->workplace = $model->workplace;
            $entity->macroprocess = $model->macroprocess;
            $entity->process = $model->process;
            $entity->activity = $model->activity;
            $entity->isRoutine = $model->isRoutine;
            $entity = $this->insertOrUpdate($entity);
        } else {
            $entity->isRoutine = $model->isRoutine;
            $entity = $this->updateRoutine($entity);
        }

        return $entity;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->activity = $model->getActivity();
            $entity->workplace = $model->getWorkplace();
            $entity->macroprocess = $model->getMacroprocess();
            $entity->process = $model->getProcess();
            $entity->isRoutine = $model->isRoutine == 1;


            return $entity;
        } else {
            return null;
        }
    }
}
