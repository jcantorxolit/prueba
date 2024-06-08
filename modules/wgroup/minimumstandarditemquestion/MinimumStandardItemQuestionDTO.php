<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\MinimumStandardItemQuestion;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class MinimumStandardItemQuestionDTO
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
     * @param $model : Modelo MinimumStandardItemQuestion
     */
    private function getBasicInfo($model)
    {

        //Codigo
        $this->id = $model->id;
        $this->minimumStandardItemId = $model->minimum_standard_item_id;
        $this->question = $model->getQuestion();
        $this->description = $model->description;

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
            if (!($model = MinimumStandardItemQuestion::find($object->id))) {
                // No existe
                $model = new MinimumStandardItemQuestion();
                $isEdit = false;
            }
        } else {
            $model = new MinimumStandardItemQuestion();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->minimum_standard_item_id = $object->minimumStandardItemId;
        $model->type = $object->type;
        $model->description = $object->description;

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

        return MinimumStandardItemQuestion::find($model->id);
    }

    public static function  bulkInsert($records, $entityId)
    {
        try {
            $userAdmn = Auth::getUser();

            foreach ($records as $record) {
                if ($record) {

                    if (MinimumStandardItemQuestion::whereMinimumStandardItemId($entityId)
                            ->whereProgramPreventionQuestionId($record->programPreventionQuestionId)
                            ->count() > 0
                    ) {
                        continue;
                    }

                    $model = new MinimumStandardItemQuestion();
                    $isEdit = false;

                    /** :: ASIGNO DATOS BASICOS ::  **/
                    $model->minimum_standard_item_id = $entityId;
                    $model->program_prevention_question_id = $record->programPreventionQuestionId;

                    if ($isEdit) {
                        // Guarda
                        $model->updatedBy = $userAdmn->id;
                        $model->save();

                        // Actualiza timestamp
                        $model->touch();
                    } else {
                        // Guarda
                        $model->createdBy = $userAdmn->id;
                        $model->updatedBy = $userAdmn->id;
                        $model->save();

                    }
                }
            }
        } catch (\Exception $ex) {

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
                if ($model instanceof MinimumStandardItemQuestion) {
                    $parsed[] = (new MinimumStandardItemQuestionDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof MinimumStandardItemQuestion) {
            return (new MinimumStandardItemQuestionDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new MinimumStandardItemQuestionDTO();
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
