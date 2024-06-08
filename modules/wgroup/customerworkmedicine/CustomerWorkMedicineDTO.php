<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerWorkMedicine;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Wgroup\CustomerAbsenteeismIndirectCost\CustomerAbsenteeismIndirectCostDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnosticDTO;


/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerWorkMedicineDTO {

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
     * @param $model: Modelo CustomerWorkMedicine
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->employee = CustomerEmployeeDTO::parse($model->employee);
        $this->examinationType = $model->getExaminationType();
        $this->examinationDate =  Carbon::parse($model->examinationDate);
        $this->occupationalConclusion = $model->occupationalConclusion;
        $this->occupationalBehavior = $model->occupationalBehavior;
        $this->generalRecommendation = $model->generalRecommendation;
        $this->medicalConcept = $model->getMedicalConcept();
        $this->examinationDateFormat =  Carbon::parse($model->examinationDate)->format('d/m/Y');
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
            if (!($model = CustomerWorkMedicine::find($object->id))) {
                // No existe
                $model = new CustomerWorkMedicine();
                $isEdit = false;
            }
        } else {
            $model = new CustomerWorkMedicine();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_employee_id = $object->employee->id;
        $model->examinationType = $object->examinationType == null ? null : $object->examinationType->value;
        $model->examinationDate = Carbon::parse($object->examinationDate)->timezone('America/Bogota');
        $model->occupationalConclusion = $object->occupationalConclusion;
        $model->occupationalBehavior = $object->occupationalBehavior;
        $model->generalRecommendation = $object->generalRecommendation;
        $model->medicalConcept = $object->medicalConcept == null ? null : $object->medicalConcept->value;

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

        return CustomerWorkMedicine::find($model->id);
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
                if ($model instanceof CustomerWorkMedicine) {
                    $parsed[] = (new CustomerWorkMedicineDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerWorkMedicine) {
            return (new CustomerWorkMedicineDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerWorkMedicineDTO();
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
