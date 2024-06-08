<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 25/11/2017
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\Http\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\Customer\CustomerRepository;
use Wgroup\Traits\UserSecurity;

use DB;
use Excel;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use Validator;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CustomerController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerRepository();
        $this->request = app('Input');

        if (!Request::instance()->header("x-auth-csrf")) {
            $this->run();
        }
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {
            $segments = $request->segments();

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'businessName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'classification', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            if (in_array('customer-contractor', $segments)) {
                $result = $this->repository->allContractor($criteria);
            } else if (in_array('customer-economic-group', $segments)) {
                $result = $this->repository->allEconomigGroup($criteria);
            } else if (in_array('customer-contractor-economic-group', $segments)) {
                $defaultFilters[] = array("field" => 'economicGroup', "operator" => 'like', "value" => $criteria->search);
                $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
                $result = $this->repository->allContractorEconomicGroup($criteria);
            } else if (in_array('customer-agent', $segments)) {
                $result = $this->repository->allAgent($criteria);
            } else {
                $result = $this->repository->all($criteria);
            }

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
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

    public function updateMatrix()
    {
        $content = $this->request->get("data", "");;

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->updateMatrix($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function destroy()
    {
        $id = $this->request->get("id", "");

        try {
            $this->repository->delete($id);
            $this->response->setResult(1);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function find()
    {
        $content = $this->request->get("body", "");;

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->findByDocument($entity);

            if ($result == null) {
                throw new NotFoundHttpException("Record not found.");
            }

            $this->response->setResult($result);
        } catch (NotFoundHttpException $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        } catch (\Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function signUp()
    {
        $content = $this->request->get("body", "");;

        try {
            $entity = HttpHelper::parse($content, true);
            $this->repository->canSignUp($entity);
            $result = $this->repository->signUp($entity);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(400);
            $this->response->setMessage($ex->getMessage());
        } catch (NotFoundHttpException $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        } catch (\Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function show()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->parseModelWithRelations($this->repository->find($id));
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function showBasic()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->parseModelWithRelations($this->repository->find($id));
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function upload()
    {
        $id = $this->request->get("id", "0");
        try {

            $allFiles = Input::file();

            $model = $this->repository->find($id);

            foreach ($allFiles as $file) {
                $this->repository->checkUploadPostBack($file, $model);
            }
            $model = $this->repository->find($id);
            $this->response->setResult($model);
        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function download()
    {
        $id = $this->request->get("id", "0");
        try {

            $model = $this->repository->find($id);

            $file = $model->document->getDiskPath();

            $headers = $this->repository->getDownloadHeaders($model->document);

            //return Response::download($file, $model->document->file_name, $headers);
            return $model->document->download();
        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }
    }
}
