<?php

namespace AdeN\Api\Modules\Customer\Document;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Classes\Criteria;
use DB;
use Log;
use Str;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;

class CustomerDocumentService extends BaseService
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
            "TIPO DOCUMENTO" => "documentType",
            "CLASIFICACIÓN" => "classification",
            "DESCRIPCIÓN" => "description",
            "VERSIÓN" => "version",
            "ESTADO" => "status",
            "UBICACIÓN / NOMBRE DOCUMENTO" => "filename",
        ];

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');

        $zipContent = [];

        if ($customerId != null) {
            $documents = \Wgroup\Models\CustomerDocument::where('customer_id', $customerId->value)->get();
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
            'zip' => $zipContent//ExportHelper::fileIterator($data, "fullPath", "filename"),
        ];
    }

    private function prepareQueryExportData($criteria)
    {
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');

        $storagePath = str_replace("\\", "/", CmsHelper::getStorageDirectory(''));

        return DB::table('wg_customer_document')
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_document.customer_id');
            })
            ->leftjoin(DB::raw(CustomerModel::getDocumentTypeRelation('document_type')), function ($join) {
                $join->on('wg_customer_document.type', '=', 'document_type.value');
                $join->on('wg_customer_document.origin', '=', 'document_type.origin');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_classification')), function ($join) {
                $join->on('wg_customer_document.classification', '=', 'customer_document_classification.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_document.status', '=', 'customer_document_status.value');

            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_document.createdBy', '=', 'users.id');
            })
            ->join('system_files', function ($join) use ($customerId) {
                $join->on('wg_customer_document.id', '=', 'system_files.attachment_id');
                $join->where('system_files.attachment_type', '=', 'Wgroup\\Models\\CustomerDocument');
                $join->where('system_files.field', '=', 'document');
                $join->where('wg_customer_document.customer_id', '=', $customerId->value);
            })
            ->when(!empty($criteria->filter->type), function($query) use ($criteria) {
                $query->where('wg_customer_document.type', $criteria->filter->type->value);
                $query->where('wg_customer_document.origin', $criteria->filter->type->origin);
            })
            ->when(!empty($criteria->filter->year), function($query) use ($criteria) {
                $query->whereYear('wg_customer_document.created_at', $criteria->filter->year->value);                
            })
            ->when(!empty($criteria->filter->month), function($query) use ($criteria) {
                $query->whereMonth('wg_customer_document.created_at', $criteria->filter->month->value);                
            })
            ->select(
                "document_type.item AS documentType",
                "customer_document_classification.item AS classification",
                "wg_customer_document.description",
                "wg_customer_document.id",
                "wg_customer_document.version",
                "customer_document_status.item AS status",
                "wg_customer_document.created_at",
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
                        CONCAT_WS(\"/\", wg_customers.documentNumber, SUBSTR(system_files.disk_name, 7, 4), system_files.file_name),
                        NULL
                    ) as filename"),
                'wg_customer_document.customer_id AS customerId'
            );
    }
}
