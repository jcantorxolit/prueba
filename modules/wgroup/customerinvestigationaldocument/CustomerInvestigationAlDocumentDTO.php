<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerInvestigationAlDocument;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;

/**
 * Description of CustomerInvestigationAlDocumentDTO
 *
 * @author jdblandon
 */
class CustomerInvestigationAlDocumentDTO
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
        $this->customerInvestigationId = $model->customer_investigation_id;
        $this->type = $model->getDocumentType();
        $this->location = $model->location;
        $this->description = $model->description;
        $this->folio = $model->folio;
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document);
        $this->isActive = $model->isActive == 1;
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
            if (!($model = CustomerInvestigationAlDocument::find($object->id))) {
                // No existe
                $model = new CustomerInvestigationAlDocument();
                $isEdit = false;
            }
        } else {
            $model = new CustomerInvestigationAlDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_investigation_id = $object->customerInvestigationId;
        $model->type = $object->type == null ? null : $object->type->value;
        $model->location = $object->location;
        $model->description = $object->description;
        $model->folio = $object->folio;
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

        return CustomerInvestigationAlDocument::find($model->id);
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
                if ($model instanceof CustomerInvestigationAlDocument) {
                    $parsed[] = (new CustomerInvestigationAlDocumentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerInvestigationAlDocumentDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerInvestigationAlDocument) {
            return (new CustomerInvestigationAlDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerInvestigationAlDocumentDTO();
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
