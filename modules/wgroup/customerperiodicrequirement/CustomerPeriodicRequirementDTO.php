<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerPeriodicRequirement;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\CustomerPeriodicRequirementContractorType\CustomerPeriodicRequirementContractorType;
use Wgroup\CustomerPeriodicRequirementContractorType\CustomerPeriodicRequirementContractorTypeDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerPeriodicRequirementDTO {

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
     * @param $model: Modelo CustomerPeriodicRequirement
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->value = $model->requirement;
        $this->isActive = $model->getIsActive();
        $this->contractorTypeList = $model->getContractorTypes();

        $this->jan = $model->jan == 1 ? true : false;
        $this->feb = $model->feb == 1 ? true : false;
        $this->mar = $model->mar == 1 ? true : false;
        $this->apr = $model->apr == 1 ? true : false;
        $this->may = $model->may == 1 ? true : false;
        $this->jun = $model->jun == 1 ? true : false;
        $this->jul = $model->jul == 1 ? true : false;
        $this->aug = $model->aug == 1 ? true : false;
        $this->sep = $model->sep == 1 ? true : false;
        $this->oct = $model->oct == 1 ? true : false;
        $this->nov = $model->nov == 1 ? true : false;
        $this->dec = $model->dec == 1 ? true : false;

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
            if (!($model = CustomerPeriodicRequirement::find($object->id))) {
                // No existe
                $model = new CustomerPeriodicRequirement();
                $isEdit = false;
            }
        } else {
            $model = new CustomerPeriodicRequirement();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->requirement = $object->value;
        $model->isActive = $object->isActive;

        $model->jan = $object->jan;
        $model->feb = $object->feb;
        $model->mar = $object->mar;
        $model->apr = $object->apr;
        $model->may = $object->may;
        $model->jun = $object->jun;
        $model->jul = $object->jul;
        $model->aug = $object->aug;
        $model->sep = $object->sep;
        $model->oct = $object->oct;
        $model->nov = $object->nov;
        $model->dec = $object->dec;

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

        CustomerPeriodicRequirementContractorType::whereCustomerPeriodicRequirementId($model->id)->delete();
        CustomerPeriodicRequirementContractorTypeDTO::bulkInsert($object->contractorTypeList, $model->id);

        return CustomerPeriodicRequirement::find($model->id);
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
                if ($model instanceof CustomerPeriodicRequirement) {
                    $parsed[] = (new CustomerPeriodicRequirementDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerPeriodicRequirement) {
            return (new CustomerPeriodicRequirementDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerPeriodicRequirementDTO();
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
