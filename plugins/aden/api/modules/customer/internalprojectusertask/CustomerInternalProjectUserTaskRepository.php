<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\InternalProjectUserTask;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\Customer\CustomerModel;
use Carbon\Carbon;
use DB;
use Exception;
use Wgroup\SystemParameter\SystemParameter;

class CustomerInternalProjectUserTaskRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerInternalProjectUserTaskModel());

        $this->service = new CustomerInternalProjectUserTaskService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_internal_project_user_task.id",
            "task" => "wg_customer_internal_project_user_task.task",
            "observation" => DB::raw("SUBSTRING(wg_customer_internal_project_user_task.observation, 1, 50) AS shortObservation"),
            "type" => "projectTaskType.item AS type",            
            "startDateTime" => DB::raw("DATE_FORMAT(wg_customer_internal_project_user_task.startDateTime,'%d/%m/%Y') AS startDateTime"),
            "enddDateTime" => DB::raw("DATE_FORMAT(wg_customer_internal_project_user_task.endDateTime,'%d/%m/%Y') AS endDateTime"),              
            "duration" => "wg_customer_internal_project_user_task.duration",
            "agent" => DB::raw("CONCAT(wg_customer_user.firstName,' ',wg_customer_user.lastName) agent"),
            "status" => "project_status.item AS status",
            "statusCode" => "wg_customer_internal_project_user_task.status AS statusCode",
            "projectAgentId" => "wg_customer_internal_project_user_task.project_agent_id",
            "projectId" => "wg_customer_internal_project_user.project_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_customer_internal_project_user", function ($join) {
            $join->on('wg_customer_internal_project_user.id', '=', 'wg_customer_internal_project_user_task.project_agent_id');

        })->join("wg_customer_internal_project", function ($join) {
            $join->on('wg_customer_internal_project.id', '=', 'wg_customer_internal_project_user.project_id');

        })->join("wg_customer_user", function ($join) {
            $join->on('wg_customer_user.id', '=', 'wg_customer_internal_project_user.agent_id');

        })->leftjoin(DB::raw(CustomerModel::getParameterRelation('projectTaskType')), function ($join) {
            $join->on('wg_customer_internal_project_user_task.type', '=', 'projectTaskType.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('project_status')), function ($join) {
            $join->on('wg_customer_internal_project_user_task.status', '=', 'project_status.value');

        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allAgent($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_internal_project_user_task.id",
            "task" => "wg_customer_internal_project_user_task.task",
            "type" => "projectTaskType.item AS type",            
            "startDateTime" => DB::raw("DATE_FORMAT(wg_customer_internal_project_user_task.startDateTime,'%d/%m/%Y') AS startDateTime"),
            "enddDateTime" => DB::raw("DATE_FORMAT(wg_customer_internal_project_user_task.endDateTime,'%d/%m/%Y') AS endDateTime"),              
            "status" => "project_status.item AS status",
            "statusCode" => "wg_customer_internal_project_user_task.status AS statusCode",
            "projectAgentId" => "wg_customer_internal_project_user_task.project_agent_id",
            "projectId" => "wg_customer_internal_project_user.project_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_customer_internal_project_user", function ($join) {
            $join->on('wg_customer_internal_project_user.id', '=', 'wg_customer_internal_project_user_task.project_agent_id');

        })->join("wg_customer_internal_project", function ($join) {
            $join->on('wg_customer_internal_project.id', '=', 'wg_customer_internal_project_user.project_id');

        })->join("wg_customer_user", function ($join) {
            $join->on('wg_customer_user.id', '=', 'wg_customer_internal_project_user.agent_id');

        })->leftjoin(DB::raw(CustomerModel::getParameterRelation('projectTaskType')), function ($join) {
            $join->on('wg_customer_internal_project_user_task.type', '=', 'projectTaskType.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('project_status')), function ($join) {
            $join->on('wg_customer_internal_project_user_task.status', '=', 'project_status.value');

        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
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
}
