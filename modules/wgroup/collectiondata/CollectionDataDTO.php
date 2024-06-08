<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CollectionData;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;

/**
 * Description of CollectionDataDTO
 *
 * @author jdblandon
 */
class CollectionDataDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model, 0);
        }
    }

    public function setInfo($model = null, $reportId, $fmt_response = "1") {

        // recupera informacion basica del formulario
        if ($model) {
            switch ($fmt_response) {
                case "2":
                    $this->getBasicInfoChart($model, $reportId, $fmt_response);
                    break;
                default:
                    $this->getBasicInfo($model, $reportId);
            }
        }
    }

    /**
     * @param $model: Modelo CollectionData
     */
    private function getBasicInfo($model, $reportId) {

        //Codigo
        $this->id = $model->id;
        $this->name = $model->name;
        $this->description =  $model->description;
        $this->isActive = $model->isActive;
        $this->viewName = $model->viewName;
        $this->fields = $model->fields;
        $this->dataFields = $model->type == "report" ? $model->getFields($reportId) : $model->getFieldsChart($reportId);
        $this->type = $model->type;

        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoChart($model) {

        //Codigo
        $this->id = $model->id;
        $this->name = $model->name;
        $this->description = $model->description;
        $this->isActive = $model->isActive;
        $this->viewName = $model->viewName;
        $this->dataFields = $model->fields;
        $this->type = $model->type;

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
            if (!($model = CollectionData::find($object->id))) {
                // No existe
                $model = new CollectionData();
                $isEdit = false;
            }
        } else {
            $model = new CollectionData();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->name = $object->name;
        $model->description = $object->description;
        $model->isActive = $object->isActive;
        $model->viewName = $object->viewName;
        $model->type = $object->type;

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

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        // Datos de contacto
        //TODO
        /*if ($object->agents) {
            foreach ($object->agents as $projectAgent) {
                $isAlertEdit = true;
                if ($projectAgent) {


                    if ($projectAgent->id) {
                        // Existe
                        if (!($CollectionDataAgent = CollectionDataAgent::find($projectAgent->id))) {
                            // No existe
                            if (!($CollectionDataAgent = CollectionDataAgent::whereProjectId($model->id)->whereAgentId($projectAgent->agentId)->first())) {
                                $CollectionDataAgent = new CollectionDataAgent();
                                $isAlertEdit = false;
                            }
                        }
                    } else {
                        $CollectionDataAgent = new CollectionDataAgent();
                        $isAlertEdit = false;
                    }

                    $CollectionDataAgent->project_id    = $model->id;
                    $CollectionDataAgent->agent_id = $projectAgent->agentId;
                    $CollectionDataAgent->estimatedHours = $projectAgent->scheduledHours;

                    if ($isAlertEdit) {

                        // actualizado por
                        $CollectionDataAgent->updatedBy = $userAdmn->id;

                        // Guarda
                        $CollectionDataAgent->save();

                        // Actualiza timestamp
                        $CollectionDataAgent->touch();
                    } else {
                        // Creado por
                        //Log::info("Envio correo proyecto before");


                        $CollectionDataAgent->createdBy = $userAdmn->id;
                        $CollectionDataAgent->updatedBy = $userAdmn->id;

                        // Guarda
                        $CollectionDataAgent->save();

                    }
                }
            }
        }*/

        return CollectionData::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $reportId, $fmt_response = "1") {

        // parse model
        if ($model) {
            $this->setInfo($model, $reportId, $fmt_response);
        }

        return $this;
    }

    private function parseArray($model, $reportId, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoChart($model, $reportId, $fmt_response);
                break;
            default:
                $this->getBasicInfo($model, $reportId);
        }

        return $this;
    }

    public static function parse($info, $reportId, $fmt_response = "1") {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CollectionData) {
                    $parsed[] = (new CollectionDataDTO())->parseModel($model, $reportId, $fmt_response);
                }else {
                    $parsed[] = (new CollectionDataDTO())->parseArray($model, $reportId, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CollectionData) {
            return (new CollectionDataDTO())->parseModel($data, $reportId, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CollectionDataDTO();
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
