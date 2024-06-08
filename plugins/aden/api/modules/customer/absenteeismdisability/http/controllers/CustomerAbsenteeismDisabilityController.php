<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 25/11/2017
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\AbsenteeismDisability\Http\Controllers;

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

use AdeN\Api\Modules\Customer\AbsenteeismDisability\CustomerAbsenteeismDisabilityRepository;

class CustomerAbsenteeismDisabilityController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerAbsenteeismDisabilityRepository();
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
                //For Productivity Module
                array("field" => 'causeValue', "operator" => 'eq'),
                array("field" => 'typeValue', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
                array("field" => 'month', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'firstName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workplace', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'contractType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'typeText', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'category', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'causeItem', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'start', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'end', "operator" => 'like', "value" => $criteria->search)
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

    public function indexRelated()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'causeValue', "operator" => 'eq'),
                array("field" => 'typeValue', "operator" => 'eq'),
                array("field" => 'customerEmployeeId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'causeItem', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'start', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'end', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allRelated($criteria);

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

    public function indexDisabilityGeneral()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'cause', "operator" => 'eq'),
                array("field" => 'workplaceId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'year', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Jan', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Feb', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Mar', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Apr', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'May', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Jun', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Jul', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Aug', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Sep', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Oct', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Nov', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'Dec', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allDisabilityGeneral($criteria);

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

    public function indexDiagnosticAnalysis()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'category', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'start', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'end', "operator" => 'like', "value" => $criteria->search),
                //TO DO: Modificar en el repositorio para que tenga encuenta funciones de agrupamiento
                //array("field" => 'records', "operator" => 'like', "value" => $criteria->search),
                //array("field" => 'days', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allDiagnosticAnalysis($criteria);

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

    public function indexDiagnosticSummary()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'period', "operator" => 'like'),
                array("field" => 'cause', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'cause', "operator" => 'like', "value" => $criteria->search),
                // TO DO : Ordenar por cantidad
                array("field" => 'quantity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'period', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allDiagnosticSummary($criteria);

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

    public function indexPersonAnalysis()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'category', "operator" => 'eq'),
                array("field" => 'diagnosticId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'employee', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'start', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'end', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'origin', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'numberDays', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'acumulateDays', "operator" => 'like', "value" => $criteria->search),

            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allPersonAnalysis($criteria);

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

    public function indexDaysAnalysis()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'category', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'item', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),
                // array("field" => 'records', "operator" => 'like', "value" => $criteria->search),
                // array("field" => 'days', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allDaysAnalysis($criteria);

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

    public function update()
    {
        $content = $this->request->get("data", "");;

        try {
            $entity = HttpHelper::parse($content, true);
            $result = [];//$this->repository->insertOrUpdateInfoDetail($entity);
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

    public function showWithFormat()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->parseModelWithFormatRelations($this->repository->findOne($id));
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

    public function exportGeneral()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        $customerId = $this->request->get("customerId", "");
        $cause = $this->request->get("cause", "");

        try {

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerId', "operator" => 'eq', "value" => $customerId),
                array("field" => 'cause', "operator" => 'eq', "value" => $cause)
            ]);

            $this->repository->exportExcelDisabilityGeneral($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
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
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportPersonAnalysis()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerId', "operator" => 'eq', "value" => $customerId),
                array("field" => 'category', "operator" => 'eq', "value" => "Incapacidad")
            ]);

            $this->repository->exportExcelDisabilityPersonAnalysis($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function downloadTemplate()
    {
        try {
            return $this->repository->getTemplateFile();
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }
    }
}
