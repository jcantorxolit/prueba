<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Report;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CollectionData\CollectionDataDTO;
use Wgroup\ReportCollectionDataField\ReportCollectionDataField;
use Wgroup\ReportCollectionDataField\ReportCollectionDataFieldDTO;

/**
 * Description of ReportDTO
 *
 * @author jdblandon
 */
class ReportDTO {

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
     * @param $model: Modelo Report
     */
    private function getBasicInfo($model) {

        $this->id = $model->id;
        $this->name = $model->name;
        $this->description = $model->description;
        $this->isActive = $model->isActive == 1;
        $this->allowAgent = $model->allowAgent == 1;
        $this->allowCustomer = $model->allowCustomer == 1;
        $this->isQueue = $model->isQueue == 1;
        $this->requireFilter = $model->requireFilter == 1;
        $this->chartType = $model->chartType;
        $this->collection = CollectionDataDTO::parse($model->collection, $model->id);
        $this->collectionChart = CollectionDataDTO::parse($model->collectionChart, $model->id, "2");
        $this->fields = ReportCollectionDataFieldDTO::parse($model->dataFields);

        //var_dump($this->fields);
/*
        //Codigo
        $this->id = $model->id;
        //$this->collection = CollectionDataDTO::parse($model->collection, $model->id);
        //$this->collectionChart = CollectionDataDTO::parse($model->collectionChart, $model->id, "2");
        $this->name = $model->name;
        $this->description = $model->description;
        $this->isActive = $model->isActive == 1;
        $this->allowAgent = $model->allowAgent == 1;
        $this->allowCustomer = $model->allowCustomer == 1;
        //$this->fields = ReportCollectionDataFieldDTO::parse($model->dataFields);
        $this->chartType = $model->chartType;

        $this->tokensession = $this->getTokenSession(true);*/
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
            if (!($model = Report::find($object->id))) {
                // No existe
                $model = new Report();
                $isEdit = false;
            }
        } else {
            $model = new Report();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->collection_id = $object->collection->id == "-S-" ? null : $object->collection->id;
        $model->name = $object->name;
        $model->description = $object->description;
        $model->isActive = $object->isActive;
        $model->allowAgent = $object->allowAgent;
        $model->allowCustomer = $object->allowCustomer;
        $model->isQueue = $object->isQueue;
        $model->requireFilter = $object->requireFilter;

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

        if ($object->fields) {
            foreach ($object->fields as $field) {
                $isFieldEdit = true;
                if ($field) {


                    if ($field->id) {
                        // Existe
                        if (!($ReportDataField = ReportCollectionDataField::find($field->collectionDataFieldId))) {
                            // No existe
                            $ReportDataField = new ReportCollectionDataField();
                            $isFieldEdit = false;
                        }
                    } else {
                        $ReportDataField = new ReportCollectionDataField();
                        $isFieldEdit = false;
                    }

                    $ReportDataField->report_id                 = $model->id;
                    $ReportDataField->collection_data_field_id  = $field->id;
                    $ReportDataField->isActive = 1;

                    if ($isFieldEdit) {

                        // actualizado por
                        $ReportDataField->updatedBy = $userAdmn->id;

                        // Guarda
                        $ReportDataField->save();

                        // Actualiza timestamp
                        $ReportDataField->touch();
                    } else {
                        // Creado por
                        //Log::info("Envio correo proyecto before");


                        $ReportDataField->createdBy = $userAdmn->id;
                        $ReportDataField->updatedBy = $userAdmn->id;

                        // Guarda
                        $ReportDataField->save();
                    }
                }
            }
        }

        return Report::find($model->id);
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
                if ($model instanceof Report) {
                    $parsed[] = (new ReportDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new ReportDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof Report) {
            return (new ReportDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new ReportDTO();
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
