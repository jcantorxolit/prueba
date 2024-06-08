<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerEmployeeValidity;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerEmployeeValidityDTO
 *
 * @author jdblandon
 */
class CustomerEmployeeValidityDTO {

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
     * @param $model: Modelo CustomerEmployeeValidity
     */
    private function getBasicInfo($model) {
        $this->id = $model->id;
        $this->customerEmployeeId = $model->customer_employee_id;
        $this->startDate =  Carbon::parse($model->startDate);
        $this->endDate =  $model->endDate != null ? Carbon::parse($model->endDate) : null;
        $this->description = $model->description;
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
            if (!($model = CustomerEmployeeValidity::find($object->id))) {
                // No existe
                $model = new CustomerEmployeeValidity();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEmployeeValidity();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_employee_id = $object->customerEmployeeId;
        $model->startDate = $object->startDate;
        $model->endDate = $object->endDate;
        $model->description = $object->description;

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

        return CustomerEmployeeValidity::find($model->id);
    }

    public static function  bulkInsert($object)
    {
        $userAdmn = Auth::getUser();

        try {

            //Log::info("calendar bulkInsert");

            foreach ($object->validityList as $record) {
                $isEdit = true;
                if ($record) {
                    if ($record->id) {
                        if (!($model = CustomerEmployeeValidity::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerEmployeeValidity();
                        }
                    } else {
                        $model = new CustomerEmployeeValidity();
                        $isEdit = false;
                    }

                    $model->customer_employee_id = $object->id;
                    $model->startDate = $record->startDate ? Carbon::parse($record->startDate) : null;
                    $model->endDate = $record->endDate ? Carbon::parse($record->endDate) : null;
                    $model->description = $record->description;

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
                    }
                }
            }
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info($ex->getMessage());
        }


        return CustomerEmployeeValidity::find($model->id);
    }

    public static function  delete($id)
    {
        try {
            $model = CustomerEmployeeValidity::find($id);
            if ($model ) {
                $model->delete();
            }
        }
        catch (\Exception $ex) {
            return false;
        }

        return true;
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
                if ($model instanceof CustomerEmployeeValidity) {
                    $parsed[] = (new CustomerEmployeeValidityDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new CustomerEmployeeValidityDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerEmployeeValidity) {
            return (new CustomerEmployeeValidityDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerEmployeeValidityDTO();
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
