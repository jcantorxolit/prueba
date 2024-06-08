<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerImprovementPlanActionPlan;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerImprovementPlanActionPlanNotified\CustomerImprovementPlanActionPlanNotifiedDTO;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerImprovementPlanActionPlanDTO
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
     * @param $model : Modelo CustomerImprovementPlanActionPlan
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->notifiedList = $model->getNotifiedList();
        $this->customerImprovementPlanId = $model->customer_improvement_plan_id;
        $this->rootCause = $model->getRootCause();
        $this->cause = $this->rootCause != null && $this->rootCause->parent != null ? $this->rootCause->parent : null;
        $this->activity = $model->activity;
        $this->entry = $model->getEntry();
        $this->amount = $model->amount;
        $this->endDate = $model->endDate ? Carbon::parse($model->endDate) : null;
        $this->endDateFormat = $model->endDate ? Carbon::parse($model->endDate)->format('d/m/Y') : null;
        $this->responsible = $model->getResponsible();
        $this->responsibleType = $model->responsibleType;
        $this->status = $model->getStatus();


        $this->tokensession = $this->getTokenSession(true);
    }


    public static function  fillAndSaveModel($object)
    {
        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();
        $params = [];

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerImprovementPlanActionPlan::find($object->id))) {
                // No existe
                $model = new CustomerImprovementPlanActionPlan();
                $isEdit = false;
            }
        } else {
            $model = new CustomerImprovementPlanActionPlan();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_improvement_plan_id = $object->customerImprovementPlanId;
        $model->customer_improvement_plan_cause_root_cause_id = $object->rootCause != null ? $object->rootCause->id : null;
        $model->activity = $object->activity;
        $model->entry = $object->entry != null ? $object->entry->id : null;
        $model->amount = $object->amount;
        $model->endDate = $object->endDate ? Carbon::parse($object->endDate)->timezone('America/Bogota') : null;
        $model->responsible = $object->responsible != null ? $object->responsible->id : null;
        $model->responsibleType = $object->responsible != null ? $object->responsible->type : null;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->status = "AB";
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            //Send e-mail to responsible
            try {
                if (isset($object->responsible->email) && $object->responsible->email != '') {
                    if (($modelCustomer = $model->getCustomer())) {
                        $params['Empresa'] = $modelCustomer->businessName;
                        $params['Modulo'] = $model->getImprovementPlan() && $model->getImprovementPlan()->entity ? $model->getImprovementPlan()->entity->item : null;
                        $params['Descripcion'] = $model->getImprovementPlan() ? $model->getImprovementPlan()->description : null;
                        $params['Actividad'] = $object->activity;
                        $params['Responsable'] = $object->responsible->name;
                        $params['Fecha'] = $object->endDate ? Carbon::parse($object->endDate)->timezone('America/Bogota')->format('d/m/Y') : null;;
                        Mail::sendTo($object->responsible->email, 'rainlab.user::mail.plan_accion_asesores', $params);
                    }
                }
            } catch (\Exception $ex) {
            }
        }

        //TODO
        CustomerImprovementPlanActionPlanNotifiedDTO::bulkInsert($object->notifiedList, $params, $model->id);

        return CustomerImprovementPlanActionPlan::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {

        // parse model
        if ($model) {
            $this->setInfo($model, $fmt_response);
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
                if ($model instanceof CustomerImprovementPlanActionPlan) {
                    $parsed[] = (new CustomerImprovementPlanActionPlanDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerImprovementPlanActionPlan) {
            return (new CustomerImprovementPlanActionPlanDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerImprovementPlanActionPlanDTO();
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
