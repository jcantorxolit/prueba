<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Poll;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CollectionData\CollectionDataDTO;
use Wgroup\PollQuestion\PollQuestionDTO;

/**
 * Description of PollDTO
 *
 * @author jdblandon
 */
class PollDTO {

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
     * @param $model: Modelo Poll
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->collection = CollectionDataDTO::parse($model->collection, $model->id);
        $this->name = $model->name;
        $this->description = $model->description;
        $this->isActive = $model->isActive == 1 ? true : false;
        $this->startDateTime = $model->startDate;
        $this->endDateTime = $model->endDate;

        //$this->fields = PollCollectionDataFieldDTO::parse($model->dataFields);
        //$this->chartType = $model->chartType;

        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoQuestion($model) {

        //Codigo
        $this->id = $model->id;
        //$this->collection = CollectionDataDTO::parse($model->collection, $model->id);
        $this->name = $model->name;
        $this->description = $model->description;
        $this->isActive = $model->isActive == 1 ? true : false;
        $this->startDateTime = $model->startDate;
        $this->endDateTime = $model->endDate;
        $this->questions = PollQuestionDTO::parse(Poll::getQuestions($model->customer_poll_id), "2");

        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicSummary($model) {

        $this->name = $model->businessName;
        $this->questions = $model->questions;
        $this->answers = $model->answers;
        $this->avance = $model->avance;
        $this->status = $model->status;
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
            if (!($model = Poll::find($object->id))) {
                // No existe
                $model = new Poll();
                $isEdit = false;
            }
        } else {
            $model = new Poll();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        //$model->collection_id = $object->collection->id == "-S-" ? null : $object->collection->id;
        $model->name = $object->name;
        $model->description = $object->description;
        $model->isActive = $object->isActive;
        $model->startDate = $object->startDateTime;
        $model->endDate = $object->endDateTime;

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

        return Poll::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1") {

        // parse model
        if ($model) {
            switch ($fmt_response) {
                case "2":
                    $this->getBasicInfoQuestion($model);
                    break;
                case "3":
                    $this->getBasicSummary($model);
                    break;
                default:
                    $this->getBasicInfo($model);
            }
        }

        return $this;
    }

    private function parseArray($model, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoQuestion($model);
                break;
            case "3":
                $this->getBasicSummary($model);
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
                if ($model instanceof Poll) {
                    $parsed[] = (new PollDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new PollDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof Poll) {
            return (new PollDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new PollDTO();
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
