<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerOccupationalReportAlFactor;

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
class CustomerOccupationalReportFactorDTO {

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
     * @param $model: Modelo CustomerOccupationalReport
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
            if (!($model = CustomerOccupationalReport::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalReport();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalReport();
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



        return CustomerOccupationalReport::find($model->id);
    }

    public static function  bulkInsert($object, $entityId)
    {
        $userAdmn = Auth::getUser();

        try {
            foreach ($object as $record) {
                $isEdit = true;
                if ($record) {
                    if ($record->id) {
                        if (!($model = CustomerOccupationalReportFactor::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerOccupationalReportFactor();
                        } else {
                            if (!$record->isActive) {
                                $model->delete();
                            }
                        }
                    } else {
                        $model = new CustomerOccupationalReportFactor();
                        $isEdit = false;
                    }

                    if ($record->isActive) {
                        $model->customer_occupational_report_al_id      = $entityId;
                        $model->factor_id                               = $record->itemId;

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


        return CustomerOccupationalReportFactor::find($model->id);
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
                if ($model instanceof CustomerOccupationalReport) {
                    $parsed[] = (new CustomerOccupationalReportDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerOccupationalReport) {
            return (new CustomerOccupationalReportDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerOccupationalReportDTO();
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
