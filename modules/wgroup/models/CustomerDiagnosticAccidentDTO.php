<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

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
class CustomerDiagnosticAccidentDTO {

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
     * @param $model: Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model) {
        //Log::info($model);
        $this->id = $model->id;
        $this->diagnosticId = $model->diagnostic_id;
        $this->accidentId = $model->accident_id;
        $this->accident = $model->accident->accident;
        $this->numberOfAT = $model->numberOfAT;
        $this->disabilityDay = $model->disabilityDay;
        $this->unsafeAct = $model->getUnsafeAct();
        $this->unsafeCondition = $model->getUnsafeCondition();
        $this->description = $model->description;
        $this->correctiveAction = $model->correctiveAction;
        $this->createdBy = $model->creator->name;
        if ($model->updatedBy != null)
            $this->updatedBy = $model->updater->name;
        $this->created_at = $model->created_at->format('d/m/Y');
        if ($model->updated_at != null)
            $this->updated_at = $model->updated_at->format('d/m/Y');

        $this->tokensession = $this->getTokenSession(true);
    }



    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }


        foreach($object->items as $item) {



            if ($item->id) {
                // Existe
                if (!($model = CustomerDiagnosticAccident::find($item->id))) {
                    // No existe
                    $model = new CustomerDiagnosticAccident();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerDiagnosticAccident();
                $isEdit = false;
            }

            $model->numberOfAT = $item->numberOfAT;
            $model->disabilityDay = $item->disabilityDay;

            if ($item->unsafeAct != null)
                $model->unsafeAct = $item->unsafeAct->value == "-S-" ? null : $item->unsafeAct->value;

            if ($item->unsafeCondition != null)
                $model->unsafeCondition = $item->unsafeCondition->value == "-S-" ? null : $item->unsafeCondition->value;

            $model->description = $item->description;
            $model->correctiveAction = $item->correctiveAction;

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
        }

        return null;

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
                if ($model instanceof CustomerDiagnosticAccident) {
                    $parsed[] = (new CustomerDiagnosticAccidentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDiagnosticAccidentDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDiagnosticAccident) {
            return (new CustomerDiagnosticAccidentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDiagnosticAccidentDTO();
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
