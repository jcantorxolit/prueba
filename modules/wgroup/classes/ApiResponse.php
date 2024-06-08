<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Classes;

/**
 * Description of ApiResponse
 *
 * @author TeamCloud
 */
class ApiResponse
{

    public $message;
    public $errorcode;
    public $statuscode;
    public $extra = [];
    public $result;
    public $data;

    // For datatable
    public $draw;
    public $recordsTotal;
    public $recordsFiltered;
    public $error;

    function __construct()
    {
        $this->errorcode = "";
        $this->statuscode = 200;
        $this->message = "";
        $this->result = "";
        $this->extra = [];
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getErrorcode()
    {
        return $this->errorcode;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function setErrorcode($errorcode)
    {
        $this->errorcode = $errorcode;
    }

    public function setResult($result)
    {
        $this->result = $result;
    }

    public function getExtra()
    {
        return $this->extra;
    }

    public function setExtra($extra)
    {
        $this->extra = $extra;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getStatuscode()
    {
        return $this->statuscode;
    }

    public function setStatuscode($statuscode)
    {
        $this->statuscode = $statuscode;
    }

    function getDraw()
    {
        return $this->draw;
    }

    function getRecordsTotal()
    {
        return $this->recordsTotal;
    }

    function getRecordsFiltered()
    {
        return $this->recordsFiltered;
    }

    function getError()
    {
        return $this->error;
    }

    function setDraw($draw)
    {
        $this->draw = $draw;
    }

    function setRecordsTotal($recordsTotal)
    {
        $this->recordsTotal = $recordsTotal;
    }

    function setRecordsFiltered($recordsFiltered)
    {
        $this->recordsFiltered = $recordsFiltered;
    }

    function setError($error)
    {
        $this->error = $error;
    }


}
