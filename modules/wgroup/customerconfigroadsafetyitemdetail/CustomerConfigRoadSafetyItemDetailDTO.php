<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerConfigRoadSafetyItemDetail;

use Carbon\Carbon;
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
class CustomerConfigRoadSafetyItemDetailDTO
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
     * @param $model : Modelo CustomerConfigRoadSafetyItemDetail
     */
    private function getBasicInfo($model)
    {

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->roadSafetyItemDetailId = $model->road_safety_item_detail_id;
        $this->type = $model->type;
        $this->description = $model->description;

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
            if (!($model = CustomerConfigRoadSafetyItemDetail::find($object->id))) {
                // No existe
                $model = new CustomerConfigRoadSafetyItemDetail();
                $isEdit = false;
            }
        } else {
            $model = new CustomerConfigRoadSafetyItemDetail();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->road_safety_item_detail_id = $object->roadSafetyItemDetailId;

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

        return CustomerConfigRoadSafetyItemDetail::find($model->id);
    }

    public static function bulkInsert($records, $entityId)
    {
        try {
            foreach ($records as $record) {
                $isEdit = true;
                if ($record) {


                    if ($record->id && !$record->isActive) {
                        $model = CustomerConfigRoadSafetyItemDetail::find($record->id);
                        $model->delete();
                        continue;
                    }

                    if (!$record->id && !$record->isActive) {
                        continue;
                    }

                    if ($record->id) {
                        if (!($model = CustomerConfigRoadSafetyItemDetail::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerConfigRoadSafetyItemDetail();
                        }
                    } else {
                        $model = new CustomerConfigRoadSafetyItemDetail();
                        $isEdit = false;
                    }

                    /** :: ASIGNO DATOS BASICOS ::  **/
                    $model->customer_id = $entityId;
                    $model->road_safety_item_detail_id = $record->roadSafetyItemDetailId;

                    if ($isEdit) {
                        // Guarda
                        $model->save();

                        // Actualiza timestamp
                        $model->touch();
                    } else {
                        // Guarda
                        $model->save();
                    }
                }
            }
        } catch (\Exception $ex) {

        }
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
                if ($model instanceof CustomerConfigRoadSafetyItemDetail) {
                    $parsed[] = (new CustomerConfigRoadSafetyItemDetailDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerConfigRoadSafetyItemDetail) {
            return (new CustomerConfigRoadSafetyItemDetailDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerConfigRoadSafetyItemDetailDTO();
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
