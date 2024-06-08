<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerImprovementPlanTracking;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerImprovementPlan\CustomerImprovementPlan;
use Wgroup\Models\Customer;
use Mail;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerImprovementPlanTrackingDTO
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
     * @param $model : Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerImprovementPlanId = $model->customer_improvement_plan_id;
        $this->responsible = $model->getResponsible();
        $this->responsibleType = $model->responsibleType;
        $this->status = $model->getStatus();
        $this->observation = $model->observation;
        $this->startDate = $model->startDate ? Carbon::parse($model->startDate) : null;
        $this->startDateFormat = $model->startDate ? Carbon::parse($model->startDate)->format('d/m/Y') : null;

        $this->createdBy = $model->creator->name;

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at ? $model->updated_at->format('d/m/Y') : null;


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
            if (!($model = CustomerImprovementPlanTracking::find($object->id))) {
                // No existe
                $model = new CustomerImprovementPlanTracking();
                $isEdit = false;
            }
        } else {
            $model = new CustomerImprovementPlanTracking();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->customer_improvement_plan_id = $object->customerImprovementPlanId;
        $model->startDate = $object->startDate ? Carbon::parse($object->startDate)->timezone('America/Bogota') : null;
        $model->responsible = $object->responsible != null ? $object->responsible->id : null;
        $model->responsibleType = $object->responsible != null ? $object->responsible->type : null;
        $model->status = $object->status != null ? $object->status->value : 'A';
        $model->observation = $object->observation;

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

        return CustomerImprovementPlanTracking::find($model->id);

    }

    public static function  bulkInsert($records, $object, $parentId)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$records) {
            return false;
        }


        foreach ($records as $record) {

            if ($record->responsible == null) {
                continue;
            }

            if (!$record->id) {
                if (CustomerImprovementPlanTracking::whereCustomerImprovementPlanId($parentId)
                        ->whereResponsible($record->responsible->id)
                        ->where('responsibleType', $record->responsible->type)->count() > 0
                ) {
                    continue;
                }
            }

            if ($record->id) {
                // Existe
                if (!($model = CustomerImprovementPlanTracking::find($record->id))) {
                    // No existe
                    $model = new CustomerImprovementPlanTracking();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerImprovementPlanTracking();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/

            // cliente asociado
            $model->customer_improvement_plan_id = $parentId;
            $model->responsible = $record->responsible != null ? $record->responsible->id : null;
            $model->responsibleType = $record->responsible != null ? $record->responsible->type : null;
            $model->startDate = $record->startDate ? Carbon::parse($record->startDate)->timezone('America/Bogota') : null;
            $model->status = isset($model->status) && $model->status != null ? $model->status : 'A';

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

                //Send e-mail to responsible of track the advance
                try {
                    if (isset($record->responsible->email) && $record->responsible->email != '') {
                        if (($modelCustomer = Customer::find($object->customerId))) {
                            $params['Empresa'] = $modelCustomer->businessName;
                            $params['name'] = $record->responsible->name;
                            $params['Modulo'] = CustomerImprovementPlan::getEntityOrigin($object->entityName) ? CustomerImprovementPlan::getEntityOrigin($object->entityName)->item : $object->entityName;
                            $params['Hallazgo'] = $object->description;
                            $params['Descripcion'] = $object->observation;
                            $params['Responsable'] = $record->responsible->name;
                            $params['Fecha'] = $record->startDate ? Carbon::parse($record->startDate)->timezone('America/Bogota')->format('d/m/Y') : null;;
                            Mail::sendTo($record->responsible->email, 'rainlab.user::mail.plan_mejoramiento_seguimiento_asesores', $params);
                        }
                    }
                } catch (\Exception $ex) {
                }
            }
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/

        return true;
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

                break;
            case "3":

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
                if ($model instanceof CustomerImprovementPlanTracking) {
                    $parsed[] = (new CustomerImprovementPlanTrackingDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerImprovementPlanTrackingDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerImprovementPlanTracking) {
            return (new CustomerImprovementPlanTrackingDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerImprovementPlanTrackingDTO();
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
