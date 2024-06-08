<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInvestigationAlMeasure;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\CustomerInvestigationAlMeasureTracking\CustomerInvestigationAlMeasureTracking;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerInvestigationAlMeasureDTO {

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
     * @param $model: Modelo CustomerInvestigationAlMeasure
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerInvestigationId = $model->customer_investigation_id;
        $this->type = $model->type ==1;
        $this->typeEnvironment = $model->typeEnvironment ==1;
        $this->typeWorker = $model->typeWorker ==1;
        $this->controlType = $model->getControlType();
        $this->description = $model->description;
        $this->responsible = $model->responsible;
        $this->checkDate =  $model->checkDate ? Carbon::parse($model->checkDate) : null;
        $this->isActive = $model->isActive == 1;
        $this->impact = $model->impact;
        $this->planId = (!($actionPlanModel = $model->getActionPlan())) ? 0 : $actionPlanModel->id;

        $this->checkDateFormat =  $model->checkDate ? Carbon::parse($model->checkDate)->format('d/m/Y') : null;

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
            if (!($model = CustomerInvestigationAlMeasure::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAlMeasure();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAlMeasure();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_investigation_id = $object->customerInvestigationId;
        $model->type = $object->type;
        $model->typeEnvironment = isset($object->typeEnvironment) ? $object->typeEnvironment : 0;
        $model->typeWorker = isset($object->typeWorker) ? $object->typeWorker : 0;
        $model->controlType = $object->controlType == null ? null : $object->controlType->value;
        $model->description = $object->description;
        $model->checkDate = $object->checkDate ? Carbon::parse($object->checkDate) : null;
        $model->responsible = $object->responsible;
        $model->isActive = $object->isActive;
        $model->impact = $object->impact;


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

            $newModel = new CustomerInvestigationAlMeasureTracking();
            $newModel->customer_investigation_measure_id = $model->id;
            $newModel->dateOf = Carbon::now();
            $newModel->status = 'Pendiente';
            $newModel->implementationDate = null;
            $newModel->comment = null;
            $newModel->isEffective = false;
            $newModel->description = '';
            $newModel->isReschedule = false;
            $newModel->sort = $object->impact;
            $newModel->save();
        }

        return CustomerInvestigationAlMeasure::find($model->id);
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
                if ($model instanceof CustomerInvestigationAlMeasure) {
                    $parsed[] = (new CustomerInvestigationAlMeasureDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAlMeasure) {
            return (new CustomerInvestigationAlMeasureDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlMeasureDTO();
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
