<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Mail;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerController;
use Wgroup\CustomerAudit\CustomerAudit;
use Wgroup\CustomerTrackingNotification\CustomerTrackingNotification;
use Wgroup\CustomerTrackingNotification\CustomerTrackingNotificationDTO;
use Wgroup\Models\Customer;
use RainLab\User\Models\User;
use AdeN\Api\Modules\Customer\CustomerModel;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerTrackingDTO
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
        /*
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->type = $model->getTrackingType();
        $this->agent = $model->agent->name;
        $this->observation = $model->observation;
        $this->status = $model->getStatusType();
        $this->eventDateTime = Carbon::parse($model->eventDateTime)->format('d/m/Y H:i:s');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        */

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->type = $model->getTrackingType();
        $this->isVisible = $model->isVisible == 1;
        $this->isEventSchedule = $model->isEventSchedule == 1;
        $this->isCustomer = $model->isCustomer == 1;
        $this->eventDate = $model->eventDateTime ? Carbon::parse($model->eventDateTime) : null;
        $this->minDate = Carbon::now();
        $this->eventDateTime = $model->eventDateTime ? Carbon::parse($model->eventDateTime)->format('d/m/Y H:i:s') : null;
        $this->observation = $model->observation;
        $this->shortObservation = $model->observation != "" ? $this->substru($model->observation, 0, 100) : "";
        $this->status = $model->getStatusType();
        $this->agent = CustomerModel::findAgentAndUserRaw($model->customer_id, $model->agent_id, $model->userType);
        $this->userModel = $model->creator();
        $this->comment = "";
        $this->comments = CustomerTrackingCommentDTO::parse($model->comments);
        //$this->coments = $model->comments;

        $this->alerts = CustomerTrackingAlertDTO::parse($model->alerts);
        $this->notifications = CustomerTrackingNotificationDTO::parse($model->notifications);


        //$this->alerts = [];

        $this->createdAt = $model->created_at ? Carbon::parse($model->created_at)->timezone('America/Bogota')->format('d/m/Y H:i:s') : null;
        $this->updated_at = $model->updated_at ? $model->updated_at->format('d/m/Y') : null;
        $this->tokensession = $this->getTokenSession(true);
    }

    private function substru($str,$from,$len){
        return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $from .'}'.'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $len .'}).*#s','$1', $str);
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $isEditMain = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerTracking::find($object->id))) {
                // No existe
                $model = new CustomerTracking();
                $isEdit = false;
                $isEditMain = false;
            }
        } else {
            $model = new CustomerTracking();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->type = $object->type ? $object->type->value : null;
        $model->isVisible = $object->isVisible;
        $model->isEventSchedule = $object->isEventSchedule;
        $model->isCustomer = $object->isCustomer;
        //DAB->20180831
        /*if ($object->isCustomer) {
            if ($object->module == "tracking") {
                $model->eventDateTime = Carbon::createFromFormat('d/m/Y H:i:s', $object->event_date);
            } else {
                $model->eventDateTime = Carbon::createFromFormat('d/m/Y H:i:s', $object->event_date);//$object->event_date;
            }
        } else {
            $model->eventDateTime = Carbon::createFromFormat('d/m/Y H:i:s', $object->event_date);
        }*/
        $model->eventDateTime = $object->eventDate ? Carbon::parse($object->eventDate)->timezone('America/Bogota') : null;
        $model->observation = $object->observation;
        $model->status = $object->status ?  $object->status->value : null;
        $model->agent_id = $object->agent ? $object->agent->id : null;
        $model->userType = $object->agent ? $object->agent->type : null;

        if ($isEdit) {
            $params['ID'] = $model->id;
            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

            $customerAudit = new CustomerAudit();
            $customerAudit->customer_id = $model->customer_id;
            $customerAudit->model_name = "Seguimiento";
            $customerAudit->model_id = $model->customer_id;
            $customerAudit->user_type = $userAdmn->wg_type;
            $customerAudit->user_id = $userAdmn->id;
            $customerAudit->action = "Editar";
            $customerAudit->observation = "Se realiza modificación exitosa del seguimiento: (" . $object->observation . ")";
            $customerAudit->date = Carbon::now('America/Bogota');
            $customerAudit->save();

            if (isset($object->comment)) {
                if (trim($object->comment) != '') {
                    $trackingCommentModel = new CustomerTrackingComment();

                    $trackingCommentModel->customer_tracking_id = $model->id;
                    $trackingCommentModel->comment = $object->comment;
                    $trackingCommentModel->createdBy = $userAdmn->id;
                    $trackingCommentModel->updatedBy = $userAdmn->id;
                    $trackingCommentModel->save();

                    //Envio de correo
                    try {
                        if (isset($object->agent->email) && $object->agent->email != '') {
                            if (($modelCustomer = Customer::find($object->customerId))) {
                                //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                                $params['Empresa'] = $modelCustomer->businessName;
                                $params['Descripcion'] = $object->comment;
                                $params['Fecha'] = $model->eventDateTime;
                                //TODO
                                Mail::sendTo($object->agent->email, 'rainlab.user::mail.seguimientos_asesores', $params);
                            }
                        } else {
                            if ($agentModel = Agent::find($object->agent->id)) {
                                if ($userModel = User::find($agentModel->user_id)) {
                                    if (($modelCustomer = Customer::find($object->customerId))) {
                                        //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                                        $params['Empresa'] = $modelCustomer->businessName;
                                        $params['Descripcion'] = $object->comment;
                                        $params['Fecha'] = $model->eventDateTime;
                                        //TODO
                                        Mail::sendTo($userModel->email, 'rainlab.user::mail.seguimientos_asesores', $params);

                                        /*Mail::send('rainlab.user::mail.seguimientos_asesores', [], function($message) use ($params, $userModel) {
                                            $message->from('noreply@oralcenter.com', 'Oral Center');
                                            $message->to($userModel->email, $name = $entity->name);
                                            $message->subject("Gracias por elegirnos");
                                        });*/
                                    }
                                }
                            }
                        }
                    } catch (\Exception $ex) {
                        //Flash::error($ex->getMessage());
                    }
                }
            }
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            $customerAudit = new CustomerAudit();
            $customerAudit->customer_id = $model->customer_id;
            $customerAudit->model_name = "Seguimiento";
            $customerAudit->model_id = $model->customer_id;
            $customerAudit->user_type = $userAdmn->wg_type;
            $customerAudit->user_id = $userAdmn->id;
            $customerAudit->action = "Guardar";
            $customerAudit->observation = "Se realiza adición exitosa del seguimiento: (" . $object->observation . ")";
            $customerAudit->date = Carbon::now('America/Bogota');
            $customerAudit->save();

            $params['ID'] = $model->id;
            //Envio de correo
            try {
                if (isset($object->agent->email) && $object->agent->email != '') {
                    if (($modelCustomer = Customer::find($object->customerId))) {
                        //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                        $params['Empresa'] = $modelCustomer->businessName;
                        $params['Descripcion'] = $object->observation;
                        $params['Fecha'] = $model->eventDateTime;
                        //TODO
                        Mail::sendTo($object->agent->email, 'rainlab.user::mail.seguimientos_asesores', $params);

                        /*Mail::send('rainlab.user::mail.seguimientos_asesores', $params, function($message) use ($object) {

                            $message->from('noreply@soft-waygroup.com', 'Waygroup');
                            $message->to($object->agent->email);

                        });*/
                    }
                } else {

                    $infoDetailEmail = Agent::getInfoDetailTable($object->agent->id, "email");

                    if ($infoDetailEmail != null && $infoDetailEmail->value != '') {
                        if (($modelCustomer = Customer::find($object->customerId))) {
                            //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                            $params['Empresa'] = $modelCustomer->businessName;
                            $params['Descripcion'] = $object->observation;
                            $params['Fecha'] = $model->eventDateTime;
                            //TODO
                            Mail::sendTo($infoDetailEmail->value, 'rainlab.user::mail.seguimientos_asesores', $params);
                            /*Mail::send('rainlab.user::mail.seguimientos_asesores', $params, function($message) use ($infoDetailEmail) {

                                $message->from('noreply@soft-waygroup.com', 'Waygroup');
                                $message->to($infoDetailEmail->value);

                            });*/
                        }
                    } else {
                        if ($agentModel = Agent::find($object->agent->id)) {
                            if ($userModel = User::find($agentModel->user_id)) {
                                if (($modelCustomer = Customer::find($object->customerId))) {
                                    //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                                    $params['Empresa'] = $modelCustomer->businessName;
                                    $params['Descripcion'] = $object->observation;
                                    $params['Fecha'] = $model->eventDateTime;
                                    //TODO
                                    Mail::sendTo($userModel->email, 'rainlab.user::mail.seguimientos_asesores', $params);

                                   /* Mail::send('rainlab.user::mail.seguimientos_asesores', $params, function($message) use ($userModel) {

                                        $message->from('noreply@soft-waygroup.com', 'Waygroup');
                                        $message->to($userModel->email);

                                    });*/
                                }
                            }
                        }
                    }
                }

            } catch (\Exception $ex) {
                //Flash::error($ex->getMessage());
                //var_dump($ex->getMessage());
            }
        }


        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        // Datos de contacto
        if ($object->alerts) {
            foreach ($object->alerts as $trackinAlert) {

                if ($trackinAlert->timeType != null && $trackinAlert->preference != null && $trackinAlert->type != null) {
                    if ($trackinAlert && $trackinAlert->type != null) {

                        if ($trackinAlert->id) {
                            // Existe
                            if (!($customerTrackingAlert = CustomerTrackingAlert::find($trackinAlert->id))) {
                                // No existe
                                $customerTrackingAlert = new CustomerTrackingAlert();
                                $isAlertEdit = false;
                            }
                        } else {
                            $customerTrackingAlert = new CustomerTrackingAlert();
                            $isAlertEdit = false;
                        }

                        $customerTrackingAlert->customer_tracking_id = $model->id;
                        $customerTrackingAlert->type = $trackinAlert->type->value;
                        $customerTrackingAlert->preference = $trackinAlert->preference->value;
                        $customerTrackingAlert->time = $trackinAlert->time;
                        $customerTrackingAlert->timeType = $trackinAlert->timeType->value;
                        $customerTrackingAlert->sent = $trackinAlert->sent;

                        if ($trackinAlert->status && $trackinAlert->status->value != "-S-") {
                            $customerTrackingAlert->status = $trackinAlert->status->value;
                        }

                        $customerTrackingAlert->agent_id = $object->agent->id;

                        if ($isEdit) {
                            // actualizado por
                            $customerTrackingAlert->updatedBy = $userAdmn->id;

                            // Guarda
                            $customerTrackingAlert->save();

                            // Actualiza timestamp
                            $customerTrackingAlert->touch();
                        } else {
                            // Creado por
                            $customerTrackingAlert->createdBy = $userAdmn->id;
                            $customerTrackingAlert->updatedBy = $userAdmn->id;

                            // Guarda
                            $customerTrackingAlert->save();
                        }
                    }
                }
            }
        }

        if (isset($object->notifications) && $object->notifications) {
            foreach ($object->notifications as $notification) {

                if ($notification->user != null) {

                    if ($notification->id) {
                        // Existe
                        if (!($notificationModel = CustomerTrackingNotification::find($notification->id))) {
                            // No existe
                            $notificationModel = new CustomerTrackingNotification();
                            $isAlertEdit = false;
                        }
                    } else {
                        $notificationModel = new CustomerTrackingNotification();
                        $isAlertEdit = false;
                    }

                    $notificationModel->customer_tracking_id = $model->id;
                    $notificationModel->user_id = $notification->user->id;
                    $notificationModel->type = $notification->user->type;

                    if ($isEdit) {
                        // actualizado por
                        $notificationModel->updatedBy = $userAdmn->id;

                        // Guarda
                        $notificationModel->save();

                        // Actualiza timestamp
                        $notificationModel->touch();

                    } else {
                        // Creado por
                        $notificationModel->createdBy = $userAdmn->id;
                        $notificationModel->updatedBy = $userAdmn->id;

                        // Guarda
                        $notificationModel->save();
                    }

                    //Envio de correo
                    try {
                        if (isset($notification->user->email) && $notification->user->email != '') {
                            if (($modelCustomer = Customer::find($object->customerId))) {
                                //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                                $params['Empresa'] = $modelCustomer->businessName;
                                if ($isEditMain) {
                                    $params['Descripcion'] = is_array($object->comment) ? $object->observation : $object->comment;
                                } else {
                                    $params['Descripcion'] = $object->observation;
                                }
                                $params['Fecha'] = $model->eventDateTime;
                                //TODO
                                Mail::sendTo($notification->user->email, 'rainlab.user::mail.seguimientos_asesores', $params);
                            }
                        } /*else {
                            if ($agentModel = Agent::find($object->agent->id)) {
                                if ($userModel = User::find($agentModel->user_id)) {
                                    if (($modelCustomer = Customer::find($object->customerId))) {
                                        //$params = ['NombreEmpresa' => $modelCustomer->businessName, 'Descripción' => $object->observation];
                                        $params['Empresa'] = $modelCustomer->businessName;
                                        //$params['Descripcion'] = (isset($object->comment)) && $object->comment != '' ? $object->comment : $object->observation;
                                        $params['Descripcion'] = $object->observation;
                                        $params['Fecha'] = $model->eventDateTime;
                                        //TODO
                                        //Mail::sendTo($notification->user->type, 'rainlab.user::mail.seguimientos_asesores', $params);

                                        Mail::send('rainlab.user::mail.seguimientos_asesores', $params, function ($message) use ($notification) {
                                            $message->from('noreply@sylogisoftware.com', 'SYLOGI');
                                            $message->to($notification->user->email);
                                            //$message->to("david.blandon@gmail.com");
                                            $message->subject("SYLOGI - NOTIFICACIÓN Seguimiento");
                                        });
                                    }
                                }
                            }
                        }*/
                    } catch (Exception $ex) {
                        //Flash::error($ex->getMessage());
                    }
                }
            }

        }

        return CustomerTracking::find($model->id);
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
                if ($model instanceof CustomerTracking) {
                    $parsed[] = (new CustomerTrackingDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerTracking) {
            return (new CustomerTrackingDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerTrackingDTO();
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
