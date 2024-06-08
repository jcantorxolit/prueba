<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemCriterion0312;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312\MinimumStandardItem0312Repository;

class MinimumStandardItemCriterion0312Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new MinimumStandardItemCriterion0312Model());

        $this->service = new MinimumStandardItemCriterion0312Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_minimum_standard_item_criterion_0312.id",
            "size" => "wg_customer_employee_number.item AS size",
            "riskLevel" => "wg_customer_risk_level.item AS risk_level",
            "description" => "wg_minimum_standard_item_criterion_0312.description",
            "minimumStandardItemId" => "wg_minimum_standard_item_criterion_0312.minimum_standard_item_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        
		$query->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_employee_number')), function ($join) {
            $join->on('wg_customer_employee_number.value', '=', 'wg_minimum_standard_item_criterion_0312.size');
		})->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_risk_level')), function ($join) {
            $join->on('wg_customer_risk_level.value', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
		});
		
        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
        $size = $entity->size ? $entity->size->value : null;
        $riskLevel = $entity->riskLevel ? $entity->riskLevel->value : null;        

        if (!$entity->id) {
            return !$this->model->whereMinimumStandardItemId($entity->minimumStandardItemId)
                ->whereSize($size)
                ->whereRiskLevel($riskLevel)                
                ->count() > 0;
        } else {
            $entityModel = $this->find($entity->id);
            $entityToCompare = $this->model->whereMinimumStandardItemId($entity->minimumStandardItemId)
                ->whereSize($size)
                ->whereRiskLevel($riskLevel)                
                ->first();

            if ($entityToCompare !== null && $entityModel !== null) {
                return $entityModel->id == $entityToCompare->id;
            }
        }

        return true;
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
        $entityModel->size = $entity->size ? $entity->size->value : null;
        $entityModel->riskLevel = $entity->riskLevel ? $entity->riskLevel->value : null;
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
            $entity->item = (new MinimumStandardItem0312Repository())->parseModelWithRelations($model->getItem());
            $entity->size = $model->getSize();
            $entity->riskLevel = $model->getRiskLevel();
            $entity->description = $model->description;

            return $entity;
        } else {
            return null;
        }
    }
}
