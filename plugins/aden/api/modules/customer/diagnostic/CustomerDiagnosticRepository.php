<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Diagnostic;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;

class CustomerDiagnosticRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerDiagnosticModel());

        $this->service = new CustomerDiagnosticService();
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
        $this->setColumns([
            "id" => "wg_customer_diagnostic.id",
            "status" => "diagnostic_status.item AS status",
            "createdBy" => "users.name AS createdBy",
            "createdAt" => "wg_customer_diagnostic.created_at",
            "endDate" => "wg_customer_diagnostic.endDate",
            "customerId" => "wg_customer_diagnostic.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->leftjoin("users", function ($join) {
            $join->on('wg_customer_diagnostic.createdBy', '=', 'users.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('diagnostic_status')), function ($join) {
            $join->on('wg_customer_diagnostic.status', '=', 'diagnostic_status.value');

        });

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

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["recordsTotal"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["recordsFiltered"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allComment($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_diagnostic_comment.id",
            "comment" => "wg_customer_diagnostic_comment.comment",
            "createdBy" => "users.name AS createdBy",
            "createdAt" => "wg_customer_diagnostic_comment.created_at",
            "customerDiagnosticId" => "wg_customer_diagnostic_comment.customer_tracking_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->join('wg_customer_diagnostic_comment', function ($join) {
            $join->on('wg_customer_diagnostic.id', '=', 'wg_customer_diagnostic_comment.customer_tracking_id');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_diagnostic_comment.createdBy', '=', 'users.id');

        });

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
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["recordsTotal"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["recordsFiltered"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
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

        $entityModel->delete();

        $result["result"] = true;
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

    public function getChartBar($criteria)
    {
        return $this->service->getChartBar($criteria);
    }

    public function getChartPie($criteria)
    {
        return $this->service->getChartPie($criteria);
    }

    public function getStats($criteria)
    {
        return $this->service->getStats($criteria);
    }

    public function getPrograms($diagnosticId)
    {
        return $this->service->getPrograms($diagnosticId);
    }

    public function getPeriodsByCustomer($customerId)
    {
        return $this->service->getPeriodsByCustomer($customerId);
    }

    public function getPeriodsByCustomerCompare($customerId)
    {
        return $this->service->getPeriodsByCustomerCompare($customerId);
    }

    public function getDiagnosticProgress($customerId)
    {
        return $this->service->getDiagnosticProgress($customerId);
    }

    public function getTotalByCustomerAndYearChartLine($customerId)
    {
        return $this->service->getTotalByCustomerAndYearChartLine($customerId);
    }
}
