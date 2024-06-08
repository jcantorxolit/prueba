<?php

namespace AdeN\Api\Modules\PositivaFgn\Indicator\Http\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\PositivaFgn\Indicator\IndicatorRepository;
use Exception;
use Log;
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

    /*Listado principal*/
    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {
            $criteria = CriteriaHelper::parse($content, []);
            $defaultFilters = [
                ["field" => 'title', "operator" => 'like', "value" => $criteria->search],
                ["field" => 'description', "operator" => 'like', "value" => $criteria->search],
                ["field" => 'isActive', "operator" => 'like', "value" => $criteria->search],
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
                array("field" => 'activity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'strategy', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activityCode', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activityGestpos', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'task', "operator" => 'like', "value" => $criteria->search),
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

    public function consolidated()
    {
        try {
            $this->repository->consolidate();

        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indicators()
    {
        $content = $this->request->get("data", "");
        try {
            $criteria = HttpHelper::parse($content, true);
            $result = $this->repository->getAllIndicatorsByActivity($criteria);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    /*
     *Actividades PTA
     */

    public function activitiesPTACompliance()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $data = HttpHelper::parse($content, false);
            $criteria = CriteriaHelper::parse($content, []);

            $filters = $data->customFilter;
            $result = $this->repository->getActivitiesPTACompliance($criteria, $filters);

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

    public function activitiesPTAComplianceDetails()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {
            $criteria = CriteriaHelper::parse($content, []);
            $defaultFilters = [
                array("field" => 'activity', "operator" => 'like', "value" => $criteria->search),
            ];

            $data = HttpHelper::parse($content, false);
            $axis = $data->axis;
            $filters = $data->customFilter;

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->getActivitiesPTAComplianceDetails($criteria, $filters, $axis);

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

    public function activitiesPTAComplianceAxis()
    {
        $content = $this->request->get("data", "");
        try {
            $criteria = HttpHelper::parse($content, true);
            $result = $this->repository->getActivitiesPTAComplianceAxis($criteria);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function activitiesPTAComplianceExport()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");

        try
        {
            $criteria = HttpHelper::parse($content, true);
            $this->repository->getActivitiesPTAComplianceExportExcel($criteria);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    /*
     *Actividades fallidas
     */

    public function getActivitiesFailedCompliance()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $data = HttpHelper::parse($content, false);
            $criteria = CriteriaHelper::parse($content, []);

            $filters = $data->customFilter;
            $result = $this->repository->getActivitiesFailedCompliance($criteria, $filters);

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

    public function activitiesFailedComplianceExport()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");

        try
        {
            $criteria = HttpHelper::parse($content, true);
            $this->repository->getActivitiesFailedComplianceExportExcel($criteria);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    /*
     *Actividades Consolidado
     */
    public function activitiesConsolidatedCompliance()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $data = HttpHelper::parse($content, false);
            $criteria = CriteriaHelper::parse($content, []);

            $filters = $data->customFilter;
            $result = $this->repository->activitiesConsolidatedCompliance($criteria, $filters);

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

    public function activitiesConsolidatedExport()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");

        try
        {
            $criteria = HttpHelper::parse($content, true);
            $this->repository->getActivitiesConsolidatedComplianceExcel($criteria);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    /*
     *Indicador Actividades por estrategia
     */

    public function activitiesStrategyCompliance()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {

            $data = HttpHelper::parse($content, false);
            $criteria = CriteriaHelper::parse($content, []);

            $filters = $data->customFilter;
            $result = $this->repository->getActivitiesByStrategies($criteria, $filters);

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

    public function activitiesByStrategyExport()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");

        try
        {
            $criteria = HttpHelper::parse($content, true);
            $this->repository->getActivitiesStrategiesComplianceExcel($criteria);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    /*
     *Actividades por asesor
     */

    public function getActivitiesConsultantCompliance()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $data = HttpHelper::parse($content, false);
            $criteria = CriteriaHelper::parse($content, []);

            $filters = $data->customFilter;
            $result = $this->repository->getActivitiesConsultantCompliance($criteria, $filters);

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

    public function activitiesConsultantComplianceExport()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");

        try
        {
            $criteria = HttpHelper::parse($content, true);
            $this->repository->getActivitiesConsultantComplianceExportExcel($criteria);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

}
