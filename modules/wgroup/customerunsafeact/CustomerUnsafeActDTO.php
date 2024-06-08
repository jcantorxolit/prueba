<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerUnsafeAct;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;
use Wgroup\Models\Customer;
use Mail;

/**
 * Description of CustomerUnsafeActDTO
 *
 * @author jdblandon
 */
class CustomerUnsafeActDTO
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

        $documentModel = CustomerUnsafeAct::find($model->id);

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->status = $model->getStatus();
        $this->dateOf = $model->dateOf != null ? Carbon::parse($model->dateOf) : null;
        $this->dateOfFormat = $model->dateOf != null ? Carbon::parse($model->dateOf)->format('d/m/Y H:i') : null;
        $this->workPlace = $model->getWorkPlace();
        $this->riskType = $model->getRiskType();
        $this->classification = $model->getClassification();
        $this->place = $model->place;
        $this->lat = $model->lat;
        $this->lng = $model->lng;
        $this->description = $model->description;
        $this->responsible = $model->getResponsible();
        $this->reportedBy = $model->creator ? $model->creator->name . ' ' . $model->creator->surname : '';
        $this->image = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($documentModel->image);
        if ($this->image == null && $model->imageUrl != '') {
            $this->image = new \stdClass();
            $this->image->path = $model->imageUrl;
        }

        $this->images = [];
        $photos = $documentModel->photos;
        foreach ($photos as $photo) {
            $image = new \stdClass;
            $image->url = $photo->getTemporaryUrl();
            $image->id = $photo->id;
            array_push($this->images, $image);
        }

        $this->address = $this->getAddress($model->lat, $model->lng);
        $this->tokensession = $this->getTokenSession(true);

    }

    private function getAddress($lat, $lng)
    {
        if ($lat == null || $lat == "" || $lat == 0 || $lng == null || $lng == '' || $lng == 0) {
            return "";
        }

        //$url = 'http://maps.googleapis.com/maps/api/geocode/json?latlng=' . trim($lat) . ',' . trim($lng) . '&sensor=false';
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . trim($lat) . ',' . trim($lng) . '&&key=AIzaSyAQaf-emaQFkIpmM4HlpP51vDhPqD8JPYI';
        //var_dump($url);
        $json = @file_get_contents($url);
        //var_dump($json);
        $data = json_decode($json);
        $status = $data ? $data->status : "";
        if ($status == "OK") {
            return $data->results[0]->formatted_address;
        } else {
            return "";
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
            if (!($model = CustomerUnsafeAct::find($object->id))) {
                // No existe
                $model = new CustomerUnsafeAct();
                $isEdit = false;
            }
        } else {
            $model = new CustomerUnsafeAct();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->status = $object->status ? $object->status->value : null;
        $model->dateOf = $object->dateOf ? Carbon::parse($object->dateOf)->timezone('America/Bogota') : null;
        $model->work_place = $object->workPlace ? $object->workPlace->id : null;
        $model->risk_type = $object->riskType ? $object->riskType->id : null;
        $model->classification_id = $object->classification ? $object->classification->id : null;
        $model->place = $object->place;
        $model->lat = $object->lat;
        $model->lng = $object->lng;
        $model->description = $object->description;
        if (isset($object->responsible) && $object->responsible != null) {
            $model->responsible_id = $object->responsible->id;
            $model->responsible_type = $object->responsible->type == "Asesor" ? "agent" : "user";
        }

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;
            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();


        } else {

            // Creado por
            $model->origin = "Web";
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;
            // Guarda
            $model->save();

            try {
                if (isset($object->responsible) && $object->responsible != null) {
                    $customerModel = Customer::find($object->customerId);

                    $mailer["date"] = $model->dateOf ? $model->dateOf->format("d/m/Y") : null;
                    $mailer["workCenter"] = $object->workPlace ? $object->workPlace->name : null;;
                    $mailer["riskType"] = $object->riskType ? $object->riskType->name : null;
                    $mailer["classification"] = $object->classification ? $object->classification->name : null;
                    $mailer["responsible"] = $object->responsible->name;
                    $mailer["place"] = $model->place;
                    $mailer["description"] = $model->description;
                    $mailer["email"] = $object->responsible->email;
                    $mailer["reportedBy"] = $userAdmn ? $userAdmn->name : null;
                    $mailer["customer"] = $customerModel ? $customerModel->businessName : null;
                    $mailer["subject"] = "Actos y Condiciones Inseguras";

                    //$this->sendNotify($mailer);

                    Mail::send('rainlab.user::mail.notificacion_actos_condiciones_inseguras', $mailer, function($message) use ($mailer)
                    {
                        $message->to($mailer["email"], $mailer["responsible"]);
                    });
                }
            } catch (\Exception $ex) {

            }
        }

        return CustomerUnsafeAct::find($model->id);
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
                if ($model instanceof CustomerUnsafeAct) {
                    $parsed[] = (new CustomerUnsafeActDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerUnsafeActDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerUnsafeAct) {
            return (new CustomerUnsafeActDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerUnsafeActDTO();
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
