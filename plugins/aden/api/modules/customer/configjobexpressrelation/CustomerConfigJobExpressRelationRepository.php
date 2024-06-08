<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigJobExpressRelation;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigActivityExpressRelation\CustomerConfigActivityExpressRelationRepository;
use AdeN\Api\Modules\Customer\ConfigJobExpress\CustomerConfigJobExpressRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\CustomerConfigWorkplaceRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigJobExpressRelationRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigJobExpressRelationModel());

        $this->service = new CustomerConfigJobExpressRelationService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_express_relation.id",
            "customerId" => "wg_customer_config_job_express_relation.customer_id",
            "processExpressRelationId" => "wg_customer_config_job_express_relation.customer_process_express_relation_id",
            "jobExpressId" => "wg_customer_config_job_express_relation.customer_job_express_id",
            "createdBy" => "wg_customer_config_job_express_relation.created_by",
            "updatedBy" => "wg_customer_config_job_express_relation.updated_by",
            "createdAt" => "wg_customer_config_job_express_relation.created_at",
            "updatedAt" => "wg_customer_config_job_express_relation.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_job_express_relation.parent_id', '=', 'tableParent.id');
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
            ->where('customer_process_express_relation_id', $entity->processExpressRelationId)
            ->where('customer_job_express_id', $entity->jobExpressId)
            ->first())) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->customerProcessExpressRelationId = $entity->processExpressRelationId;
        $entityModel->customerJobExpressId = $entity->jobExpressId;
        $entityModel->isActive = $entity->isActive;

        if ($isNewRecord) {
            $entityModel->isFullyConfigured = 0;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        if (isset($entity->activityList)) {
            CustomerConfigActivityExpressRelationRepository::bulkInsertOrUpdate($entity->activityList, $entityModel->id);
        }

        return  $entityModel;
    }

    public function duplicate($entity)
    {
        $authUser = $this->getAuthUser();

        $entityModel = $this->model->newInstance();

        $entityModel->customerId = $entity->customerId;
        $entityModel->customerProcessExpressRelationId = $entity->processExpressRelationId;
        $entityModel->customerJobExpressId = $entity->jobExpressId;
        $entityModel->isActive = true;
        $entityModel->isFullyConfigured = $entity->module == 'A';
        $entityModel->createdBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        if (in_array($entity->module, ['A'])) {
            if (isset($entity->activityList)) {
                CustomerConfigActivityExpressRelationRepository::bulkDuplicate($entity->activityList, $entityModel->id);
            }
        }

        return  $entityModel;
    }

    public static function bulkInsertOrUpdate($jobList, $parentId)
    {
        $reposity = new self;
        $reposityJob = new CustomerConfigJobExpressRepository();

        foreach ($jobList as $job) {
            $entity = new \stdClass();
            $entity->id = 0;
            $entity->customerId = $job->customerId;
            $entity->processExpressRelationId = $parentId;
            $entity->jobExpressId = $reposityJob->findOrCreate($job)->id;
            $entity->isActive = $job->isActive;
            $entity->activityList = isset($job->activityList) ? $job->activityList : [];
            $reposity->insertOrUpdate($entity);
        }
    }

    public static function bulkDuplicate($jobList, $parentId)
    {
        $reposity = new self;

        foreach ($jobList as $job) {
            $job->processExpressRelationId = $parentId;
            $reposity->duplicate($job);
        }
    }

    public static function bulkDelete($parentUids)
    {
        $reposity = new self;

        $parentUids = is_array($parentUids) ? $parentUids : [$parentUids];

        $uidsDeleted = $reposity->model->whereIn('customer_process_express_relation_id', $parentUids)->get(['id'])->toArray();

        if (count($uidsDeleted) > 0) {
            CustomerConfigActivityExpressRelationRepository::bulkDelete($uidsDeleted);
            $reposity->model->whereIn('id', $uidsDeleted)->delete();        
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        CustomerConfigActivityExpressRelationRepository::bulkDelete($entityModel->id);

        //TODO delete activities
        $entityModel->delete();

        CustomerConfigWorkplaceRepository::updateIsFullyConfiguredInCascadeAfterDelete($entityModel->customerProcessExpressRelationId, 'Job');
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->processExpressRelationId = $model->customerProcessExpressRelationId;
            $entity->jobExpressId = $model->customerJobExpressId;
            $entity->createdBy = $model->createdBy;
            $entity->updatedBy = $model->updatedBy;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }

    public static function updateIsFullyConfigured($customerProcessExpressRelationId)
    {
        $repository = new self;
        $authUser = $repository->getAuthUser();
        $repository->service->updateIsFullyConfigured($customerProcessExpressRelationId, $authUser ? $authUser->id : 1);
    }
}
