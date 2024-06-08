<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerUnsafeActObservation;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;
use Wgroup\CustomerUnsafeAct\CustomerUnsafeAct;

/**
 * Description of CustomerUnsafeActObservationDTO
 *
 * @author jdblandon
 */
class CustomerUnsafeActObservationDTO
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
     * @param $model : Modelo CustomerTracking
     */
    private function getBasicInfo($model)
    {

        $documentModel = CustomerUnsafeActObservation::find($model->id);

        $this->id = $model->id;
        $this->customerUnsafeActId = $model->customer_unsafe_act_id;
        $this->status = $model->getStatus();
        $this->dateOf = $model->dateOf != null ? Carbon::parse($model->dateOf) : null;
        $this->dateOfFormat = $model->dateOf != null ? Carbon::parse($model->dateOf)->format('d/m/Y H:i') : null;
        $this->description = $model->description;
        $this->creator = new \stdClass(); //$model->creator;
		$this->creator->name = $model->creator ? $model->creator->name : null;
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
            if (!($model = CustomerUnsafeActObservation::find($object->id))) {
                // No existe
                $model = new CustomerUnsafeActObservation();
                $isEdit = false;
            }
        } else {
            $model = new CustomerUnsafeActObservation();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_unsafe_act_id = $object->customerUnsafeActId;
        $model->status = $object->status ? $object->status->value : null;
        //$model->dateOf = $object->dateOf ? Carbon::parse($object->dateOf)->timezone('America/Bogota') : null;
        $model->dateOf = Carbon::now('America/Bogota');
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

        $entityModel = CustomerUnsafeAct::find($object->customerUnsafeActId);

        if ($entityModel != null) {
            $entityModel->status = $object->status ? $object->status->value : null;
            $entityModel->save();
        }

        return CustomerUnsafeActObservation::find($model->id);
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
                if ($model instanceof CustomerUnsafeActObservation) {
                    $parsed[] = (new CustomerUnsafeActObservationDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerUnsafeActObservationDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerUnsafeActObservation) {
            return (new CustomerUnsafeActObservationDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerUnsafeActObservationDTO();
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
