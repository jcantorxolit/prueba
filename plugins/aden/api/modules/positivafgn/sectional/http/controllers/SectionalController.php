<?php

namespace AdeN\Api\Modules\PositivaFgn\Sectional\Http\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\PositivaFgn\Sectional\SectionalRepository;
use Exception;
use Log;
use Request;
use Response;
use Wgroup\Traits\UserSecurity;

class SectionalController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new SectionalRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {
            $mandatoryFilters = [
                array("field" => 'sectionalId', "operator" => 'eq'),
            ];
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $criteria = CriteriaHelper::parse($content, []);
            $defaultFilters = [
                ["field" => 'regional', "operator" => 'like', "value" => $criteria->search],
                ["field" => 'nit', "operator" => 'like', "value" => $criteria->search],
                ["field" => 'name', "operator" => 'like', "value" => $criteria->search],
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
    
    /*List sectionals for professional*/
    public function indexSectionalXProfessional()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {
            $mandatoryFilters = [
                array("field" => 'sectionalId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'full_name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'number', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
            ];
            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allSectionalXProfessional($criteria);

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

    /*list professional*/
    public function indexProfessional()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try
        {
            $criteria = CriteriaHelper::parse($content, []);
            $defaultFilters = [
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'job', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isActive', "operator" => 'like', "value" => $criteria->search),
            ];
            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $otherFilters = [
                array("field" => 'sectionalId', "operator" => 'eq'),
            ];
            $filters = CriteriaHelper::parse($content, $otherFilters);

            $result = $this->repository->allProfessional($criteria, $filters);

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
        try {
            $content = $this->request->get("data", "");
            $id = HttpHelper::parse($content, true)->id;

            $model = $this->repository->find($id);
            $result = $this->repository->parseModelWithRelations($model);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
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
}
