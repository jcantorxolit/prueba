<?php

namespace AdeN\Api\Modules\Customer\Employee\Indicators\Http\Controllers;

use Request;
use Response;

use AdeN\Api\Classes\BaseController;
use Wgroup\Traits\UserSecurity;
use AdeN\Api\Modules\Customer\Employee\Indicators\CustomerEmployeeIndicatorRepository;


class CustomerEmployeeIndicatorController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerEmployeeIndicatorRepository();
        $this->request = app('Input');
        $this->run();
    }


    public function consolidateStatusEmployees()
    {
        try {
            $customerId = $this->request->get("customerId");
            $this->repository->consolidateStatusEmployees($customerId);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }



    public function consolidateDemographic()
    {
        try {
            $customerId = $this->request->get("customerId");
            $this->repository->consolidateDemographic($customerId);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function consolidateSupportDocuments()
    {
        try {
            $customerId = $this->request->get("customerId");
            $this->repository->consolidateSupportDocuments($customerId);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

}
