<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\ReportCollectionDataField;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;

/**
 * Description of ReportCollectionDataFieldDTO
 *
 * @author jdblandon
 */
class ReportCollectionDataFieldDTO {

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
     * @param $model: Modelo ReportCollectionDataField
     */
    private function getBasicInfo($model) {

        //Codigo

        //var_dump($model->dataField);

        if ($model != null && $model->dataField != null)
        {
            $this->id = $model->dataField->id;
            $this->reportId = $model->report_id;
            $this->collectionDataFieldId = $model->id;
            $this->table = $model->dataField->table;
            $this->name = $model->dataField->name;
            $this->alias = $model->dataField->alias;
            $this->dataType = $model->dataField->dataType;
            $this->isActive = $model->isActive;
            $this->visible = $model->visible;
            $this->tokensession = $this->getTokenSession(true);
        }
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
            if (!($model = ReportCollectionDataField::find($object->id))) {
                // No existe
                $model = new ReportCollectionDataField();
                $isEdit = false;
            }
        } else {
            $model = new ReportCollectionDataField();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->collection_data_field_id = $object->collectionDataFieldId;
        $model->report_id = $object->reportId;
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

        return ReportCollectionDataField::find($model->id);
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
                if ($model instanceof ReportCollectionDataField) {
                    $parsed[] = (new ReportCollectionDataFieldDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new ReportCollectionDataFieldDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof ReportCollectionDataField) {
            return (new ReportCollectionDataFieldDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new ReportCollectionDataFieldDTO();
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
