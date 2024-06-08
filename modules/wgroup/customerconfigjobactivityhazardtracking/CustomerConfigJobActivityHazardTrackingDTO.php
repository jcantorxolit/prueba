<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerConfigJobActivityHazardTracking;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerConfigJobActivityHazardTrackingDTO
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
        $this->jobActivityHazardId = $model->job_activity_hazard_id;
        $this->type = $model->type;
        $this->description = $model->description;
        $this->item = $model->item;
        $this->source = $model->source;
        $this->date = Carbon::parse($model->created_at)->format('d/m/Y H:i');
        $this->user = $model->creator ? $model->creator->name : '';

        $this->tokensession = $this->getTokenSession(true);
    }

    public static function  fillAndSaveModel($object)
    {
        try {
            $isEdit = true;
            $userAdmn = Auth::getUser();

            if (!$object) {
                return false;
            }

            /** :: DETERMINO SI ES EDICION O CREACION ::  **/
            if ($object->id) {
                // Existe
                if (!($model = CustomerConfigJobActivityHazardTracking::find($object->id))) {
                    // No existe
                    $model = new CustomerConfigJobActivityHazardTracking();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerConfigJobActivityHazardTracking();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/
            // cliente asociado
            $model->job_activity_hazard_id = $object->jobActivityHazardId;
            $model->type = $object->type;
            $model->description = $object->description;
            $model->item = $object->item;
            $model->source = $object->source;
            $model->old_value = $object->oldValue;
            $model->new_value = $object->newValue;
            $model->reason = $object->reason;
            $model->reason_observation = $object->reasonObservation;

            if ($isEdit) {

                // actualizado por
                $model->updated_by = $userAdmn->id;

                // Guarda
                $model->save();

                // Actualiza timestamp
                $model->touch();

            } else {

                // Creado por
                $model->created_by = $userAdmn->id;
                $model->updated_by = $userAdmn->id;

                // Guarda
                $model->save();

            }

            return CustomerConfigJobActivityHazardTracking::find($model->id);
        } catch (Exception $ex) {
            var_dump($ex->getMessage());
            return null;
        }
    }

    public static function create($jobActivityHazardId, $type, $description, $oldValue, $newValue, $reaon, $observation, $source)
    {

        $historical = new \stdClass();
        $historical->id = 0;
        $historical->jobActivityHazardId = $jobActivityHazardId;
        $historical->type = $type;
        $historical->description = $description;
        $historical->oldValue = $oldValue;
        $historical->newValue = $newValue;
        $historical->reason = $reaon;
        $historical->reasonObservation = $observation;
        $historical->source = $source;
        $historical->item = null;
        CustomerConfigJobActivityHazardTrackingDTO::fillAndSaveModel($historical);
    }

    public static function  bulkInsert($object)
    {
        $isEdit = true;
        $userAdmn = Auth::getUser();

        try {
            foreach ($object->interventions as $intervention) {
                $isEdit = true;
                if ($intervention && $intervention->type != null) {
                    if ($intervention->id) {
                        if (!($model = CustomerConfigJobActivityHazardTracking::find($intervention->id))) {
                            $isEdit = false;
                            $model = new CustomerConfigJobActivityHazardTracking();
                        }
                    } else {
                        $model = new CustomerConfigJobActivityHazardTracking();
                        $isEdit = false;
                    }

                    $model->job_activity_hazard_id = $object->id;
                    $model->type = $intervention->type->value;
                    $model->description = $intervention->description;

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
        } catch (Exception $ex) {
            Flash::error($ex->getMessage());
            //Log::info("Envio correo proyecto ex");
            //Log::info($ex->getMessage());
        }


        //return CustomerConfigJobActivityHazardTracking::find($model->id);
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
                $this->getReportSummaryPrg($model);
                break;
            case "3":
                $this->getReportSummaryAdv($model);
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
                if ($model instanceof CustomerConfigJobActivityHazardTracking) {
                    $parsed[] = (new CustomerConfigJobActivityHazardTrackingDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerConfigJobActivityHazardTrackingDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerConfigJobActivityHazardTracking) {
            return (new CustomerConfigJobActivityHazardTrackingDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerConfigJobActivityHazardTrackingDTO();
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
