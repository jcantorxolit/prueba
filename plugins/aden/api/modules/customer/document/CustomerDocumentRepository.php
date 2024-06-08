<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Document;

use DB;
use Exception;
use Log;
use Queue;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Wgroup\SystemParameter\SystemParameter;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Modules\Customer\Audit\CustomerAuditRepository;

class CustomerDocumentRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Models\\CustomerDocument";
    const STATUS_ACTIVE = 1;
    const STATUS_CANCELED = 2;

    public function __construct()
    {
        parent::__construct(new CustomerDocumentModel());

        $this->service = new CustomerDocumentService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Tipo ID Empresa", "name" => "customerDocumentType"],
            ["alias" => "Número ID Empresa", "name" => "customerDocumentNumber"],
            ["alias" => "Empresa", "name" => "customerName"],
            ["alias" => "Tipo ID Empleado", "name" => "employeeDocumentType"],
            ["alias" => "Número ID Empleado", "name" => "employeeDocumentNumber"],
            ["alias" => "Empleado", "name" => "employeeName"],
            ["alias" => "Centro de Trabajo", "name" => "workPlace"],
            ["alias" => "Cargo", "name" => "job"],
        ];
    }

    public function getMandatoryFilters()
    {
        return [
            array("field" => 'isActive', "operator" => 'eq', "value" => '1'),
        ];
    }

    public function all($criteria)
    {
        $urlImages = "uploads/public";

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
            "customerId" => "wg_customer_document.customer_id AS customerId",
            "protectionType" => "wg_customer_document_security.protectionType",
            "hasPermission" => DB::raw("(CASE WHEN user_id IS NULL THEN 0 ELSE 1 END) AS hasPermission"),
            /*'document' => DB::raw("IF (
                system_files.disk_name IS NOT NULL,
                CONCAT_WS(
                    \"/\",
                    '{$urlImages}',
                    SUBSTR(system_files.disk_name, 1, 3),
                    SUBSTR(system_files.disk_name, 4, 3),
                    SUBSTR(system_files.disk_name, 7, 3),
                    system_files.disk_name
                ),
                NULL
            ) as documentUrl"),       */
            "program" => "wg_customer_document.program",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_document.type', '=', 'document_type.value');
            $join->on('wg_customer_document.origin', '=', 'document_type.origin');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
            $join->on('wg_customer_document.classification', '=', 'customer_document_classification.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
            $join->on('wg_customer_document.status', '=', 'customer_document_status.value');
        })->leftjoin("wg_customer_document_security", function ($join) {
            $join->on('wg_customer_document.customer_id', '=', 'wg_customer_document_security.customer_id');
            $join->on('wg_customer_document.type', '=', 'wg_customer_document_security.documentType');
            $join->on('wg_customer_document.origin', '=', 'wg_customer_document_security.origin');
        })->leftjoin(DB::raw(CustomerDocumentModel::getSecurityUserRelationTable('security')), function ($join) use ($authUser) {
            $join->on('wg_customer_document.id', '=', 'security.id');
            $join->on('security.user_id', '=', DB::raw($authUser ? $authUser->id : 0));
        })/*->leftjoin(DB::raw(CustomerDocumentModel::getSystemFile()), function ($join) {
            $join->on('wg_customer_document.id', '=', 'system_files.attachment_id');

        })*/->leftjoin("users", function ($join) {
            $join->on('wg_customer_document.createdBy', '=', 'users.id');
        });

        $query->whereRaw("((protectionType = 'public' OR protectionType IS NULL) OR (protectionType = 'private' AND user_id IS NOT NULL))");

        $this->applyCriteria($query, $criteria);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, CustomerDocumentModel::CLASS_NAME);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!$authUser && isset($entity->user)) {
            $authUser = $this->findAuthUser($entity->user);
        }

        $userType = $authUser ? $authUser->wg_type : 'webService';
        $userId = $authUser ? $authUser->id : 0;

        if ($entity->id) {
            if (!($entityModel = $this->find($entity->id))) {
                $entityModel = $this->model->newInstance();
                $isNewRecord = true;
            } else {
                $entityModel->status = self::STATUS_CANCELED;
                $entityModel->updatedBy = $userId;
                $entityModel->canceledBy = $userId;
                $entityModel->canceledAt = Carbon::now('America/Bogota');
                $entityModel->save();

                $observation = "Se realiza anulación exitosa del anexo: ({$entity->description})";

                CustomerAuditRepository::create($entityModel->customerId, "Anexos", "Anular", $observation, $userType, $userId);

                $entityModel = $this->model->newInstance();
                $isNewRecord = true;
            }
        } else {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customer_id = $entity->customerId;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->origin = $entity->type ? $entity->type->origin : null;
        $entityModel->classification = $entity->classification ? $entity->classification->value : null;
        $entityModel->description = $entity->description;
        $entityModel->status = self::STATUS_ACTIVE;
        $entityModel->version = $entity->version;
        $entityModel->program = isset($entity->program) ? $entity->program : "";

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();

            $observation = "Se realiza adicion exitosa del anexo: ({$entity->description})";
            CustomerAuditRepository::create($entityModel->customerId, "Anexos", "Guardar", $observation, $userType, $userId);
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();

            $observation = "Se realiza adicion modificacion del anexo: ({$entity->description})";
            CustomerAuditRepository::create($entityModel->customerId, "Anexos", "Editar", $observation, $userType, $userId);
        }

        $result = $entityModel;

        return $result;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function findOne($id)
    {
        $className = self::ENTITY_NAME;
        return (new $className)->find($id);
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;

            return $entity;
        } else {
            return null;
        }
    }

    public function export($criteria)
    {
        $start = Carbon::now();
        /*
        
        $data = $this->service->getExportData($criteria);

        $excelFilename = 'GUIA_DOCUMENTOS_SOPORTE_' . Carbon::now()->timestamp;
        ExportHelper::excelStorage($excelFilename, 'GUIA', $data['excel']);


        $zipFilename = 'DOCUMENTOS_SOPORTE_' . Carbon::now()->timestamp . '.zip';
        $zipFullPath = CmsHelper::getStorageDirectory('zip/exports') . '/' . $zipFilename;

        if (!CmsHelper::makeDir(CmsHelper::getStorageDirectory('zip/exports'))) {
            throw new \Exception("Can create folder", 403);
        }

        $zipData = array_merge($data['zip'], [[
            'fullPath' => CmsHelper::getStorageDirectory('excel/exports') . '/' . $excelFilename . ".xlsx",
            "filename" => $excelFilename . ".xlsx"
        ]]);

        ExportHelper::zipFileSystemStream($zipFullPath, $zipData);

        */
        $end = Carbon::now();
        $authUser = $this->getAuthUser();
        $criteria->email = $authUser->email;
        $criteria->name = $authUser->name;
        $criteria->userId = $authUser->id;
        Queue::push(CustomerDocumentJob::class, ['criteria' => $criteria], 'zip');

        return [
            'message' => 'ok',
            'elapseTime' => $end->diffInSeconds($start),
            'endTime' => $end->timestamp,            
            'path' => CmsHelper::getPublicDirectory('zip/exports/'),
            //'zip' =>   $zipData
        ];
    }

    public function getPeriods($customerId, $year)
    {    
        return [
            "years" => $this->getYears($customerId),
            "months" => $this->getMonths($customerId, $year)
        ];
    }

    private function getYears($customerId)
    {
        $query = $this->model->newQuery();

        $query
        ->select( DB::raw("YEAR(wg_customer_document.created_at) as year"))
        ->where("customer_id", $customerId)
        ->groupBy(DB::raw("YEAR(wg_customer_document.created_at)"))
        ->orderBy(DB::raw("YEAR(wg_customer_document.created_at)", "desc"));

        return $query->get()->map(function($item) {
            return [
                "item" => $item->year,
                "value" => $item->year
            ];
        });
    }

    private function getMonths($customerId, $year)
    {
        $query = $this->model->newQuery();

        $year = $year ?? 1;
        
        $query
        ->select( DB::raw("MONTH(wg_customer_document.created_at) as month"))
        ->where("customer_id", $customerId)
        ->when($year != null, function($query) use($year) {
            $query->whereYear("wg_customer_document.created_at", $year);
        })        
        ->groupBy(DB::raw("MONTH(wg_customer_document.created_at)"));        

        return $query->get()->map(function($item) {
            return [
                "item" => $this->getMonthName($item->month),
                "value" => $item->month
            ];
        });
    }
}
