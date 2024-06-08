<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerMatrixDataControl;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerMatrixDataControlDTO
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
     * @param $model : Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerMatrixdDataId = $model->customer_matrix_data_id;
        $this->type = $model->getType();
        $this->description = $model->description;
        $this->isActive = $model->isActive == 1;

        $this->createdBy = $model->creator->name;
        $this->updatedBy = $model->updater->name;
        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');

        $this->tokensession = $this->getTokenSession(true);
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
            if (!($model = CustomerMatrixDataControl::find($object->id))) {
                // No existe
                $model = new CustomerMatrixDataControl();
                $isEdit = false;
            }
        } else {
            $model = new CustomerMatrixDataControl();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->customer_matrix_data_id = $object->customerMatrixDataId;
        $model->type = $object->type != null ? $object->type->value : null;
        $model->description = $object->description;
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

        return CustomerMatrixDataControl::find($model->id);

    }

    public static function  bulkInsert($records, $parentId)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$records) {
            return false;
        }


        foreach ($records as $record) {
            if ($record->id) {
                // Existe
                if (!($model = CustomerMatrixDataControl::find($record->id))) {
                    // No existe
                    $model = new CustomerMatrixDataControl();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerMatrixDataControl();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/

            // cliente asociado
            $model->customer_matrix_data_id = $parentId;
            $model->type = $record->type != null ? $record->type->value : null;
            $model->description = $record->description;


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
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/

        return true;
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

                break;
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
                if ($model instanceof CustomerMatrixDataControl) {
                    $parsed[] = (new CustomerMatrixDataControlDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerMatrixDataControlDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerMatrixDataControl) {
            return (new CustomerMatrixDataControlDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerMatrixDataControlDTO();
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