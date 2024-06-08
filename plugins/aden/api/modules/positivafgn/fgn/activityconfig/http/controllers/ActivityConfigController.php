<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\Http\Controllers;

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

use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\ActivityConfigRepository;

class ActivityConfigController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new ActivityConfigRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $mandatoryFilters = [
                array("field" => 'fgnActivityId', "operator" => 'eq')
            ];
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'strategy', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'modality', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'executionType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'task', "operator" => 'like', "value" => $criteria->search),
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

    public function indexActivity()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $criteria = CriteriaHelper::parse($content, []);
            $defaultFilters = [
                array("field" => 'code', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'task', "operator" => 'like', "value" => $criteria->search),
            ];

            $entity = HttpHelper::parse($content, false);
            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->allActivityStrategy($criteria, $entity->strategy);

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

    public function indexSubtask()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $mandatoryFilters = [
                array("field" => 'activityConfigId', "operator" => 'eq')
            ];
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'subtask', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'executionType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'providesCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'providesCompliance', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->allSubtask($criteria);

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
    
    public function storeSubtask()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->insertOrUpdateSubtask($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function showSubtask()
    {
        $id = $this->request->get("id", "");
        $activityId = $this->request->get("activityId", "");
        try {
            $result = $this->repository->parseModelWithRelationsSubTask($id, $activityId);
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
