<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigProcessStaging;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigProcessStagingRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigProcessStagingModel());

        $this->service = new CustomerConfigProcessStagingService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_process_staging.id",
            "index" => "wg_customer_config_process_staging.index",
            "workplace" => "wg_customer_config_process_staging.workplace",
            "macroprocess" => "wg_customer_config_process_staging.macroprocess",
            "name" => "wg_customer_config_process_staging.name",
            "status" => "wg_customer_config_process_staging.status",
            "observation" => "wg_customer_config_process_staging.observation",
            "isValid" => "wg_customer_config_process_staging.is_valid",           
            "sessionId" => "wg_customer_config_process_staging.session_id",           
            "customerId" => "wg_customer_config_process_staging.customer_id",            
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
        $entityModel->macroProcessId = $entity->macroprocess ? $entity->macroprocess->id : null;
        $entityModel->macroprocess = $entity->macroprocess ? $entity->macroprocess->name : null;
        $entityModel->name = $entity->name;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->observation = null;
        $entityModel->index = $entity->index;
        $entityModel->sessionId = $entity->sessionId;
        $entityModel->isValid = true;
        $entityModel->save();        

        DB::statement("CALL TL_Process_Staging({$entity->customerId}, '$entityModel->session_id')");

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
            $entity->macroprocess = $this->service->findMacroprocess($model->macroProcessId);
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
