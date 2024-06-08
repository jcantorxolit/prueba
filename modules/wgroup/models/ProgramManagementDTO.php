<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Models\Customer;

/**
 * Description of ProgramManagementDTO
 *
 * @author jdblandon
 */
class ProgramManagementDTO
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
        switch ($fmt_response) {
            case "1":
                if ($model) {
                    $this->getBasicInfo($model);
                }
                break;
            case "2":
                if ($model) {
                    $this->getBasicInfoReport($model);
                }
                break;
            default:
                $this->getBasicInfo($model);

        }
    }

    /**
     * @param $model : Modelo CustomerTracking
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->name = $model->name;
        $this->abbreviation = $model->abbreviation;
        $this->color = $model->color;
        $this->highlightColor = $model->highlightColor;
        $this->isWeighted = $model->isWeighted == 1 ? "Sí" : "No";
        $this->status = $model->getStatus();
    }

    /**
     * @param $model : Modelo CustomerTracking
     */
    private function getBasicInfoReport($model)
    {
        $this->id = $model->id;
        $this->name = $model->name;
        $this->abbreviation = $model->abbreviation;
        $this->advance = $model->advance;
        $this->answers = $model->answers;
        $this->average = $model->average;
        $this->questionsCount = $model->questions;
        $this->total = $model->total;
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = ProgramManagement::find($object->id))) {
                // No existe
                $model = new ProgramManagement();
                $isEdit = false;
            }
        } else {
            $model = new ProgramManagement();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->name = $object->name;
        $model->abbreviation = $object->abbreviation;
        $model->status = $object->status == null ? null : $object->status->value;
        $model->color = $object->color;
        $model->highlightColor = $object->highlightColor;
        $model->isWeighted = $object->isWeighted;

        if ($isEdit) {

            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {
            // Guarda
            $model->save();

        }

        return ProgramManagement::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {

        if ($model) {
            $this->setInfo($model, $fmt_response);
        }

        return $this;
    }

    private function parseArray($model, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            case "1":
                $this->getBasicInfoReport($model);
                break;
            case "3":
                $this->getBasicInfoReport($model);
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
                if ($model instanceof ProgramManagement) {
                    $parsed[] = (new ProgramManagementDTO())->parseModel($model, $fmt_response);
                }
                else
                {
                    $parsed[] = (new ProgramManagementDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof ProgramManagement) {
            return (new ProgramManagementDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new ProgramManagementDTO();
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
