<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerAbsenteeismIndirectCost;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Wgroup\Models\Customer;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerAbsenteeismIndirectCostDTO {

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
     * @param $model: Modelo CustomerAbsenteeismIndirectCost
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerDisabilityId = $model->customer_disability_id;
        $this->concept = $model->getConcept();
        $this->amount = $model->amount;

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
            if (!($model = CustomerAbsenteeismIndirectCost::find($object->id))) {
                // No existe
                $model = new CustomerAbsenteeismIndirectCost();
                $isEdit = false;
            }
        } else {
            $model = new CustomerAbsenteeismIndirectCost();
            $isEdit = false;
        }

        $model->customer_disability_id = $object->customerDisabilityId;
        $model->concept = $object->concept == null ? null : $object->concept->value;
        $model->amount = $object->amount;

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

        return CustomerAbsenteeismIndirectCost::find($model->id);
    }

    public static function  bulkInsert($records, $entityId)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$records) {
            return false;
        }

        foreach ($records as $record) {
            /** :: DETERMINO SI ES EDICION O CREACION ::  **/
            if ($record->id) {
                // Existe
                if (!($model = CustomerAbsenteeismIndirectCost::find($record->id))) {
                    // No existe
                    $model = new CustomerAbsenteeismIndirectCost();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerAbsenteeismIndirectCost();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/
            $model->customer_disability_id = $entityId;
            $model->concept = $record->concept == null ? null : $record->concept->value;
            $model->amount = $record->amount;

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
        return $records;
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
                if ($model instanceof CustomerAbsenteeismIndirectCost) {
                    $parsed[] = (new CustomerAbsenteeismIndirectCostDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerAbsenteeismIndirectCost) {
            return (new CustomerAbsenteeismIndirectCostDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerAbsenteeismIndirectCostDTO();
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
