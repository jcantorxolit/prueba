<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerRoadSafetyItem;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\ConfigRoadSafetyRate\ConfigRoadSafetyRate;
use Wgroup\ConfigRoadSafetyRate\ConfigRoadSafetyRateDTO;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\CustomerRoadSafetyItemComment\CustomerRoadSafetyItemComment;
use Wgroup\CustomerRoadSafetyItemComment\CustomerRoadSafetyItemCommentDTO;
use Wgroup\Models\Customer;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerRoadSafetyItemDTO
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
        $this->customerRoadSafetyId = $model->customer_road_safety_id;
        $this->roadSafetyItemId = $model->road_safety_item_id;
        $this->rateId = $model->rate_id;
        $this->rate = ConfigRoadSafetyRateDTO::parse(ConfigRoadSafetyRate::find($model->rate_id));
        $this->apply = $model->getApply();
        $this->evidence = $model->getEvidence();
        $this->requirement = $model->getRequirement();

       /* if ($this->rate->id == 1 || $this->rate->id == 2) {
            $this->realRate = $this->rate;
            $this->rate = null;
        }*/
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
            if (!($model = CustomerRoadSafetyItem::find($object->id))) {
                // No existe
                $model = new CustomerRoadSafetyItem();
                $isEdit = false;
            }
        } else {
            $model = new CustomerRoadSafetyItem();
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

        return CustomerRoadSafetyItem::find($model->id);

    }

    public static function  update($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->customerRoadSafetyItemId) {
            // Existe
            if (!($model = CustomerRoadSafetyItem::find($object->customerRoadSafetyItemId))) {
                // No existe
                $model = new CustomerRoadSafetyItem();
                $isEdit = false;
            }
        } else {
            $model = new CustomerRoadSafetyItem();
            $isEdit = false;
        }

        $model->rate_id = $object->rate ? $object->rate->id : null;
        $model->apply = $object->apply ? $object->apply->value : null;
        $model->evidence = $object->evidence ? $object->evidence->value : null;
        $model->requirement = $object->requirement ? $object->requirement->value : null;

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
            if (CustomerRoadSafetyItemComment::whereCustomerRoadSafetyItemId($object->customerRoadSafetyItemId)
                    ->whereComment($object->comment)
                    ->count() == 0
            ) {
                CustomerRoadSafetyItemCommentDTO::insert($object);
            }
        }

        return CustomerRoadSafetyItem::find($model->id);

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
                if ($model instanceof CustomerRoadSafetyItem) {
                    $parsed[] = (new CustomerRoadSafetyItemDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerRoadSafetyItemDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerRoadSafetyItem) {
            return (new CustomerRoadSafetyItemDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerRoadSafetyItemDTO();
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
