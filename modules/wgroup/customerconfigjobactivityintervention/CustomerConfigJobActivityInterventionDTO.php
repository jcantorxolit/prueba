<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerConfigJobActivityIntervention;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\CustomerConfigJob\CustomerConfigJobDTO;
use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcessesDTO;
use Wgroup\CustomerConfigProcesses\CustomerConfigProcessesDTO;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlaceDTO;
use Wgroup\Models\Customer;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerConfigJobActivityInterventionDTO {

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
        $this->hazardId = $model->job_activity_hazard_id;
        $this->type = $model->getType();
        $this->description = $model->description;

        $this->tracking = $model->getTracking();
        $this->observation = $model->observation;

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
            if (!($model = CustomerConfigJobActivityIntervention::find($object->id))) {
                // No existe
                $model = new CustomerConfigJobActivityIntervention();
                $isEdit = false;
            }
        } else {
            $model = new CustomerConfigJobActivityIntervention();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        // cliente asociado
        $model->job_activity_id = $object->jobActivityId;
        $model->classification = $object->classification == null ? null : $object->classification->id;
        $model->type = $object->type == null ? null : $object->type->id;
        $model->description = $object->description == null ? null : $object->description->id;
        $model->health_effect = $object->effect == null ? null : $object->effect->id;
        $model->time_exposure = $object->exposure;;
        $model->control_method_text = $object->controlMethodText;;
        $model->control_method = $object->controlMethod == null ? null : $object->controlMethod->id;
        $model->measure_nd = $object->measureND == null ? null : $object->measureND->id;
        $model->measure_ne = $object->measureNE == null ? null : $object->measureNE->id;
        $model->measure_nc = $object->measureNC == null ? null : $object->measureNC->id;

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

        return CustomerConfigJobActivityIntervention::find($model->id);

    }

    public static function  bulkInsert($object)
    {
        $isEdit = true;
        $userAdmn = Auth::getUser();

        try {
            foreach ($object->interventions as $intervention) {
                $isEdit = true;
                if ($intervention && $intervention->type != null) {
                    if ($intervention->id) {
                        if (!($model = CustomerConfigJobActivityIntervention::find($intervention->id))) {
                            $isEdit = false;
                            $model = new CustomerConfigJobActivityIntervention();
                        }
                    } else {
                        $model = new CustomerConfigJobActivityIntervention();
                        $isEdit = false;
                    }

                    $model->job_activity_hazard_id    = $object->id;
                    $model->type =  $intervention->type ? $intervention->type->value : null;
                    $model->description = $intervention->description;
                    $model->tracking = $intervention->tracking ? $intervention->tracking->value : null;
                    $model->observation = $intervention->observation;

                    if ($isEdit) {

                        // actualizado por
                        $model->updatedBy = $userAdmn->id;

                        // Guarda
                        $model->save();

                        // Actualiza timestamp
                        $model->touch();
                    } else {
                        // Creado por
                        //Log::info("Envio correo proyecto before");


                        $model->createdBy = $userAdmn->id;
                        $model->updatedBy = $userAdmn->id;

                        // Guarda
                        $model->save();
                    }
                }
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info("Envio correo proyecto ex");
            //Log::info($ex->getMessage());
        }


        //return CustomerConfigJobActivityIntervention::find($model->id);
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
                if ($model instanceof CustomerConfigJobActivityIntervention) {
                    $parsed[] = (new CustomerConfigJobActivityInterventionDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerConfigJobActivityInterventionDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerConfigJobActivityIntervention) {
            return (new CustomerConfigJobActivityInterventionDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerConfigJobActivityInterventionDTO();
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
