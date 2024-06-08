<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerOccupationalInvestigationAl;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Wgroup\CustomerAbsenteeismIndirectCost\CustomerAbsenteeismIndirectCostDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\CustomerOccupationalInvestigationAlBody\CustomerOccupationalInvestigationAlBodyDTO;
use Wgroup\CustomerOccupationalInvestigationAlFactor\CustomerOccupationalInvestigationAlFactorDTO;
use Wgroup\CustomerOccupationalInvestigationAlLesion\CustomerOccupationalInvestigationAlLesionDTO;
use Wgroup\CustomerOccupationalInvestigationAlMechanism\CustomerOccupationalInvestigationAlMechanismDTO;
use Wgroup\CustomerOccupationalInvestigationAlResponsible\CustomerOccupationalInvestigationAlResponsibleDTO;
use Wgroup\CustomerOccupationalInvestigationAlWitness\CustomerOccupationalInvestigationAlWitnessDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnosticDTO;
use Wgroup\Models\AgentDTO;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDto;
use Wgroup\Models\InfoDetail;
use Wgroup\Models\InfoDetailDto;
use AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible\CustomerOccupationalInvestigationAlResponsibleRepository;
use Wgroup\Employee\Employee;
use Wgroup\Employee\EmployeeDTO;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerOccupationalInvestigationAlDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model: Modelo CustomerOccupationalInvestigationAl
     */
    private function getBasicInfo($model) {

        //---------------------------------------------BASIC INVESTIGATION
        $this->id = $model->id;
        $this->customer = CustomerDto::parse($model->customer);
        $this->employee = CustomerEmployeeDTO::parse($model->employee);
        $this->isReportAtRelated = $model->isReportAtRelated == 1;
        $this->reportAt = $model->getReportAt();
        $this->accidentDate =  $model->accidentDate ? Carbon::parse($model->accidentDate)/*->timezone('America/Bogota')*/ : null;
        $this->accidentType =  $model->getAccidentType();
        $this->country = $model->country;
        $this->state = $model->state;
        $this->city = $model->city;

        $this->accidentWeekDay =  null;
        $this->accidentMonth =  null;

        $this->reportDate =  $model->reportDate ? Carbon::parse($model->reportDate)/*->timezone('America/Bogota')*/ : null;
        $this->notificationArlDate =  $model->notificationArlDate ? Carbon::parse($model->notificationArlDate)/*->timezone('America/Bogota')*/ : null;
        $this->notificationDocumentDate =  $model->notificationDocumentDate ? Carbon::parse($model->notificationDocumentDate)/*->timezone('America/Bogota')*/ : null;

        $this->responsibleList = (new CustomerOccupationalInvestigationAlResponsibleRepository())->findByParent($model->id) ;

        $this->observation =  $model->observation;

        $this->accidentDateFormat =  $model->accidentDate ? Carbon::parse($model->accidentDate)/*->timezone('America/Bogota')*/->format('d/m/Y H:i') : null;


        //---------------------------------------------CUSTOMER
        $this->customerPrincipalZone = $model->getCustomerPrincipalZone();
        $this->customerPrincipalEconomicActivity = $model->getCustomerPrincipalEconomicActivity();
        $this->customerBranchEconomicActivity = $model->getCustomerBranchEconomicActivity();

        $this->customerBranchCountry = $model->customerBranchCountry;
        $this->customerBranchState = $model->customerBranchState;
        $this->customerBranchCity = $model->customerBranchCity;
        $this->customerBranchZone = $model->getCustomerBranchZone();

        $this->customerIsWorkingInHq = $model->customerIsWorkingInHq == 1;

        $this->customerObservation = $model->customerObservation;
        $this->details = InfoDetailDto::parse($model->getInfoDetail());


        //---------------------------------------------EMPLOYEE
        $this->employeeLinkType = $model->getEmployeeLinkType();
        $this->employeeStartDate = $model->employeeStartDate ? Carbon::parse($model->employeeStartDate)/*->timezone('America/Bogota')*/ : null;
        $this->employeeZone = $model->getEmployeeZone();
        $this->employeeHabitualOccupationCode = $model->employeeHabitualOccupationCode;
        $this->employeeHabitualOccupation = $model->employeeHabitualOccupation;
        $this->employeeHabitualOccupationTime = $model->employeeHabitualOccupationTime;
        $this->employeeDuration = $model->employeeDuration;
        $this->employeeJobTask = $model->employeeJobTask;


        //---------------------------------------------------------ACCIDENT
        $this->accidentDateOf =  $model->accidentDateOf ? Carbon::parse($model->accidentDateOf)/*->timezone('America/Bogota')*/ : null;
        $this->accidentDateOfFormat =  $model->accidentDateOf ? Carbon::parse($model->accidentDateOf)/*->timezone('America/Bogota')*/->format('d/m/Y H:i') : null;
        $this->accidentWorkingDay =  $model->getAccidentWorkingDay();
        $this->accidentCategory = $model->getAccidentCategory();
        $this->accidentWorkTimeHour = $model->accidentWorkTimeHour;
        $this->accidentWorkTimeMinute = $model->accidentWorkTimeMinute;
        $this->accidentIsDeathCause = $model->getAccidentIsDeathCause();
        $this->accidentDateOfDeath =  $model->accidentDateOfDeath ? Carbon::parse($model->accidentDateOfDeath)/*->timezone('America/Bogota')*/ : null;

        $this->accidentIsRegularWork =  $model->getAccidentIsRegularWork ();
        $this->accidentOtherRegularWorkText = $model->accidentOtherRegularWorkText;
        $this->accidentOtherRegularWorkTextCode = $model->accidentOtherRegularWorkTextCode;

        $this->accidentCountry = $model->accidentCountry;
        $this->accidentState = $model->accidentState;
        $this->accidentCity = $model->accidentCity;
        $this->accidentZone = $model->getAccidentZone();
        $this->accidentLocation = $model->getAccidentLocation();
        $this->accidentPlace = $model->getAccidentPlace();
        $this->accidentInjuryTypeText = $model->accidentInjuryTypeText;

        $this->observation =  $model->observation;
        $this->accidentWeekDay =  null;
        $this->accidentMonth =  null;
        $this->accidentWeekDayIndex = -1;

        if ($this->accidentDateOf) {
            $tm = Carbon::parse($this->accidentDateOf)/*->timezone('America/Bogota')*/;
            $this->accidentWeekDayIndex = $tm->dayOfWeek;
        }

        $this->lesions = $model->getLesions();
        $this->bodies = $model->getBodies();
        $this->factors = $model->getFactors();
        $this->mechanisms = $model->getMechanisms();
        $this->witnessList = $model->getWitnesses();

        $this->tokensession = $this->getTokenSession(true);
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerOccupationalInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customer->id;
        $model->customer_employee_id = $object->employee->id;
        $model->isReportAtRelated = $object->isReportAtRelated;
        $model->reportAt_id = ($object->reportAt != null) ? $object->reportAt->id : null;

        $model->accidentDate = $object->accidentDate ? Carbon::parse($object->accidentDate)->timezone('America/Bogota') : null;
        $model->accidentType = $object->accidentType ? $object->accidentType->value : null;
        $model->country_id = $object->country ? $object->country->id : null;
        $model->state_id = $object->state ? $object->state->id : null;
        $model->city_id = $object->city ? $object->city->id : null;
        $model->observation = $object->observation;
        $model->reportDate = $object->reportDate ? Carbon::parse($object->reportDate)->timezone('America/Bogota') : null;
        $model->notificationArlDate = $object->notificationArlDate ? Carbon::parse($object->notificationArlDate)->timezone('America/Bogota') : null;
        $model->notificationDocumentDate = $object->notificationDocumentDate ? Carbon::parse($object->notificationDocumentDate)->timezone('America/Bogota') : null;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->status = 'open';
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        $object->id = $model->id;
        //Save Agents
        if (isset($object->responsibleList)) {
            (new CustomerOccupationalInvestigationAlResponsibleRepository())->bulkInsertOrUpdate($object->responsibleList, $model->id);
        }

        //Save Customer Info
        self::fillAndSaveCustomerModel($object);

        //Save Employee Info
        self::fillAndSaveEmployeeModel($object);

        //Save Accident Info
        self::fillAndSaveAccidentModel($object);

        CustomerOccupationalInvestigationAlBodyDTO::bulkInsert($object->bodies, $model->id);
        CustomerOccupationalInvestigationAlFactorDTO::bulkInsert($object->factors, $model->id);
        CustomerOccupationalInvestigationAlLesionDTO::bulkInsert($object->lesions, $model->id);
        CustomerOccupationalInvestigationAlMechanismDTO::bulkInsert($object->mechanisms, $model->id);
        CustomerOccupationalInvestigationAlWitnessDTO::bulkInsert($object->witnessList, $model->id);

        return CustomerOccupationalInvestigationAl::find($model->id);
    }

    public static function  fillAndSaveCustomerModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerOccupationalInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->customerObservation = $object->customerObservation;
        $model->customerPrincipalZone = ($object->customerPrincipalZone != null) ? $object->customerPrincipalZone->value : null;
        $model->customerPrincipalEconomicActivity = ($object->customerPrincipalEconomicActivity != null) ? $object->customerPrincipalEconomicActivity->id : null;
        $model->customerBranchEconomicActivity = ($object->customerBranchEconomicActivity != null) ? $object->customerBranchEconomicActivity->id : null;


        $model->customer_branch_country_id = ($object->customerBranchCountry != null) ? $object->customerBranchCountry->id : null;
        $model->customer_branch_state_id = ($object->customerBranchState != null) ? $object->customerBranchState->id : null;
        $model->customer_branch_city_id = ($object->customerBranchCity != null) ? $object->customerBranchCity->id : null;
        $model->customerBranchZone = ($object->customerBranchZone != null) ? $object->customerBranchZone->value : null;
        $model->customerIsWorkingInHq = $object->customerIsWorkingInHq;


        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        // limpiar la informacion de contacto
        foreach ($model->getInfoDetail() as $infoDetail) {
            $infoDetail->delete();
        }

        if ($object->details) {
            foreach ($object->details as $contactInfo) {
                if (isset($contactInfo->id) && $contactInfo->type != null && $contactInfo->value != '') {
                    $infoDetail = new InfoDetail();
                    $infoDetail->entityId = $model->id;
                    $infoDetail->entityName = get_class($model);
                    $infoDetail->type = ($contactInfo->type) ? $contactInfo->type->value : null;
                    $infoDetail->value = $contactInfo->value;
                    $infoDetail->save();
                }
            }
        }

        return CustomerOccupationalInvestigationAl::find($model->id);
    }

    public static function  fillAndSaveEmployeeModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerOccupationalInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalInvestigationAl();
            $isEdit = false;
        }

        self::updateEmployee($object->employee->entity);

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->employeeLinkType = ($object->employeeLinkType != null) ? $object->employeeLinkType->value : null;
        $model->employeeStartDate = $object->employeeStartDate ? Carbon::parse($object->employeeStartDate)->timezone('America/Bogota') : null;
        $model->employeeZone = ($object->employeeZone != null) ? $object->employeeZone->value : null;
        $model->employeeHabitualOccupationCode = $object->employeeHabitualOccupationCode;
        $model->employeeHabitualOccupation = $object->employeeHabitualOccupation;
        $model->employeeHabitualOccupationTime = $object->employeeHabitualOccupationTime;
        $model->employeeDuration = $object->employeeDuration;
        $model->employeeJobTask = $object->employeeJobTask;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        return CustomerOccupationalInvestigationAl::find($model->id);
    }

    public static function updateEmployee($entity)
    {
        $userAdmn = Auth::getUser();

        if (($model = Employee::find($entity->id))) {
            $model->country_id = !isset($entity->country->id) ? null : $entity->country->id;
            $model->state_id = !isset($entity->state->id) ? null : $entity->state->id;
            $model->city_id = !isset($entity->town->id) ? null : $entity->town->id;
            $model->updatedBy = $userAdmn->id;
            $model->save();
        }
    }

    public static function  fillAndSaveAccidentModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerOccupationalInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->accidentDateOf = $object->accidentDateOf ? Carbon::parse($object->accidentDateOf)->timezone('America/Bogota') : null;
        $model->accidentWorkingDay = ($object->accidentWorkingDay != null) ? $object->accidentWorkingDay->value : null;
        $model->accidentCategory = ($object->accidentCategory != null) ? $object->accidentCategory->value : null;
        $model->accidentWorkTimeHour = $object->accidentWorkTimeHour;
        $model->accidentWorkTimeMinute = $object->accidentWorkTimeMinute;
        $model->accidentIsDeathCause = ($object->accidentIsDeathCause != null) ? $object->accidentIsDeathCause->value : null;
        $model->accidentDateOfDeath = $object->accidentDateOfDeath ? Carbon::parse($object->accidentDateOfDeath)->timezone('America/Bogota') : null;

        $model->accidentIsRegularWork = ($object->accidentIsRegularWork != null) ? $object->accidentIsRegularWork->value : null;
        $model->accidentOtherRegularWorkText = isset($object->accidentOtherRegularWorkText) ? $object->accidentOtherRegularWorkText : null;
        $model->accidentOtherRegularWorkTextCode = isset($object->accidentOtherRegularWorkTextCode) ? $object->accidentOtherRegularWorkTextCode : null;

        $model->accident_country_id = $object->accidentCountry == null ? null : $object->accidentCountry->id;
        $model->accident_state_id = $object->accidentState == null ? null : $object->accidentState->id;
        $model->accident_city_id = $object->accidentCity == null ? null : $object->accidentCity->id;
        $model->accidentZone = ($object->accidentZone != null) ? $object->accidentZone->value : null;
        $model->accidentLocation = ($object->accidentLocation != null) ? $object->accidentLocation->value : null;
        $model->accidentPlace = ($object->accidentPlace != null) ? $object->accidentPlace->value : null;

        $model->accidentInjuryTypeText = isset($object->accidentInjuryTypeText) ? $object->accidentInjuryTypeText : null;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        return CustomerOccupationalInvestigationAl::find($model->id);
    }

    public static function  fillAndSaveSummaryModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerOccupationalInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->place = $object->place;
        $model->address = $object->address;
        $model->realizedBy = $object->realizedBy;
        $model->reviewedBy = $object->reviewedBy;

        $model->toWhom = $object->toWhom;
        $model->toWhomJob = $object->toWhomJob;
        $model->agrResponsible = $object->agrResponsible;
        $model->riskManager = $object->riskManager;


        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        return CustomerOccupationalInvestigationAl::find($model->id);
    }

    public static function  fillAndSaveEventModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerOccupationalInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->eventObservation = $object->eventObservation;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        return CustomerOccupationalInvestigationAl::find($model->id);
    }

    public static function  fillAndSaveAnalysisModel($object)
    {
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerOccupationalInvestigationAl::find($object->id))) {
                return null;
            }
        }

        $model->injuryObservation = $object->injuryObservation;
        // actualizado por
        $model->updatedBy = $userAdmn->id;

        // Guarda
        $model->save();

        // Actualiza timestamp
        $model->touch();

        return CustomerOccupationalInvestigationAl::find($model->id);
    }

    public static function  fillAndUpdateModel($object)
    {
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerOccupationalInvestigationAl::find($object->id))) {
                return null;
            }
        }

        $model->status = 'close';
        // actualizado por
        $model->updatedBy = $userAdmn->id;

        // Guarda
        $model->save();

        // Actualiza timestamp
        $model->touch();

        return CustomerOccupationalInvestigationAl::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1") {

        // parse model
        if ($model) {
            $this->setInfo($model, $fmt_response);
        }

        return $this;
    }

    public static function parse($info, $fmt_response = "1") {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerOccupationalInvestigationAl) {
                    $parsed[] = (new CustomerOccupationalInvestigationAlDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerOccupationalInvestigationAl) {
            return (new CustomerOccupationalInvestigationAlDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerOccupationalInvestigationAlDTO();
            }
        }
    }

    private function getUserSsession() {
        if (!Auth::check())
            return null;

        return Auth::getUser();
    }

    private function getTokenSession($encode = false) {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }
}
