<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\InternalProjectDocument;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Modules\Customer\Document\CustomerDocumentModel;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Wgroup\SystemParameter\SystemParameter;

class CustomerInternalProjectDocumentRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerInternalProjectDocumentModel());

        $this->service = new CustomerInternalProjectDocumentService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_internal_project_document.id",
            "documentType" => "document_type.item AS documentType",
            "classification" => "customer_document_classification.item AS classification",
            "description" => "wg_customer_internal_project_document.description",
            "version" => "wg_customer_internal_project_document.version",
            "createdAt" => "wg_customer_internal_project_document.created_at",
            "createdBy" => "users.name AS createdBy",
            "status" => "customer_document_status.item AS status",
            "statusCode" => "wg_customer_internal_project_document.status AS statusCode",
            "customerId" => "wg_customer_internal_project.customer_id",
            "protectionType" => "wg_customer_document_security.protectionType",
            "hasPermission" => DB::raw("(CASE WHEN user_id IS NULL THEN 0 ELSE 1 END) AS hasPermission"),
            "customerInternalProjectId" => "wg_customer_internal_project_document.customer_internal_project_id",
            "customerId" => "wg_customer_internal_project.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_internal_project_document.type', '=', 'document_type.value');
            $join->on('wg_customer_internal_project_document.origin', '=', 'document_type.origin');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
            $join->on('wg_customer_internal_project_document.classification', '=', 'customer_document_classification.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
            $join->on('wg_customer_internal_project_document.status', '=', 'customer_document_status.value');
        })->join("wg_customer_internal_project", function ($join) {
            $join->on('wg_customer_internal_project.id', '=', 'wg_customer_internal_project_document.customer_internal_project_id');
        })->leftjoin("wg_customer_document_security", function ($join) {
            $join->on('wg_customer_internal_project.customer_id', '=', 'wg_customer_document_security.customer_id');
            $join->on('wg_customer_internal_project_document.type', '=', 'wg_customer_document_security.documentType');
            $join->on('wg_customer_internal_project_document.origin', '=', 'wg_customer_document_security.origin');
        })->leftjoin(DB::raw(CustomerDocumentModel::getSecurityUserRelationTable('security')), function ($join) use ($authUser) {
            $join->on('wg_customer_internal_project_document.id', '=', 'security.id');
            $join->on('security.user_id', '=', DB::raw($authUser ? $authUser->id : 0));
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_internal_project_document.created_by', '=', 'users.id');
        });

        $query->whereRaw("((protectionType = 'public' OR protectionType IS NULL) OR (protectionType = 'private' AND user_id IS NOT NULL))");

        $this->applyCriteria($query, $criteria);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, CustomerInternalProjectDocumentModel::class);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        } else {
            $entityModel->status = 2;
            $entityModel->save();

            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerInternalProjectId = $entity->customerInternalProjectId;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->origin = $entity->type ? $entity->type->origin : null;
        $entityModel->classification = $entity->classification ? $entity->classification->value : null;
        $entityModel->description = $entity->description;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->version = $entity->version;

        if ($isNewRecord) {
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

        $entityModel->status = 2;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        return $entityModel->save();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerInternalProjectId = $model->customerInternalProjectId;
            $entity->type = $model->getDocumentType();
            $entity->classification = $model->getClassification();
            $entity->description = $model->description;
            $entity->status = $model->getStatus();
            $entity->version = $model->version;

            return $entity;
        } else {
            return null;
        }
    }
}
