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
use Wgroup\Models\Rate;

/**
 * Description of CustomerDto
 *
 * @author TeamCloud
 */
class RateDto {

    public $id = "0";
    public $color = "#FFFFFF";
    public $text = "- Seleccionar -";
    public $code = "";
    public $value = "";

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        if ($model) {
                $this->getInfoBasic($model);
        }
    }

    private function getInfoBasic($model) {

        $this->id = $model->id;
        $this->value = $model->name;
        $this->color = $model->color;
        $this->text = $model->text;
        $this->code = $model->code;

    }


    /// ::: METODOS PRIVADOS DE CADA DTO

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
                if ($model instanceof Rate) {
                    $parsed[] = (new RateDto())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof Rate) {
            return (new RateDto())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new RateDto();
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
