<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\EmailHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigWorkplace\CustomerConfigWorkplaceRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\CustomerConfigWorkplaceShiftScheduleDetailRepository;
use DB;
use Mail;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class CustomerConfigWorkplaceShiftScheduleRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigWorkplaceShiftScheduleModel());

        $this->service = new CustomerConfigWorkplaceShiftScheduleService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_workplace_shift_schedule.id",
            "startDate" => "wg_customer_config_workplace_shift_schedule.start_date",
            "endDate" => "wg_customer_config_workplace_shift_schedule.end_date",
            "qtyEligibleEmployee" => "wg_customer_config_workplace.qty_eligible_employee",
            "qtySuggestedShift" => "wg_customer_config_workplace_shift_schedule.qty_suggested_shift",
            "qtySelectedEmployee" => "wg_customer_config_workplace_shift_schedule.qty_selected_employee",
            "qtyAvailableEmployee" => "wg_customer_config_workplace_shift_schedule.qty_available_employee",
            "status" => "customer_workplace_shift_status.item AS status",
            "statusCode" => "wg_customer_config_workplace_shift_schedule.status AS statusCode",
            "hasEmployee" => DB::raw("CASE WHEN totalEmployee IS NOT NULL AND totalEmployee > 0 THEN 'Si' ELSE 'No' END AS hasEmployee"),
            "customerWorkplaceId" => "wg_customer_config_workplace_shift_schedule.customer_workplace_id",
            "customerId" => "wg_customer_config_workplace.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $customerWorkplaceId = CriteriaHelper::getMandatoryFilter($criteria, 'customerWorkplaceId');

        $qEmployee = DB::table('wg_customer_config_workplace_shift_schedule_detail_employee')
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail.id', '=', 'wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_detail_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_schedule.customer_workplace_id');
            })
            ->select(
                'wg_customer_config_workplace_shift_schedule.id AS customer_workplace_shift_schedule_id',
                DB::raw("COUNT(*) AS totalEmployee")
            )
            ->where('wg_customer_config_workplace_shift_schedule_detail.is_deleted', 0)
            ->groupBy(
                'wg_customer_config_workplace_shift_schedule.id'
            );

        if ($customerWorkplaceId) {
            $qEmployee->where('wg_customer_config_workplace.id', $customerWorkplaceId->value);
        }

        $query = $this->query();

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_workplace_shift_status')), function ($join) {
            $join->on('customer_workplace_shift_status.value', '=', 'wg_customer_config_workplace_shift_schedule.status');
        })->leftjoin('wg_customer_config_workplace', function ($join) {
            $join->on('wg_customer_config_workplace_shift_schedule.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
        })->leftjoin(DB::raw("({$qEmployee->toSql()}) AS wg_customer_config_workplace_shift_schedule_detail_employee"), function ($join) {
            $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_id', '=', 'wg_customer_config_workplace_shift_schedule.id');
        })->mergeBindings($qEmployee)->where('wg_customer_config_workplace.is_shift_configured', 1);

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
        $startDate = $entity->startDate ? Carbon::parse($entity->startDate)->timezone('America/Bogota')->format('Y-m-d') : null;
        $endDate = $entity->endDate ? Carbon::parse($entity->endDate)->timezone('America/Bogota')->format('Y-m-d') : null;

        if ($entity->id == 0) {
            $entityToCompareStart = $this->model
                ->where(function ($query) use ($startDate) {
                    $query->whereRaw("'$startDate' BETWEEN start_date AND end_date");
                })
                ->where('status', '<>', 'inactive')
                ->where('customer_workplace_id', $entity->workplace->id)
                ->first();

            $entityToCompareEnd = $this->model
                ->where(function ($query) use ($endDate) {
                    $query->whereRaw("'$endDate' BETWEEN start_date AND end_date");
                })
                ->where('status', '<>', 'inactive')
                ->where('customer_workplace_id', $entity->workplace->id)
                ->first();

            if ($entityToCompareStart == null && $entityToCompareEnd == null) {
                return true;
            }

            if ($entityToCompareStart) {
                $firstShift = count($entity->items) > 0 ? $entity->items[0] : null;

                if ($firstShift == null) {
                    return false;
                }

                $items = $this->service->getItemList($entityToCompareStart->id);

                $lastShift = $items->count() > 0 ? $items->last() : null;

                if ($lastShift == null) {
                    return false;
                }

                $startDateTimeFmt = $startDate . ' ' . Carbon::parse($firstShift->startTime)->timezone('America/Bogota')->format('H:i:s');
                $endDateTimeFmt = Carbon::parse($entityToCompareStart->endDate)->format('Y-m-d') . ' ' . $lastShift['endTime']->format('H:i:s');

                Log::info("startDateTimeFmt:: $startDateTimeFmt");
                Log::info("endDateTimeFmt:: $endDateTimeFmt");

                $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $startDateTimeFmt);
                $endDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $endDateTimeFmt);

                return $startDateTime >= $endDateTime;
            }

            if ($entityToCompareEnd) {
                $lastShift = count($entity->items) > 0 ? $entity->items[count($entity->items) - 1] : null;

                if ($lastShift == null) {
                    return false;
                }

                $items = $this->service->getItemList($entityToCompareEnd->id);

                $firstShift = $items->count() > 0 ? $items->first() : null;

                if ($firstShift == null) {
                    return false;
                }

                $startDateTimeFmt = Carbon::parse($entityToCompareEnd->startDate)->format('Y-m-d') . ' ' . $firstShift['startTime']->format('H:i:s');
                $lastShiftStartTimeFmt = $endDate . ' ' . Carbon::parse($lastShift->startTime)->timezone('America/Bogota')->format('H:i:s');
                $lastShiftEndTimeFmt = $endDate . ' ' . Carbon::parse($lastShift->endTime)->timezone('America/Bogota')->format('H:i:s');

                Log::info("startDateTimeFmt:: $startDateTimeFmt");
                Log::info("lastShiftStartTimeFmt:: $lastShiftStartTimeFmt");
                Log::info("lastShiftEndTimeFmt:: $lastShiftEndTimeFmt");

                $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $startDateTimeFmt);
                $lastShiftEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $lastShiftEndTimeFmt);

                return $startDateTime >= $lastShiftEndTime;
            }
        } else {
            $entitiesToCompare = $this->model
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereRaw("'$startDate' BETWEEN start_date AND end_date");
                    $query->orWhereRaw("'$endDate' BETWEEN start_date AND end_date");
                })
                ->where('status', '<>', 'inactive')
                ->where('customer_workplace_id', $entity->workplace->id)
                ->get();

            if (count($entitiesToCompare) == 0) {
                return true;
            }

            if (count($entitiesToCompare) == 1) {
                $entityToCompare = $entitiesToCompare[0];
                return $entityToCompare ? $entityToCompare->id == $entity->id : true;
            }

            $entityToCompareStart = $this->model
                ->where(function ($query) use ($startDate) {
                    $query->whereRaw("'$startDate' BETWEEN start_date AND end_date");
                })
                ->where('status', '<>', 'inactive')
                ->where('id', '<>', $entity->id)
                ->where('customer_workplace_id', $entity->workplace->id)
                ->first();

            $entityToCompareEnd = $this->model
                ->where(function ($query) use ($endDate) {
                    $query->whereRaw("'$endDate' BETWEEN start_date AND end_date");
                })
                ->where('status', '<>', 'inactive')
                ->where('id', '<>', $entity->id)
                ->where('customer_workplace_id', $entity->workplace->id)
                ->first();


            if ($entityToCompareStart == null && $entityToCompareEnd == null) {
                return true;
            }

            if ($entityToCompareStart) {
                $firstShift = count($entity->items) > 0 ? $entity->items[0] : null;

                if ($firstShift == null) {
                    return false;
                }

                $items = $this->service->getItemList($entityToCompareStart->id);

                $lastShift = $items->count() > 0 ? $items->last() : null;

                if ($lastShift == null) {
                    return false;
                }

                $startDateTimeFmt = $startDate . ' ' . Carbon::parse($firstShift->startTime)->timezone('America/Bogota')->format('H:i:s');
                $endDateTimeFmt = Carbon::parse($entityToCompareStart->endDate)->format('Y-m-d') . ' ' . $lastShift['endTime']->format('H:i:s');

                Log::info("startDateTimeFmt:: $startDateTimeFmt");
                Log::info("endDateTimeFmt:: $endDateTimeFmt");

                $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $startDateTimeFmt);
                $endDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $endDateTimeFmt);

                return $startDateTime >= $endDateTime;
            }

            if ($entityToCompareEnd) {
                $lastShift = count($entity->items) > 0 ? $entity->items[count($entity->items) - 1] : null;

                if ($lastShift == null) {
                    return false;
                }

                $items = $this->service->getItemList($entityToCompareEnd->id);

                $firstShift = $items->count() > 0 ? $items->first() : null;

                if ($firstShift == null) {
                    return false;
                }

                $startDateTimeFmt = Carbon::parse($entityToCompareEnd->startDate)->format('Y-m-d') . ' ' . $firstShift['startTime']->format('H:i:s');
                $lastShiftStartTimeFmt = $endDate . ' ' . Carbon::parse($lastShift->startTime)->timezone('America/Bogota')->format('H:i:s');
                $lastShiftEndTimeFmt = $endDate . ' ' . Carbon::parse($lastShift->endTime)->timezone('America/Bogota')->format('H:i:s');

                Log::info("startDateTimeFmt:: $startDateTimeFmt");
                Log::info("lastShiftStartTimeFmt:: $lastShiftStartTimeFmt");
                Log::info("lastShiftEndTimeFmt:: $lastShiftEndTimeFmt");

                $startDateTime = Carbon::createFromFormat('Y-m-d H:i:s', $startDateTimeFmt);
                $lastShiftEndTime = Carbon::createFromFormat('Y-m-d H:i:s', $lastShiftEndTimeFmt);

                return $startDateTime >= $lastShiftEndTime;
            }
        }
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerWorkplaceId = $entity->workplace ? $entity->workplace->id : null;
        $entityModel->startDate = $entity->startDate ? Carbon::parse($entity->startDate)->timezone('America/Bogota') : null;
        $entityModel->endDate = $entity->endDate ? Carbon::parse($entity->endDate)->timezone('America/Bogota') : null;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->qtySuggestedShift = $entity->qtySuggestedShift;
        $entityModel->qtySuggestedShiftEmployee = $entity->qtySuggestedShiftEmployee;
        $entityModel->qtySelectedEmployee = isset($entity->qtySelectedEmployee) ? $entity->qtySelectedEmployee : 0;
        $entityModel->qtyAvailableEmployee = isset($entity->qtyAvailableEmployee) ? $entity->qtyAvailableEmployee : 0;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        if (isset($entity->items)) {
            CustomerConfigWorkplaceShiftScheduleDetailRepository::bulkInsertOrUpdate($entity->items, $entityModel->id);
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->status = 'inactive';
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        return $entityModel->save();
    }

    public function updateScheduledStatus($id, $isSendEmail)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to update status.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->status = 'scheduled';
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        if (boolval($isSendEmail)) {
            $workplace = (new CustomerConfigWorkplaceRepository)->find($entityModel->customerWorkplaceId);
            $scriteria = new \stdClass();
            $scriteria->id = $entityModel->id;
            $scriteria->customerId = $workplace ? $workplace->customerId : null;
            $data = $this->service->getEmailData($scriteria);
            EmailHelper::notifyScheduledShift($data);
        }
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerWorkplaceId = $model->customerWorkplaceId;
            $entity->workplace = (new CustomerConfigWorkplaceRepository)->findWorkplaceWithShift($model->customerWorkplaceId);
            $entity->startDate = $model->startDate ? Carbon::parse($model->startDate) : null;
            $entity->endDate = $model->endDate ? Carbon::parse($model->endDate) : null;
            $entity->status = $model->getStatus();
            $entity->qtyEligibleEmployee = $model->qtyEligibleEmployee;
            $entity->qtySuggestedShift = $model->qtySuggestedShift;
            $entity->qtySuggestedShiftEmployee = $model->qtySuggestedShiftEmployee;
            $entity->qtySelectedEmployee = $model->qtySelectedEmployee;
            $entity->qtyAvailableEmployee = $model->qtyAvailableEmployee;
            $entity->items = $this->service->getItemList($model->id);

            return $entity;
        } else {
            return null;
        }
    }

    public function updateEmployeeQty($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $workplace = (new CustomerConfigWorkplaceRepository)->findWorkplaceWithShift($entityModel->customerWorkplaceId);

        $qtyAvailableEmployee = $workplace ? $workplace->qtyEligibleEmployee : 0;

        $qtySelectedEmployee = $this->service->getSelectedEmployeeCount($entityModel->id);

        $authUser = $this->getAuthUser();
        $entityModel->qtySelectedEmployee = $qtySelectedEmployee;
        $entityModel->qtyAvailableEmployee = $qtyAvailableEmployee - $qtySelectedEmployee;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        return $entityModel->save();
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'HISTORICO_TURNOS_' . Carbon::now()->timestamp;
        ExportHelper::excelStorage($filename, 'TURNOS', $data);

        return [
            'fullUrl' => CmsHelper::getPublicDirectory('excel/exports') . '/' . $filename . ".xlsx",
            'filename' => $filename,
        ];
    }
}
