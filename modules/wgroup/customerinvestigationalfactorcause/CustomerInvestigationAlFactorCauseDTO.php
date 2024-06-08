<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInvestigationAlFactorCause;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerInvestigationAlFactorCauseRootCause\CustomerInvestigationAlFactorCauseRootCauseDTO;
use Wgroup\CustomerInvestigationAlFactorCauseSubCause\CustomerInvestigationAlFactorCauseSubCauseDTO;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerInvestigationAlFactorCauseDTO
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
     * @param $model : Modelo CustomerInvestigationAlFactorCause
     */
    private function getBasicInfo($model)
    {

        //Codigo
        $this->id = $model->id;
        $this->customerInvestigationId = $model->customer_investigation_id;
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
            if (CustomerInvestigationAlFactorCause::whereCustomerInvestigationId($object->customerInvestigationId)
                    ->whereCause($object->cause->id)->count() > 0
            ) {
                return false;
            }
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerInvestigationAlFactorCause::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAlFactorCause();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAlFactorCause();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_investigation_id = $object->customerInvestigationId;
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

        CustomerInvestigationAlFactorCauseSubCauseDTO::bulkInsert($object->subCauseList, $model->id);
        CustomerInvestigationAlFactorCauseRootCauseDTO::bulkInsert($object->rootCauseList, $model->id);

        return CustomerInvestigationAlFactorCause::find($model->id);
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
                //var_dump("CustomerInvestigationAlFactorCause:: ". $model instanceof CustomerInvestigationAlFactorCause);
                //var_dump($model);
                if ($model instanceof CustomerInvestigationAlFactorCause) {
                    $parsed[] = (new CustomerInvestigationAlFactorCauseDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAlFactorCause) {
            return (new CustomerInvestigationAlFactorCauseDTO())->parseModel($data, $fmt_response);
        } else {
            //var_dump("NOT CustomerInvestigationAlFactorCause");
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlFactorCauseDTO();
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
                //var_dump("CustomerInvestigationAlFactorCause:: ". $model instanceof CustomerInvestigationAlFactorCause);
                //var_dump($model);
                if ($model instanceof CustomerInvestigationAlFactorCause) {
                    $parsed[] = json_decode($model);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAlFactorCause) {
            return (new CustomerInvestigationAlFactorCauseDTO())->parseModel($data, $fmt_response);
        } else {
            //var_dump("NOT CustomerInvestigationAlFactorCause");
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlFactorCauseDTO();
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
