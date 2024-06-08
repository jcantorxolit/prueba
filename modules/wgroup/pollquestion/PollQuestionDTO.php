<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\PollQuestion;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\PollQuestionAnswer\PollQuestionAnswer;
use Wgroup\PollQuestionAnswer\PollQuestionAnswerDTO;

/**
 * Description of PollQuestionDTO
 *
 * @author jdblandon
 */
class PollQuestionDTO {

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
                    $this->getBasicInfoAnswer($model);
                    break;
                default:
                    $this->getBasicInfo($model);
            }
        }
    }

    /**
     * @param $model: Modelo PollQuestion
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->poll = $model->poll;
        $this->title = $model->title;
        $this->position = $model->position;
        $this->type = $model->getType();
        $this->rangeFrom = $model->rangeFrom;
        $this->rangeTo = $model->rangeTo;
        $this->isAscRange = $model->isAscRange == 1 ? true : false;
        $this->isActive = $model->isActive == 1 ? true : false;

        $this->answers = PollQuestionAnswerDTO::parse($model->answers);
        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoAnswer($model) {

        //Codigo
        $this->id = $model->id;
        $this->title = $model->title;
        $this->position = $model->position;
        $this->type = $model->type;
        $this->typeParam = PollQuestion::getTypeValue($model->type);
        $this->rangeFrom = $model->rangeFrom;
        $this->rangeTo = $model->rangeTo;
        $this->isAscRange = $model->isAscRange;

        $this->isActive = $model->isActive == 1 ? true : false;
        $this->answerValue = $model->answer_value;
        $this->answerId = $model->answer_id;

        if ($model->type == "range") {
            $this->answers = $this->getRangeAnswers($model);
        } else {
            $this->answers = PollQuestionAnswerDTO::parse(PollQuestion::getAnswerValues($model->id));
        }

        $this->tokensession = $this->getTokenSession(true);
    }

    private function getRangeAnswers($model)
    {
        if (intval($model->rangeTo) > intval($model->rangeFrom)) {
            $diff = intval($model->rangeTo) - intval($model->rangeFrom);
            $initValue = intval($model->rangeFrom);
        } else if (intval($model->rangeTo) < intval($model->rangeFrom)) {
            $diff = intval($model->rangeFrom) - intval($model->rangeTo);
            $initValue = intval($model->rangeTo);
        } else {
            $diff = 0;
            $initValue = intval($model->rangeFrom);
        }

        $values = array();

        for ($i = 0; $i <= $diff; $i++) {
            $values[] = $initValue + $i;
        }

        if ($model->isAscRange == 1) {
            sort($values, SORT_NUMERIC);
        } else {
            rsort($values, SORT_NUMERIC);
        }

        $data = array();

        foreach ($values as $value) {
            $data[] = json_decode(json_encode(array(
                            "id" => $value,
                            "value" => "$value",
                            "isActive" => 1
                        )), FALSE);
        }

        return PollQuestionAnswerDTO::parse($data);

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
            if (!($model = PollQuestion::find($object->id))) {
                // No existe
                $model = new PollQuestion();
                $isEdit = false;
            }
        } else {
            $model = new PollQuestion();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->poll_id = $object->poll->id;
        $model->title = $object->title;
        $model->position = $object->position;
        $model->type = $object->type->value;
        $model->isActive = $object->isActive;
        $model->rangeFrom = $object->rangeFrom;
        $model->rangeTo = $object->rangeTo;

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

        if ($object->answers) {
            foreach ($object->answers as $answer) {
                $isEdit = true;
                if ($answer) {

                    if ($answer->id) {
                        // Existe
                        if (!($questionAnswer = PollQuestionAnswer::find($answer->id))) {
                            // No existe
                            $questionAnswer = new PollQuestionAnswer();
                            $isEdit = false;
                        }
                    } else {
                        $questionAnswer = new PollQuestionAnswer();
                        $isEdit = false;
                    }

                    $questionAnswer->poll_question_id = $model->id;
                    $questionAnswer->value = $answer->value;
                    $questionAnswer->isActive = $answer->isActive;

                    if ($isEdit) {

                        // actualizado por
                        $questionAnswer->updatedBy = $userAdmn->id;

                        // Guarda
                        $questionAnswer->save();

                        // Actualiza timestamp
                        $questionAnswer->touch();
                    } else {
                        $questionAnswer->createdBy = $userAdmn->id;
                        $questionAnswer->updatedBy = $userAdmn->id;

                        // Guarda
                        $questionAnswer->save();
                    }
                }
            }
        }

        return PollQuestion::find($model->id);
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
                $this->getBasicInfoAnswer($model);
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
                if ($model instanceof PollQuestion) {
                    $parsed[] = (new PollQuestionDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new PollQuestionDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof PollQuestion) {
            return (new PollQuestionDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new PollQuestionDTO();
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
