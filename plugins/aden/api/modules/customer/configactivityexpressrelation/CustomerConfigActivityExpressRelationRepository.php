<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigActivityExpressRelation;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigActivityExpress\CustomerConfigActivityExpressRepository;
use AdeN\Api\Modules\Customer\ConfigJobExpressRelation\CustomerConfigJobExpressRelationRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\CustomerConfigWorkplaceRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigActivityExpressRelationRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigActivityExpressRelationModel());

        $this->service = new CustomerConfigActivityExpressRelationService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_activity_express_relation.id",
            "customerId" => "wg_customer_config_activity_express_relation.customer_id",
            "jobExpressRelationId" => "wg_customer_config_activity_express_relation.customer_job_express_relation_id",
            "activityExpressId" => "wg_customer_config_activity_express_relation.customer_activity_express_id",
            "isRoutine" => "wg_customer_config_activity_express_relation.is_routine",
            "createdBy" => "wg_customer_config_activity_express_relation.created_by",
            "updatedBy" => "wg_customer_config_activity_express_relation.updated_by",
            "createdAt" => "wg_customer_config_activity_express_relation.created_at",
            "updatedAt" => "wg_customer_config_activity_express_relation.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_activity_express_relation.parent_id', '=', 'tableParent.id');
		}
		*/


        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->model->where('customer_id', $entity->customerId)
            ->where('customer_job_express_relation_id', $entity->jobExpressRelationId)
            ->where('customer_activity_express_id', $entity->activityExpressId)
            ->first())) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->customerJobExpressRelationId = $entity->jobExpressRelationId;
        $entityModel->customerActivityExpressId = $entity->activityExpressId;
        $entityModel->isRoutine = $entity->isRoutine;


        if ($isNewRecord) {            
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public function duplicate($entity)
    {
        $authUser = $this->getAuthUser();

        $entityModel = $this->model->newInstance();
        $entityModel->customerId = $entity->customerId;
        $entityModel->customerJobExpressRelationId = $entity->jobExpressRelationId;
        $entityModel->customerActivityExpressId = $entity->activityExpressId;
        $entityModel->isRoutine = $entity->isRoutine;
        $entityModel->createdBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        return $this->parseModelWithRelations($entityModel);
    }

    public static function bulkInsertOrUpdate($activityList, $parentId)
    {
        $reposity = new self;
        $reposityJob = new CustomerConfigActivityExpressRepository();

        foreach ($activityList as $activity) {
            $entity = new \stdClass();
            $entity->id = 0;
            $entity->customerId = $activity->customerId;
            $entity->jobExpressRelationId = $parentId;
            $entity->activityExpressId = $reposityJob->findOrCreate($activity)->id;
            $entity->isRoutine = isset($activity->isRoutine) ? $activity->isRoutine : 0;            
            $reposity->insertOrUpdate($entity);
        }
    }

    public static function bulkDuplicate($activityList, $parentId)
    {
        $reposity = new self;

        foreach ($activityList as $activity) {            
            $activity->jobExpressRelationId = $parentId;            
            $reposity->duplicate($activity);
        }
    }

    public static function bulkDelete($parentUids)
    {
        $parentUids = is_array($parentUids) ? $parentUids : [$parentUids];

        $reposity = new self;
        $reposity->model->whereIn('customer_job_express_relation_id', $parentUids)->delete();
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }
        
        $entityModel->delete();

        CustomerConfigWorkplaceRepository::updateIsFullyConfiguredInCascadeAfterDelete($entityModel->customerJobExpressRelationId, 'Activity');
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->jobExpressRelationId = $model->customerJobExpressRelationId;
            $entity->activityExpressId = $model->customerActivityExpressId;
            $entity->isRoutine = $model->isRoutine;
            $entity->createdBy = $model->createdBy;
            $entity->updatedBy = $model->updatedBy;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }
}
