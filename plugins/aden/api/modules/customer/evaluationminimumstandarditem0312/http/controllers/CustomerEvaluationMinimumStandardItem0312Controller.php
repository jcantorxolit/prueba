<?php
/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem0312\Http\Controllers;

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

use AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem0312\CustomerEvaluationMinimumStandardItem0312Repository;
use AdeN\Api\Helpers\KendoCriteriaHelper;

class CustomerEvaluationMinimumStandardItem0312Controller extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerEvaluationMinimumStandardItem0312Repository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerEvaluationMinimumStandardId', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq'),                      
                array("field" => 'cycleId', "operator" => 'eq'),                      
                array("field" => 'parentId', "operator" => 'eq'),                      
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [                
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
            ];
                        
            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $customerEvaluationMinimumStandardIdField = CriteriaHelper::getMandatoryFilter($criteria, "customerEvaluationMinimumStandardId");

            $entity = $customerEvaluationMinimumStandardIdField ? $this->repository->findMinimumStandard($customerEvaluationMinimumStandardIdField->value) : null;            

            if ($entity == null || $entity->status == 'A') {
                $result = $this->repository->all($criteria);
            } else {                
                $result = $this->repository->allClosed($criteria);
            }

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["total"]);
            $this->response->setRecordsFiltered($result["total"]);            
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexQuestion()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerEvaluationMinimumStandardId', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq'),             
                array("field" => 'cycleId', "operator" => 'eq'),
                array("field" => 'minimumStandardId', "operator" => 'eq'),                
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);         

            $defaultFilters = [
                array("field" => 'comment', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdBy', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdAt', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $customerEvaluationMinimumStandardIdField = CriteriaHelper::getMandatoryFilter($criteria, "customerEvaluationMinimumStandardId");

            $entity = $customerEvaluationMinimumStandardIdField ? $this->repository->findMinimumStandard($customerEvaluationMinimumStandardIdField->value) : null;            

            if ($entity == null || $entity->status == 'A') {
                $result = $this->repository->allQuestion($criteria);
            } else {                
                $result = $this->repository->allQuestionClosed($criteria);
            }
            
            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["total"]);
            $this->response->setRecordsFiltered($result["total"]);   
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

            //return Response::download($file, $model->document->file_name, $headers);\n\t
            return $model->document->download();
        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }
    }

    public function export()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        
        try {

            $criteria = HttpHelper::parse($content, true);

            $this->repository->exportExcel($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }
}
