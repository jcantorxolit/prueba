<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigJobActivityDocument;

use AdeN\Api\Classes\BaseRepository;
use Carbon\Carbon;
use Exception;
use DB;
use AdeN\Api\Modules\Customer\CustomerModel;

class CustomerConfigJobActivityDocumentRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigJobActivityDocumentModel());

        $this->service = new CustomerConfigJobActivityDocumentService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity_document.id",
            "type" => "document_type.item AS type",
            "createdAt" => "wg_customer_config_job_activity_document.created_at",
            "createdBy" => "users.name AS createdBy",
            "jobActivityId" => "wg_customer_config_job_activity_document.job_activity_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        /* Example relation*/
        $query->join('wg_customer_config_activity', function ($join) {
            $join->on('wg_customer_config_job_activity_document.job_activity_id', '=', 'wg_customer_config_activity.id');

        })
        ->leftjoin(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
            $join->on('wg_customer_config_job_activity_document.type', '=', 'document_type.value');
            $join->on('wg_customer_config_job_activity_document.origin', '=', 'document_type.origin');
        })
        ->mergeBindings($qDocumentType)
        // ->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
        //     $join->on('wg_customer_config_job_activity_document.type', '=', 'document_type.value');
        //     $join->on('wg_customer_config_job_activity_document.origin', '=', 'document_type.origin');

        // })
        ->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job_activity_document.createdBy', '=', 'users.id');

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

        $entityModel->jobActivityId = $entity->jobActivityId ? $entity->jobActivityId->id : null;
        $entityModel->type = $entity->type;
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
            $entity->jobActivityId = $model->jobActivityId;
            $entity->type = $model->type;
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
