<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Employee\CriticalActivity;

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
use Wgroup\CustomerEmployeeCriticalActivity\CustomerEmployeeCriticalActivity;

class CustomerEmployeeCriticalActivityRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerEmployeeCriticalActivityModel());

        $this->service = new CustomerEmployeeCriticalActivityService();
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
        $this->setColumns([
            "id" => "wg_customer_config_job_activity.id",
            "job" => "wg_customer_config_job_data.name AS job",
            "activity" => "wg_customer_config_activity.name AS activity",
            "jobId" => "wg_customer_employee_critical_activity.job_id",
            "customerId" => "wg_customer_config_job_data.customer_id AS customerId",
            "customerEmployeeId" => "wg_customer_employee_critical_activity.customer_employee_id",
            "criticalActivityId" => "wg_customer_employee_critical_activity.id AS criticalActivityId"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query()
            ->join('wg_customer_config_job_activity', function ($join) {
                $join->on('wg_customer_config_job_activity.id', '=', 'wg_customer_employee_critical_activity.job_activity_id');
            })
            ->join('wg_customer_config_activity_process', function ($join) {
                $join->on('wg_customer_config_activity_process.id', '=', 'wg_customer_config_job_activity.activity_id');
            })
            ->join('wg_customer_config_activity', function ($join) {
                $join->on('wg_customer_config_activity.id', '=', 'wg_customer_config_activity_process.activity_id');
            })
            ->join('wg_customer_config_job', function ($join) {
                $join->on('wg_customer_config_job.id', '=', 'wg_customer_config_job_activity.job_id');
            })
            ->join('wg_customer_config_job_data', function ($join) {
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
            });

        $this->applyCriteria($query, $criteria);

        $result = $this->get($query, $criteria);

        return $result;
    }


    public function allAvailable($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity.id",
            "job" => "wg_customer_config_job_data.name AS job",
            "activity" => "wg_customer_config_activity.name AS activity",
            "jobId" => "wg_customer_config_job_activity.job_id",
            "customerId" => "wg_customer_config_job_data.customer_id AS customerId"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->parseBaseQuery($criteria);

        $this->applyCriteria($query, $criteria, ['customerEmployeeId', 'jobId']);

        $result = $this->get($query, $criteria);

        $result['uids'] = $this->allUids($criteria);

        return $result;
    }

    public function allUids($criteria)
    {
        $this->clearColumns();
        $this->setColumns([
            "id" => "wg_customer_config_job_activity.id",
            "job" => "wg_customer_config_job_data.name AS job",
            "activity" => "wg_customer_config_activity.name AS activity",
            "customerId" => "wg_customer_config_job_data.customer_id"
        ]);

        $this->parseCriteria(null);

        $query = $this->parseBaseQuery($criteria);

        $this->applyCriteria($query, $criteria, ['customerEmployeeId', 'jobId']);

        $data = $this->get($query, $criteria);

        $result = array_values(array_map(function ($row) {
            return $row->id;
        }, $data['data']));

        return $result;
    }

    private function parseBaseQuery($criteria)
    {
        $customerEmployeeId = CriteriaHelper::getMandatoryFilter($criteria, 'customerEmployeeId');
        $jobId = CriteriaHelper::getMandatoryFilter($criteria, 'jobId');

        $q1 = DB::table("wg_customer_employee_critical_activity")
            ->select(
                'wg_customer_employee_critical_activity.job_id',
                'wg_customer_employee_critical_activity.job_activity_id'
            )
            ->whereRaw("wg_customer_employee_critical_activity.customer_employee_id = {$customerEmployeeId->value}");

        $query = $this->query(DB::table("wg_customer_config_job_activity"));

        $query
            ->join('wg_customer_config_activity_process', function ($join) {
                $join->on('wg_customer_config_activity_process.id', '=', 'wg_customer_config_job_activity.activity_id');
            })
            ->join('wg_customer_config_activity', function ($join) {
                $join->on('wg_customer_config_activity.id', '=', 'wg_customer_config_activity_process.activity_id');
            })
            ->join('wg_customer_config_job', function ($join) {
                $join->on('wg_customer_config_job.id', '=', 'wg_customer_config_job_activity.job_id');
            })
            ->join('wg_customer_config_job_data', function ($join) {
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
            })
            ->leftjoin(DB::raw("({$q1->toSql()}) as customer_employee_critical_activity"), function ($join) {
                $join->on('customer_employee_critical_activity.job_id', '=', 'wg_customer_config_job.id');
                $join->on('customer_employee_critical_activity.job_activity_id', '=', 'wg_customer_config_job_activity.id');
            })
            ->mergeBindings($q1);

        $query->whereNotIn('wg_customer_config_job_activity.id', function ($query) use ($customerEmployeeId, $jobId) {
            $query->select('job_activity_id')
                ->from('wg_customer_employee_critical_activity')
                ->where('customer_employee_id', '=', SqlHelper::getPreparedData($customerEmployeeId))
                ->where('job_id', '=', SqlHelper::getPreparedData($jobId));
        })
            ->where('wg_customer_config_activity.isCritical', 1);

        return $query;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }
        $entityModel->customer_employee_id = $entity->customerEmployeeId;
        $entityModel->job_activity_id = $entity->jobActivityId;
        $entityModel->job_id = $entity->jobId;

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

    public function bulkInsert($entity)
    {
        foreach ($entity->activities as $activity) {
            $entityData = new \stdClass;
            $entityData->id = 0;
            $entityData->customerEmployeeId = $entity->customerEmployeeId;
            $entityData->jobActivityId = $activity;
            $entityData->jobId = $entity->jobId;
            $this->insertOrUpdate($entityData);
        }

        return [
            "isSucces" => true
        ];
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
}
