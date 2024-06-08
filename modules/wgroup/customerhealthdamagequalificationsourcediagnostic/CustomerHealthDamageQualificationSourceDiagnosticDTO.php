<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationSourceDiagnostic;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\CustomerHealthDamageQualificationSourceDiagnosticDocument\CustomerHealthDamageQualificationSourceDiagnosticDocumentDTO;
use Wgroup\CustomerHealthDamageQualificationSourceDiagnosticSupport\CustomerHealthDamageQualificationSourceDiagnosticSupportDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerHealthDamageQualificationSourceDiagnosticDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationSourceDiagnostic
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageQualificationSourceId = $model->customer_health_damage_qualification_source_id;
        $this->dateOf =  $model->dateOf ? Carbon::parse($model->dateOf) : null;
        $this->diagnostic = $model->getDiagnostic();
        $this->laterality = $model->getLaterality();
        $this->entityPerformsDiagnostic = $model->getEntityPerformsDiagnostic();
        $this->codeCIE10 = $model->getCodeCIE10();
        $this->description = $model->description;
        $this->isRequestedSupport = $model->isRequestedSupport == 1;
        $this->requestDate =  $model->requestDate ? Carbon::parse($model->requestDate) : null;
        $this->applicant = $model->getApplicant();
        $this->directorApt = $model->getDirectorApt();
        $this->dateSend =  $model->dateSend ? Carbon::parse($model->dateSend) : null;
        $this->dateReceived =  $model->dateReceived ? Carbon::parse($model->dateReceived) : null;

        $this->dateOfFormat =  $model->dateOf ? Carbon::parse($model->dateOf)->format('d/m/Y') : null;
        $this->requestDateFormat =  $model->requestDate ? Carbon::parse($model->requestDate)->format('d/m/Y') : null;
        $this->dateSendFormat =  $model->dateSend ? Carbon::parse($model->dateSend)->format('d/m/Y') : null;
        $this->dateReceivedFormat =  $model->dateReceived ? Carbon::parse($model->dateReceived)->format('d/m/Y') : null;

        $this->supports = $model->getSupports();
        $this->documents = $model->getDocuments();



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
            if (!($model = CustomerHealthDamageQualificationSourceDiagnostic::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationSourceDiagnostic();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationSourceDiagnostic();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_qualification_source_id = $object->customerHealthDamageQualificationSourceId;
        $model->dateOf = $object->dateOf ? Carbon::parse($object->dateOf)->timezone('America/Bogota') : null;
        $model->diagnostic = $object->diagnostic ? $object->diagnostic->value : null;
        $model->laterality = $object->laterality ? $object->laterality->value : null;
        $model->entityPerformsDiagnostic = $object->entityPerformsDiagnostic ? $object->entityPerformsDiagnostic->value : null;
        $model->codeCIE10 = $object->codeCIE10 ? $object->codeCIE10->id : null;
        $model->description = $object->description;
        $model->isRequestedSupport = $object->isRequestedSupport;
        $model->requestDate = $object->requestDate ? Carbon::parse($object->requestDate)->timezone('America/Bogota') : null;
        $model->applicant = $object->applicant ? $object->applicant->value : null;
        $model->directorApt = $object->directorApt ? $object->directorApt->value : null;
        $model->dateSend = $object->dateSend ? Carbon::parse($object->dateSend)->timezone('America/Bogota') : null;
        $model->dateReceived = $object->dateReceived ? Carbon::parse($object->dateReceived)->timezone('America/Bogota') : null;

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

        CustomerHealthDamageQualificationSourceDiagnosticDocumentDTO::bulkInsert($object->documents, $model->id);
        CustomerHealthDamageQualificationSourceDiagnosticSupportDTO::bulkInsert($object->supports, $model->id);

        return CustomerHealthDamageQualificationSourceDiagnostic::find($model->id);
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
                if ($model instanceof CustomerHealthDamageQualificationSourceDiagnostic) {
                    $parsed[] = (new CustomerHealthDamageQualificationSourceDiagnosticDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationSourceDiagnostic) {
            return (new CustomerHealthDamageQualificationSourceDiagnosticDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationSourceDiagnosticDTO();
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
