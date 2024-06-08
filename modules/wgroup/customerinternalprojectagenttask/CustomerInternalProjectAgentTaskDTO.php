<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInternalProjectAgentTask;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerController;
use Wgroup\Models\Customer;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerInternalProjectAgentTaskDTO {

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
     * @param $model: Modelo CustomerInternalProjectAgentTask
     */
    private function getBasicInfo($model) {
        //Codigo
        $this->id = $model->id;
        $this->projectAgentId = $model->project_agent_id;
        $this->type = $model->getType();
        $this->task = $model->task;
        $this->observation = $model->observation;
        $this->startDateTime = $model->startDateTime ? Carbon::parse($model->startDateTime) : null;
        $this->endDateTime = $model->endDateTime ? Carbon::parse($model->endDateTime) : null;
        $this->duration =  $model->duration;
        $this->status = $model->status;

        $this->created_at = $model->created_at ? $model->created_at->format('d/m/Y') : null;
        $this->updated_at = $model->updated_at ? $model->updated_at->format('d/m/Y') : null;
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
            if (!($model = CustomerInternalProjectAgentTask::find($object->id))) {
                // No existe
                $model = new CustomerInternalProjectAgentTask();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInternalProjectAgentTask();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->project_agent_id = $object->projectAgentId;
        $model->type = $object->type ? $object->type->id : null;
        $model->task = $object->task;
        $model->observation = $object->observation;

        $model->startDateTime = $object->startDateTime ? Carbon::parse($object->startDateTime)->timezone('America/Bogota') : null;
        $model->endDateTime = $object->endDateTime ? Carbon::parse($object->endDateTime)->timezone('America/Bogota') : null;
        $model->duration = $object->duration;

        $model->status = $object->status;

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

        if (! empty($object->tracking->action)) {
            $modelTracking = new CustomerInternalProjectAgentTaskTracking();

            $modelTracking->project_agent_task_id = $model->id;
            $modelTracking->type = $object->tracking->action;
            $modelTracking->observation = $object->tracking->description;
            $modelTracking->createdBy = $userAdmn->id;
            $modelTracking->updatedBy = $userAdmn->id;

            // Guarda
            $modelTracking->save();
        }

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        // Datos de contacto


        return CustomerInternalProjectAgentTask::find($model->id);
    }

    public static function  fillAndUpdateModel($object)
    {
        ////Log::info($object->startDateTime->setTimezone('America/Bogota'));
        ////Log::info(json_encode($object->startDateTime->setTimezone('America/Bogota')));
        ////Log::info($object);

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerInternalProjectAgentTask::find($object->id))) {
                // No existe
                $model = new CustomerInternalProjectAgentTask();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInternalProjectAgentTask();
            $isEdit = false;
        }

        $model->status = $object->status;


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

        if (! empty($object->tracking->action)) {
            $modelTracking = new CustomerInternalProjectAgentTaskTracking();

            $modelTracking->project_agent_task_id = $model->id;
            $modelTracking->type = $object->tracking->action;
            $modelTracking->observation = $object->tracking->description;
            $modelTracking->createdBy = $userAdmn->id;
            $modelTracking->updatedBy = $userAdmn->id;

            // Guarda
            $modelTracking->save();
        }



        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        // Datos de contacto


        return CustomerInternalProjectAgentTask::find($model->id);
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
                if ($model instanceof CustomerInternalProjectAgentTask) {
                    $parsed[] = (new CustomerInternalProjectAgentTaskDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInternalProjectAgentTask) {
            return (new CustomerInternalProjectAgentTaskDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInternalProjectAgentTaskDTO();
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
