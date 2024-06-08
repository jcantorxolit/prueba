<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Employee\Document;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use Illuminate\Pagination\Paginator;
use Queue;

use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\StringHelper;
use Maatwebsite\Excel\Facades\Excel;
use Wgroup\CustomerEmployeeDocument\CustomerEmployeeDocument;

class CustomerEmployeeDocumentRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerEmployeeDocumentModel());

        $this->service = new CustomerEmployeeDocumentService();
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

    public static function getCustomExpirationFilters()
    {
        return [
            ["alias" => "Tipo Identificación", "name" => "documentType"],
            ["alias" => "Número Identificación", "name" => "documentNumber"],
            ["alias" => "Nombre", "name" => "fullName"],
            ["alias" => "Centro de Trabajo", "name" => "workplace"],
            ["alias" => "Tipo Documento", "name" => "requirement"],
            ["alias" => "Descripción", "name" => "description"],
            ["alias" => "Fecha de Inicio Vigencia", "name" => "startDate"],
            ["alias" => "Fecha de Expiración Vigencia", "name" => "endDate"],
            ["alias" => "Versión", "name" => "version"],
            ["alias" => "Requerido", "name" => "isRequired"],
            ["alias" => "Estado", "name" => "status"],
            ["alias" => "Año", "name" => "year"],
            ["alias" => "Mes", "name" => "month"],
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
            "id" => "wg_customer_employee_document.id",
            "requirement" => "document_type.item AS requirement",
            "description" => "wg_customer_employee_document.description",
            "startDate" => "wg_customer_employee_document.startDate",
            "endDate" => "wg_customer_employee_document.endDate",
            "version" => "wg_customer_employee_document.version",
            "isRequired" => DB::raw("CASE WHEN document_type.isRequired = '1' THEN 'Requerido' ELSE 'Opcional' END AS isRequired"),
            "status" => "customer_document_status.item AS status",
            "isVerified" => DB::raw("CASE WHEN wg_customer_employee_document.isApprove = 1 THEN 'Aprobado' WHEN wg_customer_employee_document.isDenied = 1 THEN 'Denegado' ELSE '' END AS isVerified"),
            "observation" => DB::raw("CASE WHEN wg_customer_employee_document.isApprove = 1 THEN '' WHEN wg_customer_employee_document.isDenied = 1 THEN document_tracking.observation ELSE '' END AS observation"),
            "createdAt" => "wg_customer_employee_document.created_at",
            "createdBy" => "users.name AS createdBy",
            "statusCode" => "wg_customer_employee_document.status AS statusCode",
            "isRequiredCode" => "document_type.isRequired AS isRequiredCode",
            "customerEmployeeId" => "wg_customer_employee_document.customer_employee_id",
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
            ) as documentUrl"),*/
            "year" => DB::raw("YEAR(`endDate`) AS year"),
            "month" => DB::raw("MONTH(`endDate`) AS month"),
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $customerEmployeeId = CriteriaHelper::getMandatoryFilter($criteria, 'customerEmployeeId');

        $qTrackingGroup = DB::table('wg_customer_employee_document_tracking')
            ->select(
                DB::raw('MAX(wg_customer_employee_document_tracking.id) AS id'),
                'customer_employee_document_id'
            )
            ->join('wg_customer_employee_document', function ($join) {
                $join->on('wg_customer_employee_document.id', '=', 'wg_customer_employee_document_tracking.customer_employee_document_id');
            })
            ->groupBy('customer_employee_document_id');

        if ($customerEmployeeId) {
            $qTrackingGroup->where('wg_customer_employee_document.customer_employee_id', $customerEmployeeId->value);
        }

        $qTracking = DB::table('wg_customer_employee_document_tracking')
            ->join('wg_customer_employee_document', function ($join) {
                $join->on('wg_customer_employee_document.id', '=', 'wg_customer_employee_document_tracking.customer_employee_document_id');
            })
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_employee_document.customer_employee_id');
            })
            ->join(DB::raw("({$qTrackingGroup->toSql()}) AS wg_customer_employee_document_tracking_group"), function ($join) {
                $join->on('wg_customer_employee_document_tracking_group.id', '=', 'wg_customer_employee_document_tracking.id');
            })
            ->mergeBindings($qTrackingGroup)
            ->select(
                'wg_customer_employee_document_tracking.customer_employee_document_id',
                'observation'
            );

        if ($customerEmployeeId) {
            $qTracking->where('wg_customer_employee.id', $customerEmployeeId->value);
        }

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        $query
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee_document.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->leftjoin(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
                $join->on('wg_customer_employee_document.requirement', '=', 'document_type.value');
                $join->on('wg_customer_employee_document.origin', '=', 'document_type.origin');
            })
            ->mergeBindings($qDocumentType)
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_employee_document.status', '=', 'customer_document_status.value');
            })
            ->leftjoin(DB::raw("({$qTracking->toSql()}) AS document_tracking"), function ($join) {
                $join->on('wg_customer_employee_document.id', '=', 'document_tracking.customer_employee_document_id');
            })
            ->mergeBindings($qTracking)->leftjoin("users", function ($join) {
                $join->on('wg_customer_employee_document.createdBy', '=', 'users.id');
            });
        // ->leftjoin('system_files', function ($join) {
        //     $join->on('system_files.attachment_id', '=', 'wg_customer_employee_document.id');
        //     $join->whereRaw("system_files.attachment_type = '" . CustomerEmployeeDocumentModel::CLASS_NAME . "'");
        //     $join->whereRaw("system_files.field = 'document'");
        // });

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, CustomerEmployeeDocumentModel::CLASS_NAME);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allFilter($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_employee_document.id",
            "requirement" => "document_type.item AS requirement",
            "description" => "wg_customer_employee_document.description",
            "startDate" => "wg_customer_employee_document.startDate",
            "endDate" => "wg_customer_employee_document.endDate",
            "version" => "wg_customer_employee_document.version",
            "isRequired" => DB::raw("CASE WHEN document_type.isRequired = '1' THEN 'Requerido' ELSE 'Opcional' END AS isRequired"),
            "status" => "customer_document_status.item AS status",
            "isVerified" => DB::raw("CASE WHEN wg_customer_employee_document.isApprove = 1 THEN 'Aprobado' WHEN wg_customer_employee_document.isDenied = 1 THEN 'Denegado' ELSE '' END AS isVerified"),
            "observation" => DB::raw("CASE WHEN wg_customer_employee_document.isApprove = 1 THEN '' WHEN wg_customer_employee_document.isDenied = 1 THEN document_tracking.observation ELSE '' END AS observation"),
            "createdAt" => "wg_customer_employee_document.created_at",
            "createdBy" => "users.name AS createdBy",
            "statusCode" => "wg_customer_employee_document.status AS statusCode",
            "isRequiredCode" => "document_type.isRequired AS isRequiredCode",
            "customerEmployeeId" => "wg_customer_employee_document.customer_employee_id",
            "year" => DB::raw("YEAR(`endDate`) AS year"),
            "month" => DB::raw("MONTH(`endDate`) AS month"),
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $customerEmployeeId = CriteriaHelper::getMandatoryFilter($criteria, 'customerEmployeeId');

        $qTrackingGroup = DB::table('wg_customer_employee_document_tracking')
            ->select(
                DB::raw('MAX(wg_customer_employee_document_tracking.id) AS id'),
                'customer_employee_document_id'
            )
            ->join('wg_customer_employee_document', function ($join) {
                $join->on('wg_customer_employee_document.id', '=', 'wg_customer_employee_document_tracking.customer_employee_document_id');
            })
            ->groupBy('customer_employee_document_id');

        if ($customerEmployeeId) {
            $qTrackingGroup->where('wg_customer_employee_document.customer_employee_id', $customerEmployeeId->value);
        }

        $qTracking = DB::table('wg_customer_employee_document_tracking')
            ->join('wg_customer_employee_document', function ($join) {
                $join->on('wg_customer_employee_document.id', '=', 'wg_customer_employee_document_tracking.customer_employee_document_id');
            })
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_employee_document.customer_employee_id');
            })
            ->join(DB::raw("({$qTrackingGroup->toSql()}) AS wg_customer_employee_document_tracking_group"), function ($join) {
                $join->on('wg_customer_employee_document_tracking_group.id', '=', 'wg_customer_employee_document_tracking.id');
            })
            ->mergeBindings($qTrackingGroup)
            ->select(
                'wg_customer_employee_document_tracking.customer_employee_document_id',
                'observation'
            );

        if ($customerEmployeeId) {
            $qTracking->where('wg_customer_employee.id', $customerEmployeeId->value);
        }

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        /* Example relation*/
        $query->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_document.customer_employee_id', '=', 'wg_customer_employee.id');
        })
            ->leftjoin(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
                $join->on('wg_customer_employee_document.requirement', '=', 'document_type.value');
                $join->on('wg_customer_employee_document.origin', '=', 'document_type.origin');
            })
            ->mergeBindings($qDocumentType)
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_employee_document.status', '=', 'customer_document_status.value');
            })->leftjoin(DB::raw("({$qTracking->toSql()}) AS document_tracking"), function ($join) {
                $join->on('wg_customer_employee_document.id', '=', 'document_tracking.customer_employee_document_id');
            })->mergeBindings($qTracking)->leftjoin("users", function ($join) {
                $join->on('wg_customer_employee_document.createdBy', '=', 'users.id');
            });

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");

        $this->applyCriteria($query, $criteria);

        $result = $this->get($query, $criteria);

        $result['uids'] = $this->allUids($criteria);

        return $result;
    }

    public function allExpiration($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_employee_document.id",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber",
            "fullName" => "wg_employee.fullName",
            "workplace" => "wg_customer_config_workplace.name as workplace",
            "requirement" => "document_type.item AS requirement",
            "description" => "wg_customer_employee_document.description",
            "startDate" => "wg_customer_employee_document.startDate",
            "endDate" => "wg_customer_employee_document.endDate",
            "version" => "wg_customer_employee_document.version",
            "isRequired" => DB::raw("CASE WHEN document_type.isRequired = '1' THEN 'Requerido' ELSE 'Opcional' END AS isRequired"),
            "status" => "customer_document_status.item AS status",
            "isVerified" => DB::raw("CASE WHEN wg_customer_employee_document.isApprove = 1 THEN 'Aprobado' WHEN wg_customer_employee_document.isDenied = 1 THEN 'Denegado' ELSE '' END AS isVerified"),
            "createdAt" => "wg_customer_employee_document.created_at",
            "createdBy" => "users.name AS createdBy",
            "statusCode" => "wg_customer_employee_document.status AS statusCode",
            "isRequiredCode" => "document_type.isRequired AS isRequiredCode",
            "customerId" => "wg_customer_employee.customer_id",
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
            ) as documentUrl"),*/
            "year" => DB::raw("YEAR(`endDate`) AS year"),
            "month" => DB::raw("MONTH(`endDate`) AS month"),
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        /* Example relation*/
        $query->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_document.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join('wg_employee', function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
        })
            ->leftjoin(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
                $join->on('wg_customer_employee_document.requirement', '=', 'document_type.value');
                $join->on('wg_customer_employee_document.origin', '=', 'document_type.origin');
            })
            ->mergeBindings($qDocumentType)
            // ->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
            //     $join->on('wg_customer_employee_document.requirement', '=', 'document_type.value');
            //     $join->on('wg_customer_employee_document.origin', '=', 'document_type.origin');
            // })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_employee_document.status', '=', 'customer_document_status.value');
            })->leftjoin("users", function ($join) {
                $join->on('wg_customer_employee_document.createdBy', '=', 'users.id');
            });

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");
        //$query->whereRaw("system_files.attachment_type = 'Wgroup\\\\CustomerEmployeeDocument\\\\CustomerEmployeeDocument' AND system_files.field = 'document'");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == 'in') {
                        $query->whereIn(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getPreparedData($item), 'and');
                    } else {
                        $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, CustomerEmployeeDocumentModel::CLASS_NAME);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allRequired($criteria)
    {
        $this->setColumns([
            "id" => "document_type.value AS id",
            "documentType" => "document_type.item AS documentType",
            "customerId" => "document_type.customer_id",
            "isRequired" => "document_type.isRequired",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        $query = $this->query(DB::table(DB::raw("({$qDocumentType->toSql()}) as document_type")));
        $query->mergeBindings($qDocumentType);

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == "notIn") {
                        $query->whereNotIn('document_type.value', function ($query) use ($item) {
                            $query->select(DB::raw('requirement'))
                                ->from('wg_customer_employee_document')
                                ->where('customer_employee_id', '=', SqlHelper::getPreparedData($item));
                        });
                    } else if ($item->operator == "raw") {
                        $query->where(function ($query) use ($item) {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), '=', SqlHelper::getPreparedData($item))
                                ->orWhereNull(SqlHelper::getPreparedField($this->filterColumns[$item->field]));
                        });
                    } else {
                        $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModel($data);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allRequiredCritital($criteria)
    {
        $this->setColumns([
            "id" => "document_type.value AS id",
            "documentType" => "document_type.item AS documentType",
            "customerEmployeeId" => "wg_customer_employee.id AS employee_id",
            "criticalActivityCustomerEmployeeId" => "wg_customer_employee_critical_activity.customer_employee_id"
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query(DB::table('wg_customer_employee_critical_activity'));

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        /* Example relation*/
        $query->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_critical_activity.customer_employee_id', '=', 'wg_customer_employee.id');
            $join->on('wg_customer_employee_critical_activity.job_id', '=', 'wg_customer_employee.job');
        })->join('wg_customer_config_job_activity', function ($join) {
            $join->on('wg_customer_employee_critical_activity.job_activity_id', '=', 'wg_customer_config_job_activity.id');
        })->join('wg_customer_config_activity_process', function ($join) {
            $join->on('wg_customer_config_activity_process.id', '=', 'wg_customer_config_job_activity.activity_id');
        })->join('wg_customer_config_job_activity_document', function ($join) {
            $join->on('wg_customer_config_activity_process.activity_id', '=', 'wg_customer_config_job_activity_document.job_activity_id');
        })
            ->leftjoin(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
                $join->on('wg_customer_config_job_activity_document.type', '=', 'document_type.value');
            })
            ->mergeBindings($qDocumentType)
            // ->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
            //     $join->on('wg_customer_config_job_activity_document.type', '=', 'document_type.value');
            // })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_employee_critical_activity.createdBy', '=', 'users.id');
            });

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == "notIn") {
                        $query->whereNotIn('document_type.value', function ($query) use ($item) {
                            $query->select(DB::raw('requirement'))
                                ->from('wg_customer_employee_document')
                                ->where('customer_employee_id', '=', SqlHelper::getPreparedData($item));
                        });
                    } else {
                        $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModel($data);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allExport($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_employee_document.id",
            "documentNumber" => "wg_employee.documentNumber",
            "fullName" => "wg_employee.fullName",
            "requirement" => "document_type.item AS requirement",
            "description" => "wg_customer_employee_document.description",
            "startDate" => "wg_customer_employee_document.startDate",
            "endDate" => "wg_customer_employee_document.endDate",
            "status" => "customer_document_status.item AS status",
            "statusCode" => "wg_customer_employee_document.status AS statusCode",
            "requirementCode" => "wg_customer_employee_document.requirement AS requirementCode",
            "requirementOrigin" => "wg_customer_employee_document.origin",
            "customerId" => "wg_customer_employee.customer_id"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        $query->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_document.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })
            ->leftjoin(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
                $join->on('wg_customer_employee_document.requirement', '=', 'document_type.value');
                $join->on('wg_customer_employee_document.origin', '=', 'document_type.origin');
            })
            ->mergeBindings($qDocumentType)
            // ->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
            //     $join->on('wg_customer_employee_document.requirement', '=', 'document_type.value');
            //     $join->on('wg_customer_employee_document.origin', '=', 'document_type.origin');
            // })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_employee_document.status', '=', 'customer_document_status.value');
            })->leftjoin("users", function ($join) {
                $join->on('wg_customer_employee_document.createdBy', '=', 'users.id');
            });

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");

        $this->applyCriteria($query, $criteria);

        $data = $this->get($query, $criteria);

        $data['uids'] = $this->allUids($criteria);

        return $data;
    }

    public function allUids($criteria)
    {
        $this->clearColumns();
        $this->setColumns([
            "id" => "wg_customer_employee_document.id",
            "requirementCode" => "wg_customer_employee_document.requirement AS requirementCode",
            "requirementOrigin" => "wg_customer_employee_document.origin",
            "customerId" => "wg_customer_employee.customer_id",
            "customerEmployeeId" => "wg_customer_employee_document.customer_employee_id",
            "statusCode" => "wg_customer_employee_document.status AS statusCode",
        ]);

        $this->parseCriteria(null);

        $query = $this->query();

        $qDocumentType = CustomerModel::getEmployeeDocumentTypeRelationRaw($criteria);

        $query->join('wg_customer_employee', function ($join) {
            $join->on('wg_customer_employee_document.customer_employee_id', '=', 'wg_customer_employee.id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');
        })
            ->leftjoin(DB::raw("({$qDocumentType->toSql()}) as document_type"), function ($join) {
                $join->on('wg_customer_employee_document.requirement', '=', 'document_type.value');
                $join->on('wg_customer_employee_document.origin', '=', 'document_type.origin');
            })
            ->mergeBindings($qDocumentType)
            // ->leftjoin(DB::raw(CustomerModel::getEmployeeDocumentTypeRelation('document_type')), function ($join) {
            //     $join->on('wg_customer_employee_document.requirement', '=', 'document_type.value');
            //     $join->on('wg_customer_employee_document.origin', '=', 'document_type.origin');
            // })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_document_status')), function ($join) {
                $join->on('wg_customer_employee_document.status', '=', 'customer_document_status.value');
            })->leftjoin("users", function ($join) {
                $join->on('wg_customer_employee_document.createdBy', '=', 'users.id');
            });

        $query->whereRaw("(`wg_customer_employee`.`customer_id` = `document_type`.`customer_id` OR `document_type`.`customer_id` IS NULL)");

        $this->applyCriteria($query, $criteria);

        $data = $this->get($query, $criteria);

        $result = array_values(array_map(function ($row) {
            return $row['id'];
        }, $data['data']));

        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
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
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerEmployeeId = $model->customer_employee_id;
            $entity->requirement = $model->getRequirement();
            $entity->description = $model->description;
            $entity->version = $model->version;
            $entity->status = $model->getStatusType();
            $entity->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document);
            $entity->startDate =  $model->startDate ? Carbon::parse($model->startDate) : null;
            $entity->endDate =  $model->endDate ? Carbon::parse($model->endDate) : null;
            $entity->isRequired =  null;
            $entity->isVerified =  null;
            if ($model->isApprove == 1) {
                $entity->isVerified =  'Aprobado';
            } else if ($model->isDenied == 1) {
                $entity->isVerified =  'Denegado';
            }

            return $entity;
        } else {
            return null;
        }
    }

    public function parseModelWithDocumentRelations($criteria)
    {
        $documentModel = CustomerEmployeeDocument::find($criteria->id);

        $entityModel = $this->service->findEntity($documentModel, $criteria);

        $model = (object) $documentModel;
        //Mapping fields
        $entity = new \stdClass();

        $isRequired = $entityModel ? $entityModel->isRequired : false;

        $entity->id = $model->id;
        $entity->customerEmployeeId = $model->customer_employee_id;
        $entity->requirement = $entityModel ? [
            'id' => $entityModel->requirementId,
            'isRequired' => $entityModel->isRequiredCode,
            'item' => $entityModel->requirementItem,
            'origin' => $entityModel->requirementOrigin,
            'value' => $entityModel->requirementValue,
        ] : null;
        $entity->description = $model->description;
        $entity->version = $model->version;
        $entity->status = $model->getStatusType();
        $entity->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document);
        $entity->startDate =  $model->startDate ? Carbon::parse($model->startDate) : null;
        $entity->endDate =  $model->endDate ? Carbon::parse($model->endDate) : null;
        $entity->isRequired =  $isRequired ? 'Si' : 'No';
        $entity->isRequiredCode =  $entityModel ? $entityModel->isRequiredCode : null;
        $entity->observation =  $entityModel ? $entityModel->observation : null;
        $entity->isVerified =  $entityModel ? $entityModel->isVerified : null;

        return $entity;
    }

    public function exportExpirationExcel($criteria)
    {
        $data = $this->service->getExpirationExportData($criteria);
        $filename = 'Consulta_Vencimiento_Dctos_Soporte_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Vencimiento Docs', $data);
    }

    public function export($criteria, $zipFilename = null)
    {
        $start = Carbon::now();

        $authUser = $this->getAuthUser();
        $criteria->email = $authUser->email;
        $criteria->name = $authUser->name;
        $criteria->userId = $authUser->id;

        Queue::push(CustomerEmployeeDocumentJob::class, ['criteria' => $criteria], 'zip');

        /*        

        $data = $this->service->getExportData($criteria);

        $excelFilename = 'GUIA_DOCUMENTOS_SOPORTE_EMPLEADOS_' . Carbon::now()->timestamp;
        ExportHelper::excelStorage($excelFilename, 'GUIA', $data['excel']);


        $zipFilename = $zipFilename ? $zipFilename : 'DOCUMENTOS_SOPORTE_EMPLEADOS_' . Carbon::now()->timestamp . '.zip';
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

        return [
            'message' => 'ok',
            'elapseTime' => $end->diffInSeconds($start),
            'endTime' => $end->timestamp,
            'filename' => $zipFilename,
            'path' => CmsHelper::getPublicDirectory('zip/exports/'),
            //'uids' => $data['uids']
        ];
    }

    public function exportByType($criteria)
    {
        $zipFilename = 'DOCUMENTOS_SOPORTE_EMPLEADOS_' . Carbon::now()->timestamp . '.zip';
        $filename = CriteriaHelper::getMandatoryFilter($criteria, 'filename');
        if ($filename) {
            $zipFilename =  str_replace(' ', '_', StringHelper::removeAccents(mb_strtoupper($filename->value))) . '_' . Carbon::now()->timestamp . '.zip';
            unset($criteria->mandatoryFilters[0]);
        }
        return $this->export($criteria, $zipFilename);
    }

    public static function executeDenied()
    {
        DB::transaction(function () {
            $repository = new self;
            $repository->service->bulkInsertCustomerEmployeeDocumentTracking();
            $repository->service->bulkInsertCustomerEmployeeAudit();
            $repository->service->bulkIDeniedCustomerEmployeeDocument();
        });
    }

    public function getTemplateFile()
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/Plantilla_Creacion_De_Trabajadores.xlsx";
        Excel::load(CmsHelper::getAppPath($file))->download('xlsx');
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
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_employee_document.customer_employee_id');
            })
            ->select(DB::raw("YEAR(wg_customer_employee_document.created_at) as year"))
            ->where("wg_customer_employee.customer_id", $customerId)
            ->groupBy(DB::raw("YEAR(wg_customer_employee_document.created_at)"))
            ->orderBy(DB::raw("YEAR(wg_customer_employee_document.created_at)", "desc"));

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
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_employee_document.customer_employee_id');
            })
            ->select(DB::raw("MONTH(wg_customer_employee_document.created_at) as month"))
            ->where("wg_customer_employee.customer_id", $customerId)
            ->when($year != null, function($query) use($year) {
                $query->whereYear("wg_customer_employee_document.created_at", $year);
            })                
            ->groupBy(DB::raw("MONTH(wg_customer_employee_document.created_at)"));

        return $query->get()->map(function ($item) {
            return [
                "item" => $this->getMonthName($item->month),
                "value" => $item->month
            ];
        });
    }
}
