<?php

/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\Covid\DailyPersonNear\Http\Controllers;

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

use AdeN\Api\Modules\Customer\Covid\DailyPersonNear\CustomerCovidDailyPersonNearRepository;

class CustomerCovidDailyPersonNearController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerCovidDailyPersonNearRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerCovidId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [                
                array("field" => 'manacleId', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'distance', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workplace', "operator" => 'like', "value" => $criteria->search)    
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

    public function export()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");
        try {
            $mandatoryFilters = [
                array("field" => 'customerCoviId', "operator" => 'eq')
            ];

            $content = HttpHelper::parse($content, true);
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);     
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


}
