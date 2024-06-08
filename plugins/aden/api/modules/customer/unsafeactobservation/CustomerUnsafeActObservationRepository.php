<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\UnsafeActObservation;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerUnsafeActObservationRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerUnsafeActObservationModel());

        $this->service = new CustomerUnsafeActObservationService();
    }

    public function all($criteria)
    {        
        $this->setColumns([
"id" => "wg_customer_unsafe_act_observation.id",
"customerUnsafeActId" => "wg_customer_unsafe_act_observation.customer_unsafe_act_id",
"status" => "wg_customer_unsafe_act_observation.status",
"dateof" => "wg_customer_unsafe_act_observation.dateOf",
"description" => "wg_customer_unsafe_act_observation.description",
"createdby" => "wg_customer_unsafe_act_observation.createdBy",
"updatedby" => "wg_customer_unsafe_act_observation.updatedBy",
"createdAt" => "wg_customer_unsafe_act_observation.created_at",
"updatedAt" => "wg_customer_unsafe_act_observation.updated_at",
]);

        $this->parseCriteria($criteria);

        $query = $this->query();

		/* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_unsafe_act_observation.parent_id', '=', 'tableParent.id');
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

        $entityModel->customerUnsafeActId = $entity->customerUnsafeActId;
$entityModel->status = $entity->status ? $entity->status->value : null;
$entityModel->dateof = $entity->dateof ? Carbon::parse($entity->dateof)->timezone('America/Bogota') : null;
$entityModel->description = $entity->description;
$entityModel->createdby = $entity->createdby;
$entityModel->updatedby = $entity->updatedby;


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
		
        $result = $entityModel;

        return $result;
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
$entity->customerUnsafeActId = $model->customerUnsafeActId;
$entity->status = $model->getStatus();
$entity->dateof = $model->dateof;
$entity->description = $model->description;
$entity->createdby = $model->createdby;
$entity->updatedby = $model->updatedby;
$entity->createdAt = $model->createdAt;
$entity->updatedAt = $model->updatedAt;

  
            return $entity;
        } else {
            return null;
        }
    }
}