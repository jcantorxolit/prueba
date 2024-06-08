<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInternalCertificateGradeAgent;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateGrade\CertificateGrade;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectAgent;

/**
 * Description of CustomerInternalCertificateGradeAgentDTO
 *
 * @author jdblandon
 */
class CustomerInternalCertificateGradeAgentDTO {

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
     * @param $model: Modelo CustomerInternalCertificateGradeAgent
     */
    private function getBasicInfo($model) {
        $this->id = $model->id;
        $this->certificateGradeId = $model->customer_internal_certificate_grade_id;
        $this->agent = $model->getAgent();
        $this->agentId = $this->agent->id;
        $this->name = $this->agent->fullName;
        $this->estimatedHours = $model->estimatedHours;
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
            if (!($model = CustomerInternalCertificateGradeAgent::find($object->id))) {
                // No existe
                $model = new CustomerInternalCertificateGradeAgent();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInternalCertificateGradeAgent();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_internal_certificate_grade_id = $object->certificateGradeId;
        $model->agent_id = $object->agent == null ? null : $object->agent->agentId;
        $model->estimatedHours = $object->estimatedHours;

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

        return CustomerInternalCertificateGradeAgent::find($model->id);
    }

    public static function  bulkInsert($object)
    {
        $isEdit = true;
        $userAdmn = Auth::getUser();

        try {


            foreach ($object->agents as $agent) {
                $isEdit = true;
                if ($agent) {
                    if ($agent->id) {
                        if (!($model = CustomerInternalCertificateGradeAgent::find($agent->id))) {
                            $isEdit = false;
                            $model = new CustomerInternalCertificateGradeAgent();
                        }
                    } else {
                        $model = new CustomerInternalCertificateGradeAgent();
                        $isEdit = false;
                    }

                    $model->customer_internal_certificate_grade_id    = $object->id;
                    $model->agent_id = $agent->agentId;
                    $model->estimatedHours = $agent->estimatedHours;

                    if ($isEdit) {

                        // actualizado por
                        $model->updatedBy = $userAdmn->id;

                        // Guarda
                        $model->save();

                        // Actualiza timestamp
                        $model->touch();
                    } else {
                        // Creado por
                        //Log::info("Envio correo proyecto before");


                        $model->createdBy = $userAdmn->id;
                        $model->updatedBy = $userAdmn->id;

                        // Guarda
                        $model->save();

                        $grade = CertificateGrade::find($object->id);

                        if ($grade != null) {

                            $customerProjectAgent = new CustomerProjectAgent();
                            $customerProjectAgent->project_id    = $grade->customer_project_id;
                            $customerProjectAgent->agent_id = $agent->agentId;
                            $customerProjectAgent->estimatedHours = $agent->estimatedHours;
                            $customerProjectAgent->createdBy = $userAdmn->id;
                            $customerProjectAgent->updatedBy = $userAdmn->id;
                            $customerProjectAgent->save();

                            $model->customer_project_id = $grade->customer_project_id;
                            $model->save();
                        }
                    }
                }
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info("Envio correo proyecto ex");
            //Log::info($ex->getMessage());
        }


        return CustomerInternalCertificateGradeAgent::find($model->id);
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
                if ($model instanceof CustomerInternalCertificateGradeAgent) {
                    $parsed[] = (new CustomerInternalCertificateGradeAgentDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CustomerInternalCertificateGradeAgentDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInternalCertificateGradeAgent) {
            return (new CustomerInternalCertificateGradeAgentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInternalCertificateGradeAgentDTO();
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
