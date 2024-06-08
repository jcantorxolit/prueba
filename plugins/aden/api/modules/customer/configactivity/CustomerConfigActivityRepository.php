<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigActivity;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use System\Models\File;

class CustomerConfigActivityRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigActivityModel());

        $this->service = new CustomerConfigActivityService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_activity.id",
            "customerId" => "wg_customer_config_activity.customer_id",
            "name" => "wg_customer_config_activity.name",
            "status" => "wg_customer_config_activity.status",
            "isCritical" => "wg_customer_config_activity.isCritical",
            "createdby" => "wg_customer_config_activity.createdBy",
            "updatedby" => "wg_customer_config_activity.updatedBy",
            "createdAt" => "wg_customer_config_activity.created_at",
            "updatedAt" => "wg_customer_config_activity.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_activity.parent_id', '=', 'tableParent.id');
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

        $entityModel->customerId = $entity->customerId ? $entity->customerId->id : null;
        $entityModel->name = $entity->name;
        $entityModel->status = $entity->status;
        $entityModel->iscritical = $entity->iscritical == 1;
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
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->name = $model->name;
            $entity->status = $model->status;
            $entity->iscritical = $model->iscritical;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }

    public function getTemplateFile()
    {
        $instance = CmsHelper::getInstance();
        $filePath = "templates/$instance/PlantillaActividades.xlsx";
        return response()->download(CmsHelper::getStorageTemplateDir($filePath));
    }
}
