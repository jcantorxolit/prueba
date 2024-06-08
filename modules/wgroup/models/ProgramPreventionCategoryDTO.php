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
use Wgroup\Models\Customer;

/**
 * Description of ProgramPreventionCategoryDTO
 *
 * @author jdblandon
 */
class ProgramPreventionCategoryDTO
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
        $this->programId = $model->program_id;
        $this->name = $model->name;
        $this->parentId = $model->parent_id;
        $this->status = $model->status;
        $this->items = $model->items;

        $this->questions = isset($model->questions) ? $model->questions : array();
        foreach ($this->questions as $q) {
            $q["rate"] = new RateDto();
            if (($mdlRate = Rate::find($q->rate_id))) {
                $q["rate"] = RateDto::parse($mdlRate);
            }
        }

        $this->advance = isset($model->advance) ? $model->advance : "0";
        $this->answers = isset($model->answers) ? $model->answers : "0";
        $this->average = isset($model->average) ? $model->average : "0";
        $this->questionsCount = isset($model->questionsCount) ? $model->questionsCount : "0";
        $this->total = isset($model->total) ? $model->total : "0";


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

        //$this->getBasicInfo($model);

        $this->id = $model->id;
        $this->programId = $model->program_id;
        $this->name = $model->name;
        $this->parentId = $model->parent_id;
        $this->status = $model->status;
        $this->items = $model->items;

        $this->questions = isset($model["questions"]) ? $model["questions"] : array();

        foreach ($this->questions as $q) {
            $q["rate"] = new RateDto();
            if (($mdlRate = Rate::find($q->rate_id))) {
                $q["rate"] = RateDto::parse($mdlRate);
            }
        }

        $this->advance = isset($model["advance"]) ? $model["advance"] : "0";
        $this->answers = isset($model["answers"]) ? $model["answers"] : "0";
        $this->average = isset($model["average"]) ? $model["average"] : "0";
        $this->questionsCount = isset($model["questionsCount"]) ? $model["questionsCount"] : "0";
        $this->total = isset($model["total"]) ? $model["total"] : "0";

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
                if ($model instanceof ProgramPreventionCategory) {
                    $parsed[] = (new ProgramPreventionCategoryDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new ProgramPreventionCategoryDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof ProgramPreventionCategory) {
            return (new ProgramPreventionCategoryDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new ProgramPreventionCategoryDTO();
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
