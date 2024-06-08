<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\EmailHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigWorkplace\CustomerConfigWorkplaceRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\CustomerConfigWorkplaceShiftScheduleRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\CustomerConfigWorkplaceShiftScheduleDetailRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use stdClass;
use Wgroup\SystemParameter\SystemParameter;

class CustomerConfigWorkplaceShiftScheduleDetailEmployeeRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigWorkplaceShiftScheduleDetailEmployeeModel());

        $this->service = new CustomerConfigWorkplaceShiftScheduleDetailEmployeeService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_workplace_shift_schedule_detail_employee.id",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber",
            "fullName" => "wg_employee.fullName",
            "registerDate" => "wg_customer_config_workplace_shift_schedule_detail_employee.created_at AS register_date",
            "statusChangedAt" => "wg_customer_config_workplace_shift_schedule_detail_employee.status_changed_at",
            "status" => "customer_workplace_shift_employee_status.item AS status",
            "statusCode" => "wg_customer_config_workplace_shift_schedule_detail_employee.status AS statusCode",
            "customerWorkplaceShiftScheduleDetailId" => "wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.id', '=', 'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_schedule.customer_workplace_id');
            })
            ->join('wg_customer_employee', function ($join) {
                //$join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_workplace_shift_employee_status')), function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.status', '=', 'customer_workplace_shift_employee_status.value');
            });

        $this->applyCriteria($query, $criteria);

        $result = $this->get($query, $criteria);

        foreach ($result['data'] as $item) {
            $item->registerDate = $item->register_date ? Carbon::parse($item->register_date)->timezone('America/Bogota')->format('Y-m-d H:i') : null;
            $item->statusChangedAt = $item->statusChangedAt ? Carbon::parse($item->statusChangedAt)->format('Y-m-d H:i') : null;
        }

        return $result;
    }

    public function allBasic($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_workplace_shift_schedule_detail_employee.id",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber",
            "fullName" => "wg_employee.fullName",
            "status" => "customer_workplace_shift_employee_status.item AS status",
            "statusCode" => "wg_customer_config_workplace_shift_schedule_detail_employee.status AS statusCode",
            "customerWorkplaceShiftScheduleDetailId" => "wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.id', '=', 'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_schedule.customer_workplace_id');
            })
            ->join('wg_customer_employee', function ($join) {
                //$join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_workplace_shift_employee_status')), function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.status', '=', 'customer_workplace_shift_employee_status.value');
            })
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.status', ['S', 'C']);

        $this->applyCriteria($query, $criteria);

        $result = $this->get($query, $criteria);

        return $result;
    }

    public function allComplete($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_workplace_shift_schedule_detail_employee.id",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber",
            "fullName" => "wg_employee.fullName",
            "registerDate" => "wg_customer_config_workplace_shift_schedule_detail_employee.created_at AS register_date",
            "description" => "wg_customer_config_workplace_shift_schedule_detail.description",
            "startDate" => "wg_customer_config_workplace_shift_schedule.start_date",
            "endDate" => "wg_customer_config_workplace_shift_schedule.end_date",
            "shiftTime" => DB::raw("CONCAT_WS(' - ', wg_customer_config_workplace_shift_schedule_detail.start_time, wg_customer_config_workplace_shift_schedule_detail.end_time) AS shiftTime"),
            "status" => "customer_workplace_shift_employee_status.item AS status",
            "statusCode" => "wg_customer_config_workplace_shift_schedule_detail_employee.status AS statusCode",
            "customerId" => "wg_customer_config_workplace.customer_id",
            "customerWorkplaceId" => "wg_customer_config_workplace_shift_schedule.customer_workplace_id",
            "customerWorkplaceShiftScheduleId" => "wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id",
            "customerWorkplaceShiftScheduleDetailId" => "wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.id', '=', 'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_schedule.customer_workplace_id');
            })
            ->join('wg_customer_employee', function ($join) {
                //$join->on('wg_customer_employee.workPlace', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_id', '=', 'wg_customer_config_workplace.customer_id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_workplace_shift_employee_status')), function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.status', '=', 'customer_workplace_shift_employee_status.value');
            })
            ->whereIn('wg_customer_config_workplace_shift_schedule_detail_employee.status', ['S', 'C']);

        $this->applyCriteria($query, $criteria);

        $result = $this->get($query, $criteria);

        foreach ($result['data'] as $item) {
            $item->registerDate = $item->register_date ? Carbon::parse($item->register_date)->timezone('America/Bogota')->format('Y-m-d H:i') : null;
            $item->statusChangedAt = $item->statusChangedAt ? Carbon::parse($item->statusChangedAt)->format('Y-m-d H:i') : null;
        }

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

        $entityModel->customerWorkplaceShiftScheduleDetailId = $entity->customerWorkplaceShiftScheduleDetailId;
        $entityModel->customerEmployeeId = $entity->customerEmployeeId;
        $entityModel->customerId = $entity->customerId;
        $entityModel->description = $entity->description;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->statusChangedAt = $entity->statusChangedAt ? Carbon::parse($entity->statusChangedAt)->timezone('America/Bogota') : null;
        $entityModel->customerWorkplaceShiftScheduleDetailIdPrevious = $entity->customerWorkplaceShiftScheduleDetailIdPrevious;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public static function bulkInsertOrUpdate($data)
    {
        $reposity = new self;

        foreach ($data as $entity) {
            $entity->description = null;
            $entity->status = new stdClass();
            $entity->status->value = 'S';
            $entity->statusChangedAt = null;
            $entity->customerWorkplaceShiftScheduleDetailIdPrevious = null;
            $reposity->insertOrUpdate($entity);
        }
    }

    public function delete($id, $isSendEmail)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $scriteria = new \stdClass();
        $scriteria->employeeId = [$entityModel->customerEmployeeId];
        $scriteria->customerId = $entityModel->customerId;
        $scriteria->id = $entityModel->id;
        $data = $this->service->getEmailData($scriteria);

        $authUser = $this->getAuthUser();
        $entityModel->status = 'D';
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->statusChangedAt = Carbon::now('America/Bogota');
        $entityModel->save();

        $scheduleDetail = (new CustomerConfigWorkplaceShiftScheduleDetailRepository)->find($entityModel->customerWorkplaceShiftScheduleDetailId);

        if ($scheduleDetail) {
            (new CustomerConfigWorkplaceShiftScheduleRepository)->updateEmployeeQty($scheduleDetail->customerWorkplaceShiftScheduleId);
        }

        if (CmsHelper::boolVal($isSendEmail)) {
            EmailHelper::notifyScheduledShift($data, 'rainlab.user::mail.notificacion_covid_retiro_turno');
        }

        return true;
    }

    public function changeShiift($entity)
    {
        $currentEntity = $this->updateStatusRetired($entity->id);
        $changeWithEntity = $this->updateStatusRetired($entity->changeWith->id);

        $entityChangeOne = $this->createWithChangeShiftStatus(
            $changeWithEntity->customerWorkplaceShiftScheduleDetailId,
            $currentEntity
        );

        $entityChangeTwo = $this->createWithChangeShiftStatus(
            $currentEntity->customerWorkplaceShiftScheduleDetailId,
            $changeWithEntity
        );

        if ($entity->isSendEmail) {
            $this->sendMail($entityChangeOne, $entityChangeOne->customerEmployeeId);
            $this->sendMail($entityChangeTwo, $entityChangeTwo->customerEmployeeId);
        }
    }

    private function sendMail($entity, $customerEmployeeId)
    {
        $scriteria = new \stdClass();
        $scriteria->employeeId = [$customerEmployeeId];
        $scriteria->customerId = $entity ? $entity->customerId : null;
        $scriteria->id = $entity->id;
        $data = $this->service->getEmailData($scriteria);
        EmailHelper::notifyScheduledShift($data);
    }

    private function createWithChangeShiftStatus($newShiftDetailId, $entity)
    {
        $entityModel = new stdClass();
        $entityModel->id = 0;
        $entityModel->customerWorkplaceShiftScheduleDetailId = $newShiftDetailId;
        $entityModel->customerEmployeeId = $entity->customerEmployeeId;
        $entityModel->customerId = $entity->customerId;
        $entityModel->description = 'Cambio de turno';
        $entityModel->status = new stdClass();
        $entityModel->status->value = 'C';
        $entityModel->statusChangedAt = Carbon::now();
        $entityModel->customerWorkplaceShiftScheduleDetailIdPrevious = $entity->customerWorkplaceShiftScheduleDetailId;
        $newEntity = $this->insertOrUpdate($entityModel);
        $query = $this->query();
        $query->where('id', $newEntity->id)
            ->update([
                'created_at' => $entity->createdAt
            ]);
        return $newEntity;
    }

    private function updateStatusRetired($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->status = 'R';
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->statusChangedAt = Carbon::now('America/Bogota');
        $entityModel->save();
        return $entityModel;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerWorkplaceShiftScheduleDetailId = $model->customerWorkplaceShiftScheduleDetailId;
            $entity->customerEmployeeId = $model->customerEmployeeId;
            $entity->customerId = $model->customerId;
            $entity->description = $model->description;
            $entity->status = $model->getStatus();
            $entity->statusChangedAt = $model->statusChangedAt;
            $entity->customerWorkplaceShiftScheduleDetailIdPrevious = $model->customerWorkplaceShiftScheduleDetailIdPrevious;
            $entity->createdAt = $model->createdAt;

            return $entity;
        } else {
            return null;
        }
    }
}
