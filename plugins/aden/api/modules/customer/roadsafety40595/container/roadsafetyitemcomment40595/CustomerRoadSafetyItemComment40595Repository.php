<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemComment40595;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class CustomerRoadSafetyItemComment40595Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerRoadSafetyItemComment40595Model());

        $this->service = new CustomerRoadSafetyItemComment40595Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "comment" => "wg_customer_road_safety_item_comment_40595.comment",
            "type" => "road_safety_item_comment_40595_type.item AS type",
            "createdBy" => "users.name AS createdBy",
            "createdAt" => "wg_customer_road_safety_item_comment_40595.created_at",
            "id" => "wg_customer_road_safety_item_comment_40595.id",
            "customerRoadSafetyItemId" => "wg_customer_road_safety_item_comment_40595.customer_road_safety_item_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('road_safety_item_comment_40595_type')), function ($join) {
            $join->on('road_safety_item_comment_40595_type.value', '=', 'wg_customer_road_safety_item_comment_40595.type');
        })->leftjoin("users", function ($join) {
            $join->on('users.id', '=', 'wg_customer_road_safety_item_comment_40595.created_by');
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

        $entityModel->customerRoadSafetyItemId = $entity->customerRoadSafetyItemId;
        $entityModel->comment = $entity->comment;
        $entityModel->type = $entity->type;

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

    public static function create($entity)
    {
        $newEntity = new \stdClass();

        $newEntity->id = 0;
        $newEntity->customerRoadSafetyItemId = $entity->id;
        $newEntity->comment = $entity->comment;
        $newEntity->type = "A";

        (new self)->insertOrUpdate($newEntity);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        return true;
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
            $entity->comment = $model->comment;
            $entity->type = $model->type;

            return $entity;
        } else {
            return null;
        }
    }
}
