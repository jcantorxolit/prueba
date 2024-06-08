<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\NephosIntegrationProductPlanFeature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class NephosIntegrationProductPlanFeatureDTO
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
        $this->productPlanId = $model->product_plan_id;
        $this->name = $model->name;
        $this->code = $model->code;
        $this->default = $model->default_quantity;
        $this->min = $model->min_quantity;
        $this->max = $model->max_quantity;
        $this->unitPrice = $model->unit_price;

        $this->tokensession = $this->getTokenSession(true);
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;


        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = NephosIntegrationProductPlanFeature::find($object->id))) {
                // No existe
                $model = new NephosIntegrationProductPlanFeature();
                $isEdit = false;
            }
        } else {
            $model = new NephosIntegrationProductPlanFeature();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->action = $object->action;
        $model->instance_id = $object->instanceId;
        $model->customer_id = null;
        $model->plan_id = isset($object->planId) ? $object->planId : null;
        $model->adminUser = isset($object->adminUser) ? $object->adminUser : null;
        $model->adminPwd = isset($object->adminPwd) ? $object->adminPwd : null;
        $model->users = isset($object->users) ? $object->users : null;
        $model->contractors = isset($object->contractors) ? $object->contractors : null;
        $model->disk = isset($object->disk) ? $object->disk : null;
        $model->employees = isset($object->employees) ? $object->employees : null;
        $model->resource5 = isset($object->resource5) ? $object->resource5 : null;;
        $model->resource6 = isset($object->resource6) ? $object->resource6 : null;;
        $model->resource7 = isset($object->resource7) ? $object->resource7 : null;;
        $model->resource8 = isset($object->resource8) ? $object->resource8 : null;;
        $model->resource9 = isset($object->resource9) ? $object->resource9 : null;;

        if ($isEdit) {
            // Guarda
            $model->save();

            $model->touch();

        } else {
            $model->save();
        }

        return NephosIntegrationProductPlanFeature::find($model->id);

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
            case "2":
                $this->getReportSummaryPrg($model);
                break;
            case "3":
                $this->getReportSummaryAdv($model);
                break;
            default:
                $this->getBasicInfo($model);
        }

        return $this;
    }

    public static function getMessage($info)
    {
        $instance = new NephosIntegrationProductPlanFeatureDTO();

        $instance->message = "Se instalo exitosamente la instancia con identificador interno:" . $info->id;

        return $instance;
    }

    public static function getRemoveMessage($info)
    {
        $instance = new NephosIntegrationProductPlanFeatureDTO();

        $instance->message = "Se removio exitosamente la instancia:" . $info->instance_id;

        return $instance;
    }

    public static function getConfigureMessage($info)
    {
        $instance = new NephosIntegrationProductPlanFeatureDTO();

        $instance->message = "Se configuro exitosamente la instancia:" . $info->instance_id;

        return $instance;
    }

    public static function getDisableMessage($info)
    {
        $instance = new NephosIntegrationProductPlanFeatureDTO();

        $instance->message = "Se deshabilito exitosamente la instancia:" . $info->instance_id;

        return $instance;
    }

    public static function getEnableMessage($info)
    {
        $instance = new NephosIntegrationProductPlanFeatureDTO();

        $instance->message = "Se  habilito existosamente la instancia:" . $info->instance_id;

        return $instance;
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
                if ($model instanceof NephosIntegrationProductPlanFeature) {
                    $parsed[] = (new NephosIntegrationProductPlanFeatureDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new NephosIntegrationProductPlanFeatureDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof NephosIntegrationProductPlanFeature) {
            return (new NephosIntegrationProductPlanFeatureDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new NephosIntegrationProductPlanFeatureDTO();
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
