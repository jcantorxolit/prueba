<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerAbsenteeismDisabilityReportAL;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerController;
use Wgroup\CustomerAbsenteeismDisabilityReportALAlert\CustomerAbsenteeismDisabilityReportALAlert;
use Wgroup\CustomerAbsenteeismDisabilityReportALAlert\CustomerAbsenteeismDisabilityReportALAlertDTO;
use Wgroup\CustomerAbsenteeismDisabilityReportALResp\CustomerAbsenteeismDisabilityReportALResp;
use Wgroup\CustomerAbsenteeismDisabilityReportALResp\CustomerAbsenteeismDisabilityReportALRespDTO;
use Wgroup\CustomerOccupationalReportAl\CustomerOccupationalReportDTO;
use Wgroup\Models\Customer;
use Mail;
use Wgroup\Models\CustomerDiagnosticPrevention;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerAbsenteeismDisabilityReportALDTO {

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
     * @param $model: Modelo CustomerAbsenteeismDisabilityReportAL
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerDisabilityId = $model->customer_disability_id;
        $this->reportAL = CustomerOccupationalReportDTO::parse($model->report);

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
    }

    public static function  UpdateModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        if ($object->id) {
            // Existe
            if (!($model = CustomerAbsenteeismDisabilityReportAL::find($object->id))) {
                // No existe
                $model = new CustomerAbsenteeismDisabilityReportAL();
                $isEdit = false;
            }
        } else {
            $model = new CustomerAbsenteeismDisabilityReportAL();
            $isEdit = false;
        }

        $model->status = $object->status;
        //$model->agent_id = $object->agent->id;

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
            if (!($model = CustomerAbsenteeismDisabilityReportAL::find($object->id))) {
                // No existe
                $model = new CustomerAbsenteeismDisabilityReportAL();
                $isEdit = false;
            }
        } else {
            $model = new CustomerAbsenteeismDisabilityReportAL();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_disability_id = $object->customerDisabilityId;
        $model->customer_occupational_report_al_id = $object->reportAL->id;

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


        return CustomerAbsenteeismDisabilityReportAL::find($model->id);
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
                if ($model instanceof CustomerAbsenteeismDisabilityReportAL) {
                    $parsed[] = (new CustomerAbsenteeismDisabilityReportALDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerAbsenteeismDisabilityReportAL) {
            return (new CustomerAbsenteeismDisabilityReportALDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerAbsenteeismDisabilityReportALDTO();
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
