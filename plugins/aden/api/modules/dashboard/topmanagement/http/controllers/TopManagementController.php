<?php

namespace AdeN\Api\Modules\Dashboard\TopManagement\Http\Controllers;

use AdeN\Api\Helpers\CriteriaHelper;use DB;
use Log;
use Excel;
use Request;
use Response;

use Wgroup\Traits\UserSecurity;
use AdeN\Api\Classes\BaseController;

use AdeN\Api\Modules\Dashboard\TopManagement\TopManagementRepository;

class TopManagementController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new TopManagementRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function consolidate() {
         try {
            $this->repository->consolidate();

        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function index() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'startDate', "operator" => 'eq'),
                array("field" => 'endDate', "operator" => 'eq'),
                array("field" => 'levelCompliance', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'consultant', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'availability', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'assigned', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'executed', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'percentCompliance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'levelCompliance', "operator" => 'like', "value" => $criteria->search),
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



    public function calendar() {
         try {
             $request = Request::instance();
             $types = $request->types ?? [];
             $year = $request->year;

            $result = $this->repository->getCalendar($types, $year);
            $this->response->setData($result);

        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function getHistoricalCosts() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $criteria = CriteriaHelper::parse($content, []);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getHistoricalCosts($criteria);

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


    public function getCustomers() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $criteria = CriteriaHelper::parse($content, []);

            $defaultFilters = [
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'businessName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'classification', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->getCustomers($criteria);

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


    public function getAdministrators() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getAdministrators($criteria);

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


    public function getTotalSales() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'startDate', "operator" => 'eq'),
                array("field" => 'endDate', "operator" => 'eq'),
                array("field" => 'type', "operator" => 'eq'),
                array("field" => 'concept', "operator" => 'eq'),
                array("field" => 'classification', "operator" => 'eq'),
                array("field" => 'customer', "operator" => 'eq'),
                array("field" => 'administrator', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getTotalSales($criteria);

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



    public function getSalesByType() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'startDate', "operator" => 'eq'),
                array("field" => 'endDate', "operator" => 'eq'),
                array("field" => 'type', "operator" => 'eq'),
                array("field" => 'concept', "operator" => 'eq'),
                array("field" => 'classification', "operator" => 'eq'),
                array("field" => 'customer', "operator" => 'eq'),
                array("field" => 'administrator', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getSalesByType($criteria);

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


    public function getSalesByConcept() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'startDate', "operator" => 'eq'),
                array("field" => 'endDate', "operator" => 'eq'),
                array("field" => 'type', "operator" => 'eq'),
                array("field" => 'concept', "operator" => 'eq'),
                array("field" => 'classification', "operator" => 'eq'),
                array("field" => 'customer', "operator" => 'eq'),
                array("field" => 'administrator', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getSalesByConcept($criteria);

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


    public function getSalesByClassification() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'startDate', "operator" => 'eq'),
                array("field" => 'endDate', "operator" => 'eq'),
                array("field" => 'type', "operator" => 'eq'),
                array("field" => 'concept', "operator" => 'eq'),
                array("field" => 'classification', "operator" => 'eq'),
                array("field" => 'customer', "operator" => 'eq'),
                array("field" => 'administrator', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getSalesByClassification($criteria);

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


    public function getExperienciesByMonths() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'startDate', "operator" => 'eq'),
                array("field" => 'endDate', "operator" => 'eq'),
                array("field" => 'customer', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getExperienciesByMonths($criteria);

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


    public function amountBySatisfactionGrid() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'startDate', "operator" => 'eq'),
                array("field" => 'endDate', "operator" => 'eq'),
                array("field" => 'customer', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->amountBySatisfactionGrid($criteria);

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


    public function getRegisteredVsParticipants() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'startDate', "operator" => 'eq'),
                array("field" => 'endDate', "operator" => 'eq'),
                array("field" => 'customer', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getRegisteredVsParticipants($criteria);

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


    public function getPerformanceByConsultant() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'startDate', "operator" => 'eq'),
                array("field" => 'endDate', "operator" => 'eq'),
                array("field" => 'customer', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getPerformanceByConsultant($criteria);

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


    public function getProgrammedVsExecutedSales() {

        $request = Request::instance();
        $content = $request->getContent();

        try {
            $criteria = CriteriaHelper::parse($content, []);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->getProgrammedVsExecutedSales($criteria);

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

}
