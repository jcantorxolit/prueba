<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\EmployeeChildren;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use League\Flysystem\Exception;
use RainLab\User\Facades\Auth;
use Wgroup\Models\InfoDetail;
use Wgroup\Models\InfoDetailDto;

/**
 * Description of CustomerDto
 *
 * @author TeamCloud
 */
class EmployeeChildrenDTO
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
            $this->getInfoBasic($model);
        }
    }

    private function getInfoBasic($model)
    {
        $this->id = $model->id;
        $this->employeeId = $model->employee_id;
        $this->name = $model->name;
        $this->lastName = $model->lastName;
        $this->age = $model->age;


        $this->tokensession = $this->getTokenSession(true);
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
            if (!($model = EmployeeChildren::find($object->id))) {
                // No existe
                $model = new EmployeeChildren();
                $isEdit = false;
            }
        } else {
            $model = new EmployeeChildren();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->employee_id = $object->employeeId;
        $model->name = $object->name;
        $model->lastName = $object->lastName;
        $model->age = $object->age;

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

        return EmployeeChildren::find($model->id);
    }

    public function bulkInsert($records, $entityId)
    {
        try {
            $userAdmn = Auth::getUser();

            foreach ($records as $record) {

                if ($record && $record->isActive != null && $record->isActive) {
                    $isNewRecord = false;

                    if (!($entityModel = EmployeeChildren::find($record->id))) {
                        $entityModel = new EmployeeChildren();
                        $isNewRecord = true;
                    }

                    $entityModel->employee_id = $entityId;
                    $entityModel->category = $record->category;
                    $entityModel->item = $record->item;
                    $entityModel->value = $record->value;

                    //var_dump('va a guardar');
                    if ($isNewRecord) {
                        $entityModel->createdBy = $userAdmn->id;
                        $entityModel->save();
                    } else {
                        $entityModel->updatedBy = $userAdmn->id;
                        $entityModel->save();
                    }
                }
            }
        }
        catch (\Exception $ex) {

        }
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {
        if ($fmt_response != "1") {
            // parse model
            switch ($fmt_response) {
                case "1":
                    $this->getInfoBasic($model);
                    break;
                default:
            }
        } else {
            // parse model
            if ($model) {
                $this->setInfo($model, $fmt_response);
            }
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
                if ($model instanceof EmployeeChildren) {
                    $parsed[] = (new EmployeeChildrenDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new EmployeeChildrenDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof EmployeeChildren) {
            return (new EmployeeChildrenDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new EmployeeChildrenDTO();
            }
        }
    }

    private function parseArray($model, $fmt_response = "1")
    {
        if ($fmt_response != "1") {
            // parse model
            switch ($fmt_response) {
                case "1":
                    $this->getInfoBasic($model);
                    break;
                default:
            }
        } else {
            // parse model
            if ($model) {
                $this->setInfo($model, $fmt_response);
            }
        }
        return $this;
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
