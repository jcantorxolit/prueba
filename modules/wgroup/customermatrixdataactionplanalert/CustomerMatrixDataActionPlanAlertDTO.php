<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerMatrixDataActionPlanAlert;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Models\Customer;

/**
 * Description of CustomerManagementDetailActionPlanAlertDTO
 *
 * @author jdblandon
 */
class CustomerMatrixDataActionPlanAlertDTO {

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
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfo($model) {
        $this->id = $model->id;
        $this->actionPlanId = $model->customer_matrix_data_action_plan_id;
        $this->type = $model->getType();
        $this->preference = $model->getPreference();
        $this->time = $model->time;
        $this->timeType = $model->getTimeType();
        $this->sent = $model->sent;
        $this->status = $model->getStatusType();
        $this->agent = $model->agent;
        $this->updated_at = $model->updated_at->format('d/m/Y');
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
                if ($model instanceof CustomerMatrixDataActionPlanAlert) {
                    $parsed[] = (new CustomerMatrixDataActionPlanAlertDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerMatrixDataActionPlanAlert) {
            return (new CustomerMatrixDataActionPlanAlertDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerMatrixDataActionPlanAlertDTO();
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