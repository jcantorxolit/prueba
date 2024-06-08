<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\QuoteDetail;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\QuoteService\QuoteServiceDTO;

/**
 * Description of ReportDTO
 *
 * @author jdblandon
 */
class QuoteDetailDTO {

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
     * @param $model: Modelo QuoteDetail
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->quoteId = $model->quote_id;
        $this->serviceId = $model->service_id;
        $this->service = QuoteServiceDTO::parse($model->service);
        $this->quantity = $model->quantity;
        $this->hour = $model->quantity;
        $this->total = $model->total;
        $this->totalModified = $model->totalModified;

    }

    private function getBasicInfoSummary($model)
    {
        $this->id = $model->id;
        $this->quoteId = $model->quote_id;
        $this->serviceId = $model->service_id;
        $this->service = QuoteServiceDTO::parse($model->service);
        $this->quantity = $model->quantity;
        $this->hour = $model->quantity;
        $this->total = $model->total;
        $this->totalModified = $model->totalModified;
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
            if (!($model = QuoteDetail::find($object->id))) {
                // No existe
                $model = new QuoteDetail();
                $isEdit = false;
            }
        } else {
            $model = new QuoteDetail();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customer->id == "-S-" ? null : $object->customer->id;
        $model->type = $object->type->value == "-S-" ? null : $object->type->value;
        $model->name = $object->name;
        $model->description = $object->description;
        $model->serviceOrder = $object->serviceOrder;
        $model->defaultSkill = $object->defaultSkill->value == "-S-" ? null : $object->defaultSkill->value;
        $model->estimatedHours = $object->estimatedHours;
        $model->deliveryDate = Carbon::createFromFormat('d/m/Y H:i:s', $object->event_date);
        $model->isRecurrent = $object->isRecurrent == true ? 1 : 0;
        $model->status = "activo";//$object->status->value == "-S-" ? null : $object->status->value;

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
        if ($object->agents) {
            foreach ($object->agents as $projectAgent) {
                $isAlertEdit = true;
                if ($projectAgent) {


                    if ($projectAgent->id) {
                        // Existe
                        if (!($QuoteDetailAgent = QuoteDetailAgent::find($projectAgent->id))) {
                            // No existe
                            if (!($QuoteDetailAgent = QuoteDetailAgent::whereProjectId($model->id)->whereAgentId($projectAgent->agentId)->first())) {
                                $QuoteDetailAgent = new QuoteDetailAgent();
                                $isAlertEdit = false;
                            }
                        }
                    } else {
                        $QuoteDetailAgent = new QuoteDetailAgent();
                        $isAlertEdit = false;
                    }

                    $QuoteDetailAgent->project_id    = $model->id;
                    $QuoteDetailAgent->agent_id = $projectAgent->agentId;
                    $QuoteDetailAgent->estimatedHours = $projectAgent->scheduledHours;

                    if ($isAlertEdit) {

                        // actualizado por
                        $QuoteDetailAgent->updatedBy = $userAdmn->id;

                        // Guarda
                        $QuoteDetailAgent->save();

                        // Actualiza timestamp
                        $QuoteDetailAgent->touch();
                    } else {
                        // Creado por
                        //Log::info("Envio correo proyecto before");


                        $QuoteDetailAgent->createdBy = $userAdmn->id;
                        $QuoteDetailAgent->updatedBy = $userAdmn->id;

                        // Guarda
                        $QuoteDetailAgent->save();

                        //Envio email
                        /*
                        try {
                            //Log::info("Envio correo proyecto");

                            if ($agentModel = Agent::find($projectAgent->agentId))
                            {
                                //Log::info("Existe agente".$agentModel->user_id);

                                if ($userModel = User::find($agentModel->user_id)) {
                                    //Log::info("Existe usuario".$userModel->email);

                                    if (($modelCustomer = Customer::find($object->customer->id))) {
                                        //Log::info("Existe cliente".$modelCustomer->businessName);

                                        //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                                        $params['Empresa'] = $modelCustomer->businessName;
                                        $params['Asesor'] = $agentModel->name;
                                        $params['HorasAsignadas'] = $projectAgent->scheduledHours;
                                        $params['Descripcion'] = $object->description;
                                        $params['Fecha'] = $object->event_date;

                                        if ($modelCustomer->maincontacts()->count()) {
                                            $params['Contacto'] = $modelCustomer->maincontacts()[0]->name;
                                        } else {
                                            $params['Contacto'] = '';
                                        }

                                        Mail::sendTo($userModel->email, 'rainlab.user::mail.agenda_asesores', $params);
                                    } else {
                                        //Log::info("Envio correo proyecto ex ".$object->customer->id);
                                        //Log::info($projectAgent->businessNameSSS);
                                    }
                                } else {
                                    //Log::info("Envio correo proyecto ex ".$projectAgent->agentId);
                                    //Log::info($projectAgent->businessNameSSS);
                                }
                            } else {
                                //Log::info("Envio correo proyecto ex ".$agentModel->user_id);
                                //Log::info($projectAgent->businessNameSSS);
                            }

                        }
                        catch (Exception $ex) {
                            Flash::error($ex->getMessage());
                            //Log::info("Envio correo proyecto ex");
                            //Log::info($ex->getMessage());
                            //Log::info($projectAgent->businessNameSSS);
                        }
                        */
                    }
                }
            }
        }

        return QuoteDetail::find($model->id);
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
                $this->getBasicInfoSetting($model);
                break;
            case "3":
                $this->getBasicInfoQuoteDetail($model);
                break;
            case "4":
                $this->getBasicInfoAgent($model);
                break;
            case "5":
                $this->getBasicInfoCustomer($model);
                break;
            case "6":
                $this->getBasicInfoTask($model);
                break;
            case "7":
                $this->getBasicInfoAgentBy($model);
                break;
            case "8":
                $this->getBasicInfoProjectTaskAgentBy($model);
                break;
            default:
                $this->getBasicInfoSummary($model);
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
                if ($model instanceof QuoteDetail) {
                    $parsed[] = (new QuoteDetailDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new QuoteDetailDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof QuoteDetail) {
            return (new QuoteDetailDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new QuoteDetailDTO();
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
