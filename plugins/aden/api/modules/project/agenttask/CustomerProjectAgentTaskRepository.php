<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Project\AgentTask;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerProjectAgentTaskRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerProjectAgentTaskModel());

        $this->service = new CustomerProjectAgentTaskService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_project_agent_task.id",
            "projectAgentId" => "wg_customer_project_agent_task.project_agent_id",
            "agentId" => "wg_customer_project_agent_task.agent_id",
            "type" => "wg_customer_project_agent_task.type",
            "task" => "wg_customer_project_agent_task.task",
            "observation" => "wg_customer_project_agent_task.observation",
            "startdatetime" => "wg_customer_project_agent_task.startDateTime",
            "enddatetime" => "wg_customer_project_agent_task.endDateTime",
            "status" => "wg_customer_project_agent_task.status",
            "createdby" => "wg_customer_project_agent_task.createdBy",
            "updatedby" => "wg_customer_project_agent_task.updatedBy",
            "createdAt" => "wg_customer_project_agent_task.created_at",
            "updatedAt" => "wg_customer_project_agent_task.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_project_agent_task.parent_id', '=', 'tableParent.id');
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
                        } catch (Exception $ex) { }
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

        $entityModel->projectAgentId = $entity->projectAgentId ? $entity->projectAgentId->id : null;
        $entityModel->agentId = $entity->agentId ? $entity->agentId->id : null;
        $entityModel->type = $entity->type;
        $entityModel->task = $entity->task;
        $entityModel->observation = $entity->observation;
        $entityModel->startdatetime = $entity->startdatetime ? Carbon::parse($entity->startdatetime)->timezone('America/Bogota') : null;
        $entityModel->enddatetime = $entity->enddatetime ? Carbon::parse($entity->enddatetime)->timezone('America/Bogota') : null;
        $entityModel->status = $entity->status;
        $entityModel->createdby = $entity->createdby;
        $entityModel->updatedby = $entity->updatedby;


        if ($isNewRecord) {
            $entityModel->isDeleted = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->isDeleted = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->projectAgentId = $model->projectAgentId;
            $entity->agentId = $model->agentId;
            $entity->type = $model->type;
            $entity->task = $model->task;
            $entity->observation = $model->observation;
            $entity->startdatetime = $model->startdatetime;
            $entity->enddatetime = $model->enddatetime;
            $entity->status = $model->status;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }

    public function getListTimeLine($criteria)
    {
        return $this->service->getListTimeLine($criteria);
    }
}
