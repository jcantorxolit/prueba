<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemCriterionDetail0312;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class MinimumStandardItemCriterionDetail0312Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new MinimumStandardItemCriterionDetail0312Model());

        $this->service = new MinimumStandardItemCriterionDetail0312Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_minimum_standard_item_criterion_detail_0312.id",
            "description" => "wg_minimum_standard_item_criterion_detail_0312.description",            
            "type" => "wg_minimum_standard_item_criterion_detail_0312.type",
            "minimumStandardItemCriterionId" => "wg_minimum_standard_item_criterion_detail_0312.minimum_standard_item_criterion_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

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

        $entityModel->minimumStandardItemCriterionId = $entity->minimumStandardItemCriterionId;
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
        
        return $this->parseModelWithRelations($entityModel);
    }

    public function bulkInsertOrUpdate($records, $entityId)
    {
        foreach ($records as $record) {
            $record->minimumStandardItemCriterionId = $entityId;
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
            $entity->minimumStandardItemCriterionId = $model->minimumStandardItemCriterionId;
            $entity->type = $model->type;
            $entity->description = $model->description;
            
            return $entity;
        } else {
            return null;
        }
    }
}
