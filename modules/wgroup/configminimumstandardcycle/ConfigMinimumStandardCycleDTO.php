<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\ConfigMinimumStandardCycle;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class ConfigMinimumStandardCycleDTO
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
     * @param $model : Modelo ConfigMinimumStandardCycle
     */
    private function getBasicInfo($model)
    {
        //Codigo
        $this->id = $model->id;
        $this->name = $model->name;
        $this->abbreviation = $model->abbreviation;
        $this->status = $model->status;
        $this->color = $model->color;
        $this->highlightColor = $model->highlightColor;

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
            if (!($model = ConfigMinimumStandardCycle::find($object->id))) {
                // No existe
                $model = new ConfigMinimumStandardCycle();
                $isEdit = false;
            }
        } else {
            $model = new ConfigMinimumStandardCycle();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->name = $object->name;
        $model->abbreviation = $object->abbreviation;
        $model->status = $object->status;
        $model->color = $object->color;
        $model->highlightColor = $object->highlightColor;

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

        //TODO

        return ConfigMinimumStandardCycle::find($model->id);
    }

    public static function  bulkInsert($records, $entityId)
    {
        try {
            foreach ($records as $record) {
                $isEdit = true;
                if ($record) {

                    if ($record->cause == null) {
                        continue;
                    }

                    if (!$record->id) {
                        if (ConfigMinimumStandardCycle::whereCustomerImprovementPlanCauseId($entityId)
                                ->whereCause($record->cause->id)->count() > 0
                        ) {
                            continue;
                        }
                    }

                    if ($record->id) {
                        if (!($model = ConfigMinimumStandardCycle::find($record->id))) {
                            $isEdit = false;
                            $model = new ConfigMinimumStandardCycle();
                        }
                    } else {
                        $model = new ConfigMinimumStandardCycle();
                        $isEdit = false;
                    }

                    /** :: ASIGNO DATOS BASICOS ::  **/
                    $model->customer_improvement_plan_cause_id = $entityId;
                    $model->cause = $record->cause->id;

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
        } catch (\Exception $ex) {

        }
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
                if ($model instanceof ConfigMinimumStandardCycle) {
                    $parsed[] = (new ConfigMinimumStandardCycleDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof ConfigMinimumStandardCycle) {
            return (new ConfigMinimumStandardCycleDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new ConfigMinimumStandardCycleDTO();
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
