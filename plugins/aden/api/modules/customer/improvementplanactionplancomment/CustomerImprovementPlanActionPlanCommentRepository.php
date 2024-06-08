<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ImprovementPlanActionPlanComment;

use AdeN\Api\Classes\BaseRepository;
use Carbon\Carbon;
use Exception;

class CustomerImprovementPlanActionPlanCommentRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerImprovementPlanActionPlanCommentModel());

        $this->service = new CustomerImprovementPlanActionPlanCommentService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_improvement_plan_action_plan_comment.id",
            "customerImprovementPlanActionPlanId" => "wg_customer_improvement_plan_action_plan_comment.customer_improvement_plan_action_plan_id",
            "reason" => "wg_customer_improvement_plan_action_plan_comment.reason",
            "createdAt" => "wg_customer_improvement_plan_action_plan_comment.created_at",
            "createdBy" => "wg_customer_improvement_plan_action_plan_comment.created_by",
            "updatedAt" => "wg_customer_improvement_plan_action_plan_comment.updated_at",
            "updatedBy" => "wg_customer_improvement_plan_action_plan_comment.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
        $query->leftjoin("tableParent", function ($join) {
        $join->on('wg_customer_improvement_plan_action_plan_comment.parent_id', '=', 'tableParent.id');
        }
         */

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

        $entityModel->customerImprovementPlanActionPlanId = $entity->customerImprovementPlanActionPlanId;
        $entityModel->reason = $entity->reason;
        $entityModel->oldStatus = $entity->oldStatus;

        if ($isNewRecord) {            
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

    public static function create($entity)
    {
        $repository = new self;

        $entityModel = new \stdClass();
        $entityModel->id = 0;
        $entityModel->customerImprovementPlanActionPlanId = $entity->id;
        $entityModel->reason = $entity->reason;
        $entityModel->oldStatus = $entity->oldStatus;
        $repository->insertOrUpdate($entityModel);
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
            $entity->customerImprovementPlanActionPlanId = $model->customerImprovementPlanActionPlanId;
            $entity->reason = $model->reason;
            $entity->createdAt = $model->createdAt;
            $entity->createdBy = $model->createdBy;
            $entity->updatedAt = $model->updatedAt;
            $entity->updatedBy = $model->updatedBy;

            return $entity;
        } else {
            return null;
        }
    }
}
