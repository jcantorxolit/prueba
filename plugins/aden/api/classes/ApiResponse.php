<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace AdeN\Api\Classes;

/**
 * Description of ApiResponse
 *
 * @author David Blandon
 */
class ApiResponse {

    public $message;
    public $errorCode;
    public $statusCode;
    public $extra = [];
    public $result;
    public $data;

    public $draw;
    public $recordsTotal;
    public $recordsFiltered;
    public $error;

    function __construct() {
        $this->errorCode = "";
        $this->statusCode = 200;
        $this->message = "";
        $this->result = "";
        $this->extra = [];
    }

    public function getMessage() {
        return $this->message;
    }

    public function getErrorCode() {
        return $this->errorCode;
    }

    public function getResult() {
        return $this->result;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function setErrorCode($errorCode) {
        $this->errorCode = $errorCode;
    }

    public function setResult($result) {
        $this->result = $result;
    }

    public function getExtra() {
        return $this->extra;
    }

    public function setExtra($extra) {
        $this->extra = $extra;
    }

    public function getData() {
        return $this->data;
    }

    public function setData($data) {
        $this->data = $data;
    }

    public function getStatusCode() {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode) {
        $this->statusCode = $statusCode;
    }
    function getDraw() {
        return $this->draw;
    }

    function getRecordsTotal() {
        return $this->recordsTotal;
    }

    function getRecordsFiltered() {
        return $this->recordsFiltered;
    }

    function getError() {
        return $this->error;
    }

    function setDraw($draw) {
        $this->draw = $draw;
    }

    function setRecordsTotal($recordsTotal) {
        $this->recordsTotal = $recordsTotal;
    }

    function setRecordsFiltered($recordsFiltered) {
        $this->recordsFiltered = $recordsFiltered;
    }

    function setError($error) {
        $this->error = $error;
    }
}
