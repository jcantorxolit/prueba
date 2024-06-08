<?php
/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\Http\Controllers;

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

use AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\CustomerEvaluationMinimumStandard0312Repository;

class CustomerEvaluationMinimumStandard0312Controller extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerEvaluationMinimumStandard0312Repository();
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
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'unique', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);


            $result = $this->repository->all($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
            $this->response->setExtra($result["canCreate"]);
        } catch (Exception $ex) {
            Log::error($ex);
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
                array("field" => 'customerEvaluationMinimumStandardId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'items', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'checked', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'advance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'total', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $customerEvaluationMinimumStandardIdField = CriteriaHelper::getMandatoryFilter($criteria, "customerEvaluationMinimumStandardId");
            
            $currentEntity = $customerEvaluationMinimumStandardIdField ? $this->repository->find($customerEvaluationMinimumStandardIdField->value) : null;

            if ($currentEntity) {
                $this->repository->executeBulkOperations($currentEntity->id);
            }

            if ($currentEntity == null || $currentEntity->status == 'A') {
                $result = $this->repository->allSummary($criteria);
            } else {
                $result = $this->repository->allSummaryClosed($criteria);
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
            $customerEvaluationMinimumStandardId = $this->repository->getLastId($entity->customerId);
            $this->repository->executeBulkOperations($customerEvaluationMinimumStandardId);            
            $this->response->setResult(true);            
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function migrate()
    {
        $content = $this->request->get("data", "");;

        try {
            $criteria = HttpHelper::parse($content, true);               
            $this->repository->migrateFrom($criteria);
            $this->response->setResult(true);            
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

    public function exportSummary()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        
        try {

            $criteria = HttpHelper::parse($content, true);

            $this->repository->exportSummaryExcel($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportPdf()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        
        try {
            $criteria = HttpHelper::parse($content, true);

            return $this->repository->exportPdf($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }
}
