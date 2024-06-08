<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerEmployeeDocument;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;
use Exception;
use DB;
use Wgroup\CustomerEmployee\CustomerEmployee;

/**
 * Description of CustomerEmployeeDocumentDTO
 *
 * @author jdblandon
 */
class CustomerEmployeeDocumentDTO
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
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfo($model)
    {

        $documentModel = CustomerEmployeeDocument::find($model->id);

        $this->id = $model->id;
        $this->customerEmployeeId = $model->customer_employee_id;
        $this->requirement = $model->getRequirement();
        $this->description = $model->description;
        $this->version = $model->version;
        $this->status = $model->getStatusType();
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($documentModel->document);
        $this->startDate =  $model->startDate ? Carbon::parse($model->startDate) : null;
        $this->endDate =  $model->endDate ? Carbon::parse($model->endDate) : null;

        $this->tokensession = $this->getTokenSession(true);
    }

    /**
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfoPermission($model)
    {

        $documentModel = CustomerEmployeeDocument::find($model->id);

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

    private function getBasicInfoUser($model)
    {


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
            if (!($model = CustomerEmployeeDocument::find($object->id))) {
                // No existe
                $model = new CustomerEmployeeDocument();
                $isEdit = false;
            } else {
                $model->status = 2;
                //DAB->20170202: BB:#199
                $model->isDenied = 1;
                $model->isApprove = null;
                $model->updatedBy = $userAdmn ? $userAdmn->id : 0;
                $model->canceled_by = $userAdmn ? $userAdmn->id : 0;
                $model->canceled_at = Carbon::now('America/Bogota');
                $model->save();

                $model = new CustomerEmployeeDocument();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEmployeeDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_employee_id = $object->customerEmployeeId;
        $model->requirement = $object->requirement == null ? null : $object->requirement->value;
        $model->origin = $object->requirement == null ? null : $object->requirement->origin;
        $model->description = $object->description;
        $model->status = 1;
        $model->version = $object->version;
        $model->startDate = $object->startDate ? Carbon::parse($object->startDate)->timezone('America/Bogota') : null;
        $model->endDate = $object->endDate ? Carbon::parse($object->endDate)->timezone('America/Bogota') : null;
        $model->isApprove = isset($object->isApprove) ? $object->isApprove : null;

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

        return CustomerEmployeeDocument::find($model->id);
    }


    public static function  fillAndSaveModelImport($object)
    {
        DB::beginTransaction();
        $result = [];

        try {
            if ($object != null) {
                if ($object->toApplyAll) {
                    $object->employeeList = array_map(function ($item) {
                        return $item['id'];
                    }, CustomerEmployee::whereCustomerId($object->customerId)->where("isActive", 1)->get(['id'])->toArray());
                }

                foreach ($object->employeeList as $employee) {
                    $object->customerEmployeeId = $employee;
                    $object = CustomerEmployeeDocumentDTO::findLastDocument($object);
                    $entityModel = CustomerEmployeeDocumentDTO::fillAndSaveModel($object);
                    if ($entityModel != null) {
                        $result[] = $entityModel->id;
                    }
                    if (!empty($object->lastDocument)) {
                        CustomerEmployeeDocumentDTO::cancelDocument($object->lastDocument);
                    }
                }
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
        }

        return $result;
    }

    private static function cancelDocument($modelLastDocument)
    {
        $modelLastDocument->status = 2;
        $modelLastDocument->save();
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
            if (!($model = CustomerEmployeeDocument::find($object->id))) {
                // No existe
                $model = new CustomerEmployeeDocument();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEmployeeDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/


        $model->isDenied = 1;
        $model->isApprove = null;
        $model->verified_by = $userAdmn ? $userAdmn->id : 0;
        $model->verified_at = Carbon::now('America/Bogota');

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

        $customerEmployeeDocumentTracking = new CustomerEmployeeDocumentTracking();
        $customerEmployeeDocumentTracking->customer_employee_document_id = $object->id;
        $customerEmployeeDocumentTracking->type = $object->tracking->action;
        $customerEmployeeDocumentTracking->observation = $object->tracking->description;
        $customerEmployeeDocumentTracking->createdBy = $userAdmn->id;
        $customerEmployeeDocumentTracking->updatedBy = $userAdmn->id;
        $customerEmployeeDocumentTracking->save();

        return CustomerEmployeeDocument::find($model->id);
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
            if (!($model = CustomerEmployeeDocument::find($object->id))) {
                // No existe
                $model = new CustomerEmployeeDocument();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEmployeeDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/


        $model->isApprove = 1;
        $model->isDenied = null;
        $model->verified_by = $userAdmn ? $userAdmn->id : 0;
        $model->verified_at = Carbon::now('America/Bogota');

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

        return CustomerEmployeeDocument::find($model->id);
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
                if ($model instanceof CustomerEmployeeDocument) {
                    $parsed[] = (new CustomerEmployeeDocumentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerEmployeeDocumentDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerEmployeeDocument) {
            return (new CustomerEmployeeDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerEmployeeDocumentDTO();
            }
        }
    }

    private function getUserSsession()
    {
        if (!Auth::check())
            return null;

        return Auth::getUser();
    }

    private static function findLastDocument($object)
    {
        if ($object->requirement) {

            $userAdmn = Auth::getUser();

            $hasDocument = CustomerEmployeeDocument::where("customer_employee_id", $object->customerEmployeeId)
                ->where("requirement", $object->requirement->value)
                ->where("origin", $object->requirement->origin)
                ->whereIn("status", [1, 3])
                // ->whereNull("isDenied")
                // ->whereNull("isApprove")
                ->orderby("wg_customer_employee_document.id", "DESC")
                ->first();

            CustomerEmployeeDocument::where("customer_employee_id", $object->customerEmployeeId)
                ->where("requirement", $object->requirement->value)
                ->where("origin", $object->requirement->origin)
                ->whereIn("status", [1, 3])
                ->update([
                    "status" => 2,
                    "updatedBy" => $userAdmn ? $userAdmn->id : 0,
                    "canceled_by" => $userAdmn ? $userAdmn->id : 0,
                    "canceled_at" => Carbon::now('America/Bogota'),
                ]);

            if ($hasDocument) {
                $object->lastDocument = $hasDocument;
                $object->version = (int)$hasDocument->version + 1;
            } else {
                $object->lastDocument = null;
                $object->version = 1;
            }
        }
        return $object;
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
