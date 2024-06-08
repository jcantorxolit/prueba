<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInternalCertificateGradeCalendar;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerInternalCertificateGradeCalendarDTO
 *
 * @author jdblandon
 */
class CustomerInternalCertificateGradeCalendarDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoSummary($model);
                break;
            default:
                $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model: Modelo CustomerInternalCertificateGradeCalendar
     */
    private function getBasicInfo($model) {
        $this->id = $model->id;
        $this->certificateGradeId = $model->customer_internal_certificate_grade_id;
        $this->startDate = $model->startDate;
        $this->startDateFormat = Carbon::parse($model->startDate)->format('d/m/Y');;
        $this->hourDuration = $model->hourDuration;
        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoSummary($model)
    {
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
            if (!($model = CustomerInternalCertificateGradeCalendar::find($object->id))) {
                // No existe
                $model = new CustomerInternalCertificateGradeCalendar();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInternalCertificateGradeCalendar();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_internal_certificate_grade_id = $object->certificateGradeId;
        $model->startDate = Carbon::createFromFormat('d/m/Y H:i:s', $object->startDate);
        $model->hourDuration = $object->hourDuration;

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

        return CustomerInternalCertificateGradeCalendar::find($model->id);
    }

    public static function  bulkInsert($object)
    {
        $userAdmn = Auth::getUser();

        try {

            //Log::info("calendar bulkInsert");

            foreach ($object->calendar as $record) {
                $isEdit = true;
                if ($record) {
                    if ($record->id) {
                        if (!($model = CustomerInternalCertificateGradeCalendar::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerInternalCertificateGradeCalendar();
                        }
                    } else {
                        $model = new CustomerInternalCertificateGradeCalendar();
                        $isEdit = false;
                    }

                    $model->customer_internal_certificate_grade_id    = $object->id;
                    $model->startDate = $record->startDate;//Carbon::createFromFormat('d/m/Y H:i:s', $record->startDate);
                    $model->hourDuration = $record->hourDuration;

                    if ($isEdit) {

                        // actualizado por
                        $model->updatedBy = $userAdmn->id;

                        // Guarda
                        $model->save();

                        // Actualiza timestamp
                        $model->touch();
                    } else {
                        // Creado por
                        //Log::info("Envio correo proyecto before");
                        $model->createdBy = $userAdmn->id;
                        $model->updatedBy = $userAdmn->id;

                        // Guarda
                        $model->save();
                    }
                }
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info($ex->getMessage());
        }


        return CustomerInternalCertificateGradeCalendar::find($model->id);
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
                $this->getBasicInfoSummary($model);
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
                if ($model instanceof CustomerInternalCertificateGradeCalendar) {
                    $parsed[] = (new CustomerInternalCertificateGradeCalendarDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CustomerInternalCertificateGradeCalendarDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInternalCertificateGradeCalendar) {
            return (new CustomerInternalCertificateGradeCalendarDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInternalCertificateGradeCalendarDTO();
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
