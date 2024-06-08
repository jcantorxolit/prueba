<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\EmailHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\CustomerConfigWorkplaceShiftScheduleRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\CustomerConfigWorkplaceShiftScheduleDetailEmployeeRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetailEmployee\CustomerConfigWorkplaceShiftScheduleDetailEmployeeService;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigWorkplaceShiftScheduleDetailRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigWorkplaceShiftScheduleDetailModel());

        $this->service = new CustomerConfigWorkplaceShiftScheduleDetailService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_workplace_shift_schedule_detail.id",
            "customerWorkplaceShiftScheduleId" => "wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id",
            "startTime" => "wg_customer_config_workplace_shift_schedule_detail.start_time",
            "endTime" => "wg_customer_config_workplace_shift_schedule_detail.end_time",
            "hours" => "wg_customer_config_workplace_shift_schedule_detail.hours",
            "qtyEmployee" => "wg_customer_config_workplace_shift_schedule_detail.qty_employee",
            "description" => "wg_customer_config_workplace_shift_schedule_detail.description",
            "createdBy" => "wg_customer_config_workplace_shift_schedule_detail.created_by",
            "createdAt" => "wg_customer_config_workplace_shift_schedule_detail.created_at",
            "updatedBy" => "wg_customer_config_workplace_shift_schedule_detail.updated_by",
            "updatedAt" => "wg_customer_config_workplace_shift_schedule_detail.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_workplace_shift_schedule_detail.parent_id', '=', 'tableParent.id');
		}
		*/


        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerWorkplaceShiftScheduleId = $entity->customerWorkplaceShiftScheduleId;
        $entityModel->startTime = $entity->startTime;
        $entityModel->endTime = $entity->endTime;
        $entityModel->hours = $entity->hours;
        $entityModel->qtyEmployee = $entity->qtyEmployee;
        $entityModel->description = $entity->description;
        $entityModel->isNightShift = $entity->isNightShift;


        if ($isNewRecord) {
            $entityModel->isDeleted = 0;
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

    public static function bulkInsertOrUpdate($data, $parentId)
    {
        $reposity = new self;

        foreach ($data as $item) {
            $entity = new \stdClass();
            $entity->id = $item->id;
            $entity->customerWorkplaceShiftScheduleId = $parentId;
            $entity->startTime = $item->startTime ? Carbon::parse($item->startTime)->timezone('America/Bogota') : null;
            $entity->endTime = $item->endTime ? Carbon::parse($item->endTime)->timezone('America/Bogota') : null;
            $entity->hours = $item->hours;
            $entity->qtyEmployee = $item->qtyEmployee;
            $entity->description = $item->description;
            $entity->isNightShift = $item->isNightShift;
            $reposity->insertOrUpdate($entity);
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->isDeleted = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        return $entityModel->save();
    }

    public function allocation($id, $customerId, $customerEmployeeId, $isSendEmail)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entity = new \stdClass();
        $entity->id = 0;
        $entity->customerWorkplaceShiftScheduleDetailId = $id;
        $entity->customerEmployeeId = $customerEmployeeId;
        $entity->customerId = $customerId;
        $entity->description = null;
        $entity->status = new \stdClass();
        $entity->status->value = 'S';
        $entity->statusChangedAt = null;
        $entity->customerWorkplaceShiftScheduleDetailIdPrevious = null;
        $newEntity = (new CustomerConfigWorkplaceShiftScheduleDetailEmployeeRepository)->insertOrUpdate($entity);

        $repositoryShiftSchedule = new CustomerConfigWorkplaceShiftScheduleRepository();

        $repositoryShiftSchedule->updateEmployeeQty($entityModel->customerWorkplaceShiftScheduleId);

        if (CmsHelper::boolVal($isSendEmail)) {
            $scriteria = new \stdClass();
            $scriteria->employeeId = [$customerEmployeeId];
            $scriteria->customerId = $customerId;
            $scriteria->id = $newEntity->id;
            $data = (new CustomerConfigWorkplaceShiftScheduleDetailEmployeeService)->getEmailData($scriteria);
            EmailHelper::notifyScheduledShift($data);
        }

        $result = [
            "shift" => $repositoryShiftSchedule->parseModelWithRelations($repositoryShiftSchedule->find($entityModel->customerWorkplaceShiftScheduleId)),
            "item" => $this->parseModelWithRelations($entityModel)
        ];

        return $result;
    }

    public function bulkAllocation($id, $customerId, $isSendEmail)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $data = $this->service->getAvailableEmployeeList($entityModel, $customerId);

        CustomerConfigWorkplaceShiftScheduleDetailEmployeeRepository::bulkInsertOrUpdate($data);

        $authUser = $this->getAuthUser();
        $entityModel->isAutomaticUsed = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        $repositoryShiftSchedule = new CustomerConfigWorkplaceShiftScheduleRepository();

        $repositoryShiftSchedule->updateEmployeeQty($entityModel->customerWorkplaceShiftScheduleId);

        if (CmsHelper::boolVal($isSendEmail)) {
            $uids = $data->map(function ($item) {
                return $item->customerEmployeeId;
            });

            $scriteria = new \stdClass();
            $scriteria->employeeId = [$uids];
            $scriteria->customerId = $customerId;
            $scriteria->customerWorkplaceShiftScheduleDetailId = $entityModel->id;
            $data = (new CustomerConfigWorkplaceShiftScheduleDetailEmployeeService)->getEmailData($scriteria);
            EmailHelper::notifyScheduledShift($data);
        }

        $result = [
            "shift" => $repositoryShiftSchedule->parseModelWithRelations($repositoryShiftSchedule->find($entityModel->customerWorkplaceShiftScheduleId)),
            "item" => $this->parseModelWithRelations($entityModel)
        ];

        return $result;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $startTime = $model->startTime;
            $endTime = $model->endTime;

            if (!$startTime instanceof Carbon) {
                $startTime = $model->startTime ? Carbon::createFromFormat('H:i:s', $model->startTime) : null;
            }

            if (!$endTime instanceof Carbon) {
                $endTime = $model->endTime ? Carbon::createFromFormat('H:i:s', $model->endTime) : null;
            }

            $qtySelectedEmployee = $this->service->getSelectedEmployeeCount($model->id);

            $entity->id = $model->id;
            $entity->customerWorkplaceShiftScheduleId = $model->customerWorkplaceShiftScheduleId;
            $entity->startTime = $startTime;
            $entity->endTime = $endTime;
            $entity->hours = $model->hours;
            $entity->qtyEmployee = $model->qtyEmployee;
            $entity->description = $model->description;
            $entity->isNightShift = $model->isNightShift == 1;
            $entity->isAutomaticUsed = $model->isAutomaticUsed == 1;
            $entity->qtySelectedEmployee = $qtySelectedEmployee;
            $entity->qtyAvailableEmployee = $model->qtyEmployee - $qtySelectedEmployee;

            return $entity;
        } else {
            return null;
        }
    }
}
