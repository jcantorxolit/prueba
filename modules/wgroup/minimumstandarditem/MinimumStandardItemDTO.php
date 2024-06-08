<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\MinimumStandardItem;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\MinimumStandardItemDetail\MinimumStandardItemDetailDTO;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class MinimumStandardItemDTO
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
     * @param $model : Modelo MinimumStandardItem
     */
    private function getBasicInfo($model)
    {

        //Codigo
        $this->id = $model->id;
        $this->minimumStandard = $model->getMinimumStandard();
        $this->minimumStandardParent = $this->minimumStandard ? $this->minimumStandard->parent : null;
        $this->numeral = $model->numeral;
        $this->description = $model->description;
        $this->value = $model->value;
        $this->criterion = $model->criterion;
        $this->isActive = $model->getIsActive();

        $this->legalFrameworkList = $model->getLegalFramework();
        $this->verificationModeList = $model->getVerificationMode();

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
            if (!($model = MinimumStandardItem::find($object->id))) {
                // No existe
                $model = new MinimumStandardItem();
                $isEdit = false;
            }
        } else {
            $model = new MinimumStandardItem();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->minimum_standard_id = $object->minimumStandard == null ? null : $object->minimumStandard->id;
        $model->numeral = $object->numeral;
        $model->description = $object->description;
        $model->value = $object->value;
        $model->criterion = $object->criterion;
        $model->isActive = $object->isActive;

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

        MinimumStandardItemDetailDTO::bulkInsert($object->legalFrameworkList, $model->id);
        MinimumStandardItemDetailDTO::bulkInsert($object->verificationModeList, $model->id);

        return MinimumStandardItem::find($model->id);
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
                if ($model instanceof MinimumStandardItem) {
                    $parsed[] = (new MinimumStandardItemDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof MinimumStandardItem) {
            return (new MinimumStandardItemDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new MinimumStandardItemDTO();
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
