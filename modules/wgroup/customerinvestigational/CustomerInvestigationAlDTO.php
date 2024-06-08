<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInvestigationAl;

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
use Wgroup\CustomerInvestigationAlCause\CustomerInvestigationAlCauseDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnosticDTO;
use Wgroup\InvestigationAlCause\InvestigationAlCauseDTO;
use Wgroup\Models\AgentDTO;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDto;
use Wgroup\Models\InfoDetail;
use Wgroup\Models\InfoDetailDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerInvestigationAlDTO
{

    function __construct($model = null)
    {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1")
    {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model : Modelo CustomerInvestigationAl
     */
    private function getBasicInfo($model)
    {

        //Codigo
        $this->id = $model->id;
        $this->customer = $model->customer ? CustomerDto::parse($model->customer) : null;
        $this->employee = $model->employee ? CustomerEmployeeDTO::parse($model->employee) : null;
        $this->accidentDate = $model->accidentDate ? Carbon::parse($model->accidentDate)->timezone('America/Bogota') : null;
        $this->notificationDate = $model->notificationDate ? Carbon::parse($model->notificationDate)->timezone('America/Bogota') : null;
        $this->notifiedBy = $model->getNotifiedBy();
        $this->accidentType = $model->getAccidentType();
        $this->country = $model->country;
        $this->state = $model->state;
        $this->city = $model->city;
        $this->dxResolution = $model->getDxResolution();
        $this->hazardType = $model->getHazardType();
        $this->agent = AgentDTO::parse($model->agent);
        $this->director = AgentDTO::parse($model->director);
        $this->interventionPlan = $model->getInterventionPlan();
        $this->sisalud = $model->sisalud;
        $this->injury = $model->injury;
        $this->observation = $model->observation;
        $this->investigator = AgentDTO::parse($model->investigator);
        $this->hireDate = $model->hireDate ? Carbon::parse($model->hireDate)->timezone('America/Bogota') : null;
        $this->schedule = $model->schedule;
        $this->sequence = $model->sequence;
        $this->accidentWeekDay = null;
        $this->accidentMonth = null;
        $this->status = $model->status;

        $this->place = $model->place;
        $this->address = $model->address;
        $this->checkDate = $model->checkDate ? Carbon::parse($model->checkDate)->timezone('America/Bogota') : null;
        $this->realizedBy = $model->realizedBy;
        $this->reviewedBy = $model->reviewedBy;

        $this->injuryObservation = $model->injuryObservation;
        $this->eventObservation = $model->eventObservation;

        $this->accidentDateFormat = $model->accidentDate ? Carbon::parse($model->accidentDate)->timezone('America/Bogota')->format('d/m/Y H:i') : null;
        $this->notificationDateFormat = $model->notificationDate ? Carbon::parse($model->notificationDate)->timezone('America/Bogota')->format('d/m/Y') : null;
        $this->hireDateFormat = $model->hireDate ? Carbon::parse($model->hireDate)->timezone('America/Bogota')->format('d/m/Y') : null;
        $this->checkDateFormat = $model->checkDate ? Carbon::parse($model->checkDate)->timezone('America/Bogota')->format('d/m/Y') : null;


        //---------------------------------------------------------ACCIDENT
        $this->accidentDateOf = $model->accidentDateOf ? Carbon::parse($model->accidentDateOf)->timezone('America/Bogota') : null;
        $this->accidentWorkingDay = $model->getAccidentWorkingDay();
        $this->accidentIsRegularWork = $model->getAccidentIsRegularWork();
        $this->accidentOtherRegularWorkText = $model->accidentOtherRegularWorkText;
        $this->accidentWorkTimeHour = $model->accidentWorkTimeHour;
        $this->accidentWorkTimeMinute = $model->accidentWorkTimeMinute;
        $this->accidentIsDeathCause = $model->getAccidentIsDeathCause();
        $this->accidentCategory = $model->getAccidentCategory();
        $this->accidentCountry = $model->accidentCountry;
        $this->accidentState = $model->accidentState;
        $this->accidentCity = $model->accidentCity;
        $this->accidentZone = $model->getAccidentZone();
        $this->accidentPlace = $model->getAccidentPlace();
        $this->accidentInjuryType = $model->getAccidentInjuryType();
        $this->accidentInjuryTypeText = $model->accidentInjuryTypeText;
        $this->accidentBodyPart = $model->getAccidentBodyPart();
        $this->accidentAgent = $model->getAccidentAgent();
        $this->accidentMechanism = $model->getAccidentMechanism();
        $this->accidentCompanyTransport = $model->accidentCompanyTransport == 1;
        $this->accidentAutoTransport = $model->accidentAutoTransport == 1;
        $this->accidentReportDate = $model->accidentReportDate ? Carbon::parse($model->accidentReportDate)->timezone('America/Bogota') : null;
        $this->accidentReportMadeBy = $model->accidentReportMadeBy;
        $this->accidentReportJob = $model->accidentReportJob;
        $this->accidentReportClarification = $model->accidentReportClarification;


        $this->injury = $model->injury;
        $this->observation = $model->observation;
        $this->investigator = AgentDTO::parse($model->investigator);

        $this->schedule = $model->schedule;
        $this->sequence = $model->sequence;
        $this->accidentWeekDay = null;
        $this->accidentMonth = null;

        $this->accidentDateOfFormat = $model->accidentDateOf ? Carbon::parse($model->accidentDateOf)->timezone('America/Bogota')->format('d/m/Y H:i') : null;


        //---------------------------------------------EMPLOYEE
        $this->employeeLinkType = $model->getEmployeeLinkType();
        $this->employeeZone = $model->getEmployeeZone();
        $this->employeeHabitualOccupation = $model->employeeHabitualOccupation;
        $this->employeeJobTask = $model->employeeJobTask;
        $this->employeeHabitualOccupationTime = $model->employeeHabitualOccupationTime;
        $this->employeeStartDate = $model->employeeStartDate ? Carbon::parse($model->employeeStartDate)->timezone('America/Bogota') : null;
        $this->employeeDuration = $model->employeeDuration;
        $this->employeeRegularWork = $model->employeeRegularWork;
        $this->employeeIsMissionWorker = $model->employeeIsMissionWorker == 1;
        $this->employeeMissionCompanyName = $model->employeeMissionCompanyName;
        $this->employeeMissionSalary = $model->employeeMissionSalary;
        $this->employeeMissionWorkingDay = $model->getEmployeeMissionWorkingDay();
        $this->employeeClarification = $model->employeeClarification;

        //---------------------------------------------CUSTOMER
        $this->customerPrincipalZone = $model->getCustomerPrincipalZone();
        $this->customerPrincipalEconomicActivity = $model->getCustomerPrincipalEconomicActivity();
        $this->customerPrincipalRiskClass = $model->getCustomerPrincipalRiskClass();
        $this->customerBranchEconomicActivity = $model->getCustomerBranchEconomicActivity();
        $this->customerBranchRiskClass = $model->getCustomerBranchRiskClass();


        $this->customerBranchCountry = $model->customerBranchCountry;
        $this->customerBranchState = $model->customerBranchState;
        $this->customerBranchCity = $model->customerBranchCity;
        $this->customerBranchZone = $model->getCustomerBranchZone();

        $this->customerObservation = $model->customerObservation;
        $this->customerResponsibleHealth = $model->customerResponsibleHealth;
        $this->details = InfoDetailDto::parse($model->getInfoDetail());


        $this->dateOfFiling = $model->dateOfFiling ? Carbon::parse($model->dateOfFiling)->timezone('America/Bogota') : null;
        $this->dateOfTechnicalConcept = $model->dateOfTechnicalConcept ? Carbon::parse($model->dateOfTechnicalConcept)->timezone('America/Bogota') : null;
        $this->dateOfReportDelivery = $model->dateOfReportDelivery ? Carbon::parse($model->dateOfReportDelivery)->timezone('America/Bogota') : null;
        $this->dateOfReportFeedback = $model->dateOfReportFeedback ? Carbon::parse($model->dateOfReportFeedback)->timezone('America/Bogota') : null;
        $this->dateOfLetterGeneration = $model->dateOfLetterGeneration ? Carbon::parse($model->dateOfLetterGeneration)->timezone('America/Bogota') : null;
        $this->dateOfExpirationRecommendation = $model->dateOfExpirationRecommendation ? Carbon::parse($model->dateOfExpirationRecommendation)->timezone('America/Bogota') : null;
        $this->dateOfTrackingRecommendation = $model->dateOfTrackingRecommendation ? Carbon::parse($model->dateOfTrackingRecommendation)->timezone('America/Bogota') : null;

        $this->toWhom = $model->toWhom;
        $this->toWhomJob = $model->toWhomJob;
        $this->agrResponsible = $model->agrResponsible;
        $this->riskManager = $model->riskManager;
        $this->comment = $model->comment;


        //-------------------------------------------------------------New Fields
        $this->accidentPlaceText = $model->accidentPlaceText;
        $this->accidentBodyPartText = $model->accidentBodyPartText;
        $this->accidentAgentText = $model->accidentAgentText;
        $this->accidentMechanismText = $model->accidentMechanismText;

        $this->accidentWeekDayIndex = -1;

        if ($this->accidentDateOf) {
            $tm = Carbon::parse($this->accidentDateOf)->timezone('America/Bogota');
            $this->accidentWeekDayIndex = $tm->dayOfWeek;
        }

        $this->employeeTelephone = $model->employeeTelephone;
        $this->employeeAddress = $model->employeeAddress;
        $this->employeeBirthday = $model->employeeBirthday ? Carbon::parse($model->employeeBirthday) : null;


        $this->employeeGender = $model->getEmployeeGender();
        $this->employeeCountry = $model->employeeCountry;
        $this->employeeState = $model->employeeState;
        $this->employeeCity = $model->employeeCity;
        $this->employeeEPS = $model->getEmployeeEPS();
        $this->employeeAFP = $model->getEmployeeAFP();
        $this->employeeARL = $model->getEmployeeARL();


        $this->letterCountry = $model->letterCountry;
        $this->letterState = $model->letterState;
        $this->letterDnprl = $model->letterDnprl;
        $this->letterCity = $model->letterCity;
        $this->letterElaborationDate = $model->letterElaborationDate;
        $this->letterTreatment = $model->letterTreatment;
        $this->letterShippingAddress = $model->letterShippingAddress;
        $this->letterShippingCity = $model->letterShippingCity;
        $this->letterSignedBy = $model->letterSignedBy;
        $this->letterJobSignedBy = $model->letterJobSignedBy;
        $this->letterImagine = $model->letterImagine;
        $this->letterShippingPhone = $model->letterShippingPhone;

        $this->registerDate = $model->registerDate ? Carbon::parse($model->registerDate) : null;


        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoCause($model)
    {

        //Codigo
        $this->id = $model->id;

        $this->insecureActList = $model->getInsecureAct();
        $this->insecureConditionList = $model->getInsecureCondition();
        $this->workFactorList = $model->getWorkFactor();
        $this->personalFactorList = $model->getPersonalFactor();

        $this->insecureActObservation = $model->insecureActObservation;
        $this->insecureConditionObservation = $model->insecureConditionObservation;
        $this->workFactorObservation = $model->workFactorObservation;
        $this->personalFactorObservation = $model->personalFactorObservation;
        $this->observation = $model->causeObservation;

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
            if (!($model = CustomerInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customer->id;
        $model->customer_employee_id = $object->employee->id;
        $model->accidentDate = $object->accidentDate ? Carbon::parse($object->accidentDate) : null;
        $model->notificationDate = $object->notificationDate ? Carbon::parse($object->notificationDate) : null;
        $model->notifiedBy = ($object->notifiedBy != null) ? $object->notifiedBy->value : null;
        $model->accidentType = ($object->accidentType != null) ? $object->accidentType->value : null;
        $model->country_id = $object->country == null ? null : $object->country->id;
        $model->state_id = $object->state == null ? null : $object->state->id;
        $model->city_id = $object->city == null ? null : $object->city->id;
        $model->dxResolution = ($object->dxResolution != null) ? $object->dxResolution->value : null;
        $model->hazardType = ($object->hazardType != null) ? $object->hazardType->value : null;
        $model->agent_id = ($object->agent != null) ? $object->agent->id : null;
        $model->director_id = ($object->director != null) ? $object->director->id : null;
        $model->interventionPlan = ($object->interventionPlan != null) ? $object->interventionPlan->value : null;

        $model->sisalud = $object->sisalud;
        $model->injury = $object->injury;
        $model->observation = $object->observation;
        $model->investigator_id = ($object->investigator != null) ? $object->investigator->id : null;
        $model->hireDate = $object->hireDate ? Carbon::parse($object->hireDate) : null;

        $model->schedule = $object->schedule;
        $model->sequence = $object->sequence;

        $model->checkDate = $object->checkDate ? Carbon::parse($object->checkDate) : null;
        $model->dateOfFiling = $object->dateOfFiling ? Carbon::parse($object->dateOfFiling) : null;
        $model->dateOfTechnicalConcept = $object->dateOfTechnicalConcept ? Carbon::parse($object->dateOfTechnicalConcept) : null;
        $model->dateOfReportDelivery = $object->dateOfReportDelivery ? Carbon::parse($object->dateOfReportDelivery) : null;
        $model->dateOfReportFeedback = $object->dateOfReportFeedback ? Carbon::parse($object->dateOfReportFeedback) : null;
        $model->dateOfLetterGeneration = $object->dateOfLetterGeneration ? Carbon::parse($object->dateOfLetterGeneration) : null;
        $model->dateOfExpirationRecommendation = $object->dateOfExpirationRecommendation ? Carbon::parse($object->dateOfExpirationRecommendation) : null;
        $model->dateOfTrackingRecommendation = $object->dateOfTrackingRecommendation ? Carbon::parse($object->dateOfTrackingRecommendation) : null;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {
            $model->letterImagine = $object->sisalud;
            $model->accidentDateOf = $model->accidentDate;
            $model->accident_country_id = $model->country_id;
            $model->accident_state_id = $model->state_id;
            $model->accident_city_id = $model->city_id;

            // Creado por
            $model->status = 'open';
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        return CustomerInvestigationAl::find($model->id);
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
            if (!($model = CustomerInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->customerObservation = $object->customerObservation;
        $model->customerPrincipalZone = ($object->customerPrincipalZone != null) ? $object->customerPrincipalZone->value : null;
        $model->customerPrincipalEconomicActivity = ($object->customerPrincipalEconomicActivity != null) ? $object->customerPrincipalEconomicActivity->id : null;
        $model->customerPrincipalRiskClass = $object->customerPrincipalRiskClass ? $object->customerPrincipalRiskClass->value : null;
        $model->customerBranchEconomicActivity = ($object->customerBranchEconomicActivity != null) ? $object->customerBranchEconomicActivity->id : null;
        $model->customerBranchRiskClass = $object->customerBranchRiskClass ? $object->customerBranchRiskClass->value : null;


        $model->customer_branch_country_id = ($object->customerBranchCountry != null) ? $object->customerBranchCountry->id : null;
        $model->customer_branch_state_id = ($object->customerBranchState != null) ? $object->customerBranchState->id : null;
        $model->customer_branch_city_id = ($object->customerBranchCity != null) ? $object->customerBranchCity->id : null;
        $model->customerBranchZone = ($object->customerBranchZone != null) ? $object->customerBranchZone->value : null;


        $model->customerResponsibleHealth = $object->customerResponsibleHealth;

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

        return CustomerInvestigationAl::find($model->id);
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
            if (!($model = CustomerInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->employeeLinkType = ($object->employeeLinkType != null) ? $object->employeeLinkType->value : null;
        $model->employeeZone = ($object->employeeZone != null) ? $object->employeeZone->value : null;
        $model->employeeHabitualOccupation = $object->employeeHabitualOccupation;
        $model->employeeJobTask = $object->employeeJobTask;
        $model->employeeHabitualOccupationTime = $object->employeeHabitualOccupationTime;
        $model->employeeStartDate = $object->employeeStartDate ? Carbon::parse($object->employeeStartDate) : null;
        $model->employeeDuration = $object->employeeDuration;
        $model->employeeRegularWork = $object->employeeRegularWork;
        $model->employeeIsMissionWorker = $object->employeeIsMissionWorker;
        $model->employeeMissionCompanyName = $object->employeeMissionCompanyName;
        $model->employeeMissionSalary = $object->employeeMissionSalary;
        $model->employeeMissionWorkingDay = ($object->employeeMissionWorkingDay != null) ? $object->employeeMissionWorkingDay->value : null;
        $model->employeeClarification = $object->employeeClarification;

        $model->employeeGender = ($object->employeeGender != null) ? $object->employeeGender->value : null;
        $model->employee_country_id = ($object->employeeCountry != null) ? $object->employeeCountry->id : null;
        $model->employee_state_id = ($object->employeeState != null) ? $object->employeeState->id : null;
        $model->employee_city_id = ($object->employeeCity != null) ? $object->employeeCity->id : null;
        $model->employeeEPS = ($object->employeeEPS != null) ? $object->employeeEPS->value : null;
        $model->employeeAFP = ($object->employeeAFP != null) ? $object->employeeAFP->value : null;
        $model->employeeARL = ($object->employeeARL != null) ? $object->employeeARL->value : null;
        $model->employeeTelephone = $object->employeeTelephone;
        $model->employeeAddress = $object->employeeAddress;
        $model->employeeBirthday = $object->employeeBirthday ? Carbon::parse($object->employeeBirthday) : null;


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

        return CustomerInvestigationAl::find($model->id);
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
            if (!($model = CustomerInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->accidentDateOf = $object->accidentDateOf ? Carbon::parse($object->accidentDateOf) : null;
        $model->accidentWorkingDay = ($object->accidentWorkingDay != null) ? $object->accidentWorkingDay->value : null;
        $model->accidentIsRegularWork = ($object->accidentIsRegularWork != null) ? $object->accidentIsRegularWork->value : null;
        $model->accidentOtherRegularWorkText = $object->accidentOtherRegularWorkText;
        $model->accidentWorkTimeHour = $object->accidentWorkTimeHour;
        $model->accidentWorkTimeMinute = $object->accidentWorkTimeMinute;
        $model->accidentIsDeathCause = ($object->accidentIsDeathCause != null) ? $object->accidentIsDeathCause->value : null;


        $model->accident_country_id = $object->accidentCountry == null ? null : $object->accidentCountry->id;
        $model->accident_state_id = $object->accidentState == null ? null : $object->accidentState->id;
        $model->accident_city_id = $object->accidentCity == null ? null : $object->accidentCity->id;
        $model->accidentZone = ($object->accidentZone != null) ? $object->accidentZone->value : null;
        $model->accidentPlace = ($object->accidentPlace != null) ? $object->accidentPlace->value : null;
        $model->accidentInjuryType = ($object->accidentInjuryType != null) ? $object->accidentInjuryType->value : null;
        $model->accidentInjuryTypeText = $object->accidentInjuryTypeText;
        $model->accidentBodyPart = ($object->accidentBodyPart != null) ? $object->accidentBodyPart->value : null;
        $model->accidentAgent = ($object->accidentAgent != null) ? $object->accidentAgent->value : null;
        $model->accidentMechanism = ($object->accidentMechanism != null) ? $object->accidentMechanism->value : null;
        $model->accidentCategory = ($object->accidentCategory != null) ? $object->accidentCategory->value : null;

        $model->accidentCompanyTransport = $object->accidentCompanyTransport;
        $model->accidentAutoTransport = $object->accidentAutoTransport;

        $model->accidentReportDate = $object->accidentReportDate ? Carbon::parse($object->accidentReportDate) : null;
        $model->accidentReportMadeBy = $object->accidentReportMadeBy;
        $model->accidentReportJob = $object->accidentReportJob;
        $model->accidentReportClarification = $object->accidentReportClarification;

        $model->accidentPlaceText = $object->accidentPlaceText;
        $model->accidentBodyPartText = $object->accidentBodyPartText;
        $model->accidentAgentText = $object->accidentAgentText;
        $model->accidentMechanismText = $object->accidentMechanismText;

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

        return CustomerInvestigationAl::find($model->id);
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
            if (!($model = CustomerInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->registerDate = $object->registerDate ? Carbon::parse($object->registerDate)->addHour() : null;
        $model->place = $object->place;
        $model->address = $object->address;
        $model->realizedBy = $object->realizedBy;
        $model->reviewedBy = $object->reviewedBy;

        $model->toWhom = $object->toWhom;
        $model->toWhomJob = $object->toWhomJob;
        $model->agrResponsible = $object->agrResponsible;
        $model->riskManager = $object->riskManager;
        //$model->reportDateLetter = $object->reportDateLetter ? Carbon::parse($object->reportDateLetter) : null;

        $model->letterCountry = $object->letterCountry;
        $model->letterState = $object->letterState;
        $model->letterDnprl = $object->letterDnprl;
        $model->letterCity = $object->letterCity;
        $model->letterElaborationDate = $object->letterElaborationDate;
        $model->letterTreatment = $object->letterTreatment;
        $model->letterShippingAddress = $object->letterShippingAddress;
        $model->letterShippingCity = $object->letterShippingCity;
        $model->letterSignedBy = $object->letterSignedBy;
        $model->letterJobSignedBy = $object->letterJobSignedBy;
        $model->letterImagine = $object->letterImagine;
        $model->letterShippingPhone = $object->letterShippingPhone;

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

        return CustomerInvestigationAl::find($model->id);
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
            if (!($model = CustomerInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAl();
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

        return CustomerInvestigationAl::find($model->id);
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
            if (!($model = CustomerInvestigationAl::find($object->id))) {
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

        return CustomerInvestigationAl::find($model->id);
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
            if (!($model = CustomerInvestigationAl::find($object->id))) {
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

        return CustomerInvestigationAl::find($model->id);
    }

    public static function  fillAndSaveCauseModel($object)
    {
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerInvestigationAl::find($object->id))) {
                return null;
            }
        }

        $model->insecureActObservation = $object->insecureActObservation;
        $model->insecureConditionObservation = $object->insecureConditionObservation;
        $model->workFactorObservation = $object->workFactorObservation;
        $model->personalFactorObservation = $object->personalFactorObservation;
        $model->causeObservation = $object->observation;

        // actualizado por
        $model->updatedBy = $userAdmn->id;

        // Guarda
        $model->save();

        // Actualiza timestamp
        $model->touch();

        CustomerInvestigationAlCauseDTO::bulkInsert($object->insecureActList, $model->id);
        CustomerInvestigationAlCauseDTO::bulkInsert($object->insecureConditionList, $model->id);
        CustomerInvestigationAlCauseDTO::bulkInsert($object->workFactorList, $model->id);
        CustomerInvestigationAlCauseDTO::bulkInsert($object->personalFactorList, $model->id);

        return CustomerInvestigationAl::find($model->id);
    }

    public static function  fillAndSaveActivateEntity($object)
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
            if (!($model = CustomerInvestigationAl::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->comment = $object->comment;
        $model->status = "open";


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

        return CustomerInvestigationAl::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {

        // parse model
        if ($model) {
            $this->setInfo($model, $fmt_response);
        }

        return $this;
    }

    private function parseCauseModel($model, $fmt_response = "1")
    {

        // parse model
        if ($model) {
            $this->getBasicInfoCause($model);
        }

        return $this;
    }

    public static function parse($info, $fmt_response = "1")
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerInvestigationAl) {
                    $parsed[] = (new CustomerInvestigationAlDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAl) {
            return (new CustomerInvestigationAlDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlDTO();
            }
        }
    }

    public static function parseCause($info, $fmt_response = "1")
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerInvestigationAl) {
                    $parsed[] = (new CustomerInvestigationAlDTO())->parseCauseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAl) {
            return (new CustomerInvestigationAlDTO())->parseCauseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlDTO();
            }
        }
    }

    private function getUserSsession()
    {
        if (!Auth::check())
            return null;

        return Auth::getUser();
    }

    private function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }
}
