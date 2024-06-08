<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerDiagnosticPreventionDocumentQuestion;

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
class CustomerDiagnosticPreventionDocumentQuestionDTO {

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
     * @param $model: Modelo CustomerDiagnosticPreventionDocumentQuestion
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerDiagnosticPreventionDocumentId = $model->customerDiagnosticPreventionDocumentId;
        $this->customerDiagnosticPreventionQuestionId = $model->customerDiagnosticPreventionQuestionId;
        $this->program = $model->program;
        $this->category = $model->category;
        $this->question = $model->question;
        $this->guide = $model->guide;
        $this->selected = $model->selected == 1;

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
            if (!($model = CustomerDiagnosticPreventionDocumentQuestion::find($object->id))) {
                // No existe
                $model = new CustomerDiagnosticPreventionDocumentQuestion();
                $isEdit = false;
            }
        } else {
            $model = new CustomerDiagnosticPreventionDocumentQuestion();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_diagnostic_prevention_document_id = $object->customerDiagnosticPreventionDocumentId;
        $model->program_prevention_question_id = $object->customerDiagnosticPreventionQuestionId;

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

        return CustomerDiagnosticPreventionDocumentQuestion::find($model->id);
    }

    public static function  bulkInsertOrUpdate($entities, $parentId)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        foreach ($entities as $object) {
            if (!$object) {
                return false;
            }

            $isEdit = false;

            $model = new CustomerDiagnosticPreventionDocumentQuestion();
            $model->customer_diagnostic_prevention_document_id = $parentId;
            $model->program_prevention_question_id = $object->programPreventionQuestionId;

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


        //return CustomerDiagnosticPreventionDocumentQuestion::find($model->id);
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
                if ($model instanceof CustomerDiagnosticPreventionDocumentQuestion) {
                    $parsed[] = (new CustomerDiagnosticPreventionDocumentQuestionDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDiagnosticPreventionDocumentQuestion) {
            return (new CustomerDiagnosticPreventionDocumentQuestionDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDiagnosticPreventionDocumentQuestionDTO();
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
