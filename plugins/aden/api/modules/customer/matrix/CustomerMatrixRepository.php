<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Matrix;

use AdeN\Api\Classes\BaseRepository;
use Carbon\Carbon;
use Exception;
use DB;
use Wgroup\SystemParameter\SystemParameter;

class CustomerMatrixRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerMatrixModel());

        $this->service = new CustomerMatrixService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "unique" => "wg_customer_matrix.id AS unique",
            "id" => "wg_customer_matrix.id",            
            "description" => "wg_customer_matrix.description",
            "createdAt" => "wg_customer_matrix.created_at",
            "createdby" => "users.name AS createdBy",
            "status" => "wg_customer_matrix.status",
            "customerId" => "wg_customer_matrix.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('config_matrix_status')), function ($join) {
            $join->on('wg_customer_matrix.status', '=', 'config_matrix_status.value');

        })->leftjoin("users", function ($join) {
            $join->on('users.id', '=', 'wg_customer_matrix.createdBy');

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

        $entityModel->customerId = $entity->customerId;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->description = $entity->description;
        $entityModel->status = $entity->status ? $entity->status->value : null;
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
            $entity->customerId = $model->customerId;
            $entity->type = $model->getType();
            $entity->description = $model->description;
            $entity->status = $model->getStatus();
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
