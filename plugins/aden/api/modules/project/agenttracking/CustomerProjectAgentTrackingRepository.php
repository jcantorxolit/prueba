<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Project\AgentTracking;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerProjectAgentTrackingRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerProjectAgentTrackingModel());

        $this->service = new CustomerProjectAgentTrackingService();
    }

    public function all($criteria)
    {
        $this->setColumns([
"id" => "wg_customer_project_agent_tracking.id",
"projectAgentId" => "wg_customer_project_agent_tracking.project_agent_id",
"type" => "wg_customer_project_agent_tracking.type",
"observation" => "wg_customer_project_agent_tracking.observation",
"estimatedhours" => "wg_customer_project_agent_tracking.estimatedHours",
"assignedhours" => "wg_customer_project_agent_tracking.assignedHours",
"scheduledhours" => "wg_customer_project_agent_tracking.scheduledHours",
"runninghours" => "wg_customer_project_agent_tracking.runningHours",
"createdby" => "wg_customer_project_agent_tracking.createdBy",
"updatedby" => "wg_customer_project_agent_tracking.updatedBy",
"createdAt" => "wg_customer_project_agent_tracking.created_at",
"updatedAt" => "wg_customer_project_agent_tracking.updated_at",
]);

        $this->parseCriteria($criteria);

        $query = $this->query();

		/* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_project_agent_tracking.parent_id', '=', 'tableParent.id');
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

        $entityModel->projectAgentId = $entity->projectAgentId ? $entity->projectAgentId->id : null;
$entityModel->type = $entity->type;
$entityModel->observation = $entity->observation;
$entityModel->estimatedhours = $entity->estimatedhours;
$entityModel->assignedhours = $entity->assignedhours;
$entityModel->scheduledhours = $entity->scheduledhours;
$entityModel->runninghours = $entity->runninghours;
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
$entity->type = $model->type;
$entity->observation = $model->observation;
$entity->estimatedhours = $model->estimatedhours;
$entity->assignedhours = $model->assignedhours;
$entity->scheduledhours = $model->scheduledhours;
$entity->runninghours = $model->runninghours;
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
