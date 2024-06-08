<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerOccupationalInvestigationAlWitness;

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
class CustomerOccupationalInvestigationAlWitnessDTO {

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
        $this->customerOccupationalInvestigationId = $model->customer_occupational_investigation_id;
        $this->type = $model->getType();
        $this->isWatching = $model->getIsWatching();
        $this->documentType = $model->getDocumentType();
        $this->documentNumber = $model->document_number;
        $this->name = $model->name;
        $this->job = $model->job;
        $this->story = $model->story;

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
            if (!($model = CustomerOccupationalInvestigationAlWitness::find($object->id))) {
                // No existe
                $model = new CustomerOccupationalInvestigationAlWitness();
                $isEdit = false;
            }
        } else {
            $model = new CustomerOccupationalInvestigationAlWitness();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->customer_occupational_investigation_id = $object->customerOccupationalInvestigationId;

        $model->type = $object->type != null ? $object->type->value : null;
        $model->isWatching = $object->isWatching != null ? $object->isWatching->value : null;

        $model->document_type = $object->documentType != null ? $object->documentType->value : null;
        $model->document_number = $object->documentNumber;
        $model->name = $object->name;
        $model->job = $object->job;
        $model->story = $object->story;

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



        return CustomerOccupationalInvestigationAlWitness::find($model->id);
    }

    public static function  bulkInsert($object, $entityId)
    {
        $userAdmn = Auth::getUser();

        try {
            foreach ($object as $record) {
                $isEdit = true;
                if ($record) {
                    if ($record->id) {
                        if (!($model = CustomerOccupationalInvestigationAlWitness::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerOccupationalInvestigationAlWitness();
                        }
                    } else {
                        $model = new CustomerOccupationalInvestigationAlWitness();
                        $isEdit = false;
                    }

                    $model->customer_occupational_investigation_id = $entityId;
                    $model->type = $record->type != null ? $record->type->value : null;
                    $model->isWatching = isset($record->isWatching) && $record->isWatching != null ? $record->isWatching->value : null;

                    $model->document_type = $record->documentType != null ? $record->documentType->value : null;
                    $model->document_number = $record->documentNumber;
                    $model->name = $record->name;
                    $model->job = $record->job;
                    $model->story = $record->story;

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
        catch (Exception $ex) {
            //Log::info("Envio correo proyecto ex");
            //Log::info($ex->getMessage());
        }


        return CustomerOccupationalInvestigationAlWitness::find($model->id);
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
                if ($model instanceof CustomerOccupationalInvestigationAlWitness) {
                    $parsed[] = (new CustomerOccupationalInvestigationAlWitnessDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerOccupationalInvestigationAlWitness) {
            return (new CustomerOccupationalInvestigationAlWitnessDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerOccupationalInvestigationAlWitnessDTO();
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
