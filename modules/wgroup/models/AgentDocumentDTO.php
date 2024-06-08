<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;

/**
 * Description of AgentDocumentDTO
 *
 * @author jdblandon
 */
class AgentDocumentDTO {

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
        $this->agentId = $model->agent_id;
        $this->type = $model->getDocumentType();
        $this->classification = $model->getClassification();
        $this->description = $model->description;
        $this->status = $model->getStatusType();
        $this->version = $model->version;
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document);

        $this->startDate =  $model->startDate != null ? Carbon::parse($model->startDate) : null;
        $this->endDate =  $model->endDate != null ? Carbon::parse($model->endDate) : null;

        $this->startDateText =  $model->startDate;
        $this->endDateText =  $model->endDate;

        //$this->agent = $model->agent;
        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
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
            if (!($model = AgentDocument::find($object->id))) {
                // No existe
                $model = new AgentDocument();
                $isEdit = false;
            } else {
                $model->status = 2;
                $model->save();

                $model = new AgentDocument();
                $isEdit = false;
            }

        } else {
            $model = new AgentDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->agent_id = $object->agentId;
        $model->type = $object->type->value == "-S-" ? null : $object->type->value;;
        $model->classification = $object->classification->value == "-S-" ? null : $object->classification->value;;
        $model->description = $object->description;
        $model->status = 1;//$object->status->value == "-S-" ? null : $object->status->value;
        $model->version = $object->version;
        $model->startDate = $object->startDate;
        $model->endDate = $object->endDate == "" ? null : $object->endDate;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;
            //$model->agent_id = 1;//$userAdmn->id;
            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;
            //$model->agent_id = 1;//$userAdmn->id;
            // Guarda
            $model->save();

        }

        return AgentDocument::find($model->id);
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
                if ($model instanceof AgentDocument) {
                    $parsed[] = (new AgentDocumentDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof AgentDocument) {
            return (new AgentDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new AgentDocumentDTO();
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
