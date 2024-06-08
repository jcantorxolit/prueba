<?php

namespace AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact\Http\Controllers;

use DB;
use Excel;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use Validator;

use Wgroup\Traits\UserSecurity;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;

use AdeN\Api\Modules\PositivaFgn\Vendor\MainContact\MainContactRepository;

class MainContactController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new MainContactRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function store()
    {
        $content = $this->request->get("data", "");;
        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function show()
    {
        $vendorId = $this->request->get("vendorId", "");
        try {
            $result = $this->repository->parseModelWithRelations($vendorId);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function destroy()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->delete($id);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function destroyInfo()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->deleteInfo($id);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

}
