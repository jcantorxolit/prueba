<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInternalCertificateGrade;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerInternalCertificateGradeAgent\CustomerInternalCertificateGradeAgentDTO;
use Wgroup\CustomerInternalCertificateGradeCalendar\CustomerInternalCertificateGradeCalendarDTO;
use Mail;
use Wgroup\CertificateProgram\CertificateProgram;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use DB;
use Wgroup\CustomerInternalCertificateProgram\CustomerInternalCertificateProgramDTO;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerProject;
use Request;

/**
 * Description of CustomerInternalCertificateGradeDTO
 *
 * @author jdblandon
 */
class CustomerInternalCertificateGradeDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoSummary($model);
                break;

            default:
                $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model: Modelo CustomerInternalCertificateGrade
     */
    private function getBasicInfo($model) {

        $this->id = $model->id;
        $this->program = CustomerInternalCertificateProgramDTO::parse($model->program);
        $this->code = $model->code;
        $this->name = $model->name;
        $this->location = $model->getLocation();
        $this->description = $model->description;
        $this->status = $model->getStatus();
        $this->registered = $model->getParticipantsCount();
        $this->quota = $model->program->capacity - $this->registered;
        //$this->path = Request::path();
        //$this->url = Request::url();

        $this->agents = CustomerInternalCertificateGradeAgentDTO::parse($model->agents);
        $this->calendar = CustomerInternalCertificateGradeCalendarDTO::parse($model->calendar);

        $this->tokensession = $this->getTokenSession(true);

    }

    private function getBasicInfoSummary($model)
    {

    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerInternalCertificateGrade::find($object->id))) {
                // No existe
                $model = new CustomerInternalCertificateGrade();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInternalCertificateGrade();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->customer_internal_certificate_program_id = $object->program->id;

        if ($object->code == "" || $object->code = 0) {
            $code = CustomerInternalCertificateGrade::max(DB::raw("CAST(code AS UNSIGNED)"));
            $model->code = str_pad(($code + 1), 3, "0", STR_PAD_LEFT);
        }

        $model->name = $object->name;
        $model->location = $object->location == null ? null : $object->location->value;
        $model->description = $object->description;
        $model->status = $object->status == null ? null : $object->status->value;

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

        /*$customer = Customer::where('documentNumber', '900.272.469')->first();

        if ($customer == null) {
            $customer = new Customer();
            $customer->businessName = "EASTAV - ALTURAS";
            $customer->economicActivity = "CONSULTORIA SG-SST";
            $customer->status = 1;
            $customer->type = "PA";
            $customer->documentType ="m";
            $customer->documentNumber = "900.272.469";
            $customer->country_id = 68;
            $customer->state_id = 230;
            $customer->city_id = 82;
            $customer->webSite = "www.waygroupsa.com";
            $customer->directEmployees = 1;
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;
            $customer->save();
        }

        if (empty($model->customer_project_id)) {
            $project = new CustomerProject();
            $project->customer_id = $customer->id;
            $project->type = "Consultoria";
            $project->name = "Curso ".$model->name ;
            $project->description = $model->description;
            $project->serviceOrder = "";
            $project->defaultSkill = $object->program->speciality->value;
            $project->estimatedHours = $object->program->hourDuration;
            $project->deliveryDate = Carbon::now('America/Bogota');
            $project->isRecurrent = 0;
            $project->status = "activo";//$object->status->value == "-S-" ? null : $object->status->value;
            $project->isBilled = 0;//$object->status->value == "-S-" ? null : $object->status->value;
            $project->invoiceNumber ="";//$object->status->value == "-S-" ? null : $object->status->value;
            $project->save();

            $model->customer_project_id = $project->id;
            $model->save();
        }*/


        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        if ($object->calendar) {

            $object->id = $model->id;

            CustomerInternalCertificateGradeCalendarDTO::bulkInsert($object);
        }

        if ($object->agents) {

            $object->id = $model->id;

            CustomerInternalCertificateGradeAgentDTO::bulkInsert($object);
        }


        return CustomerInternalCertificateGrade::find($model->id);
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

    private function parseArray($model, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoSummary($model);
                break;
            default:
                $this->getBasicInfo($model);
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
                if ($model instanceof CustomerInternalCertificateGrade) {
                    $parsed[] = (new CustomerInternalCertificateGradeDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CustomerInternalCertificateGradeDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInternalCertificateGrade) {
            return (new CustomerInternalCertificateGradeDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInternalCertificateGradeDTO();
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
