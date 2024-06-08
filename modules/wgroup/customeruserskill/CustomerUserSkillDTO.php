<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerUserSkill;

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
class CustomerUserSkillDTO {

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
     * @param $model: Modelo CustomerUserSkill
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerUserId = $model->customer_user_id;
        $this->skill = $model->getSkill();

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
            if (!($model = CustomerUserSkill::find($object->id))) {
                // No existe
                $model = new CustomerUserSkill();
                $isEdit = false;
            }
        } else {
            $model = new CustomerUserSkill();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_user_id = $object->userId;
        $model->skill = $object->skill != null ? $object->skill->id : null;

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



        return CustomerUserSkill::find($model->id);
    }

    public static function  bulkInsert($object)
    {
        try {
            $userAdmn = Auth::getUser();

            foreach ($object->skills as $record) {
                $isEdit = true;

                if ($record->skill != null)
                {
                    if ($record) {
                        if ($record->id) {
                            if (!($model = CustomerUserSkill::find($record->id))) {
                                $isEdit = false;
                                $model = new CustomerUserSkill();
                            }
                        } else {
                            $model = new CustomerUserSkill();
                            $isEdit = false;
                        }

                        $model->customer_user_id = $object->id;
                        $model->skill = $record->skill->id;

                        if ($isEdit) {
                            $model->updatedBy = $userAdmn->id;

                            // Guarda
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
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info($ex->getMessage());
        }


        return CustomerUserSkill::find($object->id);
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
                if ($model instanceof CustomerUserSkill) {
                    $parsed[] = (new CustomerUserSkillDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerUserSkill) {
            return (new CustomerUserSkillDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerUserSkillDTO();
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
