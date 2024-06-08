<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInternalProject;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerController;
use Wgroup\CustomerInternalProjectAgent\CustomerInternalProjectAgent;
use Wgroup\CustomerInternalProjectAgentTracking\CustomerInternalProjectAgentTracking;
use Wgroup\CustomerUser\CustomerUser;
use Wgroup\Models\Customer;
use RainLab\User\Models\User;
use Mail;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerInternalProjectDTO {

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
     * @param $model: Modelo CustomerInternalProject
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->name = $model->name;
        $this->customerId = $model->customer;
        $this->customer = CustomerInternalProjectDTO::parse($model->getCustomer(), "5");
        $this->type = $model->getType();
        $this->description = $model->description;
        $this->serviceOrder = $model->serviceOrder;
        $this->defaultSkill = $model->getDefaultSkill();
        $this->estimatedHours = $model->estimatedHours;
        $this->status = $model->getStatus();
        $this->agents = $model->getAgentsBy();
        $this->isRecurrent = $model->isRecurrent == 1 ? true : false;
        $this->isBilled = $model->isBilled == 1 ? true : false;
        $this->invoiceNumber = $model->invoiceNumber;
        $this->deliveryDate =  Carbon::parse($model->deliveryDate);
        //$this->agents = CustomerInternalProjectAgentDTO::parse($model->agents);
        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoSummary($model)
    {
        $this->id = $model->id;
        $this->projectAgentId = $model->project_agent_id;
        $this->customerId = $model->customer_id;
        $this->customerName = $model->customerName != "" ? $this->substru($model->customerName, 0, 50) : "";
        $this->agentName = $model->agentName;
        $this->description = $model->description;
        $this->name = $model->name;
        $this->type = $model->type;
        $this->assignedHours = $model->assignedHours;
        $this->scheduledHours = $model->scheduledHours;
        $this->runningHours = $model->runningHours;
        $this->serviceOrder = $model->serviceOrder;
        $this->email = isset($model->email) ? $model->email : '';

        $data[] = array("value" => (int)$model->scheduledHours, "color" => "#46BFBD","highlight" => "#5AD3D1", "label" => "Programadas");
        $data[] = array("value" => (int)$model->runningHours, "color" => "#FDB45C","highlight" => "#FFC870", "label" => "Ejecutadas");

        $this->data = $data;

    }

    private function substru($str,$from,$len){
        return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $from .'}'.'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $len .'}).*#s','$1', $str);
    }

    private function getBasicInfoSetting($model)
    {
        //$this->id = $model->id;
        $this->availabilityHours = $model->availabilityHours;
        $this->assignedHours = $model->assignedHours;
        $this->scheduledHours = $model->scheduledHours;
        $this->runningHours = $model->runningHours;

        $data[] = array("value" => (int)$model->scheduledHours, "color" => "#46BFBD","highlight" => "#5AD3D1", "label" => "Programadas");
        $data[] = array("value" => (int)$model->runningHours, "color" => "#FDB45C","highlight" => "#FFC870", "label" => "Ejecutadas");

        $this->data = $data;
    }

    private function getBasicInfoReport($model)
    {
        $type = "timeline-item";

        if ($model->type == "01")
            $type = "timeline-item success";

        $this->id = $model->id;
        $this->projectAgentId = $model->project_agent_id;
        $this->description = $model->task;
        $this->type = $type;
        $this->time = Carbon::parse($model->startDateTime)->format('d/m/Y');
    }

    private function getBasicInfoAgent($model)
    {
        $this->id = $model->id;
        $this->projectId = 0;
        $this->name = $model->name;
        $this->availabilityHours = $model->availabilityHours;
        $this->assignedHours = $model->assignedHours;
        $this->notAssignedHours = $model->notAssignedHours;
        $this->scheduledHours = 0;
    }

    private function getBasicInfoAgentBy($model)
    {
        $this->id = $model->id;
        $this->name = $model->name;
    }

    private function getBasicInfoProjectTaskAgentBy($model)
    {
        $this->id = $model->id;
        $this->task = $model->task;
        $this->type = $model->type;
        $this->status = $model->status;
        $this->startDateTime = Carbon::parse($model->startDateTime)->format('d/m/Y H:i:s');
        $this->endDateTime = Carbon::parse($model->endDateTime)->format('d/m/Y H:i:s');
    }

    private function getBasicInfoProjectTask($model)
    {
        $this->id = $model->id;
        $this->task = $model->task;
        $this->type = $model->type;
        $this->status = $model->status;
        $this->observation = $model->observation;
        $this->shortObservation = $model->observation != "" ? $this->substru($model->observation, 0, 100) : "";
        $this->startDateTime = Carbon::parse($model->startDateTime)->format('d/m/Y H:i:s');
        $this->endDateTime = Carbon::parse($model->endDateTime)->format('d/m/Y H:i:s');
        $this->duration = $model->duration;;
        $this->agent = $model->agent;;
    }

    private function getBasicInfoCustomer($model)
    {
        $this->id = $model->id;
        $this->item = $model->name;
        $this->value = $model->id;
        $this->arl = $model->arl;
    }

    private function getBasicInfoTask($model)
    {
        $this->id = $model->id;
        $this->title = $model->title;
        $this->starts_at = Carbon::parse($model->starts_at);
        $this->ends_at = Carbon::parse($model->ends_at);
        $this->type = $model->type;
        $this->tableName = $model->tableName;
    }

    private function getBasicInfoGantt($model)
    {
        $this->id = $model->id;
        $this->originalId = $model->originalId;
        $this->parentId = $model->parentId;
        $this->businessName = $model->businessName;
        $this->startDate = Carbon::parse($model->startDate)->toDateTimeString();;
        $this->endDateTime = Carbon::parse($model->endDateTime)->toDateTimeString();;
        $this->type = $model->type;
        $this->assignedHours = $model->assignedHours;
        $this->scheduledHours = $model->scheduledHours;
        $this->runningHours = $model->runningHours;
        $this->percentage = $model->percentage;
        $this->classification = $model->classification;
        $this->amount = isset ($model->amount) ? round($model->amount) : 0;
        $this->expanded = $model->expanded == 1 ? true : false;
        $this->summary = $model->summary == 1 ? true : false;
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
            if (!($model = CustomerInternalProject::find($object->id))) {
                // No existe
                $model = new CustomerInternalProject();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInternalProject();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customer->id == "-S-" ? null : $object->customer->id;
        $model->type = $object->type->id == "-S-" ? null : $object->type->id;
        $model->name = $object->name;
        $model->description = $object->description;
        $model->serviceOrder = $object->serviceOrder;
        $model->defaultSkill = $object->defaultSkill->id == "-S-" ? null : $object->defaultSkill->id;
        $model->estimatedHours = $object->estimatedHours;
        //$model->deliveryDate = Carbon::createFromFormat('d/m/Y H:i:s', $object->event_date);
        $model->deliveryDate = Carbon::parse($object->event_date)->timezone('America/Bogota');
        $model->isRecurrent = $object->isRecurrent == true ? 1 : 0;
        $model->status = "activo";//$object->status->value == "-S-" ? null : $object->status->value;
        $model->isBilled = $object->isBilled == true ? 1 : 0;//$object->status->value == "-S-" ? null : $object->status->value;
        $model->invoiceNumber = $object->invoiceNumber;//$object->status->value == "-S-" ? null : $object->status->value;

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
                        if (!($customerProjectAgent = CustomerInternalProjectAgent::find($projectAgent->id))) {
                            // No existe
                            if (!($customerProjectAgent = CustomerInternalProjectAgent::whereProjectId($model->id)->whereAgentId($projectAgent->agentId)->first())) {
                                $customerProjectAgent = new CustomerInternalProjectAgent();
                                $isAlertEdit = false;
                            }
                        }
                    } else {
                        $customerProjectAgent = new CustomerInternalProjectAgent();
                        $isAlertEdit = false;
                    }

                    $customerProjectAgent->project_id    = $model->id;
                    $customerProjectAgent->agent_id = $projectAgent->agentId;
                    $customerProjectAgent->estimatedHours = $projectAgent->scheduledHours;

                    if ($isAlertEdit) {

                        // actualizado por
                        $customerProjectAgent->updatedBy = $userAdmn->id;

                        // Guarda
                        $customerProjectAgent->save();

                        // Actualiza timestamp
                        $customerProjectAgent->touch();
                    } else {
                        // Creado por
                        $customerProjectAgent->createdBy = $userAdmn->id;
                        $customerProjectAgent->updatedBy = $userAdmn->id;

                        // Guarda
                        $customerProjectAgent->save();
                    }
                }
            }
        }

        //Envio email
        if (!$isEdit) {
            //TODO DAB
            CustomerInternalProjectDTO::sendMail($object);
        }

        return CustomerInternalProject::find($model->id);
    }

    public static function sendAndSaveStatus($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }



        $projectData = array();

        //var_dump($object);

        foreach ($object->projects  as $project) {
            $model = new CustomerInternalProjectAgentTracking();
            $object->email = $project->email;
            $object->agentName = $project->agentName;

            $model->project_agent_id = $project->projectAgentId;
            $model->type = $object->tracking->action;
            $model->observation = $object->tracking->description;
            //$model->estimatedHours = $project->estimatedHours;
            $model->assignedHours = $project->assignedHours;
            $model->scheduledHours = $project->scheduledHours;
            $model->runningHours = $project->runningHours;

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;


            // Guarda
            $model->save();

            $projectData[] = array(
                "customer" => $project->customerName,
                "projectName" => $project->name,
                "projectDescription" => $project->description,
                //"estimatedHours" => $project->estimatedHours,
                "assignedHours" => $project->assignedHours,
                "scheduledHours" => $project->scheduledHours,
                "runningHours" => $project->runningHours,
            );
        }

        $emailData = array(
            "name" => $object->agentName,
            "email" => $object->email,
            "observation" => $object->tracking->description,
            "projects" => $projectData
        );

        //TODO DAB Envio email
        CustomerInternalProjectDTO::sendMailStatus($emailData);


        return CustomerInternalProject::find($model->id);
    }

    private static function sendMail($project)
    {

        try {
            //Log::info("Envio correo proyecto");
            if ($project->agents) {
                foreach ($project->agents as $projectAgent) {
                    if ($agentModel = CustomerUser::find($projectAgent->agentId))
                    {
                        //Log::info("Existe agente: ".$agentModel->user_id);

                        if ($userModel = User::find($agentModel->user_id)) {
                            //Log::info("Existe usuario: ".$userModel->email);

                            if (($modelCustomer = Customer::find($project->customer->id))) {
                                //Log::info("Existe cliente: ".$modelCustomer->businessName);

                                //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                                $params['Empresa_Cli'] = $modelCustomer->businessName;
                                $params['Asesor_Int'] = $agentModel->name;
                                $params['HorasAsignadas_int'] = $projectAgent->scheduledHours;
                                $params['Descripcion_int'] = $project->description;
                                $params['Fecha_int'] = $project->event_date;

                                Mail::sendTo($userModel->email, 'rainlab.user::mail.agenda_asesores_int', $params);
                            } else {
                                //Log::info("Envio correo proyecto ex ".$project->customer->id);
                            }
                        } else {
                            //Log::info("Envio correo proyecto ex ".$projectAgent->agentId);
                        }
                    } else {
                        //Log::info("Envio correo proyecto ex ".$agentModel->user_id);
                    }
                }
            }
        }
        catch (Exception $ex) {
            //Flash::error($ex->getMessage());
            Log::error($ex);
            //Log::info("Envio correo proyecto ex");
            //Log::info($ex->getMessage());
            //Log::info($projectAgent->businessNameSSS);
        }
    }

    private static function sendMailStatus($emailData)
    {
        try {
            Mail::sendTo($emailData["email"], 'rainlab.user::mail.report_status_internal_project', $emailData);
        }
        catch (Exception $ex) {
            //var_dump($ex);
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

    private function parseArray($model, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoSetting($model);
                break;
            case "3":
                $this->getBasicInfoReport($model);
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
            case "9":
                $this->getBasicInfoProjectTaskAgentBy($model);
                break;
            case "10":
                $this->getBasicInfoProjectTask($model);
                break;
            case "11":
                $this->getBasicInfoGantt($model);
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
                if ($model instanceof CustomerInternalProject) {
                    $parsed[] = (new CustomerInternalProjectDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CustomerInternalProjectDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInternalProject) {
            return (new CustomerInternalProjectDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInternalProjectDTO();
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
