<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerPeriodicRequirementContractorType;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerPeriodicRequirementContractorTypeDTO {

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
     * @param $model: Modelo CustomerPeriodicRequirementContractorType
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerContractorType = $model->getCustomerContractorType();

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
            if (!($model = CustomerPeriodicRequirementContractorType::find($object->id))) {
                // No existe
                $model = new CustomerPeriodicRequirementContractorType();
                $isEdit = false;
            }
        } else {
            $model = new CustomerPeriodicRequirementContractorType();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_periodic_requirement_id = $object->customerPeriodicRequirementId;
        $model->customer_contractor_type_id = $object->customerContractorType->id;

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

        return CustomerPeriodicRequirementContractorType::find($model->id);
    }

    public static function  bulkInsert($records, $parentId)
    {
        try {
            foreach ($records as $record) {
                $isEdit = true;
                if ($record) {
                    $model = new CustomerPeriodicRequirementContractorType();
                    $isEdit = false;

                    $model->customer_periodic_requirement_id = $parentId;
                    $model->customer_contractor_type_id = $record->customerContractorType->id;

                    if ($isEdit) {
                        // Guarda
                        $model->save();

                        // Actualiza timestamp
                        $model->touch();
                    } else {
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


        //return CustomerParameter::find($model->id);
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

    public static function parse($info, $fmt_response = "1") {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerPeriodicRequirementContractorType) {
                    $parsed[] = (new CustomerPeriodicRequirementContractorTypeDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerPeriodicRequirementContractorType) {
            return (new CustomerPeriodicRequirementContractorTypeDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerPeriodicRequirementContractorTypeDTO();
            }
        }
    }

    public static function getPeriods()
    {
        $dt = Carbon::now('America/Bogota');

        $fromYear = 2016;
        $toYear = $dt->year + 1;

        $periods = array();

        for ($y = $fromYear; $y < $toYear; $y++)
        {
            for ($m = 1; $m < 13; $m++)
            {
                $period = new \stdClass();
                $period->item = $y."-".str_pad($m, 2, "0", STR_PAD_LEFT);
                $period->value = $y.str_pad($m, 2, "0", STR_PAD_LEFT);

                $periods[] = $period;
            }
        }

        return $periods;
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
