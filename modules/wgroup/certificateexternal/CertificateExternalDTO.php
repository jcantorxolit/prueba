<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CertificateExternal;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;

/**
 * Description of CertificateExternalDTO
 *
 * @author jdblandon
 */
class CertificateExternalDTO {

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
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfo($model) {

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->documentType = $model->getDocumentType();
        $this->identificationNumber = $model->identificationNumber;
        $this->name = $model->name;
        $this->lastName = $model->lastName;
        $this->company = $model->company;
        $this->grade = $model->grade;
        $this->expeditionDate =  Carbon::parse($model->expeditionDate);
        $this->expirationDate =  Carbon::parse($model->expirationDate);
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
            if (!($model = CertificateExternal::find($object->id))) {
                // No existe
                $model = new CertificateExternal();
                $isEdit = false;
            } else {
                $model->status = 2;
                $model->save();

                $model = new CertificateExternal();
                $isEdit = false;
            }

        } else {
            $model = new CertificateExternal();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->documentType = $object->documentType == null ? null : $object->documentType->value;
        $model->identificationNumber = $object->identificationNumber;
        $model->name = $object->name;
        $model->lastName = $object->lastName;
        $model->company = $object->company;
        $model->grade = $object->grade;
        $model->expeditionDate = Carbon::parse($object->expeditionDate)->timezone('America/Bogota');
        $model->expirationDate = Carbon::parse($object->expirationDate)->timezone('America/Bogota');

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

        return CertificateExternal::find($model->id);
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

            default:
                $this->getBasicInfo($model);
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
                if ($model instanceof CertificateExternal) {
                    $parsed[] = (new CertificateExternalDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CertificateExternalDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CertificateExternal) {
            return (new CertificateExternalDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CertificateExternalDTO();
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
