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

/**
 * Description of CustomerProgramManagementDTO
 *
 * @author jdblandon
 */
class CustomerProgramManagementDTO
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
     * @param $model : Modelo CustomerProgramManagementDTO
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->status = $model->getStatusType();
        $this->agent = $model->agent;

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->endDate = $model->endDate != null ? Carbon::parse($model->endDate)->format('d/m/Y') : "";
        $this->dateFrom =  $model->dateFrom != null ? Carbon::parse($model->dateFrom)->format('d/m/Y H:i:s') : "";
        $this->dateTo =  $model->dateTo != null ? Carbon::parse($model->dateTo)->format('d/m/Y H:i:s') : "";
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
            if (!($model = CustomerProgramManagement::find($object->id))) {
                // No existe
                $model = new CustomerProgramManagement();
                $isEdit = false;
            }
        } else {
            $model = new CustomerProgramManagement();
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

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

        }

        return CustomerProgramManagement::find($model->id);

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
                if ($model instanceof CustomerProgramManagement) {
                    $parsed[] = (new CustomerProgramManagementDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerProgramManagementDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerProgramManagement) {
            return (new CustomerProgramManagementDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerProgramManagementDTO();
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
