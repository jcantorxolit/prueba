<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftCondition;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigWorkplaceShiftConditionRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigWorkplaceShiftConditionModel());

        $this->service = new CustomerConfigWorkplaceShiftConditionService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_workplace_shift_condition.id",
            "covidBolivarQuestionCode" => "wg_customer_config_workplace_shift_condition.covid_bolivar_question_code",
            "customerWorkplaceId" => "wg_customer_config_workplace_shift_condition.customer_workplace_id",
            "isActive" => "wg_customer_config_workplace_shift_condition.is_active",
            "createdBy" => "wg_customer_config_workplace_shift_condition.created_by",
            "createdAt" => "wg_customer_config_workplace_shift_condition.created_at",
            "updatedBy" => "wg_customer_config_workplace_shift_condition.updated_by",
            "updatedAt" => "wg_customer_config_workplace_shift_condition.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_workplace_shift_condition.parent_id', '=', 'tableParent.id');
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

        $entityModel->covidBolivarQuestionCode = $entity->covidBolivarQuestionCode;
        $entityModel->customerWorkplaceId = $entity->customerWorkplaceId;
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

        return $this->parseModelWithRelations($entityModel);
    }

    public static function bulkInsertOrUpdate($data, $parentId)
    {
        $reposity = new self;
        
        foreach ($data as $shiftCondition) {
            $entity = new \stdClass();
            $entity->id = $shiftCondition->id;
            $entity->covidBolivarQuestionCode = $shiftCondition->covidBolivarQuestionCode;
            $entity->customerWorkplaceId = $parentId;            
            $entity->isActive = $shiftCondition->isActive;
            $reposity->insertOrUpdate($entity);
        }
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
            $entity->covidBolivarQuestionCode = $model->getCovidBolivarQuestionCode();
            $entity->customerWorkplaceId = $model->customerWorkplaceId;
            $entity->isActive = $model->isActive;
            $entity->createdBy = $model->createdBy;
            $entity->createdAt = $model->createdAt;
            $entity->updatedBy = $model->updatedBy;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }
}
