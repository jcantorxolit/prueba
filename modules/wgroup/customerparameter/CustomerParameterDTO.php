<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerParameter;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Models\CustomerAgent;

/**
 * Description of CustomerDto
 *
 * @author TeamCloud
 */
class CustomerParameterDTO
{

    function __construct($model = null, $customerId = 0)
    {
        if ($model) {
            $this->parse($model, $customerId);
        }
    }

    public function setInfo($model = null, $customerId = 0)
    {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getInfoBasic($model, $customerId);
        }
    }

    private function getInfoBasic($model)
    {

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->namespace = $model->namespace;
        $this->group = $model->group;
        $this->isActive = (bool)$model->item;
        $this->item = $model->item;
        $this->value = $model->value;
        $this->data = $model->data;
        $this->isVisible = (bool)$model->is_active;
    }

    public static function getData($model)
    {
        if ($model == null) {
            return null;
        }
        $instance = new CustomerParameter();
        $instance->id = $model->id;
        $instance->customerId = $model->customer_id;
        $instance->namespace = $model->namespace;
        $instance->group = $model->group;
        $instance->item = $model->value;
        $instance->value = $model->id;

        return $instance;

    }


    public static function  bulkInsert($records, $customerId)
    {
        try {
            foreach ($records as $record) {
                $isEdit = true;
                if ($record) {
                    if ($record->id) {
                        if (!($model = CustomerParameter::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerParameter();
                        }
                    } else {
                        $model = new CustomerParameter();
                        $isEdit = false;
                    }

                    $model->customer_id = $customerId;
                    $model->namespace = $record->namespace;
                    $model->group = $record->group;
                    $model->item = isset($record->isActive) ? $record->isActive ? 1 : 0 : 0;
                    $model->is_active = isset($record->isVisible) ? $record->isVisible : 1;

                    if (is_object($record->value)) {
                        $model->item = $record->value ? $record->value->id : null;
                        $model->value =  $record->value ? $record->value->type : null;
                    } else {
                        $model->value = $record->value;
                        $model->data = isset($record->data) ? $record->data : 0;
                    }

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
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info($ex->getMessage());
        }


        //return CustomerParameter::find($model->id);
    }


    /// ::: METODOS PRIVADOS DE CADA DTO

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model)
    {

        // parse model
        if ($model) {
            $this->setInfo($model);
        }

        return $this;
    }

    public static function parse($info)
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerParameter) {
                    $parsed[] = (new CustomerParameterDTO())->parseModel($model);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerParameter) {
            return (new CustomerParameterDTO())->parseModel($data);
        } else {
            // return empty instance

            return new CustomerParameterDTO();

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
