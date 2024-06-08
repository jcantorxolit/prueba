<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigMacroProcessStaging;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigMacroProcessStagingRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigMacroProcessStagingModel());

        $this->service = new CustomerConfigMacroProcessStagingService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_macro_process_staging.id",
            "index" => "wg_customer_config_macro_process_staging.index",
            "workplace" => "wg_customer_config_macro_process_staging.workplace",
            "name" => "wg_customer_config_macro_process_staging.name",
            "status" => "wg_customer_config_macro_process_staging.status",
            "observation" => "wg_customer_config_macro_process_staging.observation",
            "isValid" => "wg_customer_config_macro_process_staging.is_valid",           
            "sessionId" => "wg_customer_config_macro_process_staging.session_id",           
            "customerId" => "wg_customer_config_macro_process_staging.customer_id",            
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {        
        if (!($entityModel = $this->find($entity->id))) {
            throw new \Exception('Record not found');        
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->workplaceId = $entity->workplace ? $entity->workplace->id : null;
        $entityModel->workplace = $entity->workplace ? $entity->workplace->name : null;
        $entityModel->name = $entity->name;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        if ($entity->workplace && $entity->workplace->type == 'PCS') {
            $entityModel->observation = 'No es posible crear el macro proceso. El centro de trabajo es de tipo Proceso| ';    
            $entityModel->isValid = false;
        } else {
            $entityModel->observation = null;
            $entityModel->isValid = true;
        }
        $entityModel->index = $entity->index;
        $entityModel->sessionId = $entity->sessionId;
        
        $entityModel->save();        

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

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;            
            $entity->workplace = $this->service->findWorkplace($model->workplaceId);
            $entity->name = $model->name;
            $entity->status = $model->getStatus();
            $entity->observation = $model->observation;
            $entity->index = $model->index;
            $entity->sessionId = $model->sessionId;      
            $entity->isValid = $model->isValid == 1;

            return $entity;
        } else {
            return null;
        }
    }
}
