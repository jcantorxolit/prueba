<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\Http\Controllers;

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

use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ActivityConfigSectionalRepository;

class ActivityConfigSectionalController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new ActivityConfigSectionalRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $mandatoryFilters = [
                array("field" => 'fgnActivityId', "operator" => 'eq'),
            ];
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'regional', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'sectional', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goalCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goalCompliance', "operator" => 'like', "value" => $criteria->search),
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
            $result = $this->repository->parseModelWithRelations($id);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function showClear()
    {
        $id = $this->request->get("id", "");
        try {
            $result = $this->repository->parseModelWithRelations($id, true);
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

       // Function for recalculated indicator compliance and coverage
       public function recalculated()
       {
           try {
               $result = $this->repository->recalculated();
               $this->response->setResult($result);
           } catch (\Exception $ex) {
               $this->response->setStatuscode(500);
               $this->response->setMessage($ex->getMessage());
           }
           return Response::json($this->response, $this->response->getStatuscode());
       }

       // Function for recalculated indicator compliance and coverage
       public function heredeIndicators()
       {
           try {
               $result = $this->repository->heredeIndicators();
               $this->response->setResult($result);
           } catch (\Exception $ex) {
               $this->response->setStatuscode(500);
               $this->response->setMessage($ex->getMessage());
           }
           return Response::json($this->response, $this->response->getStatuscode());
       }


}
