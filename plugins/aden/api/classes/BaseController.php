<?php
/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 4/25/2016
 * Time: 8:57 PM
 */

namespace AdeN\Api\Classes;


use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Config;
use Controller;

class BaseController extends Controller
{
    protected $response;
    protected $request;

    public function __construct()
    {
        //$this->request = app('Input');

        // set response
        $this->response = new ApiResponse();
        $this->response->setMessage("1");
        $this->response->setStatuscode(200);
    }
}