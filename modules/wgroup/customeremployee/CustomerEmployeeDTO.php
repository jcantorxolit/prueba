<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerEmployee;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerConfigJob\CustomerConfigJobDTO;
use Wgroup\CustomerEmployeeAudit\CustomerEmployeeAudit;
use Wgroup\CustomerEmployeeValidity\CustomerEmployeeValidityDTO;
use Wgroup\Employee\Employee;
use Wgroup\Employee\EmployeeDTO;
use DB;

/**
 * Description of CustomerDto
 *
 * @author TeamCloud
 */
class CustomerEmployeeDTO {

    function __construct($model = null, $customerId = 0) {
        if ($model) {
            $this->parse($model, $customerId);
        }
    }

    public function setInfo($model = null, $customerId = 0) {

        // recupera informacion basica del formulario
        if ($model) {
                $this->getInfoBasic($model, $customerId);
        }
    }

    private function getInfoBasic($model) {

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->contractType = $model->getContractType();
        $this->occupation = $model->occupation;
        $this->job = $model->jobModel ?  CustomerConfigJobDTO::parse($model->jobModel) : null;
        $this->workPlace = $model->workPlaceModel;
        $this->salary = $model->salary;
        $this->entity = EmployeeDTO::parse($model->employee);
        $this->validityList = CustomerEmployeeValidityDTO::parse($model->validityList);
        $this->type = "employee";
        $this->isActive = $model->isActive == 1 ? true : false;
        $this->isAuthorized = $model->isAuthorized == 1 ? true : false;
        $this->countAttachment = $model->getCountAttachment();
        $this->workShift = $model->getWorkShift();
    }

    public static function canInsert($object)
    {
        if ($object->entity->id == 0) {
            if (Employee::where('documentNumber', $object->entity)->count() > 0 ) {
                return false;
            }
        }
        return true;
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
            if (!($model = CustomerEmployee::find($object->id))) {
                // No existe
                $model = new CustomerEmployee();
                $isEdit = false;
            } else {
                $isActive = $model->isActive == 1 ? true : false;
                if (!$object->isActive && ($isActive != $object->isActive)) {
                    $object->isAuthorized = false;
                }
            }
        } else {
            $model = new CustomerEmployee();
            $isEdit = false;
            //$object->isAuthorized = $object->isActive;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        if ($model != null) {
            if ($model->isAuthorized != $object->isAuthorized) {    
                $description = $object->isAuthorized ? "autorización" : "desautorización";            
                $action = $object->isAuthorized ? "Autorizado" : "Desautorizado";

                $customerEmployeeAudit = new CustomerEmployeeAudit();
                $customerEmployeeAudit->customer_employee_id = $model->id;
                $customerEmployeeAudit->model_name = "Empleados";
                $customerEmployeeAudit->model_id = $model->id;
                $customerEmployeeAudit->user_type = $userAdmn->wg_type;
                $customerEmployeeAudit->user_id = $userAdmn->id;
                $customerEmployeeAudit->action = "{$action} Manual";
                $customerEmployeeAudit->observation = !empty($object->reason) ? $object->reason : "Se realizó la {$description} del empleado";
                $customerEmployeeAudit->date = Carbon::now('America/Bogota');
                $customerEmployeeAudit->save();
            }

            if ($model->isActive != $object->isActive) {
                $description = $object->isActive ? "activación" : "inactivación";
                $action = $object->isActive ? "Activado" : "Inactivado";

                $customerEmployeeAudit = new CustomerEmployeeAudit();
                $customerEmployeeAudit->customer_employee_id = $model->id;
                $customerEmployeeAudit->model_name = "Empleados";
                $customerEmployeeAudit->model_id = $model->id;
                $customerEmployeeAudit->user_type = $userAdmn->wg_type;
                $customerEmployeeAudit->user_id = $userAdmn->id;
                $customerEmployeeAudit->action = "{$action} Manual";
                $customerEmployeeAudit->observation = "Se realizó la {$description} del empleado";
                $customerEmployeeAudit->date = Carbon::now('America/Bogota');
                $customerEmployeeAudit->save();
            }
        }

        $employee = EmployeeDTO::fillAndSaveModel($object->entity);

        $model->customer_id = $object->customerId;
        $model->employee_id = $employee->id;
        $model->type = "employee";
        $model->isActive = $object->isActive;
        $model->contractType = $object->contractType ? $object->contractType->value : null;
        $model->occupation = $object->occupation;
        $model->job = $object->job ? $object->job->id : null;
        $model->workPlace = $object->workPlace ? $object->workPlace->id : null;
        $model->salary = $object->salary;
        $model->isAuthorized = $object->isAuthorized;

        $model->location_id = $object->location->value ?? null;
        $model->department_id = $object->department->value ?? null;
        $model->area_id = $object->area->value ?? null;
        $model->turn_id = $object->turn->value ?? null;
        $model->work_shift = $object->workShift->value ?? null;

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

        if ($object->validityList) {

            $object->id = $model->id;

            CustomerEmployeeValidityDTO::bulkInsert($object);
        }


        self::updatePrimaryInfoDetail($model->employee_id, "email");
        self::updatePrimaryInfoDetail($model->employee_id, "cel");

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        return $object->id ? CustomerEmployee::find($model->id) : $model;
    }

    public static function    fillAndQuickSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerEmployee::find($object->id))) {
                // No existe
                $model = new CustomerEmployee();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEmployee();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $employee = EmployeeDTO::fillAndQuickSaveModel($object->entity);

        $model->customer_id = $object->customerId;
        $model->employee_id = $employee->id;
        $model->type = "employee";
        $model->isActive = $object->isActive;
        $model->contractType = $object->contractType ? $object->contractType->value : null;
        $model->job = ($object->job == null) ? null : $object->job->id;
        $model->workPlace = ($object->workPlace == null) ? null : $object->workPlace->id;
        $model->salary = $object->salary;

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

        return CustomerEmployee::find($model->id);
    }

    public function find($id)
    {
        return $this->parse(CustomerEmployee::find($id), 2) ;
    }

    private function parseModel($model, $customerId = "0") {

        // parse model
        if ($model) {
            $this->setInfo($model, $customerId);
        }

        return $this;
    }

    public static function parse($info, $customerId = 0) {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerEmployee) {
                    $parsed[] = (new CustomerEmployeeDTO())->parseModel($model, $customerId);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerEmployee) {
            return (new CustomerEmployeeDTO())->parseModel($data, $customerId);
        } else {
            // return empty instance

                return new CustomerEmployeeDTO();

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

    public static function updatePrimaryInfoDetail($id, $type)
    {
        $primaryField = $type == "email" ? "primary_email" : "primary_cellphone";

        $query = "UPDATE wg_customer_employee ce
                INNER JOIN (
                    SELECT
                        ce.id,
                        MIN(i.`value`) `value`,
                        i.id itemId
                    FROM
                        wg_employee e
                    INNER JOIN wg_customer_employee ce ON e.id = ce.employee_id
                    INNER JOIN wg_employee_info_detail i ON e.id = i.entityId
                    WHERE i.type = '$type' and e.id = $id
                    GROUP BY
                        `i`.`entityId`,
                        `i`.`entityName`,
                        `i`.`type`
                ) i ON ce.id = i.id
                SET $primaryField = i.itemId";

        DB::statement( $query );
    }
}
