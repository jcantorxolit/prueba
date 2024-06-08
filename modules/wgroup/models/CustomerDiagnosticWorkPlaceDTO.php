<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\Models\Customer;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerDiagnosticWorkPlaceDTO {

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
     * @param $model: Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model) {
        $this->id = $model->id;
        $this->diagnosticId = $model->diagnostic_id;
        $this->country = $model->country;
        $this->state = $model->state;
        $this->town = $model->town;
        $this->activity = $model->activity;
        $this->risk = $model->getRisk();
        $this->area = $model->getArea();
        $this->status = $model->getStatus();
        $this->directEmployees = 0;
        $this->contact = $model->contact;
        $this->risk1 = $model->risk1;
        $this->risk2 = $model->risk2;
        $this->risk3 = $model->risk3;
        $this->risk4 = $model->risk4;
        $this->risk5 = $model->risk5;
        $this->createdBy = $model->creator->name;
        $this->updatedBy = $model->updater->name;
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
            if (!($model = CustomerDiagnosticWorkPlace::find($object->id))) {
                // No existe
                $model = new CustomerDiagnosticWorkPlace();
                $isEdit = false;
            }
        } else {
            $model = new CustomerDiagnosticWorkPlace();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->diagnostic_id = $object->diagnosticId;
        $model->country_id = !isset($object->country->id) ? null : $object->country->id;
        // Departamento
        $model->state_id = !isset($object->state->id) ? null : $object->state->id;
        // Municipio
        $model->city_id = !isset($object->town->id) ? null : $object->town->id;


        $model->activity = $object->activity;//$object->activity->value == "-S-" ? null : $object->activity->value;
        //$model->area = $object->area->value == "-S-" ? null : $object->area->value;
        $model->risk = $object->risk->value == "-S-" ? null : $object->risk->value;
        //$model->status = $object->status->value == "-S-" ? null : $object->status->value;
        $model->directEmployees = 0;
        //$model->contact = $object->contact;
        $model->risk1 = $object->risk1;
        $model->risk2 = $object->risk2;
        $model->risk3 = $object->risk3;
        $model->risk4 = $object->risk4;
        $model->risk5 = $object->risk5;

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

        return CustomerDiagnosticWorkPlace::find($model->id);

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
                $this->getReportSummaryPrg($model);
                break;
            case "3":
                $this->getReportSummaryAdv($model);
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
                if ($model instanceof CustomerDiagnosticWorkPlace) {
                    $parsed[] = (new CustomerDiagnosticWorkPlaceDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDiagnosticWorkPlaceDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDiagnosticWorkPlace) {
            return (new CustomerDiagnosticWorkPlaceDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDiagnosticWorkPlaceDTO();
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
