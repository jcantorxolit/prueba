<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerConfigMacroProcesses;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlaceDTO;
use Wgroup\Models\Customer;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerConfigMacroProcessesDTO {

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
        $this->customerId = $model->customer_id;
        $this->workplace = CustomerConfigWorkPlaceDTO::parse($model->workplace);
        $this->workplaceText = $this->workplace == null ? '' : $this->workplace->name;
        $this->name = $model->name;
        $this->status = $model->getStatus();
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
            if (!($model = CustomerConfigMacroProcesses::find($object->id))) {
                // No existe
                $model = new CustomerConfigMacroProcesses();
                $isEdit = false;
            }
        } else {
            $model = new CustomerConfigMacroProcesses();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->customer_id = $object->customerId;

        $model->name = $object->name;
        $model->workplace_id = $object->workplace == null ? null : $object->workplace->id;
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

        return CustomerConfigMacroProcesses::find($model->id);

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
                if ($model instanceof CustomerConfigMacroProcesses) {
                    $parsed[] = (new CustomerConfigMacroProcessesDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerConfigMacroProcessesDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerConfigMacroProcesses) {
            return (new CustomerConfigMacroProcessesDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerConfigMacroProcessesDTO();
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