<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Jobcondition\Http\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\Customer\JobConditions\Jobcondition\JobConditionRepository;
use AdeN\Api\Modules\Customer\JobConditions\Jobcondition\JobConditionService;
use Exception;
use Request;
use Response;
use Wgroup\Traits\UserSecurity;

class JobConditionController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new JobConditionRepository();
        $this->service = new JobConditionService;
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
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'date', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'state', "operator" => 'like', "value" => $criteria->search),
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

    public function downloadTemplate()
    {
        try {
            $customerId = $this->request->get("customerId", "");

            $result = $this->repository->downloadTemplate($customerId);
            $this->response->setResult($result);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function config()
    {
        try {
            $content = $this->request->get("data", "");
            $entity = HttpHelper::parse($content, true);

            $result = $this->repository->config($entity);
            $this->response->setResult($result);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }
}
