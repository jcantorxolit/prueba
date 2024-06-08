<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerUser;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Wgroup\CustomerUserSkill\CustomerUserSkillDTO;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerUserDTO
{

    public function __construct($model = null)
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
     * @param $model: Modelo CustomerUser
     */
    private function getBasicInfo($model)
    {

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->fullName = $model->fullName;
        $this->firstName = $model->firstName;
        $this->lastName = $model->lastName;
        $this->availability = $model->availability;
        $this->email = $model->email;
        $this->type = $model->getType();
        $this->gender = $model->getGender();
        $this->isActive = $model->getIsActive();
        $this->skills = CustomerUserSkillDTO::parse($model->skills);
        $this->isEditMode = true;
        $this->profile = $model->getProfile();

        $this->tokensession = $this->getTokenSession(true);
    }

    public static function fillAndSaveModel($object)
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
            if (!($model = CustomerUser::find($object->id))) {
                // No existe
                $model = new CustomerUser();
                $isEdit = false;
            }
        } else {
            $model = new CustomerUser();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->firstName = $object->firstName;
        $model->lastName = $object->lastName;
        $model->fullName = $object->firstName . ' ' . $object->lastName;
        $model->type = $object->type != null ? $object->type->value : null;
        $model->gender = $object->gender != null ? $object->gender->value : null;
        $model->email = $object->email;
        $model->availability = $object->availability;
        $model->isActive = $object->isActive;
        $model->profile = $object->profile ? $object->profile->value : null;

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

        $object->id = $model->id;

        CustomerUserSkillDTO::bulkInsert($object);

        return CustomerUser::find($model->id);
    }

    public static function canCreate($entity)
    {
        if ($entity->email == null || trim($entity->email) == '') {
            return true;
        }

        if ($entity->id == 0) {
            $userModel = User::findByEmail($entity->email);

            return $userModel == null;
        } else {
            $currentUser = CustomerUser::find($entity->id);
            $emailUser = User::findByEmail($entity->email);

            if ($emailUser != null) {
                return ($emailUser->email == $currentUser->email) && ($emailUser->id == $currentUser->user_id);
            }

            return true;
        }
    }

    public static function validateEmail($entity)
    {
        if ($entity->email == null || trim($entity->email) == '') {
            return true;
        }

        if (!$entity->id) {
            $personModel = CustomerUser::where("email", $entity->email)->first();

            return $personModel == null;
        } else {
            $currentPerson = CustomerUser::find($entity->id);
            $emailPerson = CustomerUser::where("email", $entity->email)->first();

            if ($emailPerson != null) {
                return ($emailPerson->email == $currentPerson->email) && ($emailPerson->id == $currentPerson->id);
            }

            return true;
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
                if ($model instanceof CustomerUser) {
                    $parsed[] = (new CustomerUserDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerUser) {
            return (new CustomerUserDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerUserDTO();
            }
        }
    }

    private function getUserSsession()
    {
        if (!Auth::check()) {
            return null;
        }

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
