<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Employee\DemographicStaging;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use System\Models\File;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class CustomerEmployeeDemographicStagingRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerEmployeeDemographicStagingModel());

        $this->service = new CustomerEmployeeDemographicStagingService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_employee_demographic_staging.id",
            "index" => "wg_customer_employee_demographic_staging.index",
            "documentNumber" => "wg_customer_employee_demographic_staging.document_number",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",
            "typeHousing" => "wg_customer_employee_demographic_staging.type_housing",
            "antiquityCompany" => "wg_customer_employee_demographic_staging.antiquity_company",
            "antiquityCompany" => "wg_customer_employee_demographic_staging.antiquity_company",
            "antiquityJob" => "wg_customer_employee_demographic_staging.antiquity_job",
            "averageIncome" => "wg_customer_employee_demographic_staging.average_income",
            "stratum" => "wg_customer_employee_demographic_staging.stratum",
            "scholarship" => "wg_customer_employee_demographic_staging.scholarship",
            "race" => "wg_customer_employee_demographic_staging.race",
            "workArea" => "wg_customer_employee_demographic_staging.work_area",
            "observation" => "wg_customer_employee_demographic_staging.observation",
            "isValid" => "wg_customer_employee_demographic_staging.is_valid AS isValid",
            "customerId" => "wg_customer_employee_demographic_staging.customer_id",
            "sessionId" => "wg_customer_employee_demographic_staging.session_id"
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_employee_demographic_staging.customer_employee_id');

        })->leftjoin("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');

        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->customerEmployeeId = $entity->employee ? $entity->employee->id : null;
        $entityModel->documentNumber = $entity->employee ? $entity->employee->entity->documentNumber : null;
        $entityModel->typeHousing = $entity->typeHousing ? $entity->typeHousing->value : null;
        $entityModel->antiquityCompany = $entity->antiquityCompany ? $entity->antiquityCompany->value : null;
        $entityModel->antiquityJob = $entity->antiquityJob ? $entity->antiquityJob->value : null;
        $entityModel->hasChildren = $entity->hasChildren;
        $entityModel->hasPeopleInCharge = $entity->hasPeopleInCharge;
        $entityModel->qtyPeopleInCharge = $entity->qtyPeopleInCharge;
        $entityModel->averageIncome = $entity->averageIncome;
        $entityModel->stratum = $entity->stratum ? $entity->stratum->value : null;
        $entityModel->civilStatus = $entity->civilStatus ? $entity->civilStatus->value : null;
        $entityModel->scholarship = $entity->scholarship ? $entity->scholarship->value : null;
        $entityModel->race = $entity->race ? $entity->race->value : null;
        $entityModel->workingHoursPerDay = $entity->workingHoursPerDay;
        $entityModel->workArea = $entity->workArea ? $entity->workArea->value : null;
        $entityModel->hobby = $entity->hobby;
        $entityModel->isPracticeSports = $entity->isPracticeSports;
        $entityModel->frequencyPracticeSports = $entity->frequencyPracticeSports ? $entity->frequencyPracticeSports->value : null;
        $entityModel->isDrinkAlcoholic = $entity->isDrinkAlcoholic;
        $entityModel->frequencyDrinkAlcoholic = $entity->frequencyDrinkAlcoholic ? $entity->frequencyDrinkAlcoholic->value : null;
        $entityModel->isSmokes = $entity->isSmokes;
        $entityModel->frequencySmokes = $entity->frequencySmokes ? $entity->frequencySmokes->value : null;
        $entityModel->isDiagnosedDisease = $entity->isDiagnosedDisease;
        $entityModel->disease = $entity->disease;
        $entityModel->is_valid = 1;
        $entityModel->observation = null;

        $entityModel->save();

        //DB::statement("CALL TL_Absenteeism_Disability_Staging({$entity->customerId}, '$entityModel->session_id')");

        return $entityModel;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        return $entityModel->delete();
    }

    public function getTemplateFile()
    {
        $instance = CmsHelper::getInstance();
        $filePath = "templates/$instance/plantilla_importacion_perfil_sociodemografico.xlsx";
        return response()->download(CmsHelper::getStorageTemplateDir($filePath));
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
            $entity->employee = $this->service->findCustomerEmployee($model->customerEmployeeId);
            $entity->documentNumber = $model->documentNumber;
            $entity->typeHousing = $model->getTypeHousing();
            $entity->antiquityCompany = $model->getAntiquityCompany();
            $entity->antiquityJob = $model->getAntiquityJob();
            $entity->hasChildren = $model->hasChildren == 1;
            $entity->hasPeopleInCharge = $model->hasPeopleInCharge == 1;
            $entity->qtyPeopleInCharge = $model->qtyPeopleInCharge;
            $entity->averageIncome = $model->averageIncome;
            $entity->stratum = $model->getStratum();
            $entity->civilStatus = $model->getCivilStatus();
            $entity->scholarship = $model->getScholarship();
            $entity->race = $model->getRace();
            $entity->workingHoursPerDay = $model->workingHoursPerDay;
            $entity->workArea = $model->getWorkArea();
            $entity->hobby = $model->hobby;
            $entity->isPracticeSports = $model->isPracticeSports == 1;
            $entity->frequencyPracticeSports = $model->getFrequencySports();
            $entity->isDrinkAlcoholic = $model->isDrinkAlcoholic == 1;
            $entity->frequencyDrinkAlcoholic = $model->getFrequencyDrinkAlcoholic();
            $entity->isSmokes = $model->isSmokes == 1;
            $entity->frequencySmokes = $model->getFrequencySmokes();
            $entity->isDiagnosedDisease = $model->isDiagnosedDisease == 1;
            $entity->disease = $model->disease;
            $entity->isValid = $model->isValid;
            $entity->observation = $model->observation;
            $entity->index = $model->index;
            $entity->sessionId = $model->session_id;

            return $entity;
        } else {
            return null;
        }
    }
}
