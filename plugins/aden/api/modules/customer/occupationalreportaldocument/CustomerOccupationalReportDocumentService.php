<?php

namespace AdeN\Api\Modules\Customer\OccupationalReportAlDocument;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;

class CustomerOccupationalReportDocumentService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getExportData($criteria)
    {
        $baseQuery = $this->prepareQueryExportData($criteria);

        $query = $this->prepareQuery($baseQuery->toSql())
            ->mergeBindings($baseQuery);

        $this->applyWhere($query, $criteria);

        $data = $query->get();

        $heading = [
            "TIPO SEGUIMIENTO" =>  "type",
            "FECHA EVENTO" => "eventDateTime",
            "TIPO DOCUMENTO" => "documentType",
            "CLASIFICACIÓN" => "classification",
            "DESCRIPCIÓN" => "description",
            "VERSIÓN" => "version",
            "ESTADO" => "status",
            "UBICACIÓN / NOMBRE DOCUMENTO" => "filename",
        ];
        
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $uids = CriteriaHelper::getMandatoryFilter($criteria, 'id');

        if ($customerId != null) {
            $uids = new \stdClass();
            $uids->value = $data->map(function($item) {
                return $item->id;
            })->toArray();
        }

        $zipContent = [];

        if ($uids != null) {
            $documents = CustomerOccupationalReportDocumentModel::whereIn('id', $uids->value)->get();
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

    private function prepareQueryExportData($criteria)
    {
        $storagePath = str_replace("\\", "/", CmsHelper::getStorageDirectory(''));
        
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $uids = CriteriaHelper::getMandatoryFilter($criteria, 'id');        

        $query = DB::table('wg_customer_occupational_report_al_document')
            ->join('wg_customer_occupational_report_al', function ($join) use($customerId) {
                $join->on('wg_customer_occupational_report_al_document.customer_occupational_report_id', '=', 'wg_customer_occupational_report_al.id');
                if ($customerId) {
                    $join->where('wg_customer_occupational_report_al.customer_id', '=', $customerId->value);
                }
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('tracking_tiposeg')), function ($join) {
                $join->on('wg_customer_occupational_report_al.type', '=', 'tracking_tiposeg.value');
    
            })
            ->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
                $join->on('wg_customer_occupational_report_al_document.type', '=', 'document_type.value');
                $join->on('wg_customer_occupational_report_al_document.origin', '=', 'document_type.origin');
            })            
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
                $join->on('wg_customer_occupational_report_al_document.classification', '=', 'customer_document_classification.value');
            })        
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_occupational_report_al_document.status', '=', 'customer_document_status.value');
            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_occupational_report_al_document.created_by', '=', 'users.id');
            })
            ->join('system_files', function ($join) use ($customerId) {
                $join->on('wg_customer_occupational_report_al_document.id', '=', 'system_files.attachment_id');
                $join->where('system_files.attachment_type', '=', CustomerOccupationalReportDocumentModel::CLASS_NAME);
                $join->where('system_files.field', '=', 'document');                
            })
            ->when(!empty($criteria->filter->type), function($query) use ($criteria) {
                $query->where('wg_customer_occupational_report_al_document.type', $criteria->filter->type->value);
                $query->where('wg_customer_occupational_report_al_document.origin', $criteria->filter->type->origin);
            })
            ->when(!empty($criteria->filter->year), function($query) use ($criteria) {
                $query->whereYear('wg_customer_occupational_report_al_document.created_at', $criteria->filter->year->value);                
            })
            ->when(!empty($criteria->filter->month), function($query) use ($criteria) {
                $query->whereMonth('wg_customer_occupational_report_al_document.created_at', $criteria->filter->month->value);                
            })     
            ->select(
                "tracking_tiposeg.item AS type",                
                "wg_customer_occupational_report_al.eventDateTime",
                "document_type.item AS documentType",
                "customer_document_classification.item AS classification",
                "wg_customer_occupational_report_al_document.description",
                "wg_customer_occupational_report_al_document.id",
                "wg_customer_occupational_report_al_document.version",
                "customer_document_status.item AS status",
                "wg_customer_occupational_report_al_document.created_at",
                "users.name AS createdBy",
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
                        CONCAT_WS(\"/\", tracking_tiposeg.item, SUBSTR(system_files.disk_name, 7, 4), system_files.file_name),
                        NULL
                    ) as filename"),
                'wg_customer_occupational_report_al.customer_id AS customerId'
            )
            ->whereRaw("(`wg_customer_occupational_report_al`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");

        if ($uids != null) {
            $query->whereIn('wg_customer_occupational_report_al_document.id', $uids->value);
        }

        return $query;
    }
}