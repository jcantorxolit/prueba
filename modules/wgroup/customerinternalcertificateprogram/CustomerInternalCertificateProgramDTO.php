<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInternalCertificateProgram;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerInternalCertificateProgramRequirement\CustomerInternalCertificateProgramRequirementDTO;
use Wgroup\CustomerInternalCertificateProgramSpeciality\CustomerInternalCertificateProgramSpecialityDTO;
use Mail;
use DB;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerProject;

/**
 * Description of CustomerInternalCertificateProgramDTO
 *
 * @author jdblandon
 */
class CustomerInternalCertificateProgramDTO {

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
     * @param $model: Modelo CustomerInternalCertificateProgram
     */
    private function getBasicInfo($model) {

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->code = $model->code;
        $this->name = $model->name;
        $this->amount = $model->amount;
        $this->currency = $model->getCurrency();
        $this->category = $model->getCategory();
        $this->speciality = $model->getSpeciality();
        $this->capacity = $model->capacity;
        $this->hourDuration = $model->hourDuration;
        $this->validityNumber = $model->validityNumber;
        $this->validityType = $model->getValidityType();
        $this->authorizationResolution = $model->authorizationResolution;
        $this->authorizingEntity = $model->authorizingEntity;
        $this->description = $model->description;
        $this->captionHeader = $model->captionHeader;
        $this->captionFooter = $model->captionFooter;
        $this->isActive = $model->getStatus();
        $this->specialities = CustomerInternalCertificateProgramSpecialityDTO::parse($model->specialities);
        $this->requirements = CustomerInternalCertificateProgramRequirementDTO::parse($model->requirements);
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
            if (!($model = CustomerInternalCertificateProgram::find($object->id))) {
                // No existe
                $model = new CustomerInternalCertificateProgram();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInternalCertificateProgram();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        if ($object->code == "" || $object->code = 0) {
            $code = CustomerInternalCertificateProgram::max(DB::raw("CAST(code AS UNSIGNED)"));
            $model->code = str_pad(($code + 1), 3, "0", STR_PAD_LEFT);
        }

        $model->customer_id = $object->customerId;
        $model->name = $object->name;
        $model->amount = $object->amount;
        $model->currency = $object->currency == null ? null : $object->currency->value;
        $model->category = $object->category == null ? null : $object->category->value;
        $model->speciality = $object->speciality == null ? null : $object->speciality->value;
        $model->capacity = $object->capacity;
        $model->hourDuration = $object->hourDuration;
        $model->validityNumber = $object->validityNumber;
        $model->validityType = $object->validityType == null ? null : $object->validityType->value;
        $model->authorizationResolution = $object->authorizationResolution;
        $model->authorizingEntity = $object->authorizingEntity;
        $model->description = $object->description;
        $model->isActive = $object->isActive;
        $model->captionHeader = $object->captionHeader;
        $model->captionFooter = $object->captionFooter;

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

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        if ($object->specialities) {

            $object->id = $model->id;

            CustomerInternalCertificateProgramSpecialityDTO::bulkInsert($object);
        }

        if ($object->requirements) {

            $object->id = $model->id;

            CustomerInternalCertificateProgramRequirementDTO::bulkInsert($object);
        }


        return CustomerInternalCertificateProgram::find($model->id);
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
                if ($model instanceof CustomerInternalCertificateProgram) {
                    $parsed[] = (new CustomerInternalCertificateProgramDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CustomerInternalCertificateProgramDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInternalCertificateProgram) {
            return (new CustomerInternalCertificateProgramDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInternalCertificateProgramDTO();
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
