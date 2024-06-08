<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CertificateGradeParticipant;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\Models\CustomerProjectDTO;
use Wgroup\Models\InfoDetail;
use Wgroup\Models\InfoDetailDto;

/**
 * Description of CertificateGradeParticipantDTO
 *
 * @author jdblandon
 */
class CertificateGradeParticipantDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoSummary($model);
                break;
            default:
                $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model: Modelo CertificateGradeParticipant
     */
    private function getBasicInfo($model) {
        $this->id = $model->id;
        $this->certificateGradeId = $model->certificate_grade_id;
        //$this->customer = CustomerProjectDTO::parse($model->getCustomer(), "5");
        $this->customer = $model->getCustomer();
        $this->documentType = $model->getDocumentType();
        $this->identificationNumber = $model->identificationNumber;
        $this->name = $model->name;
        $this->lastName = $model->lastName;
        $this->workCenter = $model->workCenter;
        $this->price = $model->getPrice();
        $this->channel = $model->getChannel();
        $this->countryOrigin = $model->countryOrigin;
        $this->countryResidence = $model->countryResidence;
        $this->isApproved = $model->isApproved == 1 ? true : false;
        $this->hasCertificate = $model->hasCertificate == 1 ? true : false;
        $this->logo = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo);

        $this->attachment = $model->getCountAttachment();
        $this->contacts  = InfoDetailDto::parse($model->getInfoDetail());;
        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoSummary($model)
    {
        //var_dump($model);
        /*
         * */
        $this->id = $model->id;
        $this->customer = $model->customer;
        $this->documentType = $model->documentType;
        $this->identificationNumber = $model->identificationNumber;
        $this->name = $model->name;
        $this->lastName = $model->lastName;
        $this->grade = $model->grade;
        $this->certificateCreatedAt = $model->certificateCreatedAt;
        $this->certificateExpirationAt = $model->certificateExpirationAt;
        $this->origin = $model->origin;

/*
        $this->id = 0;
        $this->customer = "dd";
        $this->documentType = "";
        $this->identificationNumber = "";
        $this->name = "";
        $this->lastName = "";
        $this->grade = "";
        $this->certificateCreatedAt = "";
        $this->certificateExpirationAt = "";
*/
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
            if (!($model = CertificateGradeParticipant::find($object->id))) {
                // No existe
                $model = new CertificateGradeParticipant();
                $isEdit = false;
            }
        } else {
            $model = new CertificateGradeParticipant();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->certificate_grade_id = $object->certificateGradeId;
        $model->customer_id = $object->customer == null ? null : $object->customer->id;
        $model->documentType = $object->documentType == null ? null : $object->documentType->value;
        $model->identificationNumber = $object->identificationNumber;
        $model->name = $object->name;
        $model->lastName = $object->lastName;
        $model->workCenter = $object->workCenter;
        $model->amount =  $object->price == null ? null : $object->price->amount;
        $model->channel = $object->channel == null ? null : $object->channel->value;
        $model->country_origin_id = $object->countryOrigin == null ? null : $object->countryOrigin->id;
        $model->country_residence_id = $object->countryResidence == null ? null : $object->countryResidence->id;
        $model->isApproved = $object->isApproved == true ? 1 : 0;

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

        foreach ($model->getInfoDetail() as $infoDetail) {
            $infoDetail->delete();
        }

        if ($object->contacts) {
            foreach ($object->contacts as $contactInfo) {
                if (isset($contactInfo->id) && isset($contactInfo->value) && $contactInfo->value != "-S-") {
                    $infoDetail = new InfoDetail();
                    $infoDetail->entityId = $model->id;
                    $infoDetail->entityName = get_class($model);
                    $infoDetail->type = ($contactInfo->type) ? $contactInfo->type->value : null;
                    $infoDetail->value = $contactInfo->value;
                    $infoDetail->save();
                }
            }
        }

        return CertificateGradeParticipant::find($model->id);
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
                $this->getBasicInfoSummary($model);
                break;
            case "3":
                $this->getBasicInfoResponislbe($model);
                break;
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
                if ($model instanceof CertificateGradeParticipant) {
                    $parsed[] = (new CertificateGradeParticipantDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CertificateGradeParticipantDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CertificateGradeParticipant) {
            return (new CertificateGradeParticipantDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CertificateGradeParticipantDTO();
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