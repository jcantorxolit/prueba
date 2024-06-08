<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\RoadSafety;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;


/**
 * Description of RoadSafetyDTO
 *
 * @author jdblandon
 */
class RoadSafetyDTO
{

    function __construct($model = null)
    {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $formatResponse = "1")
    {
        if ($model) {
            if ($formatResponse == 'children') {
                $this->getBasicInfoWithChildren($model);
            } else {
                $this->getBasicInfo($model);
            }
        }
    }

    /**
     * @param $model : Modelo CustomerTracking
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->type = $model->getType();

        $this->cycle = $model->getCycle();
        $this->cycleId = $model->cycle_id;
        $this->numeral = $model->numeral;
        $this->description = $model->description;
        $this->isActive = $model->isActive == 1;
        $this->parent = $model->getParent();
        $this->parentId = $model->parent_id;

        $this->tokensession = $this->getTokenSession(true);
    }

    private function getBasicInfoWithChildren($model)
    {
        $this->id = $model->id;
        $this->type = $model->getType();

        $this->cycle = $model->getCycle();
        $this->cycleId = $model->cycle_id;
        $this->numeral = $model->numeral;
        $this->value = $model->value;
        $this->description = $model->description;
        $this->isActive = $model->isActive == 1;
        //$this->parent = $model->getParent();
        $this->parentId = $model->parent_id;
        $this->children = $model->children;

        $this->tokensession = $this->getTokenSession(true);
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        if ($object->id) {
            // Existe
            if (!($model = RoadSafety::find($object->id))) {
                // No existe
                $model = new RoadSafety();
                $isEdit = false;
            }
        } else {
            $model = new RoadSafety();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->type = $object->type == null ? null : $object->type->value;
        $model->parent_id = $object->parent == null ? null : $object->parent->id;
        $model->cycle_id = $object->cycle == null ? null : $object->cycle->id;
        $model->numeral = $object->numeral;
        $model->description = $object->description;
        $model->isActive = $object->isActive;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            //$model->touch();


        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;
            // Guarda
            $model->save();

        }

        return RoadSafety::find($model->id);
    }

    /***
     * @param $model
     * @param string $formatResponse
     * @return $this
     */
    private function parseModel($model, $formatResponse = "1")
    {

        // parse model
        if ($model) {
            $this->setInfo($model, $formatResponse);
        }

        return $this;
    }

    private function parseArray($model, $formatResponse = "1")
    {

        // parse model
        switch ($formatResponse) {

            default:
                $this->getBasicInfo($model);
        }

        return $this;
    }

    public static function parse($info, $formatResponse = "1")
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof RoadSafety) {
                    $parsed[] = (new RoadSafetyDTO())->parseModel($model, $formatResponse);
                } else {
                    $parsed[] = (new RoadSafetyDTO())->parseArray($model, $formatResponse);
                }
            }
            return $parsed;
        } else if ($info instanceof RoadSafety) {
            return (new RoadSafetyDTO())->parseModel($data, $formatResponse);
        } else {
            // return empty instance
            if ($formatResponse == "1") {
                return "";
            } else {
                return new RoadSafetyDTO();
            }
        }
    }

    public static function parseWitChildren($info)
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof RoadSafety) {
                    $parsed[] = (new RoadSafetyDTO())->parseModel($model, 'children');
                } else {
                    $parsed[] = (new RoadSafetyDTO())->parseArray($model, 'children');
                }
            }
            return $parsed;
        } else if ($info instanceof RoadSafety) {
            return (new RoadSafetyDTO())->parseModel($data, 'children');
        } else {
            // return empty instance
            return "";
        }
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
