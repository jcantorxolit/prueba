<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInvestigationAlMeasureTracking;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;

/**
 * Description of CustomerInvestigationAlMeasureTrackingDTO
 *
 * @author jdblandon
 */
class CustomerInvestigationAlMeasureTrackingDTO
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
     * @param $model : Modelo CustomerTracking
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerInvestigationMeasureId = $model->customer_investigation_measure_id;

        $this->dateOf = $model->dateOf ? Carbon::parse($model->dateOf) : null;
        $this->status = $model->getStatus();
        $this->parent = $model->getParent();
        $this->implementationDate = $model->implementationDate ? Carbon::parse($model->implementationDate) : null;

        $this->comment = $model->comment;

        $this->isEffective = $model->isEffective == 1;
        $this->description = $model->description;

        $this->isReschedule = $model->isReschedule == 1;
        $this->sort = $model->sort;

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
            if (!($model = CustomerInvestigationAlMeasureTracking::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAlMeasureTracking();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAlMeasureTracking();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_investigation_measure_id = $object->customerInvestigationMeasureId;
        $model->dateOf = $object->dateOf ? Carbon::parse($object->dateOf) : null;
        $model->status = $object->status ? $object->status->value : null;
        $model->implementationDate = $object->implementationDate ? Carbon::parse($object->implementationDate) : null;
        $model->comment = $object->comment;
        $model->isEffective = $object->isEffective;
        $model->description = $object->description;
        $model->isReschedule = $object->isReschedule;
        $model->sort = $object->sort;

        if ($isEdit) {


            // actualizado por
            $model->updatedBy = $userAdmn->id;
            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

            if ($model->status == 'NoImplementado' && $model->isReschedule) {
                $newModel = new CustomerInvestigationAlMeasureTracking();
                $newModel->customer_investigation_measure_id = $model->customer_investigation_measure_id;
                $newModel->dateOf = $object->rescheduleDate ? Carbon::parse($object->rescheduleDate) : null;
                $newModel->status = 'Pendiente';
                $newModel->implementationDate = null;
                $newModel->comment = null;
                $newModel->isEffective = false;
                $newModel->description = '';
                $newModel->isReschedule = false;
                $newModel->sort = $model->sort + 1;
                $newModel->save();
            }

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        return CustomerInvestigationAlMeasureTracking::find($model->id);
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
                if ($model instanceof CustomerInvestigationAlMeasureTracking) {
                    $parsed[] = (new CustomerInvestigationAlMeasureTrackingDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerInvestigationAlMeasureTrackingDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAlMeasureTracking) {
            return (new CustomerInvestigationAlMeasureTrackingDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlMeasureTrackingDTO();
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
