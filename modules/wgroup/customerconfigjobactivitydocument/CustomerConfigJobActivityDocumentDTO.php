<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerConfigJobActivityDocument;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\CustomerConfigJob\CustomerConfigJobDTO;
use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityInterventionDTO;
use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcessesDTO;
use Wgroup\CustomerConfigProcesses\CustomerConfigProcessesDTO;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlaceDTO;
use Wgroup\Models\Customer;
use Wgroup\CustomerEmployeeCriticalActivity\CustomerEmployeeCriticalActivityService;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerConfigJobActivityDocumentDTO {

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

        $this->jobActivityId = $model->job_activity_id;
        $this->type = $model->getRequirement();
        $this->createdAt = Carbon::parse($model->created_at)->format('d/m/Y');

        $this->tokensession = $this->getTokenSession(true);
    }

    public static function  isUnique($object)
    {
        $model = null;

        if ($object->id == 0) {
            $model = CustomerConfigJobActivityDocument::whereJobActivityId($object->jobActivityId)->whereType($object->type->value)->first();
        }

        return $model == null;
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
            if (!($model = CustomerConfigJobActivityDocument::find($object->id))) {
                // No existe
                $model = new CustomerConfigJobActivityDocument();
                $isEdit = false;
            }
        } else {
            $model = new CustomerConfigJobActivityDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        // cliente asociado

        $model->job_activity_id = $object->jobActivityId;
        $model->type = $object->type ? $object->type->value : null;
        $model->origin = $object->type ? $object->type->origin : null;

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

        //DB->20190412
        CustomerEmployeeCriticalActivityService::bulkInsertJobActivityCritical();

        return CustomerConfigJobActivityDocument::find($model->id);
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
                if ($model instanceof CustomerConfigJobActivityDocument) {
                    $parsed[] = (new CustomerConfigJobActivityDocumentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerConfigJobActivityDocumentDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerConfigJobActivityDocument) {
            return (new CustomerConfigJobActivityDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerConfigJobActivityDocumentDTO();
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
