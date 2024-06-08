<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerConfigWorkPlace;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcesses;
use Wgroup\Models\Customer;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerConfigWorkPlaceDTO
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
     * @param $model : Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->country = $model->country;
        $this->state = $model->state;
        $this->town = $model->town;
        $this->name = $model->name;
        $this->type = $model->getType();
        $this->status = $model->getStatus();
        $this->directEmployees = 0;
        $this->risk1 = $model->risk1;
        $this->risk2 = $model->risk2;
        $this->risk3 = $model->risk3;
        $this->risk4 = $model->risk4;
        $this->risk5 = $model->risk5;
        $this->createdBy = $model->creator ? $model->creator->name : null;
        $this->updatedBy = $model->updater ? $model->updater->name : null;
        $this->created_at = $model->created_at ? $model->created_at->format('d/m/Y') : null;
        $this->updated_at = $model->updated_at ? $model->updated_at->format('d/m/Y') : null;

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
            if (!($model = CustomerConfigWorkPlace::find($object->id))) {
                // No existe
                $model = new CustomerConfigWorkPlace();
                $isEdit = false;
            }
        } else {
            $model = new CustomerConfigWorkPlace();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->customer_id = $object->customerId;
        $model->country_id = !isset($object->country->id) ? null : $object->country->id;
        // Departamento
        $model->state_id = !isset($object->state->id) ? null : $object->state->id;
        // Municipio
        $model->city_id = !isset($object->town->id) ? null : $object->town->id;


        $model->name = $object->name;
        $model->status = $object->status->value == null ? null : $object->status->value;
        $model->type = $object->type->value == null ? null : $object->type->value;
        $model->directEmployees = 0;
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

            if ($model->type == 'PCS') {
                $modelMP = new CustomerConfigMacroProcesses();

                $modelMP->name = 'GENERAL';
                $modelMP->customer_id = $object->customerId;
                $modelMP->workplace_id = $model->id;
                $modelMP->status = "Activo";
                $modelMP->createdBy = $userAdmn->id;
                $modelMP->updatedBy = $userAdmn->id;

                // Guarda
                $modelMP->save();
            }

        }

        return CustomerConfigWorkPlace::find($model->id);

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
                if ($model instanceof CustomerConfigWorkPlace) {
                    $parsed[] = (new CustomerConfigWorkPlaceDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerConfigWorkPlaceDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerConfigWorkPlace) {
            return (new CustomerConfigWorkPlaceDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerConfigWorkPlaceDTO();
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
