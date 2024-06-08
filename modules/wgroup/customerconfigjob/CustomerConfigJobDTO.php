<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerConfigJob;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\CustomerConfigJobData\CustomerConfigJobDataDTO;
use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcessesDTO;
use Wgroup\CustomerConfigProcesses\CustomerConfigProcessesDTO;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlaceDTO;
use Wgroup\Models\Customer;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerConfigJobDTO {

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
        $this->macro = CustomerConfigMacroProcessesDTO::parse($model->macro);
        $this->process = CustomerConfigProcessesDTO::parse($model->process);
        $this->job = CustomerConfigJobDataDTO::parse($model->job);
        $this->jobId = $model->job_id;
//var_dump($model->job->name);

        $this->workplaceText = $this->workplace == null ? '' : $this->workplace->name;
        $this->macroText = $this->macro == null ? '' : $this->macro->name;
        $this->processText = $this->process == null ? '' : $this->process->name;
        $this->name = $this->job == null ? '' : $this->job->name;


        $this->status = $model->getStatus();
        $this->createdBy = $model->creator ? $model->creator->name : "";
        $this->updatedBy = $model->updater ? $model->updater->name : '';
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
            if (!($model = CustomerConfigJob::find($object->id))) {
                // No existe
                $model = new CustomerConfigJob();
                $isEdit = false;
            }
        } else {
            $model = new CustomerConfigJob();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->customer_id = $object->customerId;
        $model->workplace_id = $object->workplace == null ? null : $object->workplace->id;
        $model->macro_process_id = $object->macro == null ? null : $object->macro->id;
        $model->process_id = $object->process == null ? null : $object->process->id;
        $model->job_id = $object->jobId;
        $model->status = "Activo";

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

        return CustomerConfigJob::find($model->id);

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

                break;
            case "3":

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
                if ($model instanceof CustomerConfigJob) {
                    $parsed[] = (new CustomerConfigJobDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerConfigJobDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerConfigJob) {
            return (new CustomerConfigJobDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerConfigJobDTO();
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
