<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationLostOpportunityDiagnostic;

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
class CustomerHealthDamageQualificationLostOpportunityDiagnosticDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationLostOpportunityDiagnostic
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageQlFirstOpportunityId = $model->customer_health_damage_ql_first_opportunity_id;
        $this->codeCIE10 = $model->getCodeCIE10();
        $this->description = $model->description;
        $this->observation = $model->observation;

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
            if (!($model = CustomerHealthDamageQualificationLostOpportunityDiagnostic::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationLostOpportunityDiagnostic();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationLostOpportunityDiagnostic();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_ql_first_opportunity_id = $object->customerHealthDamageQlFirstOpportunityId;
        $model->codeCIE10 = $object->codeCIE10 == null ? null : $object->codeCIE10->id;
        $model->description = $object->description;
        $model->observation = $object->observation;

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

        return CustomerHealthDamageQualificationLostOpportunityDiagnostic::find($model->id);
    }

    public static function  bulkInsert($records, $entityId)
    {

        try {
            foreach ($records as $record) {
                $isEdit = true;
                if ($record && $record->codeCIE10 != null) {
                    if ($record->id) {
                        if (!($model = CustomerHealthDamageQualificationLostOpportunityDiagnostic::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerHealthDamageQualificationLostOpportunityDiagnostic();
                        }
                    } else {
                        $model = new CustomerHealthDamageQualificationLostOpportunityDiagnostic();
                        $isEdit = false;
                    }

                    /** :: ASIGNO DATOS BASICOS ::  **/
                    $model->customer_health_damage_ql_first_opportunity_id = $entityId;
                    $model->codeCIE10 = $record->codeCIE10 == null ? null : $record->codeCIE10->id;
                    $model->description = $record->description;
                    $model->observation = $record->observation;

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
                if ($model instanceof CustomerHealthDamageQualificationLostOpportunityDiagnostic) {
                    $parsed[] = (new CustomerHealthDamageQualificationLostOpportunityDiagnosticDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationLostOpportunityDiagnostic) {
            return (new CustomerHealthDamageQualificationLostOpportunityDiagnosticDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationLostOpportunityDiagnosticDTO();
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
