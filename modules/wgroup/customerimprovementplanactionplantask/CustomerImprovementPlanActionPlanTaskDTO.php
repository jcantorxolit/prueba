<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerImprovementPlanActionPlanTask;

use Carbon\Carbon;
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
class CustomerImprovementPlanActionPlanTaskDTO
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
        $this->customerImprovementPlanActionPlanId = $model->customer_improvement_plan_action_plan_id;
        $this->responsible = $model->getResponsible();
        $this->type = $model->getType();
        $this->startDate = $model->startDate ? Carbon::parse($model->startDate) : null;
        $this->endDate = $model->startDate ? Carbon::parse($model->endDate) : null;
        $this->startDateFormat = $model->endDate ? Carbon::parse($model->startDate)->format('d/m/Y H:i') : null;
        $this->endDateFormat = $model->endDate ? Carbon::parse($model->endDate)->format('d/m/Y H:i') : null;
        $this->duration = $model->duration;
        $this->description = $model->description;
        $this->status = $model->getStatus();

        $this->createdBy = $model->creator->name;
        $this->created_at = $model->created_at->format('d/m/Y');

        $this->tokensession = $this->getTokenSession(true);
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
            if (!($model = CustomerImprovementPlanActionPlanTask::find($object->id))) {
                // No existe
                $model = new CustomerImprovementPlanActionPlanTask();
                $isEdit = false;
            }
        } else {
            $model = new CustomerImprovementPlanActionPlanTask();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->customer_improvement_plan_action_plan_id = $object->customerImprovementPlanActionPlanId;
        $model->description = $object->description;
        $model->type = $object->type != null ? $object->type->value : null;
        $model->responsible = $object->responsible != null ? $object->responsible->id : null;
        $model->responsibleType = $object->responsible != null ? $object->responsible->type : null;
        $model->startDate = $object->startDate ? Carbon::parse($object->startDate)->timezone('America/Bogota') : null;
        $model->endDate = $object->endDate ? Carbon::parse($object->endDate)->timezone('America/Bogota') : null;
        $model->duration = $object->duration;
        $model->status = $object->status != null ? $object->status->value : null;

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

        return CustomerImprovementPlanActionPlanTask::find($model->id);

    }

    public static function  updateModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerImprovementPlanActionPlanTask::find($object->id))) {
                // No existe
                $model = new CustomerImprovementPlanActionPlanTask();
                $isEdit = false;
            }
        } else {
            $model = new CustomerImprovementPlanActionPlanTask();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->status = $object->status;

        if (isset($object->tracking)) {
            $model->reason .= $object->tracking->action .':'. $object->tracking->observation . ' | ';
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

        return CustomerImprovementPlanActionPlanTask::find($model->id);

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

                break;
            case "3":

                break;
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
                if ($model instanceof CustomerImprovementPlanActionPlanTask) {
                    $parsed[] = (new CustomerImprovementPlanActionPlanTaskDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerImprovementPlanActionPlanTaskDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerImprovementPlanActionPlanTask) {
            return (new CustomerImprovementPlanActionPlanTaskDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerImprovementPlanActionPlanTaskDTO();
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
