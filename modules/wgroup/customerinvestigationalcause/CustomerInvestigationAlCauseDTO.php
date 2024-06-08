<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInvestigationAlCause;

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
class CustomerInvestigationAlCauseDTO {

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
     * @param $model: Modelo CustomerInvestigationAlCause
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerInvestigationId = $model->customer_investigation_id;
        $this->cause = $model->getCause();
        $this->type = $model->type;
        $this->observation = $model->observation;

        $this->tokensession = $this->getTokenSession(true);
    }

    public static function bulkInsert($records, $parentId)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$records) {
            return false;
        }


        foreach ($records as $record) {

            if ($record->cause == null) {
                continue;
            }

            if (!$record->id) {
                if (CustomerInvestigationAlCause::whereCustomerInvestigationId($parentId)
                        ->whereCause($record->cause->id)
                        ->whereType($record->type)->count() > 0
                ) {
                    continue;
                }
            }

            if ($record->id) {
                // Existe
                if (!($model = CustomerInvestigationAlCause::find($record->id))) {
                    // No existe
                    $model = new CustomerInvestigationAlCause();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerInvestigationAlCause();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/

            // cliente asociado
            $model->customer_investigation_id = $parentId;
            $model->cause = $record->cause != null ? $record->cause->id : null;
            $model->type = $record->type;
            $model->observation = $record->observation;

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
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/

        return true;
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
                if ($model instanceof CustomerInvestigationAlCause) {
                    $parsed[] = (new CustomerInvestigationAlCauseDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAlCause) {
            return (new CustomerInvestigationAlCauseDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlCauseDTO();
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
