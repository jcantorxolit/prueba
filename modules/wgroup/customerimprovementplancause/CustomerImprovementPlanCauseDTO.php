<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerImprovementPlanCause;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerImprovementPlanCauseRootCause\CustomerImprovementPlanCauseRootCauseDTO;
use Wgroup\CustomerImprovementPlanCauseSubCause\CustomerImprovementPlanCauseSubCauseDTO;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerImprovementPlanCauseDTO
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
     * @param $model : Modelo CustomerImprovementPlanCause
     */
    private function getBasicInfo($model)
    {

        //Codigo
        $this->id = $model->id;
        $this->customerImprovementPlanId = $model->customer_improvement_plan_id;
        $this->cause = $model->getCause();
        $this->subCauseList = $model->getSubCauses();
        $this->rootCauseList = $model->getRootCauses();

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

        if (!$object->id) {
            if (CustomerImprovementPlanCause::whereCustomerImprovementPlanId($object->customerImprovementPlanId)
                    ->whereCause($object->cause->id)->count() > 0
            ) {
                return false;
            }
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerImprovementPlanCause::find($object->id))) {
                // No existe
                $model = new CustomerImprovementPlanCause();
                $isEdit = false;
            }
        } else {
            $model = new CustomerImprovementPlanCause();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_improvement_plan_id = $object->customerImprovementPlanId;
        $model->cause = $object->cause != null ? $object->cause->id : null;

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

        CustomerImprovementPlanCauseSubCauseDTO::bulkInsert($object->subCauseList, $model->id);
        CustomerImprovementPlanCauseRootCauseDTO::bulkInsert($object->rootCauseList, $model->id);

        return CustomerImprovementPlanCause::find($model->id);
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
                //var_dump("CustomerImprovementPlanCause:: ". $model instanceof CustomerImprovementPlanCause);
                //var_dump($model);
                if ($model instanceof CustomerImprovementPlanCause) {
                    $parsed[] = (new CustomerImprovementPlanCauseDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerImprovementPlanCause) {
            return (new CustomerImprovementPlanCauseDTO())->parseModel($data, $fmt_response);
        } else {
            //var_dump("NOT CustomerImprovementPlanCause");
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerImprovementPlanCauseDTO();
            }
        }
    }

    public static function parseAsArray($info, $fmt_response = "1")
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                //var_dump("CustomerImprovementPlanCause:: ". $model instanceof CustomerImprovementPlanCause);
                //var_dump($model);
                if ($model instanceof CustomerImprovementPlanCause) {
                    $parsed[] = json_decode($model);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerImprovementPlanCause) {
            return (new CustomerImprovementPlanCauseDTO())->parseModel($data, $fmt_response);
        } else {
            //var_dump("NOT CustomerImprovementPlanCause");
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerImprovementPlanCauseDTO();
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
