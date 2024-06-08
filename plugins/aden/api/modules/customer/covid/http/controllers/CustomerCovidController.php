<?php

/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\Covid\Http\Controllers;

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
use Carbon\Carbon;

use AdeN\Api\Modules\Customer\Covid\CustomerCovidRepository;

class CustomerCovidController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerCovidRepository();
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
                array("field" => 'createdBy', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'personType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workplace', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'contractor', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastRiskLevelText', "operator" => 'like', "value" => $criteria->search)
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


    public function indexIndicator()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'riskLevel', "operator" => 'eq'),
                array("field" => 'day', "operator" => 'eq'),
                array("field" => 'period', "operator" => 'eq'),
                array("field" => 'workplaceId', "operator" => 'eq'),
                array("field" => 'contractorId', "operator" => 'eq'),
                array("field" => 'isExternal', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = $this->defaultFilter($criteria);
            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->allIndicator($criteria);

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

    function defaultFilter($criteria) {
        return [
            array("field" => 'createdAt', "operator" => 'like', "value" => $criteria->search),
            array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
            array("field" => 'questions', "operator" => 'like', "value" => $criteria->search),
            array("field" => 'riskLevelText', "operator" => 'like', "value" => $criteria->search),
        ];
    }


    public function store()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            if($entity->id == 0) {
                $entity->registrationDate = Carbon::parse($entity->registrationDate)->subHours(5);
            }
            if (!$this->repository->canInsert($entity)) {
                throw new \Exception('No es posible adicionar la información, ya existe un registro para esta persona.');
            }

            $result = $this->repository->insertOrUpdate($entity);
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

    public function show()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->parseModelWithRelations($this->repository->find($id));
            $this->response->setResult($result);
        } catch (Exception $ex) {
            Log::error($ex);
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

    public function downloadTemplate()
    {
        try {

            $customerId = $this->request->get("customerId", "0");

            $this->repository->downloadTemplate($customerId);

            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());
            $this->response->setResult(0);
            // error on server
            $this->response->setStatuscode(404);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function export()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");
        try {
            $mandatoryFilters = [
                array("field" => 'customerCovidId', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $content = HttpHelper::parse($content, true);
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $this->repository->exportExcel($criteria);
        }
         catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function exportEmployee()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");
        try {

            $content = HttpHelper::parse($content, true);
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = $this->defaultFilter($criteria);
            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $this->repository->exportExcelEmployee($criteria);
        }
         catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function exportExternal()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");
        try {

            $content = HttpHelper::parse($content, true);
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = $this->defaultFilter($criteria);
            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $this->repository->exportExcelExternal($criteria);
        }
         catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

}
