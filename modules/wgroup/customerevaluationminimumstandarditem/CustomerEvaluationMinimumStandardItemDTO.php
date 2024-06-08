<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerEvaluationMinimumStandardItem;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\ConfigMinimumStandardRate\ConfigMinimumStandardRate;
use Wgroup\ConfigMinimumStandardRate\ConfigMinimumStandardRateDTO;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\CustomerEvaluationMinimumStandardItemComment\CustomerEvaluationMinimumStandardItemComment;
use Wgroup\CustomerEvaluationMinimumStandardItemComment\CustomerEvaluationMinimumStandardItemCommentDTO;
use Wgroup\Models\Customer;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerEvaluationMinimumStandardItemDTO
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
     * @param $model : Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerEvaluationMinimumStandardId = $model->customer_evaluation_minimum_standard_id;
        $this->minimumStandardItemId = $model->minimum_standard_item_id;
        $this->rateId = $model->rate_id;
        $this->rate = ConfigMinimumStandardRateDTO::parse(ConfigMinimumStandardRate::find($model->rate_id));
        $this->realRate = null;

        if ($this->rate != null && $this->rate != '' && ($this->rate->id == 1 || $this->rate->id == 2)) {
            $this->realRate = $this->rate;
            $this->rate = null;
        } else {
            $this->rate = $this->rate != '' ? $this->rate : null;
        }
    }


    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerEvaluationMinimumStandardItem::find($object->id))) {
                // No existe
                $model = new CustomerEvaluationMinimumStandardItem();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEvaluationMinimumStandardItem();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->rate_id = $object->rate->id;

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

        return CustomerEvaluationMinimumStandardItem::find($model->id);

    }

    public static function  update($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->customerEvaluationMinimumStandardItemId) {
            // Existe
            if (!($model = CustomerEvaluationMinimumStandardItem::find($object->customerEvaluationMinimumStandardItemId))) {
                // No existe
                $model = new CustomerEvaluationMinimumStandardItem();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEvaluationMinimumStandardItem();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado

        if ($object->realRate != null) {
            $model->rate_id = $object->realRate->id;
        } else {
            $model->rate_id = $object->rate->id;
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

        if ($object->comment != '') {
            if (CustomerEvaluationMinimumStandardItemComment::whereCustomerEvaluationMinimumStandardItemId($object->customerEvaluationMinimumStandardItemId)
                    ->whereComment($object->comment)
                    ->count() == 0
            ) {
                CustomerEvaluationMinimumStandardItemCommentDTO::insert($object);
            }
        }

        return CustomerEvaluationMinimumStandardItem::find($model->id);

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

    private function parseArray($model, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            default:
                $this->getBasicInfo($model);
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
                if ($model instanceof CustomerEvaluationMinimumStandardItem) {
                    $parsed[] = (new CustomerEvaluationMinimumStandardItemDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerEvaluationMinimumStandardItemDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerEvaluationMinimumStandardItem) {
            return (new CustomerEvaluationMinimumStandardItemDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerEvaluationMinimumStandardItemDTO();
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
