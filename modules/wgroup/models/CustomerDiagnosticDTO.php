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
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\Models\Customer;
use Wgroup\CustomerAudit\CustomerAudit;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerDiagnosticDTO
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
        $this->status = $model->getStatusType();
        $this->agent = $model->agent;
        $this->arlActivity = $model->arlActivity;
        $this->arlIntermediaryActivity = $model->arlIntermediaryActivity;
        $this->arlIntermediaryNit = $model->arlIntermediaryNit;
        $this->arlIntermediaryName = $model->arlIntermediaryName;
        $this->arlIntermediaryLicence = $model->arlIntermediaryLicence;
        $this->arlIntermediaryRegister = $model->arlIntermediaryRegister;
         if ($model->customer != null && $model->customer->getArl() != null)
            $this->arl = $model->customer->getArl()->item;

       $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->endDate = $model->endDate != null ? Carbon::parse($model->endDate)->format('d/m/Y') : "";
        $this->dateFrom =  $model->dateFrom != null ? Carbon::parse($model->dateFrom)->format('d/m/Y H:i:s') : "";
        $this->dateTo =  $model->dateTo != null ? Carbon::parse($model->dateTo)->format('d/m/Y H:i:s') : "";
         /**/
    }

    private function getBasicInfoSummary($model)
    {
        $this->id = $model->id;
        $this->abbreviation = $model->abbreviation;
        $this->name = $model->name;
        $this->questions = $model->questions;
        $this->answers = $model->answers;
        $this->average = $model->average;
        $this->advance = $model->advance;
    }

    private function getReportSummaryPrg($model)
    {

        //Log::info(json_encode($model));

        ////Log::info($model->datasets);

        $this->labels = $model["labels"];
        $this->datasets = $model["datasets"];
        /*
                $this->label = $model->label;
                $this->fillColor = $model->fillColor;
                $this->strokeColor = $model->strokeColor;
                $this->highlightFill = $model->highlightFill;
                $this->highlightStroke = $model->highlightStroke;
                $this->data = $model->data;
                */
    }

    private function getReportSummaryAdv($model)
    {
        //Log::info(json_encode($model));

        $this->value = (float) $model->value;
        $this->color = $model->color;
        $this->highlight = $model->highlightColor;
        $this->label = $model->label;

    }

    public static function  fillAndSaveModel($object, $finalize = false)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerDiagnostic::find($object->id))) {
                // No existe
                $model = new CustomerDiagnostic();
                $isEdit = false;
            }
        } else {
            $model = new CustomerDiagnostic();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->customer_id = $object->customerId;

        if ($object->status != null)
            $model->status = $object->status->value == "-S-" ? null : $object->status->value;
        //$model->arlActivity = $object->status->value == "-S-" ? null : $object->status->value;

        $model->arlActivity = $object->arlActivity;
        $model->arlIntermediaryActivity = $object->arlIntermediaryActivity;
        $model->arlIntermediaryNit = $object->arlIntermediaryNit;
        $model->arlIntermediaryName = $object->arlIntermediaryName;
        $model->arlIntermediaryLicence = $object->arlIntermediaryLicence;
        $model->arlIntermediaryRegister = $object->arlIntermediaryRegister;
        if ($object->dateFrom != null && $object->dateFrom != "")
            $model->dateFrom = Carbon::createFromFormat('d/m/Y H:i:s', $object->dateFrom);

        if ($object->dateTo != null && $object->dateTo != "")
            $model->dateTo = Carbon::createFromFormat('d/m/Y H:i:s', $object->dateTo);

        if($finalize){
            $model->endDate = new \DateTime();
        }

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

            $customerAudit = new CustomerAudit();
            $customerAudit->customer_id = $model->customer_id;
            $customerAudit->model_name = "Diagnostico";
            $customerAudit->model_id = $model->customer_id;
            $customerAudit->user_type = $userAdmn->wg_type;
            $customerAudit->user_id = $userAdmn->id;
            $customerAudit->action = "Editar";
            $customerAudit->observation = "Se realiza adici�n exitosa del diagnostico: (". $model->id .")";
            $customerAudit->date = Carbon::now('America/Bogota');
            $customerAudit->save();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            $customerAudit = new CustomerAudit();
            $customerAudit->customer_id = $model->customer_id;
            $customerAudit->model_name = "Diagnostico";
            $customerAudit->model_id = $model->customer_id;
            $customerAudit->user_type = $userAdmn->wg_type;
            $customerAudit->user_id = $userAdmn->id;
            $customerAudit->action = "Guardar";
            $customerAudit->observation = "Se realiza adici�n exitosa del diagnostico: (". $model->id .")";
            $customerAudit->date = Carbon::now('America/Bogota');
            $customerAudit->save();
        }

        return CustomerDiagnostic::find($model->id);

    }


    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {
        if($fmt_response != "1"){
            // parse model
            switch ($fmt_response) {
                case "2":
                    $this->getReportSummaryPrg($model);
                    break;
                case "3":
                    $this->getReportSummaryAdv($model);
                    break;
                default:
                    $this->getBasicInfoSummary($model);
            }
        }else{
            // parse model
            if ($model) {
                $this->setInfo($model, $fmt_response);
            }
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
                $this->getBasicInfoSummary($model);
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
                if ($model instanceof CustomerDiagnostic) {
                    $parsed[] = (new CustomerDiagnosticDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDiagnosticDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDiagnostic) {
            return (new CustomerDiagnosticDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDiagnosticDTO();
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
