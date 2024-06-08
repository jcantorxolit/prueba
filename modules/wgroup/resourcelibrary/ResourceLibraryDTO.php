<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\ResourceLibrary;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;


/**
 * Description of ResourceLibraryDTO
 *
 * @author jdblandon
 */
class ResourceLibraryDTO
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
                $this->getBasicInfo($model);
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

        $this->dateOf = $model->dateOf ? Carbon::parse($model->dateOf) : null;
        $this->name = $model->name;
        $this->author = $model->author;
        $this->subject = $model->subject;
        $this->description = $model->description;
        $this->shortDescription = $this->substru($model->description,0,100);//strlen($model->description) > 20 ? substr($model->description, 0, 20) : $model->description;
        $this->keywords = $model->getKeywords();
        $this->isActive = $model->isActive == 1;

        //var_dump( utf8_encode(substr($model->description, 0, 20)));

        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document);
        $this->cover = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->cover);

        $this->dateOfFormat = $model->dateOf ? Carbon::parse($model->dateOf)->format('d/m/Y') : null;
        $this->createdAtFormat = $model->created_at ? Carbon::parse($model->created_at)->format('d/m/Y') : null;

        $this->tokensession = $this->getTokenSession(true);
    }

    private function substru($str,$from,$len){
        return preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $from .'}'.'((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,'. $len .'}).*#s','$1', $str);
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
            if (!($model = ResourceLibrary::find($object->id))) {
                // No existe
                $model = new ResourceLibrary();
                $isEdit = false;
            }
        } else {
            $model = new ResourceLibrary();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->type = $object->type ? $object->type->value : null;
        $model->dateOf = $object->dateOf ? Carbon::parse($object->dateOf)->timezone('America/Bogota') : null;
        $model->name = $object->name;
        $model->author = $object->author;
        $model->subject = $object->subject;
        $model->description = $object->description;
        $model->keyword = ResourceLibraryDTO::getKeyword($object->keywords);
        $model->isActive = $object->isActive;

        if ($isEdit) {
            $model->updatedBy = $userAdmn->id;
            $model->save();
        } else {
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;
            $model->save();
        }

        return ResourceLibrary::find($model->id);
    }

    public static function  getKeyword($keywords)
    {
        if ($keywords == null) {
            return '';
        }

        $keywordList = array();

        foreach($keywords as $keyword) {
            $keywordList[] = $keyword->text;
        }

        return implode(',', $keywordList);
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
                if ($model instanceof ResourceLibrary) {
                    $parsed[] = (new ResourceLibraryDTO())->parseModel($model, $formatResponse);
                } else {
                    $parsed[] = (new ResourceLibraryDTO())->parseArray($model, $formatResponse);
                }
            }
            return $parsed;
        } else if ($info instanceof ResourceLibrary) {
            return (new ResourceLibraryDTO())->parseModel($data, $formatResponse);
        } else {
            // return empty instance
            if ($formatResponse == "1") {
                return "";
            } else {
                return new ResourceLibraryDTO();
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
                if ($model instanceof ResourceLibrary) {
                    $parsed[] = (new ResourceLibraryDTO())->parseModel($model, 'children');
                } else {
                    $parsed[] = (new ResourceLibraryDTO())->parseArray($model, 'children');
                }
            }
            return $parsed;
        } else if ($info instanceof ResourceLibrary) {
            return (new ResourceLibraryDTO())->parseModel($data, 'children');
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
