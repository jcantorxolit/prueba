<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerImprovementPlanAudit;

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
class CustomerImprovementPlanAuditDTO
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
     * @param $model : Modelo CustomerImprovementPlanAudit
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerImprovementPlanId = $model->customer_improvement_plan_id;
        $this->entityId = $model->entityId;
        $this->entityName = $model->entityName;
        $this->type = $model->type;
        $this->reason = $model->reason;

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
            if (!($model = CustomerImprovementPlanAudit::find($object->id))) {
                // No existe
                $model = new CustomerImprovementPlanAudit();
                $isEdit = false;
            }
        } else {
            $model = new CustomerImprovementPlanAudit();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_improvement_plan_id = $object->customerImprovementPlanId;
        $model->entityId = $object->entityId;
        $model->entityName = $object->entityName;
        $model->type = $object->type;
        $model->reason = $object->reason;

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

        //TODO

        return CustomerImprovementPlanAudit::find($model->id);
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
                if ($model instanceof CustomerImprovementPlanAudit) {
                    $parsed[] = (new CustomerImprovementPlanAuditDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerImprovementPlanAudit) {
            return (new CustomerImprovementPlanAuditDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerImprovementPlanAuditDTO();
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