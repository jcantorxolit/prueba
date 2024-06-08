<?php

namespace AdeN\Api\Modules\Customer\Employee\CriticalActivity;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;

class CustomerEmployeeCriticalActivityService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function bulkInsertCustomerEmployeeCriticalActivityTracking()
    {
        $query = DB::table('wg_customer_employee_critical_activity');

        $query->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_critical_activity.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join('wg_customers', function ($join) {
            $join->on('wg_customers.id', '=', 'wg_customer_employee.customer_id');
        })->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_employee_critical_activity.requirement', '=', 'document_type.value');
            $join->on('wg_customer_employee_critical_activity.origin', '=', 'document_type.origin');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_employee_critical_activity.createdBy', '=', 'users.id');
        })->select(
            DB::raw('NULL id'),
            'wg_customer_employee_critical_activity.id AS customer_employee_document_id',
            DB::raw("'Revisado Denegado' AS type"),
            DB::raw("'PERSONAL RETIRADO' AS observation"),
            DB::raw("NULL AS createdBy"),
            DB::raw("NULL AS updatedBy"),
            DB::raw("NOW() as created_at"),
            DB::raw("NOW() as updated_at")
        );

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)")
            ->where('wg_customer_employee_critical_activity.status', '<>', 2)
            ->whereRaw("(wg_customer_employee.isActive = 0 OR wg_customer_employee.isActive IS NULL)")
            ->where('wg_customer_employee_critical_activity.isApprove', 1)
            ->where('wg_customers.status', 1);

        $sql = 'INSERT INTO wg_customer_employee_critical_activity_tracking (`id`, `customer_employee_document_id`, `type`, `observation`, `createdBy`, `updatedBy`, `created_at`, `updated_at`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function bulkInsertCustomerEmployeeAudit()
    {
        $query = DB::table('wg_customer_employee_critical_activity');

        $query->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_critical_activity.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join('wg_customers', function ($join) {
            $join->on('wg_customers.id', '=', 'wg_customer_employee.customer_id');
        })->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_employee_critical_activity.requirement', '=', 'document_type.value');
            $join->on('wg_customer_employee_critical_activity.origin', '=', 'document_type.origin');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_employee_critical_activity.createdBy', '=', 'users.id');
        })->select(
            DB::raw('NULL id'),
            'wg_customer_employee.id AS customer_employee_id',
            DB::raw("'Documentos Empleado' AS model_name"),
            'wg_customer_employee.id AS model_id',
            DB::raw("'cronjob' AS user_type"),
            DB::raw("NULL AS user_id"),
            DB::raw("'Denegar' AS action"),
            DB::raw("'Se deniegan documentos del empleado por retiro' AS observation"),
            DB::raw("NULL ip"),
            DB::raw("NOW() as date"),
            DB::raw("COUNT(*) AS record_count"),
            DB::raw("NOW() as created_at"),
            DB::raw("NOW() as updated_at")
        )->groupBy('wg_customer_employee.id');

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)")
            ->where('wg_customer_employee_critical_activity.status', '<>', 2)
            ->whereRaw("(wg_customer_employee.isActive = 0 OR wg_customer_employee.isActive IS NULL)")
            ->where('wg_customer_employee_critical_activity.isApprove', 1)
            ->where('wg_customers.status', 1);

        $sql = 'INSERT INTO wg_customer_employee_audit (`id`, `customer_employee_id`, `model_name`, `model_id`, `user_type`, `user_id`, `action`, `observation`, `ip`, `date`, `record_count`, `created_at`, `updated_at`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function bulkIDeniedCustomerEmployeeCriticalActivity()
    {
        $query = DB::table('wg_customer_employee_critical_activity');

        $query->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_critical_activity.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join('wg_customers', function ($join) {
            $join->on('wg_customers.id', '=', 'wg_customer_employee.customer_id');
        })->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_employee_critical_activity.requirement', '=', 'document_type.value');
            $join->on('wg_customer_employee_critical_activity.origin', '=', 'document_type.origin');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_employee_critical_activity.createdBy', '=', 'users.id');
        });

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)")
            ->where('wg_customer_employee_critical_activity.status', '<>', 2)
            ->whereRaw("(wg_customer_employee.isActive = 0 OR wg_customer_employee.isActive IS NULL)")
            ->where('wg_customer_employee_critical_activity.isApprove', 1)
            ->where('wg_customers.status', 1);

        $query->update([
            'wg_customer_employee_critical_activity.isApprove' => 4,
            'wg_customer_employee_critical_activity.isDenied' => 1,
            'wg_customer_employee_critical_activity.updated_at' => DB::raw("NOW()")
        ]);
    }

    public function getExportData($criteria)
    {
        $baseQuery = $this->prepareQueryExportData($criteria);

        $query = $this->prepareQuery($baseQuery->toSql())
            ->mergeBindings($baseQuery);

        $this->applyWhere($query, $criteria);

        $data = $query->get();

        $heading = [
            "NUMERO IDENTIFICACIÓN" => "documentNumber",
            "NOMBRE" => "firstName",
            "APELLIDOS" => "lastName",
            "TIPO DOCUMENTO" => "requirement",
            "DESCRIPCIÓN" => "description",
            "INICIO VIGENCIA" => "startDate",
            "FINALIZACIÓN VIGENCIA" => "endDate",
            "VERSIÓN" => "version",
            "REQUERIDO" => "isRequired",
            "ESTADO" => "status",
            "VERIFICADO" => "isVerified",
            "MOTIVO DENEGADO" => "observation",
            "UBICACIÓN / NOMBRE DOCUMENTO" => "filename",
        ];


        $customerEmployeeId = CriteriaHelper::getMandatoryFilter($criteria, 'customerEmployeeId');
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');        
        $uids = CriteriaHelper::getMandatoryFilter($criteria, 'id');

        if ($customerId != null) {
            $uids = new \stdClass();
            $uids->value = $data->map(function($item) {
                return $item->id;
            })->toArray();
        }

        $zipContent = [];

        if ($customerEmployeeId != null) {
            $documents = \Wgroup\CustomerEmployeeCriticalActivity\CustomerEmployeeCriticalActivity::where('customer_employee_id', $customerEmployeeId->value)->get();
        } else if ($uids != null) {
            $documents = \Wgroup\CustomerEmployeeCriticalActivity\CustomerEmployeeCriticalActivity::whereIn('id', $uids->value)->get();
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

    public function getExpirationExportData($criteria)
    {
        $baseQuery = DB::table('wg_customer_employee_critical_activity');

        /* Example relation*/
        $baseQuery->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_critical_activity.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join('wg_employee', function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
        })->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
            $join->on('wg_customer_employee_critical_activity.requirement', '=', 'document_type.value');
            $join->on('wg_customer_employee_critical_activity.origin', '=', 'document_type.origin');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
            $join->on('wg_customer_employee_critical_activity.status', '=', 'customer_document_status.value');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_employee_critical_activity.createdBy', '=', 'users.id');
        })->select(
            "wg_customer_employee_critical_activity.id",
            "employee_document_type.item AS documentType",
            "wg_employee.documentNumber",
            "wg_employee.fullName",
            "wg_customer_config_workplace.name as workplace",
            "document_type.item AS requirement",
            "wg_customer_employee_critical_activity.description",
            "wg_customer_employee_critical_activity.startDate",
            "wg_customer_employee_critical_activity.endDate",
            "wg_customer_employee_critical_activity.version",
            DB::raw("CASE WHEN document_type.isRequired = '1' THEN 'Requerido' ELSE 'Opcional' END AS isRequired"),
            "customer_document_status.item AS status",
            DB::raw("CASE WHEN wg_customer_employee_critical_activity.isApprove = 1 THEN 'Aprobado' WHEN wg_customer_employee_critical_activity.isDenied = 1 THEN 'Denegado' ELSE '' END AS isVerified"),
            "wg_customer_employee_critical_activity.created_at",
            "users.name AS createdBy",
            "wg_customer_employee_critical_activity.status AS statusCode",
            "document_type.isRequired AS isRequiredCode",
            "wg_customer_employee.customer_id",
            DB::raw("YEAR(`endDate`) AS year"),
            DB::raw("MONTH(`endDate`) AS month"),
            'wg_customer_employee.customer_id AS customerId'
        );

        $baseQuery->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");
        $baseQuery->whereIn('wg_customer_employee_critical_activity.status', [1, 3]);

        $query = $this->prepareQuery($baseQuery->toSql())
            ->mergeBindings($baseQuery);

        $this->applyWhere($query, $criteria);

        $result = $query->get();

        $heading = [
            "TIPO IDENTIFICACIÓN" => "documentType",
            "NÚMERO IDENTIFICACIÓN" => "documentNumber",
            "NOMBRE" => "fullName",
            "CENTRO DE TRABAJO" => "workplace",
            "TIPO DOCUMENTO" => "requirement",
            "DESCRIPCIÓN" => "description",
            "FECHA INICIO VIGENCIA" => "startDate",
            "FECHA DE EXPIRACIÓN VIGENCIA" => "endDate",
            "VERSION" => "version",
            "REQUERIDO" => "isRequired",
            "ESTADO" => "status",
        ];

        return ExportHelper::headings($result, $heading);
    }

    private function prepareQueryExportData($criteria)
    {
        $storagePath = str_replace("\\", "/", CmsHelper::getStorageDirectory(''));

        $customerEmployeeId = CriteriaHelper::getMandatoryFilter($criteria, 'customerEmployeeId');
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $uids = CriteriaHelper::getMandatoryFilter($criteria, 'id');

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        $query = DB::table('wg_customer_employee_critical_activity')
            ->join('wg_customer_employee', function ($join) use($customerId) {
                $join->on('wg_customer_employee_critical_activity.customer_employee_id', '=', 'wg_customer_employee.id');
                if ($customerId) {
                    $join->where('wg_customer_employee.customer_id', '=', $customerId->value);
                }
            })
            ->join('wg_employee', function ($join) {
                $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
            })
            ->leftjoin(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
                $join->on('wg_customer_employee_critical_activity.requirement', '=', 'document_type.value');
                $join->on('wg_customer_employee_critical_activity.origin', '=', 'document_type.origin');
            })
            ->mergeBindings($qDocumentType)
            // ->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
            //     $join->on('wg_customer_employee_critical_activity.requirement', '=', 'document_type.value');
            //     $join->on('wg_customer_employee_critical_activity.origin', '=', 'document_type.origin');
            // })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_employee_critical_activity.status', '=', 'customer_document_status.value');
            })
            ->leftjoin(DB::raw(CustomerEmployeeCriticalActivityModel::getRelationTrackingMax('document_tracking')), function ($join) {
                $join->on('wg_customer_employee_critical_activity.id', '=', 'document_tracking.customer_employee_document_id');
            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_employee_critical_activity.createdBy', '=', 'users.id');
            })
            ->join('system_files', function ($join) use ($customerEmployeeId) {
                $join->on('wg_customer_employee_critical_activity.id', '=', 'system_files.attachment_id');
                $join->whereRaw("system_files.attachment_type = 'Wgroup\\\\CustomerEmployeeCriticalActivity\\\\CustomerEmployeeCriticalActivity'");
                $join->whereRaw("system_files.field = 'document'");
                if ($customerEmployeeId) {
                    $join->whereRaw("wg_customer_employee_critical_activity.customer_employee_id = $customerEmployeeId->value");
                }
            })
            ->when(!empty($criteria->filter) && !empty($criteria->filter->type), function($query) use ($criteria) {
                $query->where('wg_customer_employee_critical_activity.requirement', $criteria->filter->type->value);
                $query->where('wg_customer_employee_critical_activity.origin', $criteria->filter->type->origin);
            })
            ->when(!empty($criteria->filter) && !empty($criteria->filter->year), function($query) use ($criteria) {
                $query->whereYear('wg_customer_employee_critical_activity.created_at', $criteria->filter->year->value);                
            })
            ->when(!empty($criteria->filter) && !empty($criteria->filter->month), function($query) use ($criteria) {
                $query->whereMonth('wg_customer_employee_critical_activity.created_at', $criteria->filter->month->value);                
            })     
            ->select(
                "wg_employee.documentNumber",
                "wg_employee.firstName",
                "wg_employee.lastName",
                "document_type.item AS requirement",
                "wg_customer_employee_critical_activity.description",
                "wg_customer_employee_critical_activity.startDate",
                "wg_customer_employee_critical_activity.endDate",
                "wg_customer_employee_critical_activity.version",
                DB::raw("CASE WHEN document_type.isRequired = '1' THEN 'Requerido' ELSE 'Opcional' END AS isRequired"),
                "customer_document_status.item AS status",
                DB::raw("CASE WHEN wg_customer_employee_critical_activity.isApprove IS NOT NULL THEN 'Aprobado' WHEN wg_customer_employee_critical_activity.isDenied IS NOT NULL THEN 'Denegado' ELSE '' END AS isVerified"),
                DB::raw("CASE WHEN wg_customer_employee_critical_activity.isApprove IS NOT NULL THEN '' WHEN wg_customer_employee_critical_activity.isDenied IS NOT NULL THEN document_tracking.observation ELSE '' END AS observation"),
                "wg_customer_employee_critical_activity.created_at",
                "users.name AS createdBy",
                "wg_customer_employee_critical_activity.status AS statusCode",
                "document_type.isRequired AS isRequiredCode",
                "wg_customer_employee_critical_activity.customer_employee_id",
                DB::raw("YEAR(`endDate`) AS `year`"),
                DB::raw("MONTH(`endDate`) AS `month`"),
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
                        CONCAT_WS(\"/\", wg_employee.documentNumber, SUBSTR(system_files.disk_name, 7, 4), system_files.file_name),
                        NULL
                    ) as filename"),
                'wg_customer_employee.customer_id AS customerId',
                "wg_customer_employee_critical_activity.customer_employee_id AS customerEmployeeId",
                "wg_customer_employee_critical_activity.id"
            )
            ->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");

        if ($uids != null) {
            $query->whereIn('wg_customer_employee_critical_activity.id', $uids->value);
        }

        return $query;
    }

    public function getJobId($customerId)
    {
            return DB::table('wg_customer_config_job')
                ->join('wg_customer_config_job_data', function ($join) {
                    $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
                })
                ->join('wg_customer_config_process', function ($join) {
                    $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');
                })
                ->join('wg_customer_config_macro_process', function ($join) {
                    $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                })
                ->join('wg_customer_config_workplace', function ($join) {
                    $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
                })
                ->select('wg_customer_config_job_data.name', 'wg_customer_config_job.id', 'wg_customer_config_macro_process.workplace_id')
                ->where('wg_customer_config_workplace.customer_id', $customerId)
                ->get();
    }

    public function getWorkplaceId($customerId)
    {

            return DB::table('wg_customer_config_workplace')
                ->select('wg_customer_config_workplace.name', 'wg_customer_config_workplace.id')
                ->where('wg_customer_config_workplace.customer_id', $customerId)
                ->get();
    }

    public function findEntity($model, $criteria)
    {
        $query = $model->newQuery();

        $qTrackingGroup = DB::table('wg_customer_employee_critical_activity_tracking')
            ->select(
                DB::raw('MAX(wg_customer_employee_critical_activity_tracking.id) AS id'),
                'customer_employee_document_id'
            )
            ->join('wg_customer_employee_critical_activity', function ($join) {
                $join->on('wg_customer_employee_critical_activity.id', '=', 'wg_customer_employee_critical_activity_tracking.customer_employee_document_id');
            })
            ->groupBy('customer_employee_document_id');

        if ($model->customer_employee_id) {
            $qTrackingGroup->where('wg_customer_employee_critical_activity.customer_employee_id', $model->customer_employee_id);
        }

        $qTracking = DB::table('wg_customer_employee_critical_activity_tracking')
            ->join('wg_customer_employee_critical_activity', function ($join) {
                $join->on('wg_customer_employee_critical_activity.id', '=', 'wg_customer_employee_critical_activity_tracking.customer_employee_document_id');
            })
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_employee_critical_activity.customer_employee_id');
            })
            ->join(DB::raw("({$qTrackingGroup->toSql()}) AS wg_customer_employee_critical_activity_tracking_group"), function ($join) {
                $join->on('wg_customer_employee_critical_activity_tracking_group.id', '=', 'wg_customer_employee_critical_activity_tracking.id');
            })
            ->mergeBindings($qTrackingGroup)
            ->select(
                'wg_customer_employee_critical_activity_tracking.customer_employee_document_id',
                'observation'
            );

            if ($model->customer_employee_id) {
                $qTracking->where('wg_customer_employee.id', $model->customer_employee_id);
            }

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        /* Example relation*/
        $query->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_critical_activity.customer_employee_id', '=', 'wg_customer_employee.id');
        })
        ->leftjoin(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
            $join->on('wg_customer_employee_critical_activity.requirement', '=', 'document_type.value');
            $join->on('wg_customer_employee_critical_activity.origin', '=', 'document_type.origin');
        })
        ->mergeBindings($qDocumentType)
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
            $join->on('wg_customer_employee_critical_activity.status', '=', 'customer_document_status.value');
        })->leftjoin(DB::raw("({$qTracking->toSql()}) AS document_tracking"), function ($join) {
            $join->on('wg_customer_employee_critical_activity.id', '=', 'document_tracking.customer_employee_document_id');
        })->mergeBindings($qTracking)->leftjoin("users", function ($join) {
            $join->on('wg_customer_employee_critical_activity.createdBy', '=', 'users.id');
        })->select(
            "wg_customer_employee_critical_activity.id",
            "wg_customer_employee_critical_activity.description",
            "wg_customer_employee_critical_activity.startDate",
            "wg_customer_employee_critical_activity.endDate",
            "wg_customer_employee_critical_activity.version",
            DB::raw("CASE WHEN document_type.isRequired = '1' THEN 'Requerido' ELSE 'Opcional' END AS isRequired"),
            "customer_document_status.item AS status",
            DB::raw("CASE WHEN wg_customer_employee_critical_activity.isApprove = 1 THEN 'Aprobado' WHEN wg_customer_employee_critical_activity.isDenied = 1 THEN 'Denegado' ELSE '' END AS isVerified"),
            DB::raw("CASE WHEN wg_customer_employee_critical_activity.isApprove = 1 THEN '' WHEN wg_customer_employee_critical_activity.isDenied = 1 THEN document_tracking.observation ELSE '' END AS observation"),
            "wg_customer_employee_critical_activity.created_at",
            "users.name AS createdBy",
            "wg_customer_employee_critical_activity.status AS statusCode",
            "document_type.id AS requirementId",
            "document_type.item AS requirementItem",
            "document_type.isRequired AS isRequiredCode",
            "document_type.origin AS requirementOrigin",
            "document_type.value AS requirementValue",
            "wg_customer_employee_critical_activity.customer_employee_id",
            DB::raw("YEAR(`endDate`) AS year"),
            DB::raw("MONTH(`endDate`) AS month")
        );

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");
        $query->where('wg_customer_employee_critical_activity.id', $model->id);

        return $query->first();
    }
}
