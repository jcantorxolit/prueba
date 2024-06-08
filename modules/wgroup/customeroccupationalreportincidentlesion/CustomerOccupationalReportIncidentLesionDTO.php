<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerOccupationalReportIncidentLesion;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnosticDTO;
use Wgroup\Models\Customer;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerOccupationalReportIncidentLesionDTO {

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
     * @param $model: Modelo CustomerOccupationalReportIncident
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->employee = CustomerEmployeeDTO::parse($model->employee);


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
            if (!($model = CustomerOccupationalReportIncidentLesion::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalReportIncidentLesion();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalReportIncidentLesion();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/


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



        return CustomerOccupationalReportIncidentLesion::find($model->id);
    }

    public static function  bulkInsert($object, $entityId)
    {
        $userAdmn = Auth::getUser();

        try {
            foreach ($object as $record) {
                $isEdit = true;
                if ($record) {
                    if ($record->id) {
                        if (!($model = CustomerOccupationalReportIncidentLesion::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerOccupationalReportIncidentLesion();
                        } else {
                            if (!$record->isActive) {
                                $model->delete();
                            }
                        }
                    } else {
                        $model = new CustomerOccupationalReportIncidentLesion();
                        $isEdit = false;
                    }

                    if ($record->isActive) {
                        $model->customer_occupational_report_incident_id      = $entityId;
                        $model->lesion_id                               = $record->itemId;

                        if ($isEdit) {
                            $model->updatedBy = $userAdmn->id;
                            $model->save();
                            $model->touch();
                        } else {
                            $model->createdBy = $userAdmn->id;
                            $model->updatedBy = $userAdmn->id;
                            $model->save();
                        }
                    }
                }
            }
        }
        catch (Exception $ex) {
            //Log::info("Envio correo proyecto ex");
            //Log::info($ex->getMessage());
        }


        return CustomerOccupationalReportIncidentLesion::find($model->id);
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
                if ($model instanceof CustomerOccupationalReportIncidentLesion) {
                    $parsed[] = (new CustomerOccupationalReportIncidentLesionDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerOccupationalReportIncidentLesion) {
            return (new CustomerOccupationalReportIncidentLesionDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerOccupationalReportIncidentLesionDTO();
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
