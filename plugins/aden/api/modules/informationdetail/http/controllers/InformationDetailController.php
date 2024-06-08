<?php

namespace AdeN\Api\Modules\InformationDetail\Http\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;

use DB;
use Excel;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use Validator;
use Exception;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package Presupuesto\api
 * @author David Blandon
 */
class InformationDetailController extends BaseController
{
    private $repository;

    public function __construct()
    {
        $this->repository = new InformationDetailRepository();
        $this->request = app('Input');

        parent::__construct();
    }

    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $criteria = CriteriaHelper::parse($content);

            $result = $this->repository->all($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["total"]);
            $this->response->setRecordsFiltered($result["total"]);
        } catch (Exception $exc) {
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function save()
    {
        $input = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($input);
            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);
        } catch (Exception $exc) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function delete()
    {
        $id = $this->request->get("id", "");

        try {
            $this->repository->delete($id);
            $this->response->setResult(1);
        } catch (Exception $exc) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function show()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->find($id);
            $this->response->setResult($result);
        } catch (Exception $exc) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }
}