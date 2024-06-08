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
use Carbon\Carbon;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerDiagnosticDiseaseDTO {

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
        $this->description = $model->description;
        $this->diagnosed = $model->getDiagnosed();
        $this->directEmployees = $model->numberOfEmployees;
        $this->observation = $model->observation;
        $this->dateAt =  Carbon::parse($model->dateAt)->format('d/m/Y H:i:s');
        $this->event_date =  Carbon::parse($model->eventDateTime);
        $this->risk = $model->getRiskFactor();
        $this->createdBy = $model->creator->name;
        $this->updatedBy = $model->updater->name;
        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->statusStudy = $model->statusStudy == 1 ? true : false;
        $this->statusEps = $model->statusEps == 1 ? true : false;
        $this->statusArl = $model->statusArl == 1 ? true : false;
        $this->statusRegional = $model->statusRegional == 1 ? true : false;
        $this->statusNational = $model->statusNational == 1 ? true : false;
        $this->statusJudged = $model->statusJudged == 1 ? true : false;

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
            if (!($model = CustomerDiagnosticDisease::find($object->id))) {
                // No existe
                $model = new CustomerDiagnosticDisease();
                $isEdit = false;
            }
        } else {
            $model = new CustomerDiagnosticDisease();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->diagnostic_id = $object->diagnosticId;
        $model->description = $object->description;
        $model->diagnosed = $object->diagnosed->value == "-S-" ? null : $object->diagnosed->value;
        $model->riskFactor = $object->risk->risk->value == "-S-" ? null : $object->risk->risk->value;
        $model->numberOfEmployees = $object->directEmployees;
        $model->observation = $object->observation;
        $model->dateAt = Carbon::createFromFormat('d/m/Y H:i:s', $object->event_date);
        $model->statusStudy = $object->statusStudy == true ? 1 : 0;
        $model->statusEps = $object->statusEps == true ? 1 : 0;
        $model->statusArl = $object->statusArl == true ? 1 : 0;
        $model->statusRegional = $object->statusRegional == true ? 1 : 0;
        $model->statusNational = $object->statusNational == true ? 1 : 0;
        $model->statusJudged = $object->statusJudged == true ? 1 : 0;

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

        return CustomerDiagnosticDisease::find($model->id);

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
                if ($model instanceof CustomerDiagnosticDisease) {
                    $parsed[] = (new CustomerDiagnosticDiseaseDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDiagnosticDiseaseDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDiagnosticDisease) {
            return (new CustomerDiagnosticDiseaseDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDiagnosticDiseaseDTO();
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
