<?php

namespace Aden\Api\Modules\Customer\Jobconditions\Jobcondition\Staging;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionWorkplaceModel;
use Carbon\Carbon;
use DB;
use Wgroup\SystemParameter\SystemParameter;

class JobConditionsStagingRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new JobConditionsStagingModel());
        $this->service = new JobConditionsStagingService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_job_condition_staging.id",
            "index" => "wg_customer_job_condition_staging.index",
            "documentType" => "employee_document_type.item as documentType",
            "documentNumber" => "wg_customer_job_condition_staging.document_number",
            "registrationDate" => DB::raw("DATE_FORMAT(wg_customer_job_condition_staging.registration_date, '%d/%m/%Y') as registrationDate"),
            "workmodel" => "wg_customer_job_conditions_work_model.item as workmodel",
            "location" => "wg_customer_job_conditions_location.item as location",
            "job" => "job.name as job",
            "workplace" => "wg_customer_job_condition_staging.workplace as workplace",
            "isAuthorized" => DB::raw("IF(wg_customer_job_condition_staging.isAuthorized = 1, 'Autorizado', 'No Autorizado') as isAuthorized"),
            "isValid" => "wg_customer_job_condition_staging.isValid",
            "errors" => "wg_customer_job_condition_staging.observation as errors",
            "customerId" => "wg_customer_job_condition_staging.customer_id as customerId",
            "sessionId" => "wg_customer_job_condition_staging.session_id as sessionId",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();
        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('wg_customer_job_condition_staging.identification_type', '=', 'employee_document_type.value');
        })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_job_conditions_work_model')), function ($join) {
                $join->on('wg_customer_job_condition_staging.work_model', '=', 'wg_customer_job_conditions_work_model.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_job_conditions_location')), function ($join) {
                $join->on('wg_customer_job_condition_staging.location', '=', 'wg_customer_job_conditions_location.value');
            })
            ->leftjoin('wg_customer_config_job_data as job', 'job.id', '=', 'wg_customer_job_condition_staging.job');

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
        }
        $valEmployee = $this->service->getEmployeeForCustomer($entity);
        $valEvalForLocation = $this->service->getAutoevalForLocation($entity);
        $valEvalForLocationStaging = $this->service->getAutoevalForLocationStaging($entity);
        $entityModel->identification_type = $entity->documentType->value;
        $entityModel->document_number = $entity->documentNumber;
        $entityModel->registration_date = $entity->registrationDate ? Carbon::createFromFormat("d/m/Y", $entity->registrationDate)->toDateString() : null;
        $entityModel->work_model = $entity->workmodel->value;
        $entityModel->location = $entity->location->value;
        $entityModel->job = $entity->job ? $entity->job->id : null;
        $entityModel->workplace = $entity->workplace;
        $entityModel->observation = null;

        if (!$valEmployee) {
            $entityModel->isValid = false;
            $entityModel->observation = "El empleado no existe o no esta enlazado al cliente| ";
        } else if ($valEvalForLocation || $valEvalForLocationStaging) {
            $entityModel->isValid = false;
            $entityModel->observation = "No se pueden contener dos autoevaluaciones para el mismo lugar de trabajo| | ";
        } else {
            $entityModel->isValid = 1;
            $entityModel->isAuthorized = true;
        }

        $entityModel->save();
        return $entityModel;
    }

    public static function saveWorkplace($name)
    {
        $workplace = JobConditionWorkplaceModel::where('name', $name)->first();
        if (empty($workplace)) {
            $workplace = new JobConditionWorkplaceModel();
            $workplace->name = $name;
            $workplace->save();
        }
        $workplace->save();

        return $workplace->id;
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
            $entity->customerId = $model->customer_id;
            $entity->documentType = $model->getDocumentType();
            $entity->documentNumber = $model->document_number;
            $entity->registrationDate = $model->registration_date ? Carbon::parse($model->registration_date)->format("d/m/Y") : null;
            $entity->workmodel = $model->getWorkModel();
            $entity->location = $model->getLocation();
            $entity->workplace = $model->workplace;
            $entity->job = $model->getJob();
            $entity->isActive = $model->isActive == 1;
            $entity->isAuthorized = $model->isAuthorized == 2 ? 2 : $model->isAuthorized == 1;
            $entity->sessionId = $model->session_id;
            $entity->errors = $model->observation;

            return $entity;
        } else {
            return null;
        }
    }
}
