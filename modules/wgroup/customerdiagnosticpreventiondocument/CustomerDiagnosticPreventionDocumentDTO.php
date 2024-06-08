<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerDiagnosticPreventionDocument;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;
use Wgroup\CustomerDiagnosticPreventionDocumentQuestion\CustomerDiagnosticPreventionDocumentQuestion;
use Wgroup\CustomerDiagnosticPreventionDocumentQuestion\CustomerDiagnosticPreventionDocumentQuestionDTO;


/**
 * Description of CustomerDiagnosticPreventionDocumentDTO
 *
 * @author jdblandon
 */
class CustomerDiagnosticPreventionDocumentDTO
{

    function __construct($model = null)
    {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1")
    {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model : Modelo CustomerTracking
     */
    private function getBasicInfo($model)
    {

        $documentModel = CustomerDiagnosticPreventionDocument::find($model->id);

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->classification = $model->getClassification();
        $this->name = $model->name;
        $this->code = $model->code;
        $this->description = $model->description;
        $this->version = $model->version;
        $this->status = $model->getStatusType();
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($documentModel->document);
        $this->startDate = $model->endDate != null ? Carbon::parse($model->startDate) : null;
        $this->endDate = $model->endDate != null ? Carbon::parse($model->endDate) : null;
        $this->questions = $model->getQuestions();
        //$this->created_at = $model->created_at->format('d/m/Y');
        //$this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);

    }

    private function getBasicInfoList($model)
    {
        $documentModel = CustomerDiagnosticPreventionDocument::find($model->id);

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->classification = $model->classification;
        $this->code = $model->code;
        $this->name = $model->name;
        $this->description = $model->description;
        $this->version = $model->version;
        $this->status = $model->status;
        $this->startDate = $model->startDate;
        $this->endDate = $model->endDate;
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($documentModel->document);

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
       /* if ($object->id) {
            // Existe
            if (!($model = CustomerDiagnosticPreventionDocument::find($object->id))) {
                // No existe
                $model = new CustomerDiagnosticPreventionDocument();
                $isEdit = false;
            } else {
                //TOD ANULADO
                //$model = new CustomerDiagnosticPreventionDocument();
                //$isEdit = false;
            }
        } else {
            $model = new CustomerDiagnosticPreventionDocument();
            $isEdit = false;
        }*/

        if ($object->id) {
            // Existe
            if (!($model = CustomerDiagnosticPreventionDocument::find($object->id))) {
                // No existe
                $model = new CustomerDiagnosticPreventionDocument();
                $isEdit = false;
            }
        } else {
            $model = new CustomerDiagnosticPreventionDocument();
            $isEdit = false;
        }



        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->classification = $object->classification == null ? null : $object->classification->value;
        $model->customer_id = $object->customerId;
        $model->code = $object->code;
        $model->name = $object->name;
        $model->description = $object->description;
        $model->status = $object->status == null ? null : $object->status->value;
        $model->version = $object->version;
        $model->startDate = Carbon::parse($object->startDate)->timezone('America/Bogota');
        $model->endDate = Carbon::parse($object->endDate)->timezone('America/Bogota');


        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            //$model->touch();


        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;
            // Guarda
            $model->save();

        }

        CustomerDiagnosticPreventionDocumentQuestion::whereCustomerDiagnosticPreventionDocumentId($model->id)->delete();

        CustomerDiagnosticPreventionDocumentQuestionDTO::bulkInsertOrUpdate($object->questions, $model->id);

        return CustomerDiagnosticPreventionDocument::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {

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
                $this->getBasicInfoList($model);
                break;

            case "3":

                break;
            default:
                $this->getBasicInfo($model);
        }

        return $this;
    }

    public static function parse($info, $fmt_response = "1")
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerDiagnosticPreventionDocument) {
                    $parsed[] = (new CustomerDiagnosticPreventionDocumentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDiagnosticPreventionDocumentDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDiagnosticPreventionDocument) {
            return (new CustomerDiagnosticPreventionDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDiagnosticPreventionDocumentDTO();
            }
        }
    }

    private function getUserSsession()
    {
        if (!Auth::check())
            return null;

        return Auth::getUser();
    }

    private function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }
}
