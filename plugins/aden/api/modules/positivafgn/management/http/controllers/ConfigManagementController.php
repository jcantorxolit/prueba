<?php

namespace AdeN\Api\Modules\PositivaFgn\Management\Http\Controllers;

use AdeN\Api\Modules\PositivaFgn\Management\ConfigManagementRepository;
use AdeN\Api\Modules\PositivaFgn\Management\Relations\ComplianceLogModel;
use DB;
use Excel;
use Exception;
use Log;
use Request;
use Response;
use Session;
use Validator;

use Wgroup\Traits\UserSecurity;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;


class ConfigManagementController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new ConfigManagementRepository();
        $this->request = app('Input');
        $this->run();
    }


    public function filterAssignment()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $mandatoryFilters = [
                array("field" => 'regionalVal', "operator" => 'eq'),
                array("field" => 'sectionalVal', "operator" => 'eq'),
                array("field" => 'strategyVal', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'axis', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'action', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activityCodeFgn', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'strategy', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activityCode', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activityGestpos', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'task', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'regional', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'sectional', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goalCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'pendingCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'assignedCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goalCompliance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'pendingCompliance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'assignedCompliance', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->filterAssignment($criteria);

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

    public function activitiesProgrammingExecution()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $mandatoryFilters = [
                array("field" => 'sectionalVal', "operator" => 'eq'),
                array("field" => 'periodVal', "operator" => 'eq'),
                array("field" => 'axisVal', "operator" => 'eq'),
                array("field" => 'consultantVal', "operator" => 'eq'),
                array("field" => 'configVal', "operator" => 'eq'),
                array("field" => 'action', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $sectionalVal = CriteriaHelper::getMandatoryFilter($criteria, 'sectionalVal');
            $periodVal = CriteriaHelper::getMandatoryFilter($criteria, 'periodVal');
            $axisVal = CriteriaHelper::getMandatoryFilter($criteria, 'axisVal');
            $consultantVal = CriteriaHelper::getMandatoryFilter($criteria, 'consultantVal');
            $configVal = CriteriaHelper::getMandatoryFilter($criteria, 'configVal');
            $action = CriteriaHelper::getMandatoryFilter($criteria, 'action');

            $criteria = CriteriaHelper::parse($content, []);
            $defaultFilters = [
                array("field" => 'action', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'strategy', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'modality', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activityCode', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activityGestpos', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'task', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goalCompliance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'assignmentCompliance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'percentageCompliance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goalCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'assignmentCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'programPercentCompliance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goalCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'programCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'programPercentCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'executedCompliance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'executedPercentCompliance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'executedCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'executedPercentCoverage', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->activitiesProgrammingExecution($criteria, $sectionalVal, $periodVal, $configVal, $axisVal, $consultantVal, $action);

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
            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function show()
    {
        $id = $this->request->get("id", "");
        $period = $this->request->get("period", "");
        try {
            $result = $this->repository->parseModelWithRelations($id, $period);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function showPoblation()
    {
        $indicatorId = $this->request->get("indicatorId", "");
        $date = $this->request->get("date", "");
        try {
            $result = $this->repository->parseModelWithRelationsPoblation($indicatorId, $date);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function showPoblationBase()
    {
        $indicatorId = $this->request->get("indicatorId", "");
        $action = $this->request->get("action", "");

        try {
            $result = $this->repository->getPoblationBase($indicatorId, $action);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function config()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->config($entity);
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
            $result = $this->repository->delete($id);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function complianceLogs()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {
            $mandatoryFilters = [
                array("field" => 'indicatorId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'date', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'programmed', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'executed', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'hour_programmed', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'hour_executed', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'satisfactionIndicator45', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'satisfactionIndicator123', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observation', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->getComplianceLogs($criteria);

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


    public function showComplianceLogs()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);

            $model = ComplianceLogModel::findOrFail($entity->id);
            $result = $this->repository->parseModelWithRelationsComplianceLogs($model);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function getTotalsComplianceLogs()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->getTotalsComplianceLogs($entity->id);
            $this->response->setResult($result);

        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function storeComplianceLogs()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->insertOrUpdateComplianceLogs($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function deleteComplianceLogs()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->deleteComplianceLogs($id);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }



    public function populationIndex()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {
            $mandatoryFilters = [
                array("field" => 'indicatorId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'date', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activityState', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->getPopulationAll($criteria);

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


    public function storePopulation()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->insertOrUpdatePopulation($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function deletePopulation()
    {
        $indicatorId = $this->request->get("indicatorId", "");
        $date = $this->request->get("date", "");

        try {
            $result = $this->repository->populationDelete($indicatorId, $date);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function getPoblationTotals()
    {
        try {
            $content = $this->request->get("data", "");
            $entity = HttpHelper::parse($content, true);

            $result = $this->repository->getPoblationTotals($entity->id);
            $this->response->setResult($result);

        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

}
