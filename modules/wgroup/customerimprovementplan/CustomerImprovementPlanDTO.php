<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerImprovementPlan;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerImprovementPlanAlert\CustomerImprovementPlanAlertDTO;
use Wgroup\CustomerImprovementPlanTracking\CustomerImprovementPlanTrackingDTO;
use Wgroup\Models\Customer;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerImprovementPlanDTO
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
     * @param $model : Modelo CustomerImprovementPlan
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->entity = $model->getEntity();
        $this->entityName = $model->entityName;
        $this->entityId = $model->entityId;
        $this->classificationName = $model->classificationName;
        $this->classificationId = $model->classificationId;
        $this->type = $model->getType();
        $this->endDate = $model->endDate ? Carbon::parse($model->endDate) : null;
        $this->endDateFormat = $model->endDate ? Carbon::parse($model->endDate)->format('d/mY') : null;
        $this->description = $model->description;
        $this->observation = $model->observation;
        $this->responsible = $model->getResponsible();
        $this->responsibleType = $model->responsibleType;
        $this->status = $model->getStatus();
        $this->isRequiresAnalysis = $model->isRequiresAnalysis == 1;

        $this->trackingList = $model->getTrackingList();
        $this->alertList = $model->getAlertList();

        $this->createdAt = $model->created_at ? Carbon::parse($model->created_at)->timezone('America/Bogota')->format('d/m/Y H:i') : '';
        $this->tokensession = $this->getTokenSession(true);
        $this->period = $model->period;
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
            if (!($model = CustomerImprovementPlan::find($object->id))) {
                // No existe
                $model = new CustomerImprovementPlan();
                $isEdit = false;
            }
        } else {
            $model = new CustomerImprovementPlan();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->entityName = $object->entityName;
        $model->entityId = $object->entityId;
        $model->type = $object->type == null ? null : $object->type->value;
        $model->endDate = $object->endDate ? Carbon::parse($object->endDate)->timezone('America/Bogota') : null;
        $model->description = $object->description;
        $model->observation = $object->observation;
        $model->responsible = $object->responsible != null ? $object->responsible->id : null;
        $model->responsibleType = $object->responsible != null ? $object->responsible->type : null;
        $model->isRequiresAnalysis = isset($object->isRequiresAnalysis) ? $object->isRequiresAnalysis : 0;
        $model->classificationName = isset($object->classificationName) ? $object->classificationName : '';
        $model->classificationId = isset($object->classificationId) ? $object->classificationId : '';
        $model->period = isset($object->period) ? $object->period : null;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {
            $model->status = 'AB';//$object->status == null ? null : $object->status->value;
            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            //Send e-mail to responsible
            try {
                if (isset($object->responsible->email) && $object->responsible->email != '') {
                    if (($modelCustomer = Customer::find($object->customerId))) {
                        $params['Empresa'] = $modelCustomer->businessName;
                        $params['name'] = $object->responsible->name;
                        $params['Modulo'] = $model->getEntity() ? $model->getEntity()->item : $object->entityName;
                        $params['Hallazgo'] = $object->description;
                        $params['Observacion'] = $object->observation;
                        $params['Responsable'] = $object->responsible->name;
                        $params['Fecha'] = $object->endDate ? Carbon::parse($object->endDate)->timezone('America/Bogota')->format('d/m/Y') : null;;
                        Mail::sendTo($object->responsible->email, 'rainlab.user::mail.plan_mejoramiento', $params);
                    }
                }
            } catch (\Exception $ex) {
            }
        }

        CustomerImprovementPlanTrackingDTO::bulkInsert($object->trackingList, $object, $model->id);
        CustomerImprovementPlanAlertDTO::bulkInsert($object->alertList, $model->id);

        return CustomerImprovementPlan::find($model->id);
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
                if ($model instanceof CustomerImprovementPlan) {
                    $parsed[] = (new CustomerImprovementPlanDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerImprovementPlan) {
            return (new CustomerImprovementPlanDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerImprovementPlanDTO();
            }
        }
    }

    private function getUserSsession()
    {
        if (!Auth::check())
            return null;

        return Auth::getUser();
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
