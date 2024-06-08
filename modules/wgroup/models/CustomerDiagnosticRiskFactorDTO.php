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
class CustomerDiagnosticRiskFactorDTO {

    public $id;
    public $diagnosticId;
    public $risk;
    public $exposed;
    public $preventionProgram;
    public $observation;

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
        $this->risk = $model->getRisk();
        $this->exposed = $model->exposed;
        $this->advice = $model->advice == 1 ? true : false;
        $this->assistance = $model->assistance == 1 ? true : false;
        $this->program = $model->program == 1 ? true : false;
        $this->capacitation = $model->capacitation == 1 ? true : false;
        $this->observation = $model->observation;
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
            if (!($model = CustomerDiagnosticRiskFactor::find($object->id))) {
                // No existe
                $model = new CustomerDiagnosticRiskFactor();
                $isEdit = false;
            }
        } else {
            $model = new CustomerDiagnosticRiskFactor();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->diagnostic_id = $object->diagnosticId;
        $model->risk = $object->risk->value == "-S-" ? null : $object->risk->value;;
        $model->exposed = $object->exposed;
        $model->advice = $object->advice == true ? 1 : 0;
        $model->assistance = $object->assistance == true ? 1 : 0;
        $model->program = $object->program == true ? 1 : 0;
        $model->capacitation = $object->capacitation == true ? 1 : 0;
        $model->observation = $object->observation;

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

        return CustomerDiagnosticRiskFactor::find($model->id);

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
                if ($model instanceof CustomerDiagnosticRiskFactor) {
                    $parsed[] = (new CustomerDiagnosticRiskFactorDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDiagnosticRiskFactorDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDiagnosticRiskFactor) {
            return (new CustomerDiagnosticRiskFactorDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDiagnosticRiskFactorDTO();
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
