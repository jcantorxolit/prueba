<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\OccupationalReportAl;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;

class CustomerOccupationalReportRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerOccupationalReportModel());

        $this->service = new CustomerOccupationalReportService();
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
            "id" => "wg_customer_occupational_report_al.id",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_customer_occupational_report_al.document_number AS documentNumber",
            "firstName" => DB::raw("CONCAT_WS(' ', IFNULL(wg_customer_occupational_report_al.first_name, ''), IFNULL(wg_customer_occupational_report_al.second_name, '')) AS firstName"),
            "lastName" => DB::raw("CONCAT_WS(' ', IFNULL(wg_customer_occupational_report_al.first_lastname, ''), IFNULL(wg_customer_occupational_report_al.second_lastname, '')) AS lastName"),
            "job" => "wg_customer_config_job_data.name AS job",
            "eps" => "eps.item AS eps",
            "arl" => "arl.item AS arl",
            "afp" => "afp.item AS afp",
            "accidentDate" => "wg_customer_occupational_report_al.accident_date AS accidentDate",
            "status" => "wg_customer_occupational_report_al.status",
            "customerId" => "wg_customer_occupational_report_al.customer_id"
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_customer_occupational_report_al.document_type', '=', 'employee_document_type.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('eps')), function ($join) {
            $join->on('wg_customer_occupational_report_al.eps', '=', 'eps.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('arl')), function ($join) {
            $join->on('wg_customer_occupational_report_al.arl', '=', 'arl.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('afp')), function ($join) {
            $join->on('wg_customer_occupational_report_al.afp', '=', 'afp.value');

        })->leftjoin("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_occupational_report_al.job', '=', 'wg_customer_config_job_data.id');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_occupational_report_al.createdBy', '=', 'users.id');

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
}
