<?php

namespace AdeN\Api\Modules\Project\Documents;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Wgroup\SystemParameter\SystemParameter;
use Queue;
use AdeN\Api\Modules\Customer\Document\CustomerDocumentModel;

class ProjectDocumentRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new ProjectDocumentModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_project_document.id",
            "documentType" => "document_type.item AS documentType",
            "classification" => "customer_document_classification.item AS classification",
            "description" => "wg_customer_project_document.description",
            "version" => "wg_customer_project_document.version",
            "createdAt" => "wg_customer_project_document.created_at",
            "createdBy" => "users.name AS createdBy",
            "status" => "customer_document_status.item AS status",
            "statusCode" => "wg_customer_project_document.status AS statusCode",
            "protectionType" => "wg_customer_document_security.protectionType",
            "hasPermission" => DB::raw("(CASE WHEN user_id IS NULL THEN 0 ELSE 1 END) AS hasPermission"),
            "projectId" => "wg_customer_project_document.project_id as projectId",
            "customerId" => "wg_customer_project.customer_id as customerId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query
            ->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
                $join->on('wg_customer_project_document.type', '=', 'document_type.value');
                $join->on('wg_customer_project_document.origin', '=', 'document_type.origin');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
                $join->on('wg_customer_project_document.classification', '=', 'customer_document_classification.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_project_document.status', '=', 'customer_document_status.value');
            })
            ->join("wg_customer_project", function ($join) {
                $join->on('wg_customer_project.id', '=', 'wg_customer_project_document.project_id');
            })
            ->leftjoin("wg_customer_document_security", function ($join) {
                $join->on('wg_customer_project.customer_id', '=', 'wg_customer_document_security.customer_id');
                $join->on('wg_customer_project_document.type', '=', 'wg_customer_document_security.documentType');
                $join->on('wg_customer_project_document.origin', '=', 'wg_customer_document_security.origin');
            })
            ->leftjoin(DB::raw(CustomerDocumentModel::getSecurityUserRelationTable('security')), function ($join) use ($authUser) {
                $join->on('wg_customer_project_document.id', '=', 'security.id');
                $join->on('security.user_id', '=', DB::raw($authUser ? $authUser->id : 0));
            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_project_document.created_by', '=', 'users.id');
            }) ;

        $query->whereRaw("((protectionType = 'public' OR protectionType IS NULL) OR (protectionType = 'private' AND user_id IS NOT NULL))");

        $this->applyCriteria($query, $criteria);


        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, ProjectDocumentModel::class);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }


    public function insertOrUpdate($entity)
    {
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

        $entityModel->projectId = $entity->projectId;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->origin = $entity->type ? $entity->type->origin : null;
        $entityModel->classification = $entity->classification ? $entity->classification->value : null;
        $entityModel->description = $entity->description;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->version = $entity->version;

        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
        }

        $entityModel->save();

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
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->projectId = $model->projectId;
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

    public function export($criteria, $zipFilename = null)
    {
        $start = Carbon::now();

        $authUser = $this->getAuthUser();
        $criteria->email = $authUser->email;
        $criteria->name = $authUser->name;
        $criteria->userId = $authUser->id;

       
        Queue::push(ProjectDocumentJob::class, ['criteria' => $criteria], 'zip');

        $end = Carbon::now();

        return [
            'message' => 'ok',
            'elapseTime' => $end->diffInSeconds($start),
            'endTime' => $end->timestamp,
            'filename' => $zipFilename,
            'path' => CmsHelper::getPublicDirectory('zip/exports/'),
            //'uids' => $data['uids']
        ];
    }

    public function getExportData($criteria)
    {
        $this->setColumns([
            "customerProjectId" => "export.customerProjectId",
            "customerId" => "export.customerId",
        ]);

        $baseQuery = $this->prepareQueryExportData($criteria);

        $query = $this->prepareQuery($baseQuery->toSql())
            ->mergeBindings($baseQuery);

        $this->applyCriteria($query, $criteria);

        $data = $query->get();

        $heading = [
            "NOMBRE ACTIVIDAD" => "name",
            "TIPO DOCUMENTO" => "documentType",
            "DESCRIPCIÓN DOCUMENTO" => "description",
            "VERSIÓN DOCUMENTO" => "version",
            "ESTADO DOCUMENTO" => "status",
            "UBICACIÓN / NOMBRE DOCUMENTO" => "filename",
        ];


        $customerProjectId = CriteriaHelper::getMandatoryFilter($criteria, 'customerProjectId');
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');        
        $uids = CriteriaHelper::getMandatoryFilter($criteria, 'id');

        if ($customerId != null) {
            $uids = new \stdClass();
            $uids->value = $data->map(function($item) {
                return $item->id;
            })->toArray();
        }

        $zipContent = [];

        if ($customerProjectId != null) {
            $documents = ProjectDocumentModel::where('project_id', $customerProjectId->value)->get();
        } else if ($uids != null) {
            $documents = ProjectDocumentModel::whereIn('id', $uids->value)->get();
        }

        if ($documents != null && $documents->count() > 0) {
            foreach ($data as $value) {
                if (($document = $documents->firstWhere('id', $value->id))) {
                    $zipContent[] = [
                        'filename' => $value->filename,
                        'fileContents' => $document->document
                    ];
                }
            }
        }

        return [
            'excel' => ExportHelper::headings($data, $heading),
            'zip' => $zipContent,
            'uids' => $uids
        ];
    }

    public function prepareQuery($query, $alias = 'export')
    {
        return DB::table(DB::raw("($query) AS $alias"));
    }

    private function prepareQueryExportData($criteria)
    {
        $authUser = $this->getAuthUser();

        $storagePath = str_replace("\\", "/", CmsHelper::getStorageDirectory(''));

        $customerProjectId = CriteriaHelper::getMandatoryFilter($criteria, 'customerProjectId');
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $uids = CriteriaHelper::getMandatoryFilter($criteria, 'id');

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        $documentClass = ProjectDocumentModel::class;

        $query = DB::table('wg_customer_project_document')
            ->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
                $join->on('wg_customer_project_document.type', '=', 'document_type.value');
                $join->on('wg_customer_project_document.origin', '=', 'document_type.origin');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
                $join->on('wg_customer_project_document.classification', '=', 'customer_document_classification.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_project_document.status', '=', 'customer_document_status.value');
            })
            ->join("wg_customer_project", function ($join) {
                $join->on('wg_customer_project.id', '=', 'wg_customer_project_document.project_id');
            })
            ->leftjoin("wg_customer_document_security", function ($join) {
                $join->on('wg_customer_project.customer_id', '=', 'wg_customer_document_security.customer_id');
                $join->on('wg_customer_project_document.type', '=', 'wg_customer_document_security.documentType');
                $join->on('wg_customer_project_document.origin', '=', 'wg_customer_document_security.origin');
            })
            ->leftjoin(DB::raw(CustomerDocumentModel::getSecurityUserRelationTable('security')), function ($join) use ($authUser) {
                $join->on('wg_customer_project_document.id', '=', 'security.id');
                $join->on('security.user_id', '=', DB::raw($authUser ? $authUser->id : 0));
            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_project_document.created_by', '=', 'users.id');
            }) 
            ->join('system_files', function ($join) use ($customerProjectId, $documentClass) {
                $join->on('wg_customer_project_document.id', '=', 'system_files.attachment_id');
                $join->whereRaw("system_files.attachment_type LIKE '%ProjectDocumentModel%'");
                $join->whereRaw("system_files.field = 'document'");
                if ($customerProjectId) {
                    $join->whereRaw("wg_customer_project_document.project_id = $customerProjectId->value");
                }
            })
            ->when(!empty($criteria->filter) && !empty($criteria->filter->type), function($query) use ($criteria) {
                $query->where('wg_customer_project_document.requirement', $criteria->filter->type->value);
                $query->where('wg_customer_project_document.origin', $criteria->filter->type->origin);
            })
            ->when(!empty($criteria->filter) && !empty($criteria->filter->year), function($query) use ($criteria) {
                $query->whereYear('wg_customer_project_document.created_at', $criteria->filter->year->value);                
            })
            ->when(!empty($criteria->filter) && !empty($criteria->filter->month), function($query) use ($criteria) {
                $query->whereMonth('wg_customer_project_document.created_at', $criteria->filter->month->value);                
            })     
            ->select(
                "document_type.item AS documentType",
                "customer_document_classification.item AS classification",
                "wg_customer_project_document.description",
                "wg_customer_project_document.version",
                "wg_customer_project_document.created_at",
                "users.name AS createdBy",
                "customer_document_status.item AS status",
                "wg_customer_project_document.status AS statusCode",
                "wg_customer_document_security.protectionType",
                "wg_customer_project.name",
                DB::raw("(CASE WHEN user_id IS NULL THEN 0 ELSE 1 END) AS hasPermission"),
                DB::raw("IF (
                system_files.disk_name IS NOT NULL,
                CONCAT_WS(\"/\",'{$storagePath}',
                        SUBSTR(system_files.disk_name, 1, 3),
                        SUBSTR(system_files.disk_name, 4, 3),
                        SUBSTR(system_files.disk_name, 7, 3),
                        system_files.disk_name
                    ),
                    NULL
                ) as fullPath"),
                DB::raw("IF (
                        system_files.disk_name IS NOT NULL,
                        CONCAT_WS(\"/\", wg_customer_project.name, SUBSTR(system_files.disk_name, 7, 4), system_files.file_name),
                        NULL
                    ) as filename"),
                'wg_customer_project.customer_id AS customerId',
                "wg_customer_project_document.project_id AS customerProjectId",
                "wg_customer_project_document.id"
            )
            ->whereRaw("(`wg_customer_project`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");

        if ($uids != null) {
            $query->whereIn('wg_customer_project_document.id', $uids->value);
        }

        return $query;
    }
}
