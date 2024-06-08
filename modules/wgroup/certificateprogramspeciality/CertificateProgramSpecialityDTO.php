<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CertificateProgramSpeciality;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;

/**
 * Description of CertificateProgramSpecialityDTO
 *
 * @author jdblandon
 */
class CertificateProgramSpecialityDTO {

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
     * @param $model: Modelo CertificateProgramSpeciality
     */
    private function getBasicInfo($model) {
        $this->id = $model->id;
        $this->certificateProgramId = $model->certificate_program_id;
        $this->category = $model->getCategory();
        $this->categoryId = $model->category;
        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoSummary($model)
    {

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
            if (!($model = CertificateProgramSpeciality::find($object->id))) {
                // No existe
                $model = new CertificateProgramSpeciality();
                $isEdit = false;
            }
        } else {
            $model = new CertificateProgramSpeciality();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->certificate_program_id = $object->certificateProgramId;
        $model->category = $object->category == null ? null : $object->category->value;

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

        return CertificateProgramSpeciality::find($model->id);
    }

    public static function  bulkInsert($object)
    {
        $isEdit = true;
        $userAdmn = Auth::getUser();

        try {

            foreach ($object->specialities as $speciality) {
                $isEdit = true;
                if ($speciality) {
                    if ($speciality->id) {
                        if (!($specialityModel = CertificateProgramSpeciality::find($speciality->id))) {
                            $isEdit = false;
                            $specialityModel = new CertificateProgramSpeciality();
                        }
                    } else {
                        $specialityModel = new CertificateProgramSpeciality();
                        $isEdit = false;
                    }

                    $specialityModel->certificate_program_id    = $object->id;
                    $specialityModel->category = $speciality->category->value;

                    if ($isEdit) {

                        // actualizado por
                        $specialityModel->updatedBy = $userAdmn->id;

                        // Guarda
                        $specialityModel->save();

                        // Actualiza timestamp
                        $specialityModel->touch();
                    } else {
                        // Creado por
                        //Log::info("Envio correo proyecto before");


                        $specialityModel->createdBy = $userAdmn->id;
                        $specialityModel->updatedBy = $userAdmn->id;

                        // Guarda
                        $specialityModel->save();
                    }
                }
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info("Envio correo proyecto ex");
            //Log::info($ex->getMessage());
        }


        return CertificateProgramSpeciality::find($specialityModel->id);
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
                if ($model instanceof CertificateProgramSpeciality) {
                    $parsed[] = (new CertificateProgramSpecialityDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CertificateProgramSpecialityDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CertificateProgramSpeciality) {
            return (new CertificateProgramSpecialityDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CertificateProgramSpecialityDTO();
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
