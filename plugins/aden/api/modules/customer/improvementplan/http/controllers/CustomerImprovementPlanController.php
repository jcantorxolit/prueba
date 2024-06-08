<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 25/11/2017
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\ImprovementPlan\Http\Controllers;

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

use AdeN\Api\Modules\Customer\ImprovementPlan\CustomerImprovementPlanRepository;

class CustomerImprovementPlanController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerImprovementPlanRepository();
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
                array("field" => 'entityId', "operator" => 'eq'),                
                array("field" => 'entityName', "operator" => 'eq'),                
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [
                array("field" => 'origin', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'classification', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isRequireAnalysis', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'responsibleName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),             
                array("field" => 'hasActionPlan', "operator" => 'like', "value" => $criteria->search),             
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),             
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

    public function indexEntity()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),                
                array("field" => 'entityId', "operator" => 'eq'),                
                array("field" => 'entityName', "operator" => 'eq'),                
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [
                array("field" => 'origin', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'classification', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),                
                array("field" => 'responsibleName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),             
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allEntity($criteria);

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

    public function indexMatrix()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),                
                array("field" => 'entityId', "operator" => 'eq'),                
                array("field" => 'entityName', "operator" => 'eq'),                
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [
                /*array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'firstName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'contractType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'typeText', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'category', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'causeItem', "operator" => 'like', "value" => $criteria->search),             
                array("field" => 'startDateFormat', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDateFormat', "operator" => 'like', "value" => $criteria->search)*/
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allMatrix($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["total"]);
            $this->response->setRecordsFiltered($result["total"]);
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
            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
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
            $result = $this->repository->updateStatus($entity);
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

    public function exportExcel()
    {
        set_time_limit(0);

        $content = $this->request->get("data", "");

        try {
           
            $criteria = HttpHelper::parse($content, true);

            $this->repository->exportExcel($criteria);
           
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }
}