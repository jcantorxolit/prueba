<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CollectionDataField;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;

/**
 * Description of CollectionDataFieldDTO
 *
 * @author jdblandon
 */
class CollectionDataFieldDTO {

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
                    $this->getBasicInfoField($model);
                    break;
                default:
                    $this->getBasicInfo($model);
            }

        }
    }

    /**
     * @param $model: Modelo CollectionDataField
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->collectionId = $model->collection_id;
        $this->table = $model->table;
        $this->name = $model->name;
        $this->alias = $model->alias;
        $this->dataType = $model->dataType;
        $this->isActive = $model->isActive;
        $this->visible = $model->visible;

        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoField($model) {

        //Codigo
        $this->id = 0;
        $this->reportId = 0;
        $this->collectionDataFieldId = $model->collection_id;
        $this->table = $model->table;
        $this->name = $model->name;
        $this->alias = $model->alias;
        $this->dataType = $model->dataType;
        $this->isActive = $model->isActive;
        $this->visible = $model->visible;

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
            if (!($model = CollectionDataField::find($object->id))) {
                // No existe
                $model = new CollectionDataField();
                $isEdit = false;
            }
        } else {
            $model = new CollectionDataField();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->collection_id = $object->collectionId;
        $model->table = $object->table;
        $model->name = $object->name;
        $model->alias = $object->alias;
        $model->dataType = $object->dataType;
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

        return CollectionDataField::find($model->id);
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
                $this->getBasicInfoField($model);
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
                if ($model instanceof CollectionDataField) {
                    $parsed[] = (new CollectionDataFieldDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CollectionDataFieldDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CollectionDataField) {
            return (new CollectionDataFieldDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CollectionDataFieldDTO();
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
