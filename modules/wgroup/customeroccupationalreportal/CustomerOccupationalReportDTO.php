<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerOccupationalReportAl;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Wgroup\CustomerConfigJob\CustomerConfigJobDTO;
use Wgroup\CustomerConfigJobData\CustomerConfigJobData;
use Wgroup\CustomerConfigJobData\CustomerConfigJobDataDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\CustomerOccupationalReportAlBody\CustomerOccupationalReportBodyDTO;
use Wgroup\CustomerOccupationalReportAlFactor\CustomerOccupationalReportFactorDTO;
use Wgroup\CustomerOccupationalReportAlLesion\CustomerOccupationalReportLesionDTO;
use Wgroup\CustomerOccupationalReportAlMechanism\CustomerOccupationalReportMechanismDTO;
use Wgroup\CustomerOccupationalReportAlWitness\CustomerOccupationalReportWitnessDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnosticDTO;
use Wgroup\Models\Customer;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerOccupationalReportDTO
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
     * @param $model : Modelo CustomerOccupationalReport
     */
    private function getBasicInfo($model)
    {

        //Codigo
        $this->id = $model->id;

        $this->employee = CustomerEmployeeDTO::parse($model->employee);

        $this->typeLinkage = $model->getTypeLinkage();
        $this->firstLastName = $model->first_lastname;
        $this->secondLastName = $model->second_lastname;
        $this->firstName = $model->first_name;
        $this->secondName = $model->second_name;
        $this->documentType = $model->getDocumentType();
        $this->documentNumber = $model->document_number;
        $this->birthDate = Carbon::parse($model->birthdate);
        $this->gender = $model->getGender();
        $this->address = $model->address;
        $this->telephone = $model->telephone;
        $this->fax = $model->fax;
        $this->state = $model->state;
        $this->city = $model->town;
        $this->zone = $model->getZone();
        $this->job = $this->employee->job; //CustomerConfigJobDataDTO::parse(CustomerConfigJobData::find($model->job));
        $this->occupation = $model->occupation;
        $this->occupationTimeDay = $model->occupation_time_day;
        $this->occupationTimeMonth = $model->occupation_time_month;
        $this->startDate = Carbon::parse($model->start_date);
        $this->salary = $model->salary;
        $this->workingDay = $model->getWorkingDay();
        $this->eps = $model->getEps();
        $this->arl = $model->getArl();
        $this->isAfp = $model->getIsAfp();
        $this->afp = $model->getAfp();
        $this->customerEmploymentRelationship = $model->getCustomerEmploymentRelationship();
        $this->customerEconomicActivity = $model->getCustomerEconomicActivity();
        $this->customerBusinessName = $model->customer_business_name;
        $this->customerDocumentType = $model->getCustomerDocumentType();
        $this->customerDocumentNumber = $model->customer_document_number;
        $this->customerAddress = $model->customer_address;
        $this->customerEmail = $model->customer_email;
        $this->customerTelephone = $model->customer_telephone;
        $this->customerFax = $model->customer_fax;
        $this->customerState = $model->customerState;
        $this->customerCity = $model->customerTown;
        $this->customerZone = $model->getCustomerZone();
        $this->isCustomerBranchName = $model->getIsCustomerBranchName();
        $this->customerBranchEconomicActivity = $model->getCustomerBranchEconomicActivity();
        $this->customerBranchAddress = $model->customer_branch_address;
        $this->customerBranchTelephone = $model->customer_branch_telephone;
        $this->customerBranchFax = $model->customer_branch_fax;
        //TODO
        $this->customerBranchState = $model->customerBranchState;
        $this->customerBranchrCity = $model->customerBranchTown;
        $this->customerBranchZone = $model->getCustomerBranchZone();

        $this->accidentDate = Carbon::parse($model->accident_date);
        $this->accidentWeekDay = $model->getAccidentWeekDay();
        $this->accidentWorkingDay = $model->getAccidentWorkingDay();
        $this->accidentRegularWork = $model->getAccidentRegularWork();
        $this->accidentRegularWorkText = $model->getAccidentRegularWorkText();
        $this->accidentWorkTime = $model->accident_work_time;
        $this->accidentType = $model->getAccidentType();
        $this->accidentDeathCause = $model->getAccidentDeathCause();
        $this->accidentState = $model->accidentState;
        $this->accidentCity = $model->accidentTown;
        $this->accidentZone = $model->getAccidentZone();
        $this->accidentLocation = $model->getAccidentLocation();
        $this->accidentPlace = $model->getAccidentPlace();
        $this->accidentLesionDescription = $model->accident_lesion_description;
        $this->accidentBodyPartDescription = $model->accident_body_part_description;
        $this->accidentMechanismDescription = $model->accident_mechanism_description;
        $this->accidentDescription = $model->accident_description;
        $this->isAccidentWitness = $model->getIsAccidentWitness();
        $this->date = Carbon::parse($model->report_date);
        $this->responsibleName = $model->report_responsible_name;
        $this->responsibleDocumentType = $model->getResponsibleDocumentType();
        $this->responsibleDocumentNumber = $model->report_responsible_document_number;
        $this->responsibleDocumentJob = $model->report_responsible_job;
        $this->status = $model->status;

        $this->lesions = $model->getLesions();
        $this->bodies = $model->getBodies();
        $this->factors = $model->getFactors();
        $this->mechanisms = $model->getMechanisms();
        //var_dump($model->getWitnesses());
        $this->witnesses = CustomerOccupationalReportWitnessDTO::parse($model->getWitnesses());

        $this->review = null;

        if ($model->employee != null) {
            $this->review = "Nombre: " . $this->employee->entity->fullName . " | Fecha accidente:" . Carbon::parse($model->accident_date)->format('d/m/Y H:i') . " | Tipo accidente:" . $this->accidentType->item;
            //$this->review = "Nombre: " . $this->employee->entity->fullName;
        }

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
            if (!($model = CustomerOccupationalReport::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalReport();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalReport();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->employee->customerId;
        $model->employee_id = $object->employee->entity->id;
        $model->customer_employee_id = $object->employee->id;
        $model->type_linkage = $object->typeLinkage != null ? $object->typeLinkage->value : null;
        $model->first_lastname = $object->firstLastName;
        $model->second_lastname = $object->secondLastName;
        $model->first_name = $object->firstName;
        $model->second_name = $object->secondName;
        $model->document_type = $object->documentType != null ? $object->documentType->value : null;
        $model->document_number = $object->documentNumber;
        $model->birthdate = Carbon::parse($object->birthDate)->timezone('America/Bogota');
        $model->gender = $object->gender != null ? $object->gender->value : null;
        $model->address = $object->address;
        $model->telephone = $object->telephone;
        $model->fax = $object->fax;
        $model->state_id = $object->state != null ? $object->state->id : null;
        $model->city_id = $object->city != null ? $object->city->id : null;
        $model->zone = $object->zone != null ? $object->zone->value : null;
        $model->job = $object->job != null ? $object->job->job->id : null;
        //$model->occupation = $object->occupation != null ? $object->occupation->id : null;
        $model->occupation = $object->occupation;
        $model->occupation_time_day = $object->occupationTimeDay;
        $model->occupation_time_month = $object->occupationTimeMonth;
        $model->start_date = Carbon::parse($object->startDate)->timezone('America/Bogota');
        $model->salary = $object->salary;
        $model->working_day = $object->workingDay != null ? $object->workingDay->value : null;
        $model->eps = $object->eps != null ? $object->eps->value : null;
        $model->arl = $object->arl != null ? $object->arl->value : null;
        $model->is_afp = $object->isAfp != null ? $object->isAfp->value : null;
        $model->afp = $object->afp != null ? $object->afp->value : null;
        $model->customer_type_employment_relationship = $object->customerEmploymentRelationship != null ? $object->customerEmploymentRelationship->value : null;
        $model->customer_economic_activity = $object->customerEconomicActivity != null ? $object->customerEconomicActivity->id : null;
        $model->customer_business_name = $object->customerBusinessName;
        $model->customer_document_type = $object->customerDocumentType != null ? $object->customerDocumentType->value : null;
        $model->customer_document_number = $object->customerDocumentNumber;
        $model->customer_address = $object->customerAddress;
        $model->customer_email = $object->customerEmail;
        $model->customer_telephone = $object->customerTelephone;
        $model->customer_fax = $object->customerFax;
        $model->customer_state_id = $object->customerState != null ? $object->customerState->id : null;
        $model->customer_city_id = $object->customerCity != null ? $object->customerCity->id : null;
        $model->customer_zone = $object->customerZone != null ? $object->customerZone->value : null;
        $model->customer_branch_economic__activity = $object->customerBranchEconomicActivity != null ? $object->customerBranchEconomicActivity->value : null;
        $model->customer_branch_address = $object->customerBranchAddress;
        $model->customer_branch_telephone = $object->customerBranchTelephone;
        $model->customer_branch_fax = $object->customerBranchFax;
        //TODO
        //$model->customer_branch_state_id = $object->customerBranchState != null ? $object->customerBranchState->id : null;
        //$model->customer_branch_city_id = $object->customerBranchrCity != null ? $object->customerBranchrCity->id : null;
        //$model->customer_branch_zone = $object->customerBranchZone != null ? $object->customerBranchZone->value : null;
        //$model->is_customer_branch_same = $object->isCustomerBranchName != null ? $object->isCustomerBranchName->value : null;

        $model->accident_date = Carbon::parse($object->accidentDate)->timezone('America/Bogota');
        $model->accident_week_day = $object->accidentWeekDay != null ? $object->accidentWeekDay->value : null;
        $model->accident_working_day = $object->accidentWorkingDay != null ? $object->accidentWorkingDay->value : null;
        $model->accident_regular_work = $object->accidentRegularWork != null ? $object->accidentRegularWork->value : null;
        //$model->accident_regular_work_text = $object->accidentRegularWorkText != null ? $object->accidentRegularWorkText->value : null;
        $model->accident_regular_work_text = $object->accidentRegularWorkText != null ? $object->accidentRegularWorkText->id : null;
        $model->accident_work_time = $object->accidentWorkTime;
        $model->accident_type = $object->accidentType != null ? $object->accidentType->value : null;
        $model->accident_death_cause = $object->accidentDeathCause != null ? $object->accidentDeathCause->value : null;
        $model->accident_state_id = $object->accidentState != null ? $object->accidentState->id : null;
        $model->accident_city_id = $object->accidentCity != null ? $object->accidentCity->id : null;
        $model->accident_zone = $object->accidentZone != null ? $object->accidentZone->value : null;
        $model->accident_location = $object->accidentLocation != null ? $object->accidentLocation->value : null;
        $model->accident_place = $object->accidentPlace != null ? $object->accidentPlace->value : null;
        $model->accident_lesion_description = $object->accidentLesionDescription;
        $model->accident_body_part_description = $object->accidentBodyPartDescription;
        $model->accident_mechanism_description = $object->accidentMechanismDescription;
        $model->accident_description = $object->accidentDescription;
        $model->is_accident_witness = $object->isAccidentWitness != null ? $object->isAccidentWitness->value : null;
        $model->report_date = Carbon::parse($object->date)->timezone('America/Bogota');
        $model->report_responsible_name = $object->responsibleName;
        $model->report_responsible_document_type = $object->responsibleDocumentType != null ? $object->responsibleDocumentType->value : null;
        $model->report_responsible_document_number = $object->responsibleDocumentNumber;
        $model->report_responsible_job = $object->responsibleDocumentJob;
        $model->status = $object->status;


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

        CustomerOccupationalReportBodyDTO::bulkInsert($object->bodies, $model->id);
        CustomerOccupationalReportFactorDTO::bulkInsert($object->factors, $model->id);
        CustomerOccupationalReportLesionDTO::bulkInsert($object->lesions, $model->id);
        CustomerOccupationalReportMechanismDTO::bulkInsert($object->mechanisms, $model->id);
        CustomerOccupationalReportWitnessDTO::bulkInsert($object->witnesses, $model->id);


        return CustomerOccupationalReport::find($model->id);
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
                if ($model instanceof CustomerOccupationalReport) {
                    $parsed[] = (new CustomerOccupationalReportDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerOccupationalReport) {
            return (new CustomerOccupationalReportDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerOccupationalReportDTO();
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
