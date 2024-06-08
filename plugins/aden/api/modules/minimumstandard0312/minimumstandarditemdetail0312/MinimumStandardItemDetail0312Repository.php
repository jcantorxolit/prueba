<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemDetail0312;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class MinimumStandardItemDetail0312Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new MinimumStandardItemDetail0312Model());

        $this->service = new MinimumStandardItemDetail0312Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_minimum_standard_item_detail_0312.id",
            "minimumStandardItemId" => "wg_minimum_standard_item_detail_0312.minimum_standard_item_id",
            "type" => "wg_minimum_standard_item_detail_0312.type",
            "description" => "wg_minimum_standard_item_detail_0312.description",
            "createdAt" => "wg_minimum_standard_item_detail_0312.created_at",
            "createdBy" => "wg_minimum_standard_item_detail_0312.created_by",
            "updatedAt" => "wg_minimum_standard_item_detail_0312.updated_at",
            "updatedBy" => "wg_minimum_standard_item_detail_0312.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_minimum_standard_item_detail_0312.parent_id', '=', 'tableParent.id');
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

        $entityModel->minimumStandardItemId = $entity->minimumStandardItemId;
        $entityModel->type = $entity->type;
        $entityModel->description = $entity->description;

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
        foreach ($records as $record) {
            $record->minimumStandardItemId = $entityId;
            $this->insertOrUpdate($record);
        }

        return true;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }
        
        $entityModel->delete();

        $result["result"] = true;

        return $result;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->minimumStandardItemId = $model->minimumStandardItemId;
            $entity->type = $model->getType();
            $entity->description = $model->description;
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
