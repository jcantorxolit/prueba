<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigProcessExpressRelation;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigJobExpressRelation\CustomerConfigJobExpressRelationRepository;
use AdeN\Api\Modules\Customer\ConfigProcessExpress\CustomerConfigProcessExpressRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\CustomerConfigWorkplaceRepository;
use DB;
use Exception;
use Log;
use Event;
use Carbon\Carbon;

class CustomerConfigProcessExpressRelationRepository extends BaseRepository
{
    protected $service;

    protected $isBulkOrDuplicateOperation;

    public function __construct()
    {
        parent::__construct(new CustomerConfigProcessExpressRelationModel());

        $this->service = new CustomerConfigProcessExpressRelationService();

        $this->isBulkOrDuplicateOperation = false;
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_process_express_relation.id",
            "customerId" => "wg_customer_config_process_express_relation.customer_id",
            "workplaceId" => "wg_customer_config_process_express_relation.customer_workplace_id",
            "processExpressId" => "wg_customer_config_process_express_relation.customer_process_express_id",
            "createdBy" => "wg_customer_config_process_express_relation.created_by",
            "updatedBy" => "wg_customer_config_process_express_relation.updated_by",
            "createdAt" => "wg_customer_config_process_express_relation.created_at",
            "updatedAt" => "wg_customer_config_process_express_relation.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_process_express_relation.parent_id', '=', 'tableParent.id');
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
            ->where('customer_workplace_id', $entity->workplaceId)
            ->where('customer_process_express_id', $entity->processExpressId)
            ->first())) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->customerWorkplaceId = $entity->workplaceId;
        $entityModel->customerProcessExpressId = $entity->processExpressId;


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

        if (isset($entity->jobList)) {
            CustomerConfigJobExpressRelationRepository::bulkInsertOrUpdate($entity->jobList, $entityModel->id);
            CustomerConfigWorkplaceRepository::updateIsFullyConfiguredInCascadeAfterInsertOrUpdate($entityModel->id, 'Process');
        }

        if (!$this->isBulkOrDuplicateOperation) {
            Event::fire('migrate.gtc45', array($entityModel));
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public function duplicate($entity)
    {
        $authUser = $this->getAuthUser();

        $entityModel = $this->model->newInstance();

        $entityModel->customerId = $entity->customerId;
        $entityModel->customerWorkplaceId = $entity->workplaceId;
        $entityModel->customerProcessExpressId = $entity->processExpressId;
        $entityModel->isFullyConfigured = $entity->module == 'A';
        $entityModel->createdBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        if (in_array($entity->module, ['J', 'A'])) {
            if (isset($entity->jobList)) {
                CustomerConfigJobExpressRelationRepository::bulkDuplicate($entity->jobList, $entityModel->id);
            }
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public static function bulkInsertOrUpdate($processList, $parentId)
    {
        $reposity = new self;
        $reposity->isBulkOrDuplicateOperation = true;

        $reposityProcess = new CustomerConfigProcessExpressRepository();

        foreach ($processList as $process) {
            $entity = new \stdClass();
            $entity->id = 0;
            $entity->customerId = $process->customerId;
            $entity->workplaceId = $parentId;
            $entity->processExpressId = $reposityProcess->findOrCreate($process)->id;
            $reposity->insertOrUpdate($entity);
        }

        $reposity->isBulkOrDuplicateOperation = false;
    }

    public static function bulkDuplicate($processList, $parentId)
    {
        $reposity = new self;
        $reposity->isBulkOrDuplicateOperation = true;

        foreach ($processList as $process) {
            $process->workplaceId = $parentId;
            $reposity->duplicate($process);
        }

        $reposity->isBulkOrDuplicateOperation = false;
    }

    public function copy($entity)
    {
        $data = $this->service->getDataToCopy($entity);
        if ($data) {
            $entity->jobList = $data->jobList;
            if (isset($entity->jobList)) {
                CustomerConfigJobExpressRelationRepository::bulkDuplicate($entity->jobList, $entity->id);
                CustomerConfigWorkplaceRepository::updateIsFullyConfiguredInCascadeAfterInsertOrUpdate($entity->id, 'Process');

                $authUser = $this->getAuthUser();
                $data->updatedBy = $authUser ? $authUser->id : 1;

                Event::fire('migrate.gtc45', array($data));
            }
        }

        return $this->parseModelWithRelations($this->find($entity->id));
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        //TODO delete jobs
        CustomerConfigJobExpressRelationRepository::bulkDelete($entityModel->id);

        $entityModel->delete();

        CustomerConfigWorkplaceRepository::updateIsFullyConfiguredInCascadeAfterDelete($entityModel->customerWorkplaceId, 'Process');
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;

            $reposityProcess = new CustomerConfigProcessExpressRepository();
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->workplaceId = $model->customerWorkplaceId;
            $entity->processExpressId = $model->customerProcessExpressId;
            $entity->process = $reposityProcess->parseModelWithRelations($reposityProcess->find($model->customerProcessExpressId));
            $entity->isFullyConfigured = $model->isFullyConfigured == 1;
            $entity->jobList = $model->getJobList();

            return $entity;
        } else {
            return null;
        }
    }

    public static function updateIsFullyConfigured($customerWorkplaceId)
    {
        $repository = new self;
        $authUser = $repository->getAuthUser();
        $repository->service->updateIsFullyConfigured($customerWorkplaceId, $authUser ? $authUser->id : 1);
    }
}
