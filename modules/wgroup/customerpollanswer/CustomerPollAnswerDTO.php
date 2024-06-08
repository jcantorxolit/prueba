<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerPollAnswer;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerPoll\CustomerPoll;
use Wgroup\CustomerPoll\CustomerPollDTO;

/**
 * Description of CustomerPollAnswerDTO
 *
 * @author jdblandon
 */
class CustomerPollAnswerDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        if ($model) {
            switch ($fmt_response) {

                default:
                    $this->getBasicInfo($model);
            }
        }
    }

    /**
     * @param $model: Modelo CustomerPollAnswer
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        //$this->question = $model->question;
        $this->value = $model->value;
        $this->poll = CustomerPollDTO::parse($model->poll);
        $this->isActive = $model->isActive == 1 ? true : false;
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

        if ($object->question->answerValue == null) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->question) {
            // Existe
            if (!($model = CustomerPollAnswer::find($object->question->answerId))) {
                // No existe
                $model = new CustomerPollAnswer();
                $isEdit = false;
            }
        } else {
            $model = new CustomerPollAnswer();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_poll_id = $object->customerPollId;
        $model->poll_question_id = $object->question->id;
        $model->value = $object->question->answerValue;
        $model->isActive = $object->isActive;

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

        if (($modelCustomerPoll = CustomerPoll::find($object->customerPollId))) {
            // No existe
            $modelCustomerPoll->status = "Iniciada";
            $modelCustomerPoll->save();
            $modelCustomerPoll->touch();
        }


        return CustomerPollAnswer::find($model->id);
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
                if ($model instanceof CustomerPollAnswer) {
                    $parsed[] = (new CustomerPollAnswerDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CustomerPollAnswerDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerPollAnswer) {
            return (new CustomerPollAnswerDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerPollAnswerDTO();
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
