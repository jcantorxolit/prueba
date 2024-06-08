<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerImprovementPlanActionPlanNotified;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Mail;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerImprovementPlanActionPlanNotifiedDTO
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
     * @param $model : Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerImprovementPlanActionPlanId = $model->customer_improvement_plan_action_plan_id;
        $this->responsible = $model->getResponsible();

        $this->createdBy = $model->creator->name;
        $this->created_at = $model->created_at->format('d/m/Y');

        $this->tokensession = $this->getTokenSession(true);
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
            if (!($model = CustomerImprovementPlanActionPlanNotified::find($object->id))) {
                // No existe
                $model = new CustomerImprovementPlanActionPlanNotified();
                $isEdit = false;
            }
        } else {
            $model = new CustomerImprovementPlanActionPlanNotified();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->customer_improvement_plan_action_plan_id = $object->customerImprovementPlanActionPlanId;
        $model->responsible = $object->responsible != null ? $object->responsible->id : null;
        $model->responsibleType = $object->responsible != null ? $object->responsible->type : null;

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

        return CustomerImprovementPlanActionPlanNotified::find($model->id);

    }

    public static function  bulkInsert($records, $data, $parentId)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        /*if (!$records) {
            return false;
        }*/


        foreach ($records as $record) {

            if ($record->responsible == null) {
                continue;
            }

            if (!$record->id) {
                if (CustomerImprovementPlanActionPlanNotified::whereCustomerImprovementPlanActionPlanId($parentId)
                        ->whereResponsible($record->responsible->id)
                        ->where('responsibleType', $record->responsible->type)->count() > 0
                ) {
                    continue;
                }
            }

            if ($record->id) {
                // Existe
                if (!($model = CustomerImprovementPlanActionPlanNotified::find($record->id))) {
                    // No existe
                    $model = new CustomerImprovementPlanActionPlanNotified();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerImprovementPlanActionPlanNotified();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/

            // cliente asociado
            $model->customer_improvement_plan_action_plan_id = $parentId;
            $model->responsible = $record->responsible != null ? $record->responsible->id : null;
            $model->responsibleType = $record->responsible != null ? $record->responsible->type : null;

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

                try {
                    if (isset($record->responsible->email) && $record->responsible->email != '') {
                        if (count($data)) {
                            $params['name'] = $record->responsible->name;
                            $params['Empresa'] = $data['Empresa'];
                            $params['Modulo'] = $data['Modulo'];
                            $params['Descripcion'] = $data['Descripcion'];
                            $params['Actividad'] = $data['Actividad'];
                            $params['Responsable'] = $data['Responsable'];
                            $params['Fecha'] = $data['Fecha'];
                            Mail::sendTo($record->responsible->email, 'rainlab.user::mail.plan_accion_notificacion_asesores', $params);
                        }
                    }
                } catch (\Exception $ex) {
                }

            }
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/

        return true;
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
                if ($model instanceof CustomerImprovementPlanActionPlanNotified) {
                    $parsed[] = (new CustomerImprovementPlanActionPlanNotifiedDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerImprovementPlanActionPlanNotifiedDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerImprovementPlanActionPlanNotified) {
            return (new CustomerImprovementPlanActionPlanNotifiedDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerImprovementPlanActionPlanNotifiedDTO();
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