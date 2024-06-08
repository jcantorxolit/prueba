<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigWorkplace;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigJobExpressRelation\CustomerConfigJobExpressRelationRepository;
use AdeN\Api\Modules\Customer\ConfigMacroProcess\CustomerConfigMacroProcessRepository;
use AdeN\Api\Modules\Customer\ConfigProcessExpressRelation\CustomerConfigProcessExpressRelationRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftCondition\CustomerConfigWorkplaceShiftConditionRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftScheduleDetail\CustomerConfigWorkplaceShiftScheduleDetailService;
use AdeN\Api\Modules\Customer\CustomerRepository;
use DB;
use Exception;
use Log;
use Event;
use Carbon\Carbon;
use stdClass;
use Wgroup\SystemParameter\SystemParameter;

class CustomerConfigWorkplaceRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigWorkplaceModel());

        $this->service = new CustomerConfigWorkplaceService();

        CustomerConfigWorkplaceModel::created(function ($model) {
            if ($model->type == 'PCS') {
                CustomerConfigMacroProcessRepository::createGeneral($model);
            }
        });
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_workplace.id",
            "name" => "wg_customer_config_workplace.name",
            "country" => "rainlab_user_countries.name AS country",
            "state" => "rainlab_user_states.name AS state",
            "city" => "wg_towns.name AS city",
            "status" => "config_workplace_status.item AS status",
            "customerId" => "wg_customer_config_workplace.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->leftjoin("rainlab_user_countries", function ($join) {
            $join->on('rainlab_user_countries.id', '=', 'wg_customer_config_workplace.country_id');
        })->leftjoin("rainlab_user_states", function ($join) {
            $join->on('rainlab_user_states.id', '=', 'wg_customer_config_workplace.state_id');
        })->leftjoin("wg_towns", function ($join) {
            $join->on('wg_towns.id', '=', 'wg_customer_config_workplace.city_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('config_workplace_status')), function ($join) {
            $join->on('config_workplace_status.value', '=', 'wg_customer_config_workplace.status');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allExpress($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_workplace.id",
            "name" => "wg_customer_config_workplace.name",
            "country" => "rainlab_user_countries.name AS country",
            "state" => "rainlab_user_states.name AS state",
            "city" => "wg_towns.name AS city",
            "status" => DB::raw("CASE WHEN wg_customer_config_workplace.is_fully_configured = 1 THEN matrix_express_workplace_status.item ELSE 'En Proceso' END AS status"),
            "customerId" => "wg_customer_config_workplace.customer_id",
            "hasProcess" => DB::raw("CASE WHEN wg_customer_config_process_express_relation.id IS NOT NULL THEN 'Si' ELSE 'No' END AS hasProcess"),
            "address" => "wg_customer_config_workplace.address"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->leftjoin("rainlab_user_countries", function ($join) {
            $join->on('rainlab_user_countries.id', '=', 'wg_customer_config_workplace.country_id');
        })->leftjoin("rainlab_user_states", function ($join) {
            $join->on('rainlab_user_states.id', '=', 'wg_customer_config_workplace.state_id');
        })->leftjoin("wg_towns", function ($join) {
            $join->on('wg_towns.id', '=', 'wg_customer_config_workplace.city_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_express_workplace_status')), function ($join) {
            $join->on('matrix_express_workplace_status.value', '=', 'wg_customer_config_workplace.status');
        })->leftjoin('wg_customer_config_process_express_relation', function ($join) {
            $join->on('wg_customer_config_process_express_relation.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            $join->on('wg_customer_config_process_express_relation.customer_id', '=', 'wg_customer_config_workplace.customer_id');
        })->groupBy(
            'wg_customer_config_workplace.id',
            'wg_customer_config_workplace.customer_id'
        );

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allShift($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_workplace.id",
            "name" => "wg_customer_config_workplace.name",
            "totalEmployee" => DB::raw("wg_customer_config_workplace_employee.totalEmployee"),
            "maxAge" => "wg_customer_config_workplace.max_age",
            "maxShiftEmployee" => "wg_customer_config_workplace.max_shift_employee",
            "qtyEligibleEmployee" => "wg_customer_config_workplace.qty_eligible_employee",
            //"status" => DB::raw("CASE WHEN wg_customer_config_workplace.is_shift_fully_configured = 1 THEN matrix_express_workplace_status.item ELSE 'En Proceso' END AS status"),
            "status" => DB::raw("matrix_express_workplace_status.item AS status"),
            "customerId" => "wg_customer_config_workplace.customer_id",
            "hasShift" => DB::raw("CASE WHEN wg_customer_config_workplace_shift_schedule.id IS NOT NULL THEN 'Si' ELSE 'No' END AS hasShift")
        ]);

        $this->parseCriteria($criteria);

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');

        $qEmployee =  DB::table('wg_customer_covid_bolivar')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_covid_bolivar.customer_workplace_id');
                $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
            })
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_bolivar.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid_bolivar.customer_id');
            })
            ->select(
                'wg_customer_config_workplace.id',
                DB::raw("COUNT(*) AS totalEmployee")
            )
            ->groupBy(
                'wg_customer_config_workplace.customer_id',
                'wg_customer_config_workplace.id'
            );

        if ($customerId) {
            $qEmployee->where('wg_customer_covid_bolivar.customer_id', $customerId->value);
            $qEmployee->where('wg_customer_config_workplace.customer_id', $customerId->value);
        }

        $query = $this->query();

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_express_workplace_status')), function ($join) {
            $join->on('matrix_express_workplace_status.value', '=', 'wg_customer_config_workplace.status');
        })->leftjoin(DB::raw("({$qEmployee->toSql()}) AS wg_customer_config_workplace_employee"), function ($join) {
            $join->on('wg_customer_config_workplace_employee.id', '=', 'wg_customer_config_workplace.id');
        })->leftjoin('wg_customer_config_workplace_shift_schedule', function ($join) {
            $join->on('wg_customer_config_workplace_shift_schedule.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
        })->groupBy(
            'wg_customer_config_workplace.id',
            'wg_customer_config_workplace.customer_id'
        )->mergeBindings($qEmployee)->where('is_shift_configured', 1);

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allShiftAvailable($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_employee.id",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber",
            "fullName" => "wg_employee.fullName",
            "customerWorkplaceShiftScheduleId" => "wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id",
            "customerWorkplaceId" => "wg_customer_config_workplace_shift_schedule.customer_workplace_id",
            "customerId" => "wg_customer_config_workplace.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');

        $qNonAvailable = (new CustomerConfigWorkplaceShiftScheduleDetailService)->prepareNonAvailableEmployeeQuery($customerId->value);

        $qElegibleEmployee = (new CustomerConfigWorkplaceShiftScheduleDetailService)->prepareElegibleEmployeeQuery($customerId->value);

        $query = $this->query();

        $query
            ->join('wg_customer_config_workplace_shift_schedule', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_workplace_shift_schedule.customer_workplace_id');
            })
            ->join('wg_customer_config_workplace_shift_schedule_detail', function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule.id', '=', 'wg_customer_config_workplace_shift_schedule_detail.customer_workplace_shift_schedule_id');
            })
            ->join('wg_customer_covid_bolivar', function ($join) {
                $join->on('wg_customer_covid_bolivar.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })            
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_bolivar.customer_employee_id');
            })
            ->join('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('wg_employee.documentType', '=', 'employee_document_type.value');
            })
            ->join(DB::raw("({$qElegibleEmployee->toSql()}) AS workplace_shift_elegible_employee"), function ($join) {
                $join->on('workplace_shift_elegible_employee.customer_employee_id', '=', 'wg_customer_employee.id');
                $join->on('workplace_shift_elegible_employee.customer_id', '=', 'wg_customer_employee.customer_id');
                $join->on('workplace_shift_elegible_employee.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->mergeBindings($qElegibleEmployee)
            ->leftjoin(DB::raw("({$qNonAvailable->toSql()}) AS wg_customer_config_workplace_shift_schedule_detail_employee"), function ($join) {
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id', '=', 'wg_customer_employee.id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_workplace_shift_schedule_detail_employee.customer_workplace_shift_schedule_id', '=', 'wg_customer_config_workplace_shift_schedule.id');
            })
            ->mergeBindings($qNonAvailable)
            ->whereNull('wg_customer_config_workplace_shift_schedule_detail_employee.customer_employee_id')
            ->groupBy(
                'wg_customer_employee.id',
                'wg_customer_config_workplace_shift_schedule.customer_workplace_id',
                'wg_customer_config_workplace.customer_id'
            );

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

        $entityModel->customerId = $entity->customerId;
        $entityModel->countryId = $entity->country ? $entity->country->id : null;
        $entityModel->stateId = $entity->state ? $entity->state->id : null;
        $entityModel->cityId = $entity->city ? $entity->city->id : null;
        $entityModel->name = $entity->name;
        $entityModel->type = 'PCS';
        $entityModel->status = $entity->isActive ? 'Activo' : 'Inactivo';
        $entityModel->address = $entity->address;
        $entityModel->economicActivityId = $entity->economicActivity ? $entity->economicActivity->id : null;
        $entityModel->employeeDirect = $entity->employeeDirect;
        $entityModel->employeeContractor = $entity->employeeContractor;
        $entityModel->employeeMision = $entity->employeeMision;

        if ($isNewRecord) {
            $entityModel->isFullyConfigured = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        if (isset($entity->processList)) {
            CustomerConfigProcessExpressRelationRepository::bulkInsertOrUpdate($entity->processList, $entityModel->id);
        }

        $this->updateCustomerEconomicActivity($entityModel);

        self::updateIsFullyConfigured($entityModel->customerId);

        Event::fire('migrate.gtc45', array($entityModel));

        return $this->parseModelWithRelations($entityModel);
    }

    public function insertOrUpdateGTC45($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->countryId = $entity->country ? $entity->country->id : null;
        $entityModel->stateId = $entity->state ? $entity->state->id : null;
        $entityModel->cityId = $entity->city ? $entity->city->id : null;
        $entityModel->name = $entity->name;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->address = $entity->address;
        $entityModel->economicActivityId = isset($entity->economicActivity) && $entity->economicActivity ? $entity->economicActivity->id : null;
        $entityModel->employeeDirect = $entity->employeeDirect;
        $entityModel->employeeContractor = $entity->employeeContractor;
        $entityModel->employeeMision = $entity->employeeMision;
        $entityModel->risk1 = $entity->risk1;
        $entityModel->risk2 = $entity->risk2;
        $entityModel->risk3 = $entity->risk3;
        $entityModel->risk4 = $entity->risk4;
        $entityModel->risk5 = $entity->risk5;

        if ($isNewRecord) {
            $entityModel->isFullyConfigured = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $this->parseModelGTC45WithRelations($entityModel);
    }

    public function insertOrUpdateShiftCondition($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->maxAge = $entity->maxAge;
        $entityModel->maxShiftEmployee = $entity->maxShiftEmployee;

        $hasMaxAge = $entityModel->maxAge > 0;
        $maxShiftEmployee = $entityModel->maxShiftEmployee > 0;
        $entityModel->isShiftConfigured = $hasMaxAge && $maxShiftEmployee;

        if ($isNewRecord) {
            $entityModel->isShiftConfigured = false;
            $entityModel->isShiftFullyConfigured = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        if (isset($entity->shiftConditionList)) {
            CustomerConfigWorkplaceShiftConditionRepository::bulkInsertOrUpdate($entity->shiftConditionList, $entityModel->id);
        }

        $entityModel->qtyEligibleEmployee = $this->getElegibleEmployeeCount($entityModel->id);

        return $this->parseModelShiftCondition($entityModel);
    }

    public function duplicate($entity)
    {
        $authUser = $this->getAuthUser();

        $entityModel = $this->model->newInstance();

        $entityModel->customerId = $entity->customerId;
        $entityModel->countryId = $entity->country;
        $entityModel->stateId = $entity->state;
        $entityModel->cityId = $entity->city;
        $entityModel->name = $entity->name;
        $entityModel->type = 'PCS';
        $entityModel->status = $entity->status;
        $entityModel->economicActivityId = $entity->economicActivity;
        $entityModel->employeeDirect = $entity->employeeDirect;
        $entityModel->employeeContractor = $entity->employeeContractor;
        $entityModel->employeeMision = $entity->employeeMision;

        $entityModel->isFullyConfigured = $entity->module == 'A';
        $entityModel->createdBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();


        if (in_array($entity->module, ['P', 'J', 'A'])) {
            if (isset($entity->processList)) {
                CustomerConfigProcessExpressRelationRepository::bulkDuplicate($entity->processList, $entityModel->id);

                Event::fire('migrate.gtc45', array($entityModel));
            }
        }

        return $this->parseModelWithRelations($entityModel);
    }


    private function updateCustomerEconomicActivity($model)
    {
        //if ($model->isDirty('economicActivityId')) {
        $entity = new \stdClass;
        $entity->id = $model->customerId;
        $entity->economicActivityId = $model->economicActivityId;
        (new CustomerRepository)->updateEconomicActivity($entity);
        //}
    }

    public function copy($entity)
    {
        $data = $this->service->getDataToCopy($entity);
        if ($data) {
            $this->duplicate($data);
        }
        return $data;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        return $entityModel->delete();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->country = $model->country;
            $entity->state = $model->state;
            $entity->city = $model->city;
            $entity->name = $model->name;
            $entity->address = $model->address;
            $entity->economicActivity = $model->getEconomicActivity();
            $entity->employeeDirect = $model->employeeDirect;
            $entity->employeeContractor = $model->employeeContractor;
            $entity->employeeMision = $model->employeeMision;
            $entity->processList = $model->getProcessList();
            $entity->availableActivityList = $model->getAvailableActivityList();
            $entity->isActive = in_array($model->status, ['Activo', 'En progreso']);

            return $entity;
        } else {
            return null;
        }
    }

    public function parseModelGTC45WithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->country = $model->country;
            $entity->state = $model->state;
            $entity->city = $model->city;
            $entity->name = $model->name;
            $entity->address = $model->address;
            $entity->employeeDirect = $model->employeeDirect;
            $entity->employeeContractor = $model->employeeContractor;
            $entity->employeeMision = $model->employeeMision;
            $entity->status = $model->getStatus();
            $entity->type = $model->getType();
            $entity->risk1 = $model->risk1;
            $entity->risk2 = $model->risk2;
            $entity->risk3 = $model->risk3;
            $entity->risk4 = $model->risk4;
            $entity->risk5 = $model->risk5;

            return $entity;
        } else {
            return null;
        }
    }

    public function parseModelShiftCondition($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->country = $model->country;
            $entity->state = $model->state;
            $entity->city = $model->city;
            $entity->name = $model->name;
            $entity->maxAge = $model->maxAge;
            $entity->isShiftConfigured = $model->isShiftConfigured == 1;
            $entity->isShiftFullyConfigured = $model->isShiftFullyConfigured == 1;
            $entity->maxShiftEmployee = $model->maxShiftEmployee;
            $entity->qtyEligibleEmployee = $model->qtyEligibleEmployee;
            $entity->totalEmployee = $model->getTotalEmployee();
            $entity->shiftConditionList = $model->getShiftConditionList();
            $entity->isActive = in_array($model->status, ['Activo', 'En progreso']);

            return $entity;
        } else {
            return null;
        }
    }

    public function getList($criteria)
    {
        return $this->service->getList($criteria);
    }

    public function getWithProcessList($criteria)
    {
        return $this->service->getWithProcessList($criteria);
    }

    public function getWithShiftList($criteria)
    {
        return $this->service->getWithShiftList($criteria);
    }

    public function findWorkplaceWithShift($id)
    {
        return $this->service->findWorkplaceWithShift($id);
    }

    public static function updateIsFullyConfiguredInCascadeAfterDelete($moduleId, $module)
    {
        $repository = new self;

        switch ($module) {
            case 'Activity':
                $relation = $repository->service->findActivityRelationAfterDelete($moduleId);
                CustomerConfigJobExpressRelationRepository::updateIsFullyConfigured($relation->processExpressRelationId);
                CustomerConfigProcessExpressRelationRepository::updateIsFullyConfigured($relation->customerWorkplaceId);
                self::updateIsFullyConfigured($relation->customerId);
                break;

            case 'Job':
                $relation = $repository->service->findJobRelationAfterDelete($moduleId);
                CustomerConfigProcessExpressRelationRepository::updateIsFullyConfigured($relation->customerWorkplaceId);
                self::updateIsFullyConfigured($relation->customerId);
                break;

            case 'Process':
                $relation = $repository->service->findProcessRelationAfterDelete($moduleId);
                self::updateIsFullyConfigured($relation->customerId);
                break;

            default:
                # code...
                break;
        }
    }

    public static function updateIsFullyConfiguredInCascadeAfterInsertOrUpdate($moduleId, $module)
    {
        $repository = new self;

        switch ($module) {
            case 'Process':
                $relation = $repository->service->findProcessRelation($moduleId);
                CustomerConfigJobExpressRelationRepository::updateIsFullyConfigured($moduleId);
                CustomerConfigProcessExpressRelationRepository::updateIsFullyConfigured($relation->customerWorkplaceId);
                self::updateIsFullyConfigured($relation->customerId);
                break;

            default:
                # code...
                break;
        }
    }

    public static function updateIsFullyConfigured($customerId)
    {
        $repository = new self;
        $authUser = $repository->getAuthUser();
        $repository->service->updateIsFullyConfigured($customerId, $authUser ? $authUser->id : 1);
    }

    public function getElegibleEmployeeCount($id)
    {
        $entity = $this->model->find($id);

        $criteria = new stdClass();
        $criteria->customerWorkplaceId = $entity->id;
        $criteria->customerId = $entity->customerId;
        $entity->qtyEligibleEmployee = $this->service->findElegibleEmployee($criteria);
        $entity->save();

        return $entity->qtyEligibleEmployee;
    }
}
