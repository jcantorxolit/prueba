<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerEvaluationMinimumStandardItemDetail;

use Carbon\Carbon;
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
class CustomerEvaluationMinimumStandardItemDetailDTO {

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
     * @param $model: Modelo CustomerEvaluationMinimumStandardItemDetail
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerEvaluationStandardMinimumItemId = $model->customer_evaluation_standard_minimum_item_id;
        $this->comment = $model->comment;
        $this->user = $model->user;
        $this->createdAt = Carbon::parse($model->createdAt)->timezone('America/Bogota')->format('d/m/Y H:m:s');

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
            if (!($model = CustomerEvaluationMinimumStandardItemDetail::find($object->id))) {
                // No existe
                $model = new CustomerEvaluationMinimumStandardItemDetail();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEvaluationMinimumStandardItemDetail();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_evaluation_standard_minimum_item_id = $object->customerEvaluationStandardMinimumItemId;
        $model->comment = $object->comment;

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

        return CustomerEvaluationMinimumStandardItemDetail::find($model->id);
    }

    public static function bulkInsert($records, $entityId)
    {
        try {
            foreach ($records as $record) {
                $isEdit = true;
                if ($record) {


                    if ($record->id && !$record->isActive) {
                        $model = CustomerEvaluationMinimumStandardItemDetail::find($record->id);
                        if ($model != null) {
                            $model->delete();
                        }
                        continue;
                    }

                    if (!$record->id && !$record->isActive) {
                        continue;
                    }

                    if ($record->id) {
                        if (!($model = CustomerEvaluationMinimumStandardItemDetail::find($record->id))) {
                            $isEdit = false;
                            $model = new CustomerEvaluationMinimumStandardItemDetail();
                        }
                    } else {
                        $model = new CustomerEvaluationMinimumStandardItemDetail();
                        $isEdit = false;
                    }

                    /** :: ASIGNO DATOS BASICOS ::  **/
                    $model->customer_evaluation_minimum_standard_item_id = $entityId;
                    $model->minimum_standard_item_detail_id = $record->minimumStandardItemDetailId;

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
                if ($model instanceof CustomerEvaluationMinimumStandardItemDetail) {
                    $parsed[] = (new CustomerEvaluationMinimumStandardItemDetailDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerEvaluationMinimumStandardItemDetailDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerEvaluationMinimumStandardItemDetail) {
            return (new CustomerEvaluationMinimumStandardItemDetailDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerEvaluationMinimumStandardItemDetailDTO();
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
