<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Indicator\Http\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\Customer\JobConditions\Indicator\IndicatorRepository;
use Exception;
use Request;
use Response;
use Wgroup\Traits\UserSecurity;

class IndicatorController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new IndicatorRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function getDatesEvaluationsByEmployees()
    {
        try {
            $content = $this->request->get("data", "");
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->getDatesEvaluationsByEmployees($entity->customerId, $entity->employeeId);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function getIndicatorByEvaluation()
    {
        try {
            $content = $this->request->get("data", "");
            $entity = HttpHelper::parse($content, true);

            $result = $this->repository->getIndicatorByEvaluation($entity->evaluationId);
            $this->response->setResult($result);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    // Función que genera el excel
    public function dowloadExcel()
    {
        $content = $this->request->get("data", "");
        try {
            $criteria = HttpHelper::parse($content, true);
            $result = $this->repository->getInfoExportExcel($criteria);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function getYearEvaluations()
    {
        try {
            $content = $this->request->get("data", "");
            $entity = HttpHelper::parse($content, true);

            $result = $this->repository->getYearEvaluations($entity->customerId);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function getInterventions()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
                array("field" => 'location', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'classification', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'question', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->getInterventions($criteria);

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


    public function getLevelRisksByMonthsList()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $criteria = CriteriaHelper::parse($content, []);

            $defaultFilters = [
                array("field" => 'indicator', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'JAN', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'FEB', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'MAR', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'APR', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'MAY', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'JUN', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'JUL', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'AUG', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'SEP', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'OCT', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'NOV', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'DEC', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

             $otherFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
                array("field" => 'location', "operator" => 'eq'),
            ];
            $filters = CriteriaHelper::parse($content, $otherFilters);

            $result = $this->repository->getLevelRisksByMonthsList($criteria, $filters);

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


    public function getInterventionsByResponsibles()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
                array("field" => 'location', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'email', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->getInterventionsByResponsibles($criteria);

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


    public function getInterventionsByQuestionsHistorical()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'classificationId', "operator" => 'eq'),
                array("field" => 'questionId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'employee', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'intervention', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'responsibleName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'date', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->getInterventionsByQuestionsHistorical($criteria);

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
