<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ImprovementPlanActionPlan;

use AdeN\Api\Classes\BaseRepository;
use Carbon\Carbon;
use Exception;
use DB;
use AdeN\Api\Modules\Customer\CustomerModel;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\ImprovementPlanActionPlanComment\CustomerImprovementPlanActionPlanCommentRepository;

class CustomerImprovementPlanActionPlanRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerImprovementPlanActionPlanModel());

        $this->service = new CustomerImprovementPlanActionPlanService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_improvement_plan_action_plan.id",
            "endDate" => "wg_customer_improvement_plan_action_plan.endDate",
            "activity" => "wg_customer_improvement_plan_action_plan.activity",
            "responsible" => "responsible.name as responsible",            
            "entry" => "wg_budget.item AS entry",
            "amount" => "wg_customer_improvement_plan_action_plan.amount",
            "status" => "improvement_plan_action_plan_status.item AS status",
            "createdby" => "users.name AS createdBy",
            "statusCode" => "wg_customer_improvement_plan_action_plan.status AS statusCode",
            "customerImprovementPlanCauseRootCauseId" => "wg_customer_improvement_plan_action_plan.customer_improvement_plan_cause_root_cause_id",
            "customerImprovementPlanId" => "wg_customer_improvement_plan_action_plan.customer_improvement_plan_id",
            "customerId" => "wg_customer_improvement_plan.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $query->join("wg_customer_improvement_plan", function ($join) {
            $join->on('wg_customer_improvement_plan_action_plan.customer_improvement_plan_id', '=', 'wg_customer_improvement_plan.id');

        })->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible"), function ($join) {
            $join->on('wg_customer_improvement_plan_action_plan.responsible', '=', 'responsible.id');
            $join->on('wg_customer_improvement_plan_action_plan.responsibleType', '=', 'responsible.type');
            $join->on('wg_customer_improvement_plan.customer_id', '=', 'responsible.customer_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('improvement_plan_action_plan_status')), function ($join) {
            $join->on('wg_customer_improvement_plan_action_plan.status', '=', 'improvement_plan_action_plan_status.value');

        })->leftjoin("wg_budget", function ($join) {
            $join->on('wg_customer_improvement_plan_action_plan.entry', '=', 'wg_budget.id');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_improvement_plan_action_plan.createdBy', '=', 'users.id');

        })->mergeBindings($qAgentUser);

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

        $entityModel->customerImprovementPlanId = $entity->customerImprovementPlanId ? $entity->customerImprovementPlanId->id : null;
        $entityModel->customerImprovementPlanCauseRootCauseId = $entity->customerImprovementPlanCauseRootCauseId ? $entity->customerImprovementPlanCauseRootCauseId->id : null;
        $entityModel->activity = $entity->activity;
        $entityModel->entry = $entity->entry ? $entity->entry->value : null;
        $entityModel->amount = $entity->amount;
        $entityModel->enddate = $entity->enddate ? Carbon::parse($entity->enddate)->timezone('America/Bogota') : null;
        $entityModel->responsible = $entity->responsible ? $entity->responsible->value : null;
        $entityModel->responsibletype = $entity->responsibletype ? $entity->responsibletype->value : null;
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

    public function updateStatus($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
           return;
        }

        $entity->oldStatus = $entityModel->status;

        $entityModel->status = $entity->status ? $entity->status->value : null;        
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->save();

        if (isset($entity->reason) && $entity->reason != null) {
            CustomerImprovementPlanActionPlanCommentRepository::create($entity);            
        }

        return $this->parseModelWithRelations($entityModel);
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
            $entity->customerImprovementPlanId = $model->customerImprovementPlanId;
            $entity->customerImprovementPlanCauseRootCauseId = $model->customerImprovementPlanCauseRootCauseId;
            $entity->activity = $model->activity;
            $entity->entry = $model->getEntry();
            $entity->amount = $model->amount;
            $entity->enddate = $model->enddate;
            $entity->responsible = $model->getResponsible();
            $entity->responsibletype = $model->getResponsibletype();
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

    public static function bulkCancel($id, $reason, $userId)
    {
        $repository = new self;
        $repository->service->bulkCancel($id, $reason, $userId);
    }
}
