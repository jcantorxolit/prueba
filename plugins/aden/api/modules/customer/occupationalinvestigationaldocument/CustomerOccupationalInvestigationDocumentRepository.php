<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\OccupationalInvestigationAlDocument;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
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
use Queue;

class CustomerOccupationalInvestigationDocumentRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerOccupationalInvestigationDocumentModel());

        $this->service = new CustomerOccupationalInvestigationDocumentService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_occupational_investigation_al_document.id",
            "documentType" => "document_type.item AS documentType",
            "classification" => "customer_document_classification.item AS classification",
            "description" => "wg_customer_occupational_investigation_al_document.description",
            "version" => "wg_customer_occupational_investigation_al_document.version",
            "createdAt" => "wg_customer_occupational_investigation_al_document.created_at",
            "createdBy" => "users.name AS createdBy",
            "status" => "customer_document_status.item AS status",
            "label" => "minimum_standard_item_documenta_0312_label.item AS label",
            "statusCode" => "wg_customer_occupational_investigation_al_document.status AS statusCode",
            "customerId" => "wg_customer_occupational_investigation_al.customer_id",
            "protectionType" => "wg_customer_document_security.protectionType",
            "hasPermission" => DB::raw("(CASE WHEN user_id IS NULL THEN 0 ELSE 1 END) AS hasPermission"),            
            "customerOccupationalInvestigationId" => "wg_customer_occupational_investigation_al_document.customer_occupational_investigation_id",            
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_occupational_investigation_al_document.type', '=', 'document_type.value');
            $join->on('wg_customer_occupational_investigation_al_document.origin', '=', 'document_type.origin');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
            $join->on('wg_customer_occupational_investigation_al_document.classification', '=', 'customer_document_classification.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
            $join->on('wg_customer_occupational_investigation_al_document.status', '=', 'customer_document_status.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('minimum_standard_item_documenta_0312_label')), function ($join) {
            $join->on('wg_customer_occupational_investigation_al_document.label', '=', 'minimum_standard_item_documenta_0312_label.value');
        })->join("wg_customer_occupational_investigation_al", function ($join) {
            $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_document.customer_occupational_investigation_id');
        })->leftjoin("wg_customer_document_security", function ($join) {
            $join->on('wg_customer_occupational_investigation_al.customer_id', '=', 'wg_customer_document_security.customer_id');
            $join->on('wg_customer_occupational_investigation_al_document.type', '=', 'wg_customer_document_security.documentType');
            $join->on('wg_customer_occupational_investigation_al_document.origin', '=', 'wg_customer_document_security.origin');
        })->leftjoin(DB::raw(CustomerDocumentModel::getSecurityUserRelationTable('security')), function ($join) use ($authUser) {
            $join->on('wg_customer_occupational_investigation_al_document.id', '=', 'security.id');
            $join->on('security.user_id', '=', DB::raw($authUser ? $authUser->id : 0));
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_occupational_investigation_al_document.created_by', '=', 'users.id');
        });

        $query->whereRaw("((protectionType = 'public' OR protectionType IS NULL) OR (protectionType = 'private' AND user_id IS NOT NULL))");

        $this->applyCriteria($query, $criteria);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, CustomerOccupationalInvestigationDocumentModel::CLASS_NAME);
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

        $entityModel->customerOccupationalInvestigationId = $entity->customerOccupationalInvestigationId;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->origin = $entity->type ? $entity->type->origin : null;
        $entityModel->classification = $entity->classification ? $entity->classification->value : null;
        $entityModel->description = $entity->description;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->version = $entity->version;
        $entityModel->label = isset($entity->label) ? $entity->label : "M";        

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
            $entity->customerOccupationalInvestigationId = $model->customerOccupationalInvestigationId;
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
            ->join("wg_customer_occupational_investigation_al", function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_document.customer_occupational_investigation_id');
            })
            ->select(DB::raw("YEAR(wg_customer_occupational_investigation_al_document.created_at) as year"))
            ->where("wg_customer_occupational_investigation_al.customer_id", $customerId)
            ->groupBy(DB::raw("YEAR(wg_customer_occupational_investigation_al_document.created_at)"))
            ->orderBy(DB::raw("YEAR(wg_customer_occupational_investigation_al_document.created_at)", "DESC"));

        return $query->get()->map(function ($item) {
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
            ->join("wg_customer_occupational_investigation_al", function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_document.customer_occupational_investigation_id');
            })
            ->select(DB::raw("MONTH(wg_customer_occupational_investigation_al_document.created_at) as month"))
            ->where("wg_customer_occupational_investigation_al.customer_id", $customerId)
            ->when($year != null, function($query) use($year) {
                $query->whereYear("wg_customer_occupational_investigation_al_document.created_at", $year);
            })               
            ->groupBy(DB::raw("MONTH(wg_customer_occupational_investigation_al_document.created_at)"));

        return $query->get()->map(function ($item) {
            return [
                "item" => $this->getMonthName($item->month),
                "value" => $item->month
            ];
        });
    }

    public function export($criteria, $zipFilename = null)
    {
        $start = Carbon::now();

        $authUser = $this->getAuthUser();
        $criteria->email = $authUser->email;
        $criteria->name = $authUser->name;
        $criteria->userId = $authUser->id;

        Queue::push(CustomerOccupationalInvestigationDocumentJob::class, ['criteria' => $criteria], 'zip');

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
}
