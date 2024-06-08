<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerEmployeeAudit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerEmployeeAuditDTO
 *
 * @author jdblandon
 */
class CustomerEmployeeAuditDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        if ($model) {
            switch ($fmt_response) {
                case "2":
                    $this->getBasicInfoFilter($model);
                    break;

                default:
                    $this->getBasicInfo($model);
            }
        }
    }

    /**
     * @param $model: Modelo CustomerEmployeeAudit
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->userType = $model->getUserType();
        $this->user = $model->user;
        $this->action = $model->action;
        $this->observation = $model->observation;
        $this->shortObservation = $model->observation != "" ? $this->substru($model->observation, 0, 100) : "";
        $this->date = $model->date;
    }

    private function getBasicInfoFilter($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerEmployeeId = $model->customer_employee_id;
        $this->userType = $model->userType;
        $this->user = $model->fullName;
        $this->action = $model->action;
        $this->observation = $model->observation;
        $this->shortObservation = $model->observation != "" ? $this->substru($model->observation, 0, 100) : "";
        $this->date = $model->date;
    }

    private function substru($str,$from,$len){
        return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $from .'}'.'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $len .'}).*#s','$1', $str);
    }

    public static function  fillAndSaveModel($object)
    {
        return $object;
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

    private function parseArray($model, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoFilter($model);
                break;

            default:
                $this->getBasicInfo($model);
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
                if ($model instanceof CustomerEmployeeAudit) {
                    $parsed[] = (new CustomerEmployeeAuditDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerEmployeeAuditDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerEmployeeAudit) {
            return (new CustomerEmployeeAuditDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerEmployeeAuditDTO();
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
