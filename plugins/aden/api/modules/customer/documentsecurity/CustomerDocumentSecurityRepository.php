<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\DocumentSecurity;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Modules\Customer\DocumentSecurityUser\CustomerDocumentSecurityUserRepository;

class CustomerDocumentSecurityRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerDocumentSecurityModel());

        $this->service = new CustomerDocumentSecurityService();
    }

    public static function getCustomFilters()
    {
        return [];
    }

    public function getMandatoryFilters()
    {
        return [];
    }

    public function all($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_document_security.id",
            "documentType" => "wg_customer_document_security.documentType",
            "origin" => "wg_customer_document_security.origin",
            "protectionType" => "wg_customer_document_security.protectionType",
            "isPasswordProtected" => "wg_customer_document_security.isPasswordProtected",
            "customerId" => "wg_customer_document_security.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["recordsTotal"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["recordsFiltered"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function findRelation($customerId, $type, $origin)
    {
        $entity = $this->model
            ->where('customer_id', $customerId)
            ->where('documentType', $type)
            ->where('origin', $origin)
            ->first();

        if ($entity == null) {
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = 0;
            $entity->customerId = $customerId;
            $entity->documentType = null;
            $entity->isPublic = false;
            $entity->isProtected = false;
            $entity->users = $entity->users = $this->service->allDocumentSecurityUsers($customerId, $type, $origin);;
        }

        return $entity;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customer_id = $entity->customerId;
        $entityModel->documentType = $entity->documentType ? $entity->documentType->value : null;
        $entityModel->origin = $entity->documentType ? $entity->documentType->origin : null;
        $entityModel->protectionType = $entity->isPublic ? 'public' : 'private';
        $entityModel->isPasswordProtected = $entity->isProtected;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        //$repositoryUser = new CustomerDocumentSecurityUserRepository();
        //$repositoryUser->bulkInsertOrUpdate($entity->users, $entityModel->customer_id, $entityModel->documentType, $entityModel->origin);

        return $this->parseModelWithRelations($this->findRelation($entityModel->customer_id, $entityModel->documentType, $entityModel->origin));
    }

    public function bulkInsertOrUpdate($records, $entityId)
    {
        foreach ($records as $record) {
            $record->customer_id = $entityId;
            $this->insertOrUpdate($record);
        }

        return true;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customer_id;
            $entity->documentType = null;
            $entity->isPublic = $model->protectionType == 'public';
            $entity->isProtected = $model->isPasswordProtected == 1;
            $entity->users = $this->service->allDocumentSecurityUsers($model->customer_id, $model->documentType, $model->origin);

            return $entity;
        } else {
            return $model;
        }
    }
}
