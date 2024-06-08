<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerAbsenteeismIndicator;

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
class CustomerAbsenteeismIndicatorDTO {

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
     * @param $model: Modelo CustomerAbsenteeismIndicator
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->classification = $model->getClassification();
        $this->period = $model->getPeriod();
        $this->workCenter = $model->getWorkCenter();
        $this->workCenterText = $this->workCenter != null ? $this->workCenter->name : "";
        $this->manHoursWorked = $model->manHoursWorked;
        $this->population = $model->population;
        $this->directCost = $model->directCost;
        $this->indirectCost = $model->indirectCost;
        $this->eventNumber = $model->eventNumber;
        $this->targetEvent = $model->targetEvent;
        $this->diseaseRate = $model->diseaseRate;
        $this->disabilityDays = $model->disabilityDays;
        $this->targetDisabilityDays = $model->targetDisabilityDays;
        $this->targetFrequency = $model->targetFrequency;
        $this->targetFrequencyIndex = $model->targetFrequencyIndex;
        $this->frequencyIndex = $model->frequencyIndex;
        $this->targetSeverity = $model->targetSeverity;
        $this->targetSeverityIndex = $model->targetSeverityIndex;
        $this->severityIndex = $model->severityIndex;
        $this->targetWorkAccident = $model->targetWorkAccident;
        $this->disablingInjuriesIndex = $model->disablingInjuriesIndex;

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
            if (!($model = CustomerAbsenteeismIndicator::find($object->id))) {
                // No existe
                $model = new CustomerAbsenteeismIndicator();
                $isEdit = false;
            }
        } else {
            $model = new CustomerAbsenteeismIndicator();
            $isEdit = false;
        }

        $periodDate = $object->period->item."-01";

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->classification = $object->classification->value == "-S-" ? null : $object->classification->value;
        $model->period = $object->period->value == "-S-" ? null : $object->period->value;
        $model->workCenter = $object->workCenter->id == "-S-" ? null : $object->workCenter->id;
        $model->periodDate = Carbon::parse($periodDate);
        $model->manHoursWorked = $object->manHoursWorked;
        $model->population = $object->population;
        $model->directCost = $object->directCost;
        $model->eventNumber = $object->eventNumber;
        $model->targetEvent = $object->targetEvent;
        $model->diseaseRate = $object->diseaseRate;
        $model->disabilityDays = $object->disabilityDays;
        $model->targetDisabilityDays = $object->targetDisabilityDays;

        $model->targetFrequency = $object->targetFrequency;
        $model->targetFrequencyIndex = $object->targetFrequencyIndex;
        $model->frequencyIndex = $object->frequencyIndex;
        $model->targetSeverity = $object->targetSeverity;
        $model->targetSeverityIndex = $object->targetSeverityIndex;
        $model->severityIndex = $object->severityIndex;
        $model->targetWorkAccident = $object->targetWorkAccident;
        $model->disablingInjuriesIndex = $object->disablingInjuriesIndex;


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

        return CustomerAbsenteeismIndicator::find($model->id);
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
                if ($model instanceof CustomerAbsenteeismIndicator) {
                    $parsed[] = (new CustomerAbsenteeismIndicatorDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerAbsenteeismIndicator) {
            return (new CustomerAbsenteeismIndicatorDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerAbsenteeismIndicatorDTO();
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
