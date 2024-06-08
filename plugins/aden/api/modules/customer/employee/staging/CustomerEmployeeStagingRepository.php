<?php

namespace AdeN\Api\Modules\Customer\Employee\Staging;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use System\Models\File;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\CustomerConfigJob\CustomerConfigJobDTO;
use Wgroup\SystemParameter\SystemParameter;

class CustomerEmployeeStagingRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerEmployeeStagingModel());
        $this->service = new CustomerEmployeeStagingService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_employee_staging.id",
            "index" => "wg_employee_staging.index",
            "documentType" => "employee_document_type.item as documentType",
            "documentNumber" => "wg_employee_staging.documentNumber",
            "expeditionPlace" => "wg_employee_staging.expeditionPlace",
            "expeditionDate" => DB::raw("DATE_FORMAT(wg_employee_staging.expeditionDate, '%d/%m/%Y') as expeditionDate"),
            "birthdate" => DB::raw("DATE_FORMAT(wg_employee_staging.birthdate, '%d/%m/%Y') as birthdate"),
            "gender" => "wg_employee_staging.gender",
            "firstName" => "wg_employee_staging.firstName",
            "lastName" => "wg_employee_staging.lastName",
            "contractType" => "employee_contract_type.item as contractType",
            "profession" => "employee_profession.item as profession",
            "occupation" => "wg_employee_staging.occupation",
            "job" => "wg_customer_config_job_data.name as job",
            "workPlace" => "wg_customer_config_workplace.name as workPlace",
            "salary" => "wg_employee_staging.salary",
            "eps" => "eps.item as eps",
            "afp" => "afp.item as afp",
            "arl" => "arl.item as arl",
            "country_id" => "wg_employee_staging.country_id",
            "state_id" => "wg_employee_staging.state_id",
            "city_id" => "wg_employee_staging.city_id",
            "rh" => "wg_employee_staging.rh",
            "riskLevel" => "wg_employee_staging.riskLevel",
            "neighborhood" => "wg_employee_staging.neighborhood",
            "observation" => "wg_employee_staging.observation",
            "mobil" => "wg_employee_staging.mobil",
            "address" => "wg_employee_staging.address",
            "telephone" => "wg_employee_staging.telephone",
            "email" => "wg_employee_staging.email",
            "active" => DB::raw("IF(wg_employee_staging.isActive = 1, 'Activo', 'Inactivo') as active"),
            "isAuthorized" => DB::raw("IF(wg_employee_staging.isAuthorized = 1, 'Autorizado', 'No Autorizado') as isAuthorized"),
            "isValid" => "wg_employee_staging.isValid",
            "workShift" => "work_shifts.item as workShift",
            "errors" => "wg_employee_staging.errors",
            "customer_id" => "wg_employee_staging.customer_id",
            "session_id" => "wg_employee_staging.session_id"
        ]);


        $this->parseCriteria($criteria);
        $query = $this->query();
        $query
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_employee_staging.documentType', '=', 'employee_document_type.value');
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
            $join->on('wg_employee_staging.contractType', '=', 'employee_contract_type.value');
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_profession')), function ($join) {
            $join->on('wg_employee_staging.profession', '=', 'employee_profession.value');
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('eps')), function ($join) {
            $join->on('wg_employee_staging.eps', '=', 'eps.value');
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('afp')), function ($join) {
            $join->on('wg_employee_staging.afp', '=', 'afp.value');
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('arl')), function ($join) {
            $join->on('wg_employee_staging.arl', '=', 'arl.value');
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('work_shifts')), function ($join) {
            $join->on('wg_employee_staging.work_shift', '=', 'work_shifts.value');
        })
        ->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_employee_staging.workPlace', '=', 'wg_customer_config_workplace.id');
        })
        ->leftjoin("wg_customer_config_job", function ($join) {
            $join->on('wg_employee_staging.job', '=', 'wg_customer_config_job.id');
        })
        ->leftjoin("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
        }

        $entityModel->documentType = $entity->documentType->value;
        $entityModel->documentNumber = $entity->documentNumber;
        $entityModel->expeditionPlace = $entity->expeditionPlace;
        $entityModel->expeditionDate = $entity->expeditionDate ? Carbon::createFromFormat("d/m/Y", $entity->expeditionDate)->toDateString() : null;
        $entityModel->birthdate = $entity->birthdate ? Carbon::createFromFormat("d/m/Y", $entity->birthdate)->toDateString() : null;
        $entityModel->gender = $entity->gender->value;
        $entityModel->firstName = $entity->firstName;
        $entityModel->lastName = $entity->lastName;
        $entityModel->contractType = $entity->contractType ? $entity->contractType->value : null;
        $entityModel->profession = $entity->profession ? $entity->profession->value : null;
        $entityModel->occupation = $entity->occupation;
        $entityModel->job = $entity->job ? $entity->job->id : null;
        $entityModel->workPlace = $entity->workPlace ? $entity->workPlace->id : null;
        $entityModel->salary = $entity->salary;
        $entityModel->eps = $entity->eps->value;
        $entityModel->afp = $entity->afp->value;
        $entityModel->arl = $entity->arl->value;
        $entityModel->country_id = $entity->country_id ? $entity->country_id->name : null;
        $entityModel->state_id = $entity->state_id ? $entity->state_id->name : null;
        $entityModel->city_id = $entity->city_id ? $entity->city_id->name : null;
        $entityModel->rh = $entity->rh ? $entity->rh->item : null;
        $entityModel->riskLevel = $entity->riskLevel;
        $entityModel->neighborhood = $entity->neighborhood;
        $entityModel->observation = $entity->observation;
        $entityModel->mobil = $entity->mobil;
        $entityModel->address = $entity->address;
        $entityModel->telephone = $entity->telephone;
        $entityModel->email = $entity->email;
        $entityModel->isActive = $entity->isActive == 1;
        $entityModel->isAuthorized = $entityModel->isAuthorized == 2 ? 2 : $entity->isAuthorized == 1;
        $entityModel->work_shift = $entity->workShift->value ?? null;
        $entityModel->isValid = 1;

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
            $entity->index = $model->index;
            $entity->customerEmployeeId = $model->customer_employee_id;
            $entity->documentType = $model->getDocumentType();
            $entity->documentNumber = $model->documentNumber;
            $entity->expeditionPlace = $model->expeditionPlace;
            $entity->expeditionDate = $model->expeditionDate ? Carbon::parse($model->expeditionDate)->format("d/m/Y") : null;
            $entity->birthdate = $model->birthdate ? Carbon::parse($model->birthdate)->format("d/m/Y") : null;
            $entity->gender = $model->getGender();
            $entity->firstName = $model->firstName;
            $entity->lastName = $model->lastName;
            $entity->contractType = $model->getContractType();
            $entity->profession = $model->getProfession();
            $entity->occupation = $model->occupation;
            $entity->job = $model->jobModel ?  CustomerConfigJobDTO::parse($model->jobModel) : null;
            $entity->workPlace = $model->workPlaceModel;
            $entity->salary = $model->salary;
            $entity->eps = $model->getEPS();
            $entity->afp = $model->getAFP();
            $entity->arl = $model->getARL();
            $entity->country_id = $model->country;
            $entity->state_id = $model->state;
            $entity->city_id = $model->city;
            $entity->rh = $model->getRH();
            $entity->riskLevel = (integer)$model->riskLevel;
            $entity->neighborhood = $model->neighborhood;
            $entity->observation = $model->observation;
            $entity->mobil = $model->mobil;
            $entity->address = $model->address;
            $entity->telephone = $model->telephone;
            $entity->email = $model->email;
            $entity->isActive = $model->isActive == 1;
            $entity->isAuthorized = $model->isAuthorized == 2 ? 2 : $model->isAuthorized == 1;
            $entity->workShift = $model->getWorkShift();
            $entity->errors = $model->errors;

            return $entity;
        } else {
            return null;
        }
    }
}
