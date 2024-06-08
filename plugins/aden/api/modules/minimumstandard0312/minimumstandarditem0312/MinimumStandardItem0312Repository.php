<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItem0312;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemDetail0312\MinimumStandardItemDetail0312Repository;

class MinimumStandardItem0312Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new MinimumStandardItem0312Model());

        $this->service = new MinimumStandardItem0312Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_minimum_standard_item_0312.id",
            "cycle" => "wg_config_minimum_standard_cycle_0312.name AS cycle",
            "parentNumeral" => "wg_minimum_standard_parent_0312.numeral AS parentNumeral",
            "parentDescription" => "wg_minimum_standard_parent_0312.description AS parentDescription",
            "standardNumeral" => "wg_minimum_standard_0312.numeral AS standardNumeral",
            "standardNDescription" => "wg_minimum_standard_0312.description AS standardDescription",
            "numeral" => "wg_minimum_standard_item_0312.numeral",
            "description" => "wg_minimum_standard_item_0312.description",
            "value" => "wg_minimum_standard_item_0312.value",            
            "status" => "wg_common_active_status.item AS status",
            "isActive" => "wg_minimum_standard_item_0312.is_active",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
		$query->join("wg_minimum_standard_0312", function ($join) {
            $join->on('wg_minimum_standard_0312.id', '=', 'wg_minimum_standard_item_0312.minimum_standard_id');

		})->leftjoin("wg_config_minimum_standard_cycle_0312", function ($join) {
            $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id');

		})->leftjoin(DB::raw(SystemParameter::getRelationTable('minimum_standard_type')), function ($join) {
            $join->on('minimum_standard_type.value', '=', 'wg_minimum_standard_0312.type');

		})->leftjoin(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
            $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');

		})->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_common_active_status')), function ($join) {
            $join->on('wg_common_active_status.value', '=', 'wg_minimum_standard_item_0312.is_active');

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

        $entityModel->minimumStandardId = $entity->minimumStandard ? $entity->minimumStandard->id : null;
        $entityModel->numeral = $entity->numeral;
        $entityModel->description = $entity->description;
        $entityModel->item = $entity->item;
        $entityModel->value = $entity->value;
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

        (new MinimumStandardItemDetail0312Repository())->bulkInsertOrUpdate($entity->legalFrameworkList, $entityModel->id);

        return $this->parseModelWithRelations($entityModel);
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
            $entity->minimumStandard = $model->getminimumStandard();
            $entity->minimumStandardParent = $model->getminimumStandardParent($entity->minimumStandard);
            $entity->numeral = $model->numeral;
            $entity->description = $model->description;
            $entity->item = $model->item;
            $entity->value = $model->value;
            $entity->isActive = $model->isActive == 1;
            $entity->legalFrameworkList = $model->getLegalFrameworkList();
            
            return $entity;
        } else {
            return null;
        }
    }
}
