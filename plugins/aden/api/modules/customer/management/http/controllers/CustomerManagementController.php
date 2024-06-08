<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 25/11/2017
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\Management\Http\Controllers;

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
use AdeN\Api\Helpers\HttpHelper;
use Wgroup\Traits\UserSecurity;

use AdeN\Api\Modules\Customer\Management\CustomerManagementRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CustomerManagementController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerManagementRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'statusCode', "operator" => 'neq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [                
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workplace', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'economicSector', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'program', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdBy', "operator" => 'like', "value" => $criteria->search),                            
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),                            
                //array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),                            
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->all($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexSummary()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'statusCode', "operator" => 'neq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [                
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdBy', "operator" => 'like', "value" => $criteria->search),                            
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),                            
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allSummary($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    
    public function indexSummaryIndicator()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),                
                array("field" => 'year', "operator" => 'eq'),
                array("field" => 'programId', "operator" => 'eq'),
                array("field" => 'workplaceId', "operator" => 'in')                
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [                
                array("field" => 'program', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'abbreviation', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workplace', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'questions', "operator" => 'like', "value" => $criteria->search),                            
                array("field" => 'answers', "operator" => 'like', "value" => $criteria->search),                            
                array("field" => 'advance', "operator" => 'like', "value" => $criteria->search),                            
                array("field" => 'average', "operator" => 'like', "value" => $criteria->search),                            
                array("field" => 'total', "operator" => 'like', "value" => $criteria->search)                
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allSummaryIndicator($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexSummaryResponsible()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),                
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [                
                array("field" => 'workplace', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'responsible', "operator" => 'like', "value" => $criteria->search),                            
                array("field" => 'qty', "operator" => 'like', "value" => $criteria->search),                            
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allSummaryResponsible($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexAvailableEconomicSector()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'economicSectorId', "operator" => 'eq'),
                array("field" => 'workplaceId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [                
                array("field" => 'abbreviation', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'program', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allAvailableEconomicSector($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
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

            if (!$this->repository->canInsert($entity)) {
                throw new BadRequestHttpException("No es posible guardar el registro. Ya existe una configuración similiar.");                
            }

            $result = $this->repository->insertOrUpdate($entity);
            
            $this->response->setResult($result);
        } catch (BadRequestHttpException $ex) {
            Log::error($ex);
            $this->response->setStatuscode($ex->getStatusCode());
            $this->response->setMessage($ex->getMessage());
        } catch (\Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function update()
    {
        $content = $this->request->get("data", "");;

        try {
            $entity = HttpHelper::parse($content, true);
            $result = [];
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