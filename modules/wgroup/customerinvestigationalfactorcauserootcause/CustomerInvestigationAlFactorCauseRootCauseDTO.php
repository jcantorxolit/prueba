<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInvestigationAlFactorCauseRootCause;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerInvestigationAlFactorCauseRootCauseDTO
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
     * @param $model : Modelo CustomerInvestigationAlFactorCauseRootCause
     */
    private function getBasicInfo($model)
    {

        //Codigo
        $this->id = $model->id;
        $this->customerInvestigationFactorCauseId = $model->customer_investigation_factor_cause_id;
        $this->parent = $model->getParent();
        $this->cause = $model->cause;
        $this->probabilityOccurrence = $model->getProbabilityOccurrence();
        $this->effect = $model->getEffect();
        $this->detectionLevel = $model->getDetectionLevel();
        $this->factor = $model->factor;

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
            if (!($model = CustomerInvestigationAlFactorCauseRootCause::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAlFactorCauseRootCause();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAlFactorCauseRootCause();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_investigation_factor_cause_id = $object->customerInvestigationFactorCauseId;
        $model->cause = $object->cause;
        $model->probabilityOccurrence = $object->probabilityOccurrence != null ? $object->probabilityOccurrence->value : null;
        $model->effect = $object->effect != null ? $object->effect->value : null;
        $model->detectionLevel = $object->detectionLevel != null ? $object->detectionLevel->value : null;
        $model->factor = $object->factor;

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

        //TODO

        return CustomerInvestigationAlFactorCauseRootCause::find($model->id);
    }

    public static function  bulkInsert($records, $entityId)
    {
        try {
            foreach ($records as $record) {
                $isEdit = true;
                if ($record) {

                    if (trim($record->cause) == '') {
                        continue;
                    }

                    if (!$record->id) {
                        if (CustomerInvestigationAlFactorCauseRootCause::whereCustomerInvestigationFactorCauseId($entityId)
                                ->whereCause($record->cause)->count() > 0
                        ) {
                            continue;
                        }
                    }


                    if ($record->id) {
                        if (!($model = CustomerInvestigationAlFactorCauseRootCause::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerInvestigationAlFactorCauseRootCause();
                        }
                    } else {
                        $model = new CustomerInvestigationAlFactorCauseRootCause();
                        $isEdit = false;
                    }

                    /** :: ASIGNO DATOS BASICOS ::  **/
                    $model->customer_investigation_factor_cause_id = $entityId;
                    $model->cause = $record->cause;
                    $model->probabilityOccurrence = $record->probabilityOccurrence != null ? $record->probabilityOccurrence->value : null;
                    $model->effect = $record->effect != null ? $record->effect->value : null;
                    $model->detectionLevel = $record->detectionLevel != null ? $record->detectionLevel->value : null;
                    $model->factor = $record->factor;

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
        } catch (\Exception $ex) {
            var_dump($ex->getMessage());
        }
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
                if ($model instanceof CustomerInvestigationAlFactorCauseRootCause) {
                    $parsed[] = (new CustomerInvestigationAlFactorCauseRootCauseDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAlFactorCauseRootCause) {
            return (new CustomerInvestigationAlFactorCauseRootCauseDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlFactorCauseRootCauseDTO();
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
