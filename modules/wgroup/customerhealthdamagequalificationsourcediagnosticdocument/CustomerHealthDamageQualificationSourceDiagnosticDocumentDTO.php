<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationSourceDiagnosticDocument;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerHealthDamageQualificationSourceDiagnosticDocumentDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationSourceDiagnosticDocument
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageQualificationSourceDiagnosticId = $model->customer_health_damage_qualification_source_diagnostic_id;
        $this->document = $model->getDocument();
        $this->description = $model->description;

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
            if (!($model = CustomerHealthDamageQualificationSourceDiagnosticDocument::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationSourceDiagnosticDocument();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationSourceDiagnosticDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_qualification_source_diagnostic_id = $object->customerHealthDamageQualificationSourceDiagnosticId;
        $model->document = $object->document == null ? null : $object->document->value;
        $model->description = $object->description;

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

        return CustomerHealthDamageQualificationSourceDiagnosticDocument::find($model->id);
    }

    public static function  bulkInsert($records, $entityId)
    {

        try {
            foreach ($records as $record) {
                $isEdit = true;
                if ($record && $record->document != null) {
                    if ($record->id) {
                        if (!($model = CustomerHealthDamageQualificationSourceDiagnosticDocument::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerHealthDamageQualificationSourceDiagnosticDocument();
                        }
                    } else {
                        $model = new CustomerHealthDamageQualificationSourceDiagnosticDocument();
                        $isEdit = false;
                    }

                    /** :: ASIGNO DATOS BASICOS ::  **/
                    $model->customer_health_damage_qualification_source_diagnostic_id = $entityId;
                    $model->document = $record->document == null ? null : $record->document->value;
                    $model->description = $record->description;

                    if ($isEdit) {
                        // Guarda
                        $model->save();

                        // Actualiza timestamp
                        $model->touch();
                    } else {
                        // Guarda
                        $model->save();
                    }
                }
            }
        }
        catch (\Exception $ex) {

        }
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
                if ($model instanceof CustomerHealthDamageQualificationSourceDiagnosticDocument) {
                    $parsed[] = (new CustomerHealthDamageQualificationSourceDiagnosticDocumentDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationSourceDiagnosticDocument) {
            return (new CustomerHealthDamageQualificationSourceDiagnosticDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationSourceDiagnosticDocumentDTO();
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
