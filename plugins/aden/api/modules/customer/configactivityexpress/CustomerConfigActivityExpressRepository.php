<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigActivityExpress;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigActivityExpressRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigActivityExpressModel());

        $this->service = new CustomerConfigActivityExpressService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_activity_express.id",
            "customerId" => "wg_customer_config_activity_express.customer_id",
            "name" => "wg_customer_config_activity_express.name",
            "status" => "wg_customer_config_activity_express.status",
            "createdBy" => "wg_customer_config_activity_express.created_by",
            "updatedBy" => "wg_customer_config_activity_express.updated_by",
            "createdAt" => "wg_customer_config_activity_express.created_at",
            "updatedAt" => "wg_customer_config_activity_express.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_activity_express.parent_id', '=', 'tableParent.id');
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

        $entityModel->customerId = $entity->customerId;
        $entityModel->name = $entity->name;
        $entityModel->status = isset($entity->status) ? $entity->status : 1;


        if ($isNewRecord) {            
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entityModel;
    }

    public function findOrCreate($entity)
    {
        if (!($entityModel = $this->model
            ->where('customer_id', $entity->customerId)
            ->where('name', trim($entity->name))
            ->first())) {
            $entityModel = $this->insertOrUpdate($entity);
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
            $entity->customerId = $model->customerId;
            $entity->name = $model->name;
            $entity->status = $model->status;
  
            return $entity;
        } else {
            return null;
        }
    }

    public function getList($criteria)
    {
        return $this->service->getList($criteria);
    }
}
