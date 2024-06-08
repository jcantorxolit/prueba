<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigQuestionExpressHistorical;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Interfaces\IHistorical;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigQuestionExpressHistoricalRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigQuestionExpressHistoricalModel());

        $this->service = new CustomerConfigQuestionExpressHistoricalService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_question_express_historical.id",
            "customerId" => "wg_customer_config_question_express_historical.customer_id",
            "customerQuestionExpressId" => "wg_customer_config_question_express_historical.customer_question_express_id",
            "rate" => "wg_customer_config_question_express_historical.rate",
            "createdAt" => "wg_customer_config_question_express_historical.created_at",
            "updatedAt" => "wg_customer_config_question_express_historical.updated_at",
            "createdBy" => "wg_customer_config_question_express_historical.created_by",
            "updatedBy" => "wg_customer_config_question_express_historical.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_question_express_historical.parent_id', '=', 'tableParent.id');
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
        $entityModel->customerQuestionExpressId =  $entity->customerQuestionExpressId;
        $entityModel->origin = $entity->origin;
        $entityModel->oldRate = $entity->oldRate;
        $entityModel->newRate = $entity->newRate;

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

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }
        
        return $entityModel->delete();        
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->customerQuestionExpressId = $model->customerQuestionExpressId;
            $entity->oldRate = $model->oldRate;
            $entity->newRate = $model->newRate;

            return $entity;
        } else {
            return null;
        }
    }

    public static function create($origin, IHistorical $interface)
    {
        if (!$interface->getIsDirty('rate')) {
            return null;
        }

        $repository = new self();

        $entityModel = new \stdClass();
        $entityModel->id = 0;
        $entityModel->customerId = $interface->getParentId();
        $entityModel->customerQuestionExpressId = $interface->getModelId();
        $entityModel->origin = $origin;
        $entityModel->oldRate = $interface->getOriginalValue('rate');
        $entityModel->newRate = $interface->getModel()->rate;
        $repository->insertOrUpdate($entityModel);        
    }
}
