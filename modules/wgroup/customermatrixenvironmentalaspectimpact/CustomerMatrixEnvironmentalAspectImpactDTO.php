<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerMatrixEnvironmentalAspectImpact;

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
class CustomerMatrixEnvironmentalAspectImpactDTO
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
        $this->customerMatrixEnvironmentalAspectId = $model->customer_matrix_environmental_aspect_id;
        $this->impact = $model->getImpact();


        $this->createdBy = $model->creator->name;
        $this->created_at = $model->created_at->format('d/m/Y');

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
            if (!($model = CustomerMatrixEnvironmentalAspectImpact::find($object->id))) {
                // No existe
                $model = new CustomerMatrixEnvironmentalAspectImpact();
                $isEdit = false;
            }
        } else {
            $model = new CustomerMatrixEnvironmentalAspectImpact();
            $isEdit = false;
        }

        if ($object->aspect != null && $object->impact != null) {
            /** :: ASIGNO DATOS BASICOS ::  **/// cliente asociado
            ;
            $model->customer_matrix_environmental_aspect_id = $object->aspect->id;
            $model->customer_matrix_environmental_impact_id = $object->impact->id;

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

        return CustomerMatrixEnvironmentalAspectImpact::find($model->id);

    }

    public static function bulkInsert($records, $entityId)
    {
        try {
            $userAdmn = Auth::getUser();

            foreach ($records as $record) {

                if ($record && $record->impact != null) {
                    $isNewRecord = false;

                    if (CustomerMatrixEnvironmentalAspectImpact::whereCustomerMatrixEnvironmentalAspectId($entityId)
                            ->whereCustomerMatrixEnvironmentalImpactId($record->impact->id)->count() > 0
                    ) {
                        continue;
                    }

                    if (!($entityModel = CustomerMatrixEnvironmentalAspectImpact::find($record->id))) {
                        $entityModel = new CustomerMatrixEnvironmentalAspectImpact();
                        $isNewRecord = true;
                    }

                    $entityModel->customer_matrix_environmental_aspect_id = $entityId;
                    $entityModel->customer_matrix_environmental_impact_id = $record->impact->id;

                    //var_dump('va a guardar');
                    if ($isNewRecord) {
                        $entityModel->createdBy = $userAdmn->id;
                        $entityModel->save();
                    } else {
                        $entityModel->updatedBy = $userAdmn->id;
                        $entityModel->save();
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
                if ($model instanceof CustomerMatrixEnvironmentalAspectImpact) {
                    $parsed[] = (new CustomerMatrixEnvironmentalAspectImpactDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerMatrixEnvironmentalAspectImpactDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerMatrixEnvironmentalAspectImpact) {
            return (new CustomerMatrixEnvironmentalAspectImpactDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerMatrixEnvironmentalAspectImpactDTO();
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
