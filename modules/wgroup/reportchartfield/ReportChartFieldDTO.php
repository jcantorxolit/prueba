<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\ReportChartField;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Report\Report;

/**
 * Description of ReportChartFieldDTO
 *
 * @author jdblandon
 */
class ReportChartFieldDTO {

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
     * @param $model: Modelo ReportChartField
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->reportId = $model->report_id;
        $this->collectionDataFieldId = $model->collection_data_field_id;
        $this->axisType = $model->dataField->axisType;
        $this->isActive = $model->isActive;
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

        if ($object->report) {
            // Existe
            if (($modelReport = Report::find($object->report->id))) {
                $modelReport->collection_chart_id = $object->report->collectionChart->id;
                $modelReport->chartType = $object->chartType;
                $modelReport->save();
            }
        }

        if ($object->fieldX) {
            // Existe
            if (!($modelX = ReportChartField::find($object->fieldX->collectionDataFieldId))) {
                // No existe
                $modelX = new ReportChartField();
                $isEdit = false;
            }
        } else {
            $modelX = new ReportChartField();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $modelX->collection_data_field_id = $object->fieldX->id;
        $modelX->report_id = $object->report->id;
        $modelX->axisType = "x";
        $modelX->isActive = 1;

        if ($isEdit) {

            // actualizado por
            $modelX->updatedBy = $userAdmn->id;

            // Guarda
            $modelX->save();

            // Actualiza timestamp
            $modelX->touch();

        } else {

            // Creado por
            $modelX->createdBy = $userAdmn->id;
            $modelX->updatedBy = $userAdmn->id;

            // Guarda
            $modelX->save();

        }

        $isEdit = true;
        if ($object->fieldY) {
            // Existe
            if (!($modelY = ReportChartField::find($object->fieldY->collectionDataFieldId))) {
                // No existe
                $modelY = new ReportChartField();
                $isEdit = false;
            }
        } else {
            $modelX = new ReportChartField();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $modelY->collection_data_field_id = $object->fieldY->id;
        $modelY->report_id = $object->report->id;
        $modelY->axisType = "y";
        $modelY->isActive = 1;

        if ($isEdit) {

            // actualizado por
            $modelY->updatedBy = $userAdmn->id;

            // Guarda
            $modelY->save();

            // Actualiza timestamp
            $modelY->touch();

        } else {

            // Creado por
            $modelY->createdBy = $userAdmn->id;
            $modelY->updatedBy = $userAdmn->id;

            // Guarda
            $modelY->save();

        }

        return ReportChartField::find($modelX->id);
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
                if ($model instanceof ReportChartField) {
                    $parsed[] = (new ReportChartFieldDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new ReportChartFieldDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof ReportChartField) {
            return (new ReportChartFieldDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new ReportChartFieldDTO();
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
