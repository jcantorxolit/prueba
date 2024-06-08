<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class CustomerAbsenteeismDisabilityStagingRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerAbsenteeismDisabilityStagingModel());

        $this->service = new CustomerAbsenteeismDisabilityStagingService();
    }

    public function all($criteria)
    {        
        $this->setColumns([
            "id" => "wg_customer_absenteeism_disability_staging.id",
            "index" => "wg_customer_absenteeism_disability_staging.index",
            "documentNumber" => "wg_customer_absenteeism_disability_staging.documentNumber",
            "firstName" => "wg_employee.firstName",
            "lastName" => "wg_employee.lastName",            
            "category" => "wg_customer_absenteeism_disability_staging.category",
            "type" => "wg_customer_absenteeism_disability_staging.type",
            "cause" => "wg_customer_absenteeism_disability_staging.cause",
            "accidentType" => "wg_customer_absenteeism_disability_staging.accidentType",
            "disabilityParent" => DB::raw("CONCAT(DATE_FORMAT(wg_customer_absenteeism_disability.start, '%d/%m/%Y'), ' | ', CASE WHEN absenteeism_disability_causes.item IS NOT NULL THEN absenteeism_disability_causes.item ELSE absenteeism_disability_causes_admin.item END) AS disabilityParent"),
            "diagnostic" => "wg_customer_absenteeism_disability_staging.diagnostic_id AS diagnostic",            
            "start" => "wg_customer_absenteeism_disability_staging.start",
            "end" => "wg_customer_absenteeism_disability_staging.end",
            "bodyPart" => "wg_customer_absenteeism_disability_staging.body_part AS bodyPart",
            "observation" => "wg_customer_absenteeism_disability_staging.observation",
            "isValid" => "wg_customer_absenteeism_disability_staging.is_valid AS isValid",           
            "customerId" => "wg_customer_absenteeism_disability_staging.customer_id",
            "sessionId" => "wg_customer_absenteeism_disability_staging.session_id",
            "disabilityParentIsRequired" => "wg_customer_absenteeism_disability_staging.customer_absenteeism_disability_parent_required AS disabilityParentIsRequired",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_absenteeism_disability_staging.customer_employee_id');
        })->leftjoin("wg_employee", function ($join) {
            $join->on('wg_customer_employee.employee_id', '=', 'wg_employee.id');

        })->leftjoin("wg_customer_absenteeism_disability", function ($join) {
            $join->on('wg_customer_absenteeism_disability.id', '=', 'wg_customer_absenteeism_disability_staging.customer_absenteeism_disability_parent_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes_admin')), function ($join) {
            $join->on('wg_customer_absenteeism_disability.cause', '=', 'absenteeism_disability_causes_admin.value');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customer_id = $entity->customerId;
        $entityModel->customer_employee_id = $entity->employee ? $entity->employee->id : null;
        $entityModel->documentNumber = $entity->employee ? $entity->employee->entity->documentNumber : null;
        $entityModel->dayLiquidationBasis = $entity->dayLiquidationBasis;
        $entityModel->hourLiquidationBasis = $entity->hourLiquidationBasis;
        $entityModel->category = $entity->category ? $entity->category->value : null;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->cause = $entity->cause ? $entity->cause->value : null;
        $entityModel->accidentType = $entity->accidentType ? $entity->accidentType->value : null;
        $entityModel->diagnostic_id = $entity->diagnostic ? $entity->diagnostic->code : null;
        $entityModel->start = $entity->startDate ? Carbon::parse($entity->startDate)->timezone('America/Bogota') : null;
        $entityModel->end = $entity->endDate ? Carbon::parse($entity->endDate)->timezone('America/Bogota') : null;
        $entityModel->is_hour = $entity->isHour;
        $entityModel->numberDays = $entity->numberDays;
        $entityModel->amountPaid = $entity->amountPaid;
        $entityModel->body_part = $entity->bodyPart ? $entity->bodyPart->id : null;
        $entityModel->workplace_id = $entity->workplace ? $entity->workplace->id : null;
        if (isset($entity->disabilityParent)) {
            $entityModel->customer_absenteeism_disability_parent_id = $entity->disabilityParent ? $entity->disabilityParent->id : null;
        }

        if ($entityModel->start > Carbon::now('America/Bogota')) {
            $entityModel->is_valid = 0;
            $entityModel->observation = 'La fecha inicial no puede ser mayor a la fecha actual| ';
        } else {
            $entityModel->is_valid = 1;
            $entityModel->observation = null;
        }
        
        $entityModel->save();

        DB::statement("CALL TL_Absenteeism_Disability_Staging({$entity->customerId}, '$entityModel->session_id')");

        $result = $entityModel;

        return $result;
    }

    public function update($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            throw new \Exception('Record not found');
        }
       
        if (isset($entity->disabilityParent)) {
            $entityModel->customer_absenteeism_disability_parent_id = $entity->disabilityParent ? $entity->disabilityParent->id : null;
        }

        $entityModel->is_valid = 1;
        $entityModel->observation = null;
        
        $entityModel->save();

        DB::statement("CALL TL_Absenteeism_Disability_Staging({$entity->customerId}, '$entityModel->session_id')");

        $result = $entityModel;

        return $result;
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
        $entityModel->save();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customer_id;
            $entity->employee = $this->service->findCustomerEmployee($model->customer_employee_id);
            $entity->workplace = $model->getWorkplace();
            $entity->documentNumber = $model->documentNumber;
            $entity->dayLiquidationBasis = $model->dayLiquidationBasis;
            $entity->hourLiquidationBasis = $model->hourLiquidationBasis;
            $entity->category = $model->getCategory();
            $entity->type = $model->getType();
            $entity->cause = $model->getCause();
            $entity->accidentType = $model->getAccidenttype();
            $entity->diagnostic = $this->service->findDiagnostic($model->diagnostic_id);
            $entity->startDate = $model->start ? Carbon::parse($model->start) : null;
            $entity->endDate = $model->end ? Carbon::parse($model->end) : null;
            $entity->isHour = $model->is_hour == 1;           
            $entity->numberDays = $model->numberdays;           
            $entity->bodyPart = $this->service->findBodyPartById($model->body_part);
            $entity->isValid = $model->isValid;
            $entity->observation = $model->observation;
            $entity->index = $model->index;           
            $entity->amountPaid = $model->amountPaid;           
            $entity->sessionId = $model->session_id;           

            return $entity;
        } else {
            return null;
        }
    }
}
