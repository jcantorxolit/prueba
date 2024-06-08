<?php

namespace AdeN\Api\Modules\User\Http\Controllers;

use AdeN\Api\Modules\User\UserRepository;
use DB;
use Exception;
use Log;
use Request;
use Response;
use Validator;

use Wgroup\Traits\UserSecurity;

use AdeN\Api\Classes\BaseController;

class UserController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new UserRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function show()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->find($id);
            $this->response->setResult($result);

        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }
}
