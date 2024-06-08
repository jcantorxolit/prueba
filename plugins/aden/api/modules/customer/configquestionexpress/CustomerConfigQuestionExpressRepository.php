<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigQuestionExpress;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigQuestionExpressHistorical\CustomerConfigQuestionExpressHistoricalRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigQuestionExpressRepository extends BaseRepository
{
    protected $service;
    private $origin;

    public function __construct()
    {
        parent::__construct(new CustomerConfigQuestionExpressModel());

        $this->service = new CustomerConfigQuestionExpressService();

        CustomerConfigQuestionExpressModel::updating(function ($model) {
            CustomerConfigQuestionExpressHistoricalRepository::create($this->origin, $model);
        });
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_question_express.id",
            "customerId" => "wg_customer_config_question_express.customer_id",
            "workplaceId" => "wg_customer_config_question_express.customer_workplace_id",
            "questionExpressId" => "wg_customer_config_question_express.question_express_id",
            "rate" => "wg_customer_config_question_express.rate",
            "isActive" => "wg_customer_config_question_express.is_active",
            "createdAt" => "wg_customer_config_question_express.created_at",
            "updatedAt" => "wg_customer_config_question_express.updated_at",
            "createdBy" => "wg_customer_config_question_express.created_by",
            "updatedBy" => "wg_customer_config_question_express.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_question_express.parent_id', '=', 'tableParent.id');
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

        $entityModel->customerId = $entity->customerId;
        $entityModel->customerWorkplaceId = $entity->workplaceId;
        $entityModel->questionExpressId = $entity->questionExpressId;
        $entityModel->rate = $entity->rate;
                
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

    public function batch($entity)
    {        
        foreach ($entity->questions as $question) {
            if ($question->rate != null) {  
                $question->customerId = $entity->customerId;
                $question->workplaceId = $entity->workplaceId; 
                $this->origin = 'Peligros';             
                $this->insertOrUpdate($question);
            }
        }

        $criteria = new \stdClass();
        $criteria->id = $entity->id;
        $criteria->customerId = $entity->customerId;
        $criteria->workplaceId = $entity->workplaceId;

        return $this->findHazard($criteria);
    }

    public function updateRateById($id, $rate)
    {
        if (($entityModel = $this->find($id))) {
            $authUser = $this->getAuthUser();
            $this->origin = 'Intervención';   
            $entityModel->rate = $rate;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->is_active = false;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        return $entityModel->save();        
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
            $entity->workplaceId = $model->customerWorkplaceId;
            $entity->questionExpressId = $model->questionExpressId;
            $entity->rate = $model->rate;
            $entity->isActive = $model->isActive == 1;

            return $entity;
        } else {
            return null;
        }
    }

    public function bulkOperations($criteria)
    {
        $authUser = $this->getAuthUser();
        $criteria->userId =  $authUser ? $authUser->id : 1;

        DB::transaction(function () use ($criteria) {
            $this->service->bulkInsertQuestions($criteria);
            $this->service->bulkInactiveQuestions($criteria);
            $this->service->bulkActiveQuestions($criteria);
        });
    }

    public function getHazardList($criteria)
    {
        if ($criteria->canExecuteBulkOperation == 1) {
            $this->bulkOperations($criteria);
        }
        return $this->service->getHazardList($criteria);
    }

    public function getWorkplaceStats($criteria)
    {        
        return $this->service->getWorkplaceStats($criteria);
    }

    public function getWorkplaceList($criteria)
    {        
        return $this->service->getWorkplaceList($criteria);
    }

    public function getHazardStats($criteria)
    {
        return $this->service->getHazardStats($criteria);
    }

    public function getHazardGeneralStats($criteria)
    {
        return $this->service->getHazardGeneralStats($criteria);
    }

    public function getChartPieHazardInterventionStats($criteria)
    {
        return $this->service->getChartPieHazardInterventionStats($criteria);
    }

    public function findHazard($criteria)
    {
        return $this->service->findHazard($criteria);
    }

    public function findHazardIntervention($criteria)
    {
        if (!$criteria->isHistorical) {
            return $this->service->findHazardIntervention($criteria);
        } else {
            return $this->service->findHazardInterventionHistorical($criteria);
        }
    }
}
