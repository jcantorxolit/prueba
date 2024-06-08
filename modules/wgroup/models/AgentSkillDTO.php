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
use Wgroup\Models\CustomerAgent;

/**
 * Description of CustomerDto
 *
 * @author TeamCloud
 */
class AgentSkillDTO {

    function __construct($model = null, $customerId = 0) {
        if ($model) {
            $this->parse($model, $customerId);
        }
    }

    public function setInfo($model = null, $customerId = 0) {

        // recupera informacion basica del formulario
        if ($model) {
                $this->getInfoBasic($model, $customerId);
        }
    }

    private function getInfoBasic($model, $customerId = 0) {

        $this->id = $model->id;

        $this->agent_id = $model->agent_id;

        $this->skill = $model->getType();
    }


    /// ::: METODOS PRIVADOS DE CADA DTO

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $customerId = "0") {

        // parse model
        if ($model) {
            $this->setInfo($model, $customerId);
        }

        return $this;
    }

    public static function parse($info, $customerId = 0) {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof AgentSkill) {
                    $parsed[] = (new AgentSkillDTO())->parseModel($model, $customerId);
                }
            }
            return $parsed;
        } else if ($info instanceof AgentSkill) {
            return (new AgentSkillDTO())->parseModel($data, $customerId);
        } else {
            // return empty instance

                return new AgentSkillDTO();

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
