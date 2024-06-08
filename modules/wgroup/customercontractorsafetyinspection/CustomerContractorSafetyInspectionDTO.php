<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerContractorSafetyInspection;

use Carbon\Carbon;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;



/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerContractorSafetyInspectionDTO {

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
     * @param $model: Modelo CustomerContractorSafetyInspection
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->description = $model->description;
        $this->reason = $model->reason;
        $this->dateText = Carbon::parse($model->date)->format("d/m/Y");
        $this->date = Carbon::parse($model->date);
        $this->dateFrom = Carbon::parse($model->dateFrom);
        $this->dateTo = Carbon::parse($model->dateTo);
        $this->version = $model->version;
        $this->responsible = $model->responsible;
        $this->responsibleJob = $model->responsibleJob;
        $this->responsibleEmail = $model->responsibleEmail;
        $this->agent = $model->getAgent();
        $this->contractorType = $model->getContractorType();
        $this->isContractor = $model->isContractor == 1;

        $this->header = CustomerContractorSafetyInspectionConfigHeaderDTO::parse($model->header);
        $this->lists = CustomerContractorSafetyInspectionListDTO::parse($model->lists);

        $this->isActive = $model->isActive == 1 ? true : false;

        $this->tokensession = $this->getTokenSession(true);
    }


    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerContractorSafetyInspection::find($object->id))) {
                // No existe
                $model = new CustomerContractorSafetyInspection();
                $isEdit = false;
            }
        } else {
            $model = new CustomerContractorSafetyInspection();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->customer_safety_inspection_header_id = $object->header->id;
        $model->description = $object->description;
        $model->reason = $object->reason;
        $model->date = $object->date;
        $model->dateFrom = $object->dateFrom;
        $model->dateTo = $object->dateTo;
        $model->version = isset($object->version) ? $object->version : 1;
        $model->responsible = $object->responsible;
        $model->responsibleJob = $object->responsibleJob;
        $model->responsibleEmail = $object->responsibleEmail;
        $model->agentId = $object->agent->id;
        $model->isContractor = $object->isContractor;
        $model->contractorType = $object->contractorType ? $object->contractorType->id : null ;

        $model->isActive = $object->isActive;

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

        CustomerContractorSafetyInspectionListDTO::bulkInsertOrUpdate($object->lists, $model->id);

        return CustomerContractorSafetyInspection::find($model->id);
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

    public static function parse($info, $fmt_response = "1") {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerContractorSafetyInspection) {
                    $parsed[] = (new CustomerContractorSafetyInspectionDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerContractorSafetyInspection) {
            return (new CustomerContractorSafetyInspectionDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerContractorSafetyInspectionDTO();
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
