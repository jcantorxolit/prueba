<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 25/11/2017
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\InvestigationAl\Http\Controllers;

use DB;
use Excel;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use Validator;
use Exception;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\KendoCriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use Wgroup\Traits\UserSecurity;

use AdeN\Api\Modules\Customer\InvestigationAl\CustomerInvestigationAlRepository;

class CustomerInvestigationAlController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerInvestigationAlRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $criteria = CriteriaHelper::parse($content);

            if ($this->isUserAdmin()) {                
            } else if ($this->isUserAgentExternal()) {
                $mandatory = [array("field" => 'investigatorId', "operator" => 'eq', "value" => $this->isUserRelatedAgent())];
                $criteria = CriteriaHelper::addMandatoryFilter($criteria, $mandatory);
            } else if ($this->isUserAgentEmployee()) {
                $mandatory = [array("field" => 'agentId', "operator" => 'eq', "value" => $this->isUserRelatedAgent())];
                $criteria = CriteriaHelper::addMandatoryFilter($criteria, $mandatory);
            } else {}


            $defaultFilters = [
                array("field" => 'sisalud', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'accidentDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'accidentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'businessName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->all($criteria);

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

    public function indexTracking()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'id', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'customerDocumentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'customerBusinessName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'directorName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'agentName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'employeeDocumentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'employeeName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'accidentDateOf', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'date_ia_customer', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'date_letter_recommendation', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'dateOf', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'comment', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'sisalud', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'accidentCity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'customerPrincipalAddress', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'customerPrincipalCity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'customerPrincipalSate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'daysOf', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allTracking($criteria);

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