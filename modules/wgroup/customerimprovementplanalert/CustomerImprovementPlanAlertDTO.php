<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerImprovementPlanAlert;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerManagementDetailActionPlanAlertDTO
 *
 * @author jdblandon
 */
class CustomerImprovementPlanAlertDTO
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
     * @param $model : Modelo CustomerTracking
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerImprovementPlanId = $model->customer_improvement_plan_id;
        $this->type = $model->getType();
        $this->preference = $model->getPreference();
        $this->time = $model->time;
        $this->timeType = $model->getTimeType();
        $this->sent = $model->sent;
        $this->status = $model->getStatusType();
        $this->agent = $model->agent;
        $this->updated_at = $model->updated_at->format('d/m/Y');
    }

    public static function bulkInsert($records, $entityId0)
    {
        foreach ($records as $record) {
            if ($record->type != null) {
                $isEdit = true;
                if ($record->id) {
                    // Existe
                    if (!($model = CustomerImprovementPlanAlert::find($record->id))) {
                        // No existe
                        $model = new CustomerImprovementPlanAlert();
                        $isEdit = false;
                    }
                } else {
                    $model = new CustomerImprovementPlanAlert();
                    $isEdit = false;
                }

                $model->customer_improvement_plan_id = $entityId0;
                $model->type = $record->type != null ? $record->type->value : null;
                $model->preference = $record->preference != null ? $record->preference->value : null;
                $model->time = $record->time;
                $model->timeType = $record->timeType != null ? $record->timeType->value : null;
                $model->status = $record->status ? $record->status->value : null;

                if ($isEdit) {
                    // actualizado por
                    $model->updatedBy = Auth::getUser() ? Auth::getUser()->id : 1;

                    // Guarda
                    $model->save();

                    // Actualiza timestamp
                    $model->touch();
                } else {
                    // Creado por
                    $model->createdBy = Auth::getUser() ? Auth::getUser()->id : 1;
                    $model->updatedBy = Auth::getUser() ? Auth::getUser()->id : 1;

                    // Guarda
                    $model->save();
                }
            }
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
                if ($model instanceof CustomerImprovementPlanAlert) {
                    $parsed[] = (new CustomerImprovementPlanAlertDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerImprovementPlanAlert) {
            return (new CustomerImprovementPlanAlertDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerImprovementPlanAlertDTO();
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
