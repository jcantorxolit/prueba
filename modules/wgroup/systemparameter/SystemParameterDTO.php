<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\SystemParameter;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Models\InfoDetail;
use Wgroup\Models\InfoDetailDto;
use DB;

/**
 * Description of CustomerDto
 *
 * @author TeamCloud
 */
class SystemParameterDTO
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
            $this->getInfoBasic($model);
        }
    }

    private function getInfoBasic($model)
    {
        $this->id = $model->id;
        $this->value = $model->value;
        $this->item = $model->item;
        $this->group = $model->group;
        $this->namespace = $model->namespace;
        $this->code = null;

        $parentParameter = "";

        if ($model->group == "professor_event_main_theme") {
            $parentParameter = "professor_event_discipline";
        } else if ($model->group == "professor_event_program") {
            $parentParameter = "professor_event_main_theme";
        } else if ($model->group == "work_medicine_complementary_test_result") {
            $parentParameter = "work_medicine_complementary_test";
        }

        if ($parentParameter != '') {
            $this->code = SystemParameter::whereGroup($parentParameter)->whereNamespace($model->namespace)->whereValue($model->code)->first();
        }

        if ($this->code != null && $this->code->group == "professor_event_main_theme") {
            $this->parent = SystemParameter::whereGroup("professor_event_discipline")->whereNamespace($model->namespace)->whereValue($this->code->code)->first();
        }

        if ($this->code != null && $this->code->group == "work_medicine_complementary_test") {
            $this->parent = $this->code->item;
        } else {
            $this->parent = null;
        }

        $this->logo = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo);
        $this->tokensession = $this->getTokenSession(true);
    }

    public static function canInsert($object)
    {
        if ($object->group == "improvement_plan_root_prioritization_factor") {
            if ($object->id == 0) {
                return !SystemParameter::whereGroup($object->group)
                    ->whereValue($object->value)
                    ->count() > 0;
            } else {
                $currentModel = SystemParameter::find($object->id);

                if ($currentModel != null) {
                    if ($currentModel->value != $object->value) {
                        return !SystemParameter::whereGroup($object->group)
                            ->whereValue($object->value)
                            ->count() > 0;
                    }
                }
            }
        }

        return true;
    }

    public static function fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = SystemParameter::find($object->id))) {
                // No existe
                $model = new SystemParameter();
                $isEdit = false;
            }
        } else {
            $model = new SystemParameter();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        $model->value = SystemParameterDTO::getValue($object);
        $model->item = $object->item;
        $model->namespace = $object->namespace;
        $model->group = $object->group;
        $model->code = isset($object->code) && $object->code ? $object->code->value : null;

        if ($isEdit) {

            $model->save();

            // Actualiza timestamp
            //$model->touch();

        } else {


            // Guarda
            $model->save();
        }

        return SystemParameter::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {
        if ($fmt_response != "1") {
            // parse model
            switch ($fmt_response) {
                case "1":
                    $this->getInfoBasic($model);
                    break;
                default:
            }
        } else {
            // parse model
            if ($model) {
                $this->setInfo($model, $fmt_response);
            }
        }
        return $this;

    }

    private static function getValue($entity)
    {
        if ($entity->value != null && $entity->value != '') {
            return $entity->value;
        }

        $value = SystemParameter::where("namespace", $entity->namespace)->max("id");
        //var_dump($entity);
        if (is_numeric($value)) {
            return floatval($value) + 1;
        } else {
            return rand(99, 999);
        }
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
                if ($model instanceof SystemParameter) {
                    $parsed[] = (new SystemParameterDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new SystemParameterDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof SystemParameter) {
            return (new SystemParameterDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new SystemParameterDTO();
            }
        }
    }

    private function parseArray($model, $fmt_response = "1")
    {
        switch ($fmt_response) {
            case "1":
                //$this->getBasicInfoSummary($model);
                break;
            default:
        }

        return $this;
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
