<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Mail;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerController;
use Wgroup\CustomerAudit\CustomerAudit;
use Wgroup\CustomerTrackingNotification\CustomerTrackingNotification;
use Wgroup\CustomerTrackingNotification\CustomerTrackingNotificationDTO;
use Wgroup\Models\Customer;
use RainLab\User\Models\User;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerTrackingCommentDTO {

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
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfo($model) {
        //Codigo
        $this->id = $model->id;
        $this->customerTrackingId = $model->customer_tracking_id;
        $this->comment = $model->comment;
        if ($model->creator != null) {
            $this->responsible = $model->creator->name;
        } else {
            $this->responsible = "";
        }
        $this->date = Carbon::parse($model->created_at)->timezone('America/Bogota')->format('d/m/Y H:m');

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);
    }


    public static function  fillAndSaveModel($entities, $parentId)
    {
        $userAdmn = Auth::getUser();

        foreach ($entities as $object) {
            $isEdit = true;

            if (!$object) {
                return false;
            }

            /** :: DETERMINO SI ES EDICION O CREACION ::  **/
            if ($object->id) {
                // Existe
                if (!($model = CustomerTrackingComment::find($object->id))) {
                    // No existe
                    $model = new CustomerTrackingComment();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerTrackingComment();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/
            $model->customer_tracking_id = $parentId;
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
                if ($model instanceof CustomerTrackingComment) {
                    $parsed[] = (new CustomerTrackingCommentDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerTrackingComment) {
            return (new CustomerTrackingCommentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerTrackingCommentDTO();
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
