<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerContractDetailActionPlan;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerController;
use Wgroup\CustomerContractDetail\CustomerContractDetail;
use Wgroup\CustomerContractDetailActionPlanAlert\CustomerContractDetailActionPlanAlert;
use Wgroup\CustomerContractDetailActionPlanAlert\CustomerContractDetailActionPlanAlertDTO;
use Wgroup\CustomerContractDetailActionPlanResp\CustomerContractDetailActionPlanResp;
use Wgroup\CustomerContractDetailActionPlanResp\CustomerContractDetailActionPlanRespDTO;
use Wgroup\CustomerContractor\CustomerContractor;
use Wgroup\Models\Customer;
use Mail;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerContractDetailActionPlanDTO {

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
     * @param $model: Modelo CustomerContractDetailActionPlan
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->contractActionPlanId = $model->contract_action_plan_id;
        $this->minDate =  Carbon::now();
        $this->closeDateTime =  Carbon::parse($model->closeDateTime);
        $this->description = $model->description;
        $this->shortDescription = $model->description != "" ? $this->substru($model->description, 0, 100) : "";
        $this->status = $model->getStatusType();
        $this->agent = $model->agent;

        $this->alerts = CustomerContractDetailActionPlanAlertDTO::parse($model->getAlerts());
        $this->responsibles = CustomerContractDetailActionPlanRespDTO::parse($model->getResponsible());

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
    }

    private function substru($str,$from,$len){
        return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $from .'}'.'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $len .'}).*#s','$1', $str);
    }

    public static function  UpdateModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        if ($object->id) {
            // Existe
            if (!($model = CustomerContractDetailActionPlan::find($object->id))) {
                // No existe
                $model = new CustomerContractDetailActionPlan();
                $isEdit = false;
            }
        } else {
            $model = new CustomerContractDetailActionPlan();
            $isEdit = false;
        }

        $model->status = $object->status;
        //$model->agent_id = $object->agent->id;

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
            if (!($model = CustomerContractDetailActionPlan::find($object->id))) {
                // No existe
                $model = new CustomerContractDetailActionPlan();
                $isEdit = false;
            }
        } else {
            $model = new CustomerContractDetailActionPlan();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->contract_detail_id = $object->contractDetailId;
        $model->closeDateTime = Carbon::parse($object->closeDateTime)->timezone('America/Bogota');
        $model->description = $object->description;
        $model->status = "abierto";
        //$model->agent_id = $object->agent->id;

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

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        // Datos de contacto
        if ($object->alerts) {
            foreach ($object->alerts as $actionPlanAlert) {
                if ($actionPlanAlert) {

                    if ($actionPlanAlert->id) {
                        // Existe
                        if (!($customerManagementActionPlanAlert = CustomerContractDetailActionPlanAlert::find($actionPlanAlert->id))) {
                            // No existe
                            $customerManagementActionPlanAlert = new CustomerContractDetailActionPlanAlert();
                            $isAlertEdit = false;
                        }
                    } else {
                        $customerManagementActionPlanAlert = new CustomerContractDetailActionPlanAlert();
                        $isAlertEdit = false;
                    }

                    $customerManagementActionPlanAlert->contract_action_plan_id    = $model->id;
                    $customerManagementActionPlanAlert->type = $actionPlanAlert->type->value;
                    $customerManagementActionPlanAlert->preference = $actionPlanAlert->preference->value;
                    $customerManagementActionPlanAlert->time = $actionPlanAlert->time;
                    $customerManagementActionPlanAlert->timeType = $actionPlanAlert->timeType->value;
                    $customerManagementActionPlanAlert->sent = $actionPlanAlert->sent;

                    if($actionPlanAlert->status && $actionPlanAlert->status->value != "-S-"){
                        $customerManagementActionPlanAlert->status = $actionPlanAlert->status->value;
                    }

                    //$customerManagementActionPlanAlert->agent_id = $model->agent->id;

                    if ($isEdit) {
                        // actualizado por
                        $customerManagementActionPlanAlert->updatedBy = $userAdmn->id;

                        // Guarda
                        $customerManagementActionPlanAlert->save();

                        // Actualiza timestamp
                        $customerManagementActionPlanAlert->touch();
                    } else {
                        // Creado por
                        $customerManagementActionPlanAlert->createdBy = $userAdmn->id;
                        $customerManagementActionPlanAlert->updatedBy = $userAdmn->id;

                        // Guarda
                        $customerManagementActionPlanAlert->save();
                    }
                }
            }
        }

        if ($object->responsibles) {
            foreach ($object->responsibles as $actionPlanResponsible) {
                if ($actionPlanResponsible && $actionPlanResponsible->contactId > 0) {
                    if ($actionPlanResponsible->isActive)
                    {
                        if ($actionPlanResponsible->id) {
                            // Existe
                            if (!($customerManagementActionPlanResp = CustomerContractDetailActionPlanResp::find($actionPlanResponsible->id))) {
                                // No existe
                                $customerManagementActionPlanResp = new CustomerContractDetailActionPlanResp();
                                $isAlertEdit = false;
                            }
                        } else {
                            $customerManagementActionPlanResp = new CustomerContractDetailActionPlanResp();
                            $isAlertEdit = false;
                        }

                        $customerManagementActionPlanResp->contract_action_plan_id    = $model->id;
                        $customerManagementActionPlanResp->contact_id = $actionPlanResponsible->contactId;

                        /*
                        if($actionPlanResponsible->status && $actionPlanResponsible->status->value != "-S-"){
                            $customerManagementActionPlanResp->status = $actionPlanResponsible->status->value;
                        }
                        */
                        $customerManagementActionPlanResp->status = "Activo";
                        $customerManagementActionPlanResp->createdBy = $userAdmn->id;
                        //$customerManagementActionPlanResp->agent_id = $model->agent->id;

                        if ($isEdit) {
                            // actualizado por
                            $customerManagementActionPlanResp->updatedBy = $userAdmn->id;

                            // Guarda
                            $customerManagementActionPlanResp->save();

                            // Actualiza timestamp
                            $customerManagementActionPlanResp->touch();
                        } else {
                            // Creado por
                            $customerManagementActionPlanResp->createdBy = $userAdmn->id;
                            $customerManagementActionPlanResp->updatedBy = $userAdmn->id;

                            // Guarda
                            $customerManagementActionPlanResp->save();
                        }

                        //Envio de correo
                        try {
                            if ($actionPlanResponsible->email != "") {
                                if ($contractDetail = CustomerContractDetail::find($object->contractDetailId)) {
                                    if ($contract = CustomerContractor::find($contractDetail->contractor_id)) {
                                        if (($modelCustomer = Customer::find($contract->contractor_id))) {
                                            //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                                            $params['Empresa'] = $modelCustomer->businessName;
                                            $params['Fecha'] = $object->closeDateTime;
                                            Mail::sendTo($actionPlanResponsible->email, 'rainlab.user::mail.plan_accion_asesores', $params);
                                        }
                                    }
                                }
                            }
                        }
                        catch (Exception $ex) {
                            //Flash::error($ex->getMessage());
                        }

                    } else {
                        if ($actionPlanResponsible->id) {
                            // Existe
                            if (($customerManagementActionPlanResp = CustomerContractDetailActionPlanResp::find($actionPlanResponsible->id))) {
                                $customerManagementActionPlanResp->delete();
                            }
                        }
                    }
                }
            }
        }

        return CustomerContractDetailActionPlan::find($model->id);
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
                if ($model instanceof CustomerContractDetailActionPlan) {
                    $parsed[] = (new CustomerContractDetailActionPlanDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerContractDetailActionPlan) {
            return (new CustomerContractDetailActionPlanDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerContractDetailActionPlanDTO();
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
