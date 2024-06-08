<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInternalCertificateProgramRequirement;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerInternalCertificateProgramRequirementDTO
 *
 * @author jdblandon
 */
class CustomerInternalCertificateProgramRequirementDTO {

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
     * @param $model: Modelo CustomerInternalCertificateProgramRequirement
     */
    private function getBasicInfo($model) {
        $this->id = $model->id;
        $this->certificateProgramId = $model->customer_internal_certificate_program_id;
        $this->requirement = $model->getRequirement();
        $this->requirementId = $model->requirement;
        $this->isMandatory = $model->isMandatory == 1 ? true : false;
        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoSummary($model)
    {
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
            if (!($model = CustomerInternalCertificateProgramRequirement::find($object->id))) {
                // No existe
                $model = new CustomerInternalCertificateProgramRequirement();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInternalCertificateProgramRequirement();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_internal_certificate_program_id = $object->certificateProgramId;
        $model->requirement = $object->requirement == null ? null : $object->requirement->value;
        $model->isMandatory = $object->isMandatory;

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

        return CustomerInternalCertificateProgramRequirement::find($model->id);
    }

    public static function  bulkInsert($object)
    {
        $userAdmn = Auth::getUser();

        //Log::info("req bulkInsert");
        try {

            foreach ($object->requirements as $requirement) {
                $isEdit = true;
                if ($requirement) {
                    if ($requirement->id) {
                        if (!($requirementModel = CustomerInternalCertificateProgramRequirement::find($requirement->id))) {
                            $isEdit = false;
                            $requirementModel = new CustomerInternalCertificateProgramRequirement();
                        }
                    } else {
                        $requirementModel = new CustomerInternalCertificateProgramRequirement();
                        $isEdit = false;
                    }

                    $requirementModel->customer_internal_certificate_program_id    = $object->id;
                    $requirementModel->requirement = $requirement->requirement->value;
                    $requirementModel->isMandatory = $requirement->isMandatory;

                    if ($isEdit) {

                        // actualizado por
                        $requirementModel->updatedBy = $userAdmn->id;

                        // Guarda
                        $requirementModel->save();

                        // Actualiza timestamp
                        $requirementModel->touch();
                    } else {
                        // Creado por
                        //Log::info("Envio correo proyecto before");


                        $requirementModel->createdBy = $userAdmn->id;
                        $requirementModel->updatedBy = $userAdmn->id;

                        // Guarda
                        $requirementModel->save();
                    }
                }
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info($ex->getMessage());
        }


        return CertificateProgramSpeciality::find($requirementModel->id);
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
                if ($model instanceof CustomerInternalCertificateProgramRequirement) {
                    $parsed[] = (new CustomerInternalCertificateProgramRequirementDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CustomerInternalCertificateProgramRequirementDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInternalCertificateProgramRequirement) {
            return (new CustomerInternalCertificateProgramRequirementDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInternalCertificateProgramRequirementDTO();
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
