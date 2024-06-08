<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyItemDocument40595;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use AdeN\Api\Modules\Customer\Document\CustomerDocumentModel;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use System\Models\File;

class CustomerRoadSafetyItemDocument40595Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerRoadSafetyItemDocument40595Model());

        $this->service = new CustomerRoadSafetyItemDocument40595Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_road_safety_item_document_40595.id",
            "documentType" => "document_type.item AS documentType",
            "classification" => "customer_document_classification.item AS classification",
            "description" => "wg_customer_road_safety_item_document_40595.description",
            "version" => "wg_customer_road_safety_item_document_40595.version",
            "createdAt" => "wg_customer_road_safety_item_document_40595.created_at",
            "createdBy" => "users.name AS createdBy",
            "status" => "customer_document_status.item AS status",
            "label" => "road_safety_item_documenta_40595_label.item AS label",
            "statusCode" => "wg_customer_road_safety_item_document_40595.status AS statusCode",
            "customerId" => "wg_customer_road_safety_40595.customer_id",
            "protectionType" => "wg_customer_document_security.protectionType",
            "hasPermission" => DB::raw("(CASE WHEN user_id IS NULL THEN 0 ELSE 1 END) AS hasPermission"),
            "program" => "wg_customer_road_safety_item_document_40595.program",
            "customerRoadSafetyItemId" => "wg_customer_road_safety_item_document_40595.customer_road_safety_item_id",
            "customerRoadSafetyId" => "wg_customer_road_safety_item_40595.customer_road_safety_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.type', '=', 'document_type.value');
            $join->on('wg_customer_road_safety_item_document_40595.origin', '=', 'document_type.origin');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.classification', '=', 'customer_document_classification.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.status', '=', 'customer_document_status.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('road_safety_item_documenta_40595_label')), function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.label', '=', 'road_safety_item_documenta_40595_label.value');
        })->join("wg_customer_road_safety_item_40595", function ($join) {
            $join->on('wg_customer_road_safety_item_40595.id', '=', 'wg_customer_road_safety_item_document_40595.customer_road_safety_item_id');
        })->join("wg_customer_road_safety_40595", function ($join) {
            $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
        })->leftjoin("wg_customer_document_security", function ($join) {
            $join->on('wg_customer_road_safety_40595.customer_id', '=', 'wg_customer_document_security.customer_id');
            $join->on('wg_customer_road_safety_item_document_40595.type', '=', 'wg_customer_document_security.documentType');
            $join->on('wg_customer_road_safety_item_document_40595.origin', '=', 'wg_customer_document_security.origin');
        })->leftjoin(DB::raw(CustomerDocumentModel::getSecurityUserRelationTable('security')), function ($join) use ($authUser) {
            $join->on('wg_customer_road_safety_item_document_40595.id', '=', 'security.id');
            $join->on('security.user_id', '=', DB::raw($authUser ? $authUser->id : 0));
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.created_by', '=', 'users.id');
        });

        $query->whereRaw("((protectionType = 'public' OR protectionType IS NULL) OR (protectionType = 'private' AND user_id IS NOT NULL))");

        $this->applyCriteria($query, $criteria);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, CustomerRoadSafetyItemDocument40595Model::CLASS_NAME);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allAvailable($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_document.id",
            "documentType" => "document_type.item AS documentType",
            "classification" => "customer_document_classification.item AS classification",
            "description" => "wg_customer_document.description",
            "version" => "wg_customer_document.version",
            "createdAt" => "wg_customer_document.created_at AS dateOfCreation",
            "createdBy" => "users.name AS createdBy",
            "status" => "customer_document_status.item AS status",
            "statusCode" => "wg_customer_document.status AS statusCode",
            "customerId" => "wg_customer_document.customer_id"
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_customer_road_safety_item_document_40595')
            ->select(
                'wg_customer_road_safety_item_document_40595.customer_road_safety_item_id',
                'wg_customer_road_safety_item_document_40595.customer_document_id'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerRoadSafetyItemId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_road_safety_item_document_40595.customer_road_safety_item_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table('wg_customer_document'));

        /* Example relation*/
        $query->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_document.type', '=', 'document_type.value');
            $join->on('wg_customer_document.origin', '=', 'document_type.origin');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
            $join->on('wg_customer_document.classification', '=', 'customer_document_classification.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
            $join->on('wg_customer_document.status', '=', 'customer_document_status.value');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_document.createdBy', '=', 'users.id');
        })->leftjoin(DB::raw("({$q1->toSql()}) AS wg_customer_road_safety_item_document_40595"), function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.customer_document_id', '=', 'wg_customer_document.id');
        })->mergeBindings($q1);

        $query->whereIn("wg_customer_document.program", ['P', 'H', 'V', 'A']);
        $query->whereNull("wg_customer_road_safety_item_document_40595.customer_document_id");

        $this->applyCriteria($query, $criteria, ['customerRoadSafetyItemId']);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        if ($data instanceof Paginator || $data instanceof LengthAwarePaginator) {
            $total = $data->total();
        } else if ($data instanceof Collection) {
            $total = $data->count();
        } else if (is_array($data)) {
            $total = count($data);
        } else {
            $total = 0;
        }

        $result["data"] = $this->parseModelWithDocument($data, CustomerDocumentModel::CLASS_NAME);
        $result["total"] = $total;
        $result["recordsTotal"] = $total;
        $result["recordsFiltered"] = $total;
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allAvailablePreviousPeriod($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_road_safety_item_document_40595.id",
            "period" => "wg_customer_road_safety_40595.period",
            "documentType" => "document_type.item AS documentType",
            "classification" => "customer_document_classification.item AS classification",
            "description" => "wg_customer_road_safety_item_document_40595.description",
            "version" => "wg_customer_road_safety_item_document_40595.version",
            "createdAt" => "wg_customer_road_safety_item_document_40595.created_at",
            "createdBy" => "users.name AS createdBy",
            "status" => "customer_document_status.item AS status",
            "label" => "road_safety_item_documenta_40595_label.item AS label",
            "statusCode" => "wg_customer_road_safety_item_document_40595.status AS statusCode",
            "customerId" => "wg_customer_road_safety_40595.customer_id",
            "protectionType" => "wg_customer_document_security.protectionType",
            "hasPermission" => DB::raw("(CASE WHEN user_id IS NULL THEN 0 ELSE 1 END) AS hasPermission"),
            "program" => "wg_customer_road_safety_item_document_40595.program",
            "customerRoadSafetyItemId" => "wg_customer_road_safety_item_document_40595.customer_road_safety_item_id",
            "customerRoadSafetyId" => "wg_customer_road_safety_item_40595.customer_road_safety_id",
            "roadSafetyItemId" => "wg_customer_road_safety_item_40595.road_safety_item_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.type', '=', 'document_type.value');
            $join->on('wg_customer_road_safety_item_document_40595.origin', '=', 'document_type.origin');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.classification', '=', 'customer_document_classification.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.status', '=', 'customer_document_status.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('road_safety_item_documenta_40595_label')), function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.label', '=', 'road_safety_item_documenta_40595_label.value');
        })->join("wg_customer_road_safety_item_40595", function ($join) {
            $join->on('wg_customer_road_safety_item_40595.id', '=', 'wg_customer_road_safety_item_document_40595.customer_road_safety_item_id');
        })->join("wg_customer_road_safety_40595", function ($join) {
            $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
        })->leftjoin("wg_customer_document_security", function ($join) {
            $join->on('wg_customer_road_safety_40595.customer_id', '=', 'wg_customer_document_security.customer_id');
            $join->on('wg_customer_road_safety_item_document_40595.type', '=', 'wg_customer_document_security.documentType');
            $join->on('wg_customer_road_safety_item_document_40595.origin', '=', 'wg_customer_document_security.origin');
        })->leftjoin(DB::raw(CustomerDocumentModel::getSecurityUserRelationTable('security')), function ($join) use ($authUser) {
            $join->on('wg_customer_road_safety_item_document_40595.id', '=', 'security.id');
            $join->on('security.user_id', '=', DB::raw($authUser ? $authUser->id : 0));
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_road_safety_item_document_40595.created_by', '=', 'users.id');
        });

        $query->whereRaw("((protectionType = 'public' OR protectionType IS NULL) OR (protectionType = 'private' AND user_id IS NOT NULL))");
        $query->where("wg_customer_road_safety_40595.period", '=', DB::raw('YEAR(NOW()) - 1'));
        $query->where("wg_customer_road_safety_40595.status", 'C');
        $query->whereNotIn('wg_customer_road_safety_item_document_40595.id', function ($query) {
            $query->select('old_id')
                ->from('wg_customer_road_safety_item_document_40595')
                ->whereNotNull('old_id');
        });

        $this->applyCriteria($query, $criteria);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, CustomerRoadSafetyItemDocument40595Model::CLASS_NAME);
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

        $entityModel->customerRoadSafetyItemId = $entity->customerRoadSafetyItemId;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->origin = $entity->type ? $entity->type->origin : null;
        $entityModel->classification = $entity->classification ? $entity->classification->value : null;
        $entityModel->description = $entity->description;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->version = $entity->version;
        $entityModel->program = isset($entity->program) ? $entity->program : "";
        $entityModel->customerDocumentId = isset($entity->customerDocumentId) ? $entity->customerDocumentId : null;

        $entityModel->label = isset($entity->label) ? $entity->label : "M";
        $entityModel->oldId = isset($entity->oldId) ? $entity->oldId : null;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entityModel;
    }

    public function import($entity)
    {
        foreach ($entity->documentList as $document) {
            $modelInstance = CustomerDocumentModel::CLASS_NAME;
            $model = (new $modelInstance)->find($document->id);
            $this->duplicate($model, $entity->customerEvaluationMinimumStardardItem->customerRoadSafetyItemId);
        }

        return true;
    }

    public function importHistorical($entity)
    {
        $model = $this->find($entity->id);
        $this->duplicate($model, $entity->customerRoadSafetyItemId, 'H');
    }

    private function duplicate($model, $customerRoadSafetyItemId, $label = 'M')
    {
        if ($model) {
            $documentFile = $model->document;

            if ($documentFile) {
                $newEntity = new \stdClass();

                $newEntity->id = 0;
                $newEntity->customerRoadSafetyItemId = $customerRoadSafetyItemId;
                $newEntity->type = new \stdClass();
                $newEntity->type->value = $model->type;
                $newEntity->type->origin = $model->origin;

                $newEntity->classification = new \stdClass();
                $newEntity->classification->value = $model->classification;

                $newEntity->description = $model->description;
                $newEntity->status = new \stdClass();
                $newEntity->status->value = $model->status;

                $newEntity->version = $model->version;
                $newEntity->program = $model->program;
                $newEntity->customerDocumentId = $model->id;
                $newEntity->oldId = $model->id;
                $newEntity->label = $label;

                $entityModel = $this->insertOrUpdate($newEntity);

                try {
                    if ($entityModel) {
                        //Creation date for the new file must be the same of origin file
                        $entityModel->created_at = $model->created_at;
                        $entityModel->save();

                        $fileRelation = $entityModel->document();
                        $file = new File();
                        $file->fromUrl($documentFile->getTemporaryUrl());
                        $fileRelation->add($file);
                    }
                } catch (\Exception $ex) {
                    Log::error($ex);
                }
            }
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->status = 2;

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
            $entity->customerRoadSafetyItemId = $model->customerRoadSafetyItemId;
            $entity->type = $model->getDocumentType();
            $entity->classification = $model->getClassification();
            $entity->description = $model->description;
            $entity->status = $model->getStatus();
            $entity->version = $model->version;
            $entity->program = $model->program;
            $entity->customerDocumentId = $model->customerDocumentId;

            return $entity;
        } else {
            return null;
        }
    }
}
