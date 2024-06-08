<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDetail40595;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerRoadSafetyItemDetail40595Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerRoadSafetyItemDetail40595Model());

        $this->service = new CustomerRoadSafetyItemDetail40595Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_road_safety_item_detail_40595.id",
            "customerRoadSafetyItemId" => "wg_customer_road_safety_item_detail_40595.customer_road_safety_item_id",
            "roadSafetyItemDetailId" => "wg_customer_road_safety_item_detail_40595.road_safety_item_detail_id",
            "isActive" => "wg_customer_road_safety_item_detail_40595.is_active",
            "isDeleted" => "wg_customer_road_safety_item_detail_40595.is_deleted",
            "createdAt" => "wg_customer_road_safety_item_detail_40595.created_at",
            "createdBy" => "wg_customer_road_safety_item_detail_40595.created_by",
            "updatedAt" => "wg_customer_road_safety_item_detail_40595.updated_at",
            "updatedBy" => "wg_customer_road_safety_item_detail_40595.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_road_safety_item_detail_40595.parent_id', '=', 'tableParent.id');
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

        $entityModel->customerRoadSafetyItemId = $entity->customerRoadSafetyItemId;
        $entityModel->roadSafetyItemDetailId = $entity->roadSafetyItemDetailId;
        $entityModel->isActive = $entity->isActive;
        $entityModel->isDeleted = false;

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

    public function bulkInsertOrUpdate($records, $entityId)
    {
        $this->model->whereCustomerRoadSafetyItemId($entityId)->update(['is_deleted' => 1]);

        foreach ($records as $record) {
            $this->insertOrUpdate($record);
        }

        return true;
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
            $entity->customerRoadSafetyItemId = $model->customerRoadSafetyItemId;
            $entity->roadSafetyItemDetailId = $model->roadSafetyItemDetailId;
            $entity->isActive = $model->isActive;
            $entity->isDeleted = $model->isDeleted;
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
