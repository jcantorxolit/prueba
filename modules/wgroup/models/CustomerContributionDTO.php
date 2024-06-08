<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerController;
use Wgroup\Models\Customer;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerContributionDTO {

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
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfo($model) {
        /*
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->type = $model->getTrackingType();
        $this->agent = $model->agent->name;
        $this->observation = $model->observation;
        $this->status = $model->getStatusType();
        $this->eventDateTime = Carbon::parse($model->eventDateTime)->format('d/m/Y H:i:s');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        */

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->year = $model->year;
        $this->month = $model->getMonth();
        $this->input = $model->input;
        $this->percentReinvestmentARL = $model->percent_reinvestment_arl;
        $this->percentReinvestmentWG = $model->percent_reinvestment_wg;

        $this->reinvestmentARL = $model->input * $model->percent_reinvestment_arl / 100;
        $this->reinvestmentWG = $model->input * $model->percent_reinvestment_wg / 100;
        $this->total = $this->reinvestmentARL + $this->reinvestmentWG;

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
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
            if (!($model = CustomerContribution::find($object->id))) {
                // No existe
                $model = new CustomerContribution();
                $isEdit = false;
            }
        } else {
            $model = new CustomerContribution();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->year = $object->year;
        $model->month = $object->month->value == "-S-" ? null : $object->month->value;
        $model->input = $object->input;
        $model->percent_reinvestment_arl = $object->percentReinvestmentARL;
        $model->percent_reinvestment_wg = $object->percentReinvestmentWG;

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

        return CustomerContribution::find($model->id);
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
            return $data;
        } else if ($info instanceof CustomerContribution) {
            return (new CustomerContributionDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerContributionDTO();
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
