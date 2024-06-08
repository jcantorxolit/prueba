<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerAbsenteeismDisabilityDayChargedRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerAbsenteeismDisabilityDayChargedModel());

        $this->service = new CustomerAbsenteeismDisabilityDayChargedService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_absenteeism_disability_day_charged.id",
            "type" => "wg_config_day_charged_type.name AS type",
            "classification" => "wg_config_day_charged_classification.name AS classification",
            "part" => "wg_config_day_charged_part.name AS part",
            "value" => "wg_config_day_charged_part.value",
            "isDeleted" => "wg_customer_absenteeism_disability_day_charged.is_deleted",
            "customerDisabilityId" => "wg_customer_absenteeism_disability_day_charged.customer_disability_id",
        ]);

        $criteria->sorts = [];

        $this->parseCriteria($criteria);

        $query = $this->query();
        
		$query->join("wg_customer_absenteeism_disability", function ($join) {
            $join->on('wg_customer_absenteeism_disability_day_charged.customer_disability_id', '=', 'wg_customer_absenteeism_disability.id');

		})->join("wg_config_day_charged_part", function ($join) {
            $join->on('wg_config_day_charged_part.id', '=', 'wg_customer_absenteeism_disability_day_charged.config_day_charged_part_id');
            
		})->join("wg_config_day_charged_classification", function ($join) {
            $join->on('wg_config_day_charged_classification.id', '=', 'wg_config_day_charged_part.config_day_charged_classification_id');
            
		})->join("wg_config_day_charged_type", function ($join) {
            $join->on('wg_config_day_charged_type.id', '=', 'wg_config_day_charged_classification.config_day_charged_type_id');
            
		})->orderBy('wg_config_day_charged_part.value', 'DESC')->orderBy('wg_config_day_charged_part.priority', 'ASC');
		
        $this->applyCriteria($query, $criteria);

        $data = $this->get($query, $criteria);

        $data['dayCharged'] = $this->maxDayCharged($criteria);

        return $data;
    }

    public function allAvailable($criteria)
    {
        $this->setColumns([
            "id" => "wg_config_day_charged_part.id",
            "type" => "wg_config_day_charged_type.name AS type",
            "classification" => "wg_config_day_charged_classification.name AS classification",
            "part" => "wg_config_day_charged_part.name AS part",
            "value" => "wg_config_day_charged_part.value",
            "priority" => "wg_config_day_charged_part.priority",
            "isDeath" => "wg_config_day_charged_type.is_death"
        ]);        

        $defaultSort = new \stdClass();
        $defaultSort->column = 5;
        $defaultSort->dir = 'asc';
        $criteria->sorts = [$defaultSort];

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_absenteeism_disability_day_charged')
                ->select(
                    'wg_customer_absenteeism_disability_day_charged.customer_disability_id',
                    'wg_customer_absenteeism_disability_day_charged.config_day_charged_part_id',
                    'wg_customer_absenteeism_disability_day_charged.is_deleted'
                )
                ->where('wg_customer_absenteeism_disability_day_charged.is_deleted', 0);

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerDisabilityId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_disability_day_charged.customer_disability_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table('wg_config_day_charged_part'))
                ->mergeBindings($q1);
        
		$query->join("wg_config_day_charged_classification", function ($join) {
            $join->on('wg_config_day_charged_classification.id', '=', 'wg_config_day_charged_part.config_day_charged_classification_id');
            
		})->join("wg_config_day_charged_type", function ($join) {
            $join->on('wg_config_day_charged_type.id', '=', 'wg_config_day_charged_classification.config_day_charged_type_id');
            
		})->leftjoin(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_disability_day_charged"), function ($join) {
            $join->on('wg_config_day_charged_part.id', '=', 'wg_customer_absenteeism_disability_day_charged.config_day_charged_part_id');
            
        })
        ->whereNull('wg_customer_absenteeism_disability_day_charged.customer_disability_id');
		
        $this->applyCriteria($query, $criteria, ['customerDisabilityId']);

        return $this->get($query, $criteria);
    }

    public function maxDayCharged($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_absenteeism_disability_day_charged.id",
            "type" => "wg_config_day_charged_type.name AS type",
            "classification" => "wg_config_day_charged_classification.name AS classification",
            "part" => "wg_config_day_charged_part.name AS part",
            "value" => "wg_config_day_charged_part.value",
            "isDeleted" => "wg_customer_absenteeism_disability_day_charged.is_deleted",
            "customerDisabilityId" => "wg_customer_absenteeism_disability_day_charged.customer_disability_id",
        ]);

        $this->parseCriteria(null);        

        $query = $this->query();
        
		$query->join("wg_customer_absenteeism_disability", function ($join) {
            $join->on('wg_customer_absenteeism_disability_day_charged.customer_disability_id', '=', 'wg_customer_absenteeism_disability.id');

		})->join("wg_config_day_charged_part", function ($join) {
            $join->on('wg_config_day_charged_part.id', '=', 'wg_customer_absenteeism_disability_day_charged.config_day_charged_part_id');
            
		})->join("wg_config_day_charged_classification", function ($join) {
            $join->on('wg_config_day_charged_classification.id', '=', 'wg_config_day_charged_part.config_day_charged_classification_id');
            
		})->join("wg_config_day_charged_type", function ($join) {
            $join->on('wg_config_day_charged_type.id', '=', 'wg_config_day_charged_classification.config_day_charged_type_id');
            
		})->orderBy('wg_config_day_charged_part.value', 'DESC')->orderBy('wg_config_day_charged_part.priority', 'ASC');

        $this->applyCriteria($query, $criteria);

        $data = $query->first();

        return $data ? $data->value : 0;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerDisabilityId = $entity->customerDisabilityId;
        $entityModel->configDayChargedPartId = $entity->configDayChargedPart ? $entity->configDayChargedPart->id : null;


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

        $this->service->updateAbsenteeismDisabilityDayCharged($entityModel->customerDisabilityId);
        
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

        $this->service->updateAbsenteeismDisabilityDayCharged($entityModel->customerDisabilityId);

        return $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerDisabilityId = $model->customerDisabilityId;
            $entity->configDayChargedPartId = $model->configDayChargedPartId;
            $entity->isDeleted = $model->isDeleted;

            return $entity;
        } else {
            return null;
        }
    }

    public function getDayChargedIsDeathValue()
    {
        return $this->service->getDayChargedIsDeathValue();
    }
}
