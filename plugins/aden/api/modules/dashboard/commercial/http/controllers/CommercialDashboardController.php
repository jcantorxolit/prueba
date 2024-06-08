<?php

namespace AdeN\Api\Modules\Dashboard\Commercial\Http\Controllers;

use AdeN\Api\Helpers\CriteriaHelper;
use Log;
use Excel;
use Request;
use Response;

use Wgroup\Traits\UserSecurity;
use AdeN\Api\Classes\BaseController;
use AdeN\Api\Modules\Dashboard\Commercial\CommercialDashboardRepository;use DB;

class CommercialDashboardController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new CommercialDashboardRepository();
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
                array("field" => 'license', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'customer', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'license', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'finishDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'agent', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'value', "operator" => 'like', "value" => $criteria->search),
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

}