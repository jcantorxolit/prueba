<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerAbsenteeismIndicatorTarget;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Wgroup\Models\Customer;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerAbsenteeismIndicatorTargetDTO {

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
     * @param $model: Modelo CustomerAbsenteeismIndicatorTarget
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->period = $model->getPeriod();
        $this->targetIF = $model->targetIF;
        $this->targetIS = $model->targetIS;
        $this->targetILI = $model->targetILI;
        $this->targetEvent = $model->targetEvent;
        $this->targetDay = $model->targetDay;

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
            if (!($model = CustomerAbsenteeismIndicatorTarget::find($object->id))) {
                // No existe
                $model = new CustomerAbsenteeismIndicatorTarget();
                $isEdit = false;
            }
        } else {
            $model = new CustomerAbsenteeismIndicatorTarget();
            $isEdit = false;
        }

        $periodDate = $object->period->item."-01";

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->period = $object->period->value == "-S-" ? null : $object->period->value;
        $model->periodDate = Carbon::parse($periodDate);
        $model->targetIF = $object->targetIF;
        $model->targetIS = $object->targetIS;
        $model->targetILI = $object->targetILI;
        $model->targetEvent = $object->targetEvent;
        $model->targetDay = $object->targetDay;

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

        return CustomerAbsenteeismIndicatorTarget::find($model->id);
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
                if ($model instanceof CustomerAbsenteeismIndicatorTarget) {
                    $parsed[] = (new CustomerAbsenteeismIndicatorTargetDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerAbsenteeismIndicatorTarget) {
            return (new CustomerAbsenteeismIndicatorTargetDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerAbsenteeismIndicatorTargetDTO();
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
