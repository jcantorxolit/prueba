<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerEmployeeCriticalActivity;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;

/**
 * Description of CustomerEmployeeCriticalActivityDTO
 *
 * @author jdblandon
 */
class CustomerEmployeeCriticalActivityDTO {

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

        $documentModel = CustomerEmployeeCriticalActivity::find($model->id);

        $this->id = $model->id;
        $this->customerEmployeeId = $model->customer_employee_id;
        $this->type = $model->getRequirement();
        $this->jobId = $model->job_id;
        $this->tokensession = $this->getTokenSession(true);
    }

    /**
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfoPermission($model) {

        $documentModel = CustomerEmployeeCriticalActivity::find($model->id);

        $this->id = $model->id;
        $this->customerEmployeeId = $model->customer_employee_id;
        $this->requirement = $model->requirement;
        $this->description = $model->description;
        $this->version = $model->version;
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($documentModel->document);
        $this->date = $model->created_at;
        $this->status = $model->status;
        $this->isRequired = $model->isRequired == 'Requerido' ? true : false;
        $this->isVerified = $model->isVerified;
        $this->startDate =  $model->startDate;
        $this->endDate =  $model->endDate;
        $this->observation =  isset($model->observation) ? $model->observation : "";

        $this->fullName =  isset($model->fullName) ? $model->fullName : "";
        $this->documentNumber =  isset($model->documentNumber) ? $model->documentNumber : "";
        $this->documentType =  isset($model->documentType) ? $model->documentType : "";


        $this->tokensession = $this->getTokenSession(true);

    }

    private function getBasicInfoUser($model) {


        $this->id = $model->id;
        $this->name = $model->name;
        $this->type = $model->type;
        $this->hasPermission = $model->hasPermission == 1 ? true : false;
        $this->isPublic = $model->isPublic == 1 ? true : false;
        $this->isProtected = $model->isProtected == 1 ? true : false;
        $this->securityId = $model->securityId;


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
            if (!($model = CustomerEmployeeCriticalActivity::find($object->id))) {
                // No existe
                $model = new CustomerEmployeeCriticalActivity();
                $isEdit = false;
            } else {
                $model->status = 2;
                $model->save();

                $model = new CustomerEmployeeCriticalActivity();
                $isEdit = false;
            }

        } else {
            $model = new CustomerEmployeeCriticalActivity();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_employee_id = $object->customerEmployeeId;
        $model->job_activity_id = $object->activity == null ? null : $object->activity->id;
        $model->job_id = $object->jobId;

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

        return CustomerEmployeeCriticalActivity::find($model->id);
    }

    public static function denied($object)
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
            if (!($model = CustomerEmployeeCriticalActivity::find($object->id))) {
                // No existe
                $model = new CustomerEmployeeCriticalActivity();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEmployeeCriticalActivity();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/


        $model->isDenied = 1;
        $model->isApprove = null;

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

        $customerEmployeeDocumentTracking = new CustomerEmployeeCriticalActivityTracking();
        $customerEmployeeDocumentTracking->customer_employee_document_id = $object->id;
        $customerEmployeeDocumentTracking->type = $object->tracking->action;
        $customerEmployeeDocumentTracking->observation = $object->tracking->description;
        $customerEmployeeDocumentTracking->createdBy = $userAdmn->id;
        $customerEmployeeDocumentTracking->updatedBy = $userAdmn->id;
        $customerEmployeeDocumentTracking->save();

        return CustomerEmployeeCriticalActivity::find($model->id);
    }

    public static function approve($object)
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
            if (!($model = CustomerEmployeeCriticalActivity::find($object->id))) {
                // No existe
                $model = new CustomerEmployeeCriticalActivity();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEmployeeCriticalActivity();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/


        $model->isApprove = 1;
        $model->isDenied = null;

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

        return CustomerEmployeeCriticalActivity::find($model->id);
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
                $this->getBasicInfoPermission($model);
                break;

            case "3":
                $this->getBasicInfoUser($model);
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
                if ($model instanceof CustomerEmployeeCriticalActivity) {
                    $parsed[] = (new CustomerEmployeeCriticalActivityDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerEmployeeCriticalActivityDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerEmployeeCriticalActivity) {
            return (new CustomerEmployeeCriticalActivityDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerEmployeeCriticalActivityDTO();
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
