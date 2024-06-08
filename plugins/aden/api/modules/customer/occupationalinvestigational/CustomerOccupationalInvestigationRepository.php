<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\OccupationalInvestigationAl;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use DB;
use Exception;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Classes\SnappyPdfOptions;
use Carbon\Carbon;
use AdeN\Api\Helpers\ExportHelper;

class CustomerOccupationalInvestigationRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerOccupationalInvestigationModel());

        $this->service = new CustomerOccupationalInvestigationService();
    }

    public static function getCustomFilters()
    {
        return [];
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
            "unique" => "wg_customer_occupational_investigation_al.id as unique",
            "id" => "wg_customer_occupational_investigation_al.id",
            "accidentDate" => "wg_customer_occupational_investigation_al.accidentDate",
            "accidentType" => "wg_report_accident_type.item AS accidentType",
            "businessName" => "wg_customers.businessName",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber",
            "fullName" => "wg_employee.fullName",
            "job" => "wg_customer_config_job_data.name AS job",
            "status" => "occupational_investigation_status.item AS status",
            "statusCode" => "wg_customer_occupational_investigation_al.status AS statusCode",
            "customerId" => "wg_customer_occupational_investigation_al.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin("wg_customers", function ($join) {
            $join->on('wg_customers.id', '=', 'wg_customer_occupational_investigation_al.customer_id');

        })->leftjoin("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_occupational_investigation_al.customer_employee_id');

        })->leftjoin("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');

        })->leftjoin("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_config_job.id', '=', 'wg_customer_employee.job');

        })->leftjoin("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_employee.documentType', '=', 'employee_document_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_report_accident_type')), function ($join) {
            $join->on('wg_customer_occupational_investigation_al.accidentType', '=', 'wg_report_accident_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('occupational_investigation_status')), function ($join) {
            $join->on('wg_customer_occupational_investigation_al.status', '=', 'occupational_investigation_status.value');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_occupational_investigation_al.createdBy', '=', 'users.id');

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
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
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

    public function findOne($id)
    {
        $this->setColumns([
            "unique" => "wg_customer_occupational_investigation_al.id as unique",
            "id" => "wg_customer_occupational_investigation_al.id",
            "accidentDate" => "wg_customer_occupational_investigation_al.accidentDate",
            "accidentType" => "wg_report_accident_type.item AS accidentType",
            "businessName" => "wg_customers.businessName",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber",
            "fullName" => "wg_employee.fullName",
            "job" => "wg_customer_config_job_data.name AS job",
            "status" => "occupational_investigation_status.item AS status",
            "statusCode" => "wg_customer_occupational_investigation_al.status AS statusCode",
            "customerId" => "wg_customer_occupational_investigation_al.customer_id",
        ]);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin("wg_customers", function ($join) {
            $join->on('wg_customers.id', '=', 'wg_customer_occupational_investigation_al.customer_id');

        })->leftjoin("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_occupational_investigation_al.customer_employee_id');

        })->leftjoin("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');

        })->leftjoin("wg_customer_config_job", function ($join) {
            $join->on('wg_customer_config_job.id', '=', 'wg_customer_employee.job');

        })->leftjoin("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_employee.documentType', '=', 'employee_document_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_report_accident_type')), function ($join) {
            $join->on('wg_customer_occupational_investigation_al.accidentType', '=', 'wg_report_accident_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('occupational_investigation_status')), function ($join) {
            $join->on('wg_customer_occupational_investigation_al.status', '=', 'occupational_investigation_status.value');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_occupational_investigation_al.createdBy', '=', 'users.id');

        })->where('wg_customer_occupational_investigation_al.id', $id);

        return $query->select($this->columns)->first();
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

    public function updateStatus($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->status = 'open';
        $entityModel->save();
        
        $result["result"] = true;
    }


    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            $entity = new \stdClass();

            $entity->id = $model->id;

            return $entity;
        } else {
            $model->format = sprintf('Consecutivo: %s| Nro Identificación: %s| Nombre: %s| Tipo Accidente: %s| Fecha Accidente: %s| Estado: %s',
                $model->id, $model->documentNumber, $model->fullName, $model->accidentType, $model->accidentDate, $model->status);

            return $model;
        }
    }

    public function parseModelWithFormatRelations($model)
    {
        if ($model) {
            $model->format = sprintf('Consecutivo: %s| Nro Identificación: %s| Nombre: %s| Tipo Accidente: %s| Fecha Accidente: %s| Estado: %s',
                $model->id, $model->documentNumber, $model->fullName, $model->accidentType, $model->accidentDate, $model->status);
        }

        return $model;
    }

    public function getChartBar($criteria)
    {
        //return $this->service->getChartBar($criteria);
    }

    public function getChartAccidentType($criteria)
    {
        return $this->service->getChartAccidentType($criteria);
    }

    public function getChartDeathCause($criteria)
    {
        return $this->service->getChartDeathCause($criteria);
    }

    public function getChartLocation($criteria)
    {
        return $this->service->getChartLocation($criteria);
    }

    public function getChartLink($criteria)
    {
        return $this->service->getChartLink($criteria);
    }

    public function getChartWorkTime($criteria)
    {
        return $this->service->getChartWorkTime($criteria);
    }

    public function getChartWeekDay($criteria)
    {
        return $this->service->getChartWeekDay($criteria);
    }

    public function getChartPlace($criteria)
    {
        return $this->service->getChartPlace($criteria);
    }

    public function getChartInjury($criteria)
    {
        return $this->service->getChartInjury($criteria);
    }

    public function getChartBody($criteria)
    {
        return $this->service->getChartBody($criteria);
    }

    public function getChartFactor($criteria)
    {
        return $this->service->getChartFactor($criteria);
    }

    public function exportPdf($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'Investigación_AT' . Carbon::now()->timestamp . '.pdf';
        return ExportHelper::pdf("aden.pdf::html.occupational_investigational", $data, $filename, new SnappyPdfOptions('legal'));
    }

    public function streamPdf($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'Investigación_AT' . Carbon::now()->timestamp . '.pdf';
        return ExportHelper::stream("aden.pdf::html.occupational_investigational", $data, new SnappyPdfOptions('legal'));
    }

    public function getYears(int $customerId)
    {
        return $this->service->getYears($customerId);
    }

    public function getInfoToDashboard(int $customerId, int $period)
    {
        return $this->service->getInfoToDashboard($customerId, $period);
    }

    public function chartBarBody(int $customerId, int $period)
    {
        return $this->service->chartBarBody($customerId, $period);
    }

    public function chartBarFactor(int $customerId, int $period)
    {
        return $this->service->chartBarFactor($customerId, $period);
    }

    public function getChartStackedBarAusentismVsInvestigationAT(int $customerId, int $period, $workplaceId)
    {
        return $this->service->getChartStackedBarAusentismVsInvestigationAT($customerId, $period, $workplaceId);
    }

    public function getKpiOccupationalMedicineDashboard(int $customerId, $period, $workplaceId)
    {
        return $this->service->getKpiOccupationalMedicineDashboard($customerId, $period, $workplaceId);
    }

    public function getChartPieAbsenteeisByCause(int $customerId, $period, $workplaceId)
    {
        return $this->service->getChartPieAbsenteeisByCause($customerId, $period, $workplaceId);
    }

}
