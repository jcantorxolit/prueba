<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\MinimumStandard0312;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class MinimumStandard0312Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new MinimumStandard0312Model());

        $this->service = new MinimumStandard0312Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_minimum_standard_0312.id",            
            "cycle" => "wg_config_minimum_standard_cycle_0312.name AS cycle",
            "parentNumeral" => "wg_minimum_standard_parent_0312.numeral AS parentNumeral",
            "numeral" => "wg_minimum_standard_0312.numeral",
            "description" => "wg_minimum_standard_0312.description",
            "type" => "minimum_standard_type.item AS type",
            "status" => "wg_common_active_status.item AS status",
            "isActive" => "wg_minimum_standard_0312.is_active",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
		$query->leftjoin("wg_config_minimum_standard_cycle_0312", function ($join) {
            $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id');

		})->leftjoin(DB::raw(SystemParameter::getRelationTable('minimum_standard_type')), function ($join) {
            $join->on('minimum_standard_type.value', '=', 'wg_minimum_standard_0312.type');

		})->leftjoin(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
            $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');

		})->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_common_active_status')), function ($join) {
            $join->on('wg_common_active_status.value', '=', 'wg_minimum_standard_0312.is_active');

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

        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->cycleId = $entity->cycle ? $entity->cycle->id : null;
        $entityModel->parentId = $entity->parent ? $entity->parent->id : null;
        $entityModel->numeral = $entity->numeral;
        $entityModel->description = $entity->description;
        $entityModel->isActive = $entity->isActive;


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
            $entity->type = $model->getType();
            $entity->cycle = $model->getCycle();
            $entity->parent = $model->getParent();
            $entity->numeral = $model->numeral;
            $entity->description = $model->description;
            $entity->isActive = $model->isActive == 1;

            return $entity;
        } else {
            return null;
        }
    }

    public function getParentList() 
    {
        return $this->service->getParentList();
    }

    public function getChildList() 
    {
        return $this->model->whereType('C')->whereIsActive(1)->get();
    }

    public function getRateList()
    {
        return $this->service->getRateList();
    }

    public function getRealRateList()
    {
        return $this->service->getRealRateList();
    }
}
