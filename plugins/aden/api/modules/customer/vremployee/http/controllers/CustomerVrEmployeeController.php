<?php
/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\VrEmployee\Http\Controllers;

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
use AdeN\Api\Helpers\KendoCriteriaHelper;
use AdeN\Api\Modules\Customer\VrEmployee\CustomerVrEmployeeRepository;

class CustomerVrEmployeeController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerVrEmployeeRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];

            //$criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'registrationDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'average', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isActive', "operator" => 'like', "value" => $criteria->search),
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

    public function indexExperienceDetail()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'customerVrEmployeeId', "operator" => 'eq')
            ];

            //$criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [];
            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->allExperienceDetail($criteria);

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

    public function indexStaging()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'sessionId', "operator" => 'eq'),
                array("field" => 'isValid', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'index', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'registrationDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'experience', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'experienceScene', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'indicator', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'value', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'justification', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observationType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observationValue', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'errorValidation', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);


            $result = $this->repository->allStaging($criteria);

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
        $content = $this->request->get("data", "");
        try {
            $entity = HttpHelper::parse($content, true);
            if (!$this->repository->canInsert($entity)) {
                throw new \Exception('No es posible adicionar la información, ya existe un registro para esta persona.');
            }

            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function cancel()
    {
        $id = $this->request->get("id", "");
        try {
            $this->repository->cancel($id);
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

    public function export()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");
        try
        {
            $content = HttpHelper::parse($content, true);
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'registrationDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'average', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isActive', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
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

    public function exportIndicators()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");
        try
        {
            $data = HttpHelper::parse($content, true);
            $this->repository->exportExcelIndicators($data);
        }
         catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function exportCertificate()
    {
        set_time_limit(0);

        $content = $this->request->get("data", "");

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'period', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true), $mandatoryFilters);

            $data = $this->repository->exportCertificate($criteria);

            return Response::json($data, 200);

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

    public function downloadTemplateEmployee()
    {
        try {
            $this->repository->downloadTemplateEmployee();
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


    public function consolidate()
    {
        set_time_limit(0);
        $customerId = $this->request->get("customerId", "");
        try {
            $this->repository->consolidate($customerId);
            $this->response->setResult(1);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function showCert()
    {
        set_time_limit(0);
        $customerEmployeeVrId = $this->request->get("id", "");
        try {
            $entity = $this->repository->find($customerEmployeeVrId);
            dd($entity);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function generateReportPdf()
    {

        set_time_limit(0);
        $content = $this->request->get("data", "");
        try
        {
            $data = HttpHelper::parse($content, true);
            return $this->repository->generateReportPdf($data);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }


}
