<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerAbsenteeismDisability;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Wgroup\CustomerAbsenteeismDisabilityDocument\CustomerAbsenteeismDisabilityDocument;
use Wgroup\CustomerAbsenteeismIndirectCost\CustomerAbsenteeismIndirectCostDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnosticDTO;
use Wgroup\Models\Customer;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerAbsenteeismDisabilityDTO {

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
     * @param $model: Modelo CustomerAbsenteeismDisability
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->employee = CustomerEmployeeDTO::parse($model->employee);
        $this->dayLiquidationBasis = $model->dayLiquidationBasis;
        $this->hourLiquidationBasis = $model->hourLiquidationBasis;
        $this->diagnostic_id = $model->diagnosticId;
        $this->diagnostic = DisabilityDiagnosticDTO::parse($model->diagnostic);
        $this->type = $model->getType();
        $this->cause = $model->getCause();

        $this->typeText = $this->type != null ? $this->type->item : "";
        $this->causeText = $this->cause != null ? $this->cause->item : "";

        $this->categoryText = $model->category;
        $this->category = $model->getCategory();
        $this->accidentType = $model->getAccidentType();

        $this->hasReport = $model->hasReport();
        $this->hasInhability = CustomerAbsenteeismDisabilityDocument::hasDocumentType($this->id, "INC");;
        $this->hasInvestigation = CustomerAbsenteeismDisabilityDocument::hasDocumentType($this->id, "INV");;
        $this->hasReportEps = CustomerAbsenteeismDisabilityDocument::hasDocumentType($this->id, "REP");;
        $this->hasReportMin = CustomerAbsenteeismDisabilityDocument::hasDocumentType($this->id, "REM");;

        $this->actionPlan = $model->getActionPlan();

        $this->planId = $this->actionPlan != null ? $this->actionPlan->id : 0;
        $this->hasActionPlan = $this->actionPlan != null;

        $this->startDate =  Carbon::parse($model->start);
        $this->endDate =  Carbon::parse($model->end);

        $this->startDateFormat =  Carbon::parse($model->start)->format('d/m/Y');
        $this->endDateFormat =  Carbon::parse($model->end)->format('d/m/Y');

        $this->isHour = $model->is_hour == 1;
        $this->numberDays = $model->numberDays;
        $this->chargedDays = $model->chargedDays;
        $this->amountPaid = $model->amountPaid;
        $this->directCostTotal = $model->directCostTotal;
        $this->indirectCostTotal = $model->indirectCostTotal;
        $this->charged = $model->charged == 1;
        $this->workplace = $model->getWorkplace();
        $this->disabilityParent = $model->getDisabilityParent();
        $this->indirectCost = CustomerAbsenteeismIndirectCostDTO::parse($model->getIndirectCost());

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
            if (!($model = CustomerAbsenteeismDisability::find($object->id))) {
                // No existe
                $model = new CustomerAbsenteeismDisability();
                $isEdit = false;
            }
        } else {
            $model = new CustomerAbsenteeismDisability();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_employee_id = $object->employee->id;
        $model->dayLiquidationBasis = $object->dayLiquidationBasis;
        $model->hourLiquidationBasis = $object->hourLiquidationBasis;
        $model->diagnostic_id = ($object->diagnostic != null) ? $object->diagnostic->id : null;
        $model->type = $object->type == null ? null : $object->type->value;
        $model->cause = $object->cause == null ? null : $object->cause->value;
        $model->category = $object->category == null ? null : $object->category->value;
        $model->charged = $object->charged;
        $model->directCostTotal = $object->directCostTotal;
        $model->indirectCostTotal = $object->indirectCostTotal;
        $model->accidentType = isset($object->accidentType) && $object->accidentType ? $object->accidentType->value : null;

        $model->start = $object->startDate ? Carbon::parse($object->startDate)->timezone('America/Bogota') : null;
        $model->end = $object->endDate ? Carbon::parse($object->endDate)->timezone('America/Bogota') : null;

        $model->numberDays = $object->numberDays;
        $model->chargedDays = $object->chargedDays;
        $model->amountPaid = $object->amountPaid;
        $model->workplace_id = $object->workplace ? $object->workplace->id : null;
        $model->customer_absenteeism_disability_parent_id = $object->disabilityParent ? $object->disabilityParent->id : null;
        $model->is_hour = $object->isHour;

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

        CustomerAbsenteeismIndirectCostDTO::bulkInsert($object->indirectCost, $model->id);

        return CustomerAbsenteeismDisability::find($model->id);
    }

    public static function  fillAndUpdateModel($object)
    {
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerAbsenteeismDisability::find($object->id))) {
                return null;
            }
        }

        $model->charged = $object->charged;
        $model->amountPaid = $object->amountPaid;
        // actualizado por
        $model->updatedBy = $userAdmn->id;

        // Guarda
        $model->save();

        // Actualiza timestamp
        $model->touch();

        return CustomerAbsenteeismDisability::find($model->id);
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
                if ($model instanceof CustomerAbsenteeismDisability) {
                    $parsed[] = (new CustomerAbsenteeismDisabilityDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerAbsenteeismDisability) {
            return (new CustomerAbsenteeismDisabilityDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerAbsenteeismDisabilityDTO();
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
