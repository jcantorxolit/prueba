<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInvestigationAlControl;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerInvestigationAlControlDTO {

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
     * @param $model: Modelo CustomerInvestigationAlControl
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerInvestigationId = $model->customer_investigation_id;
        $this->controlType = $model->getControlType();
        $this->located = $model->located;
        $this->status = $model->status;
        $this->source = $model->source;

        if ($model->dateValue != null) {
            $this->dateValue =  Carbon::parse($model->dateValue)->timezone('America/Bogota');
            $this->dateValueFormat =  Carbon::parse($model->dateValue)->timezone('America/Bogota')->format('d/m/Y');
        }

        if ($model->dateLocated != null) {
            $this->dateLocated =  Carbon::parse($model->dateLocated)->timezone('America/Bogota');
            $this->dateLocatedFormat =  Carbon::parse($model->dateLocated)->timezone('America/Bogota')->format('d/m/Y');
        }

        if ($model->creator != null) {
            $this->creator = $model->creator->name;
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
            if (!($model = CustomerInvestigationAlControl::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAlControl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAlControl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_investigation_id = $object->customerInvestigationId;
        $model->controlType = $object->controlType == null ? null : $object->controlType->value;
        $model->dateValue = $object->dateValue ? Carbon::parse($object->dateValue) : null;
        $model->located = $object->located;
        $model->dateLocated = null;
        if ($object->dateLocated != null) {
            $model->dateLocated = $object->dateLocated ? Carbon::parse($object->dateLocated) : null;
        }
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
            $model->source = "Manual";
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        return CustomerInvestigationAlControl::find($model->id);
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
                if ($model instanceof CustomerInvestigationAlControl) {
                    $parsed[] = (new CustomerInvestigationAlControlDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAlControl) {
            return (new CustomerInvestigationAlControlDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlControlDTO();
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
