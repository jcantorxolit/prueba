<?php

namespace AdeN\Api\Modules\Customer\Contributions\Http\Controllers;

use AdeN\Api\Modules\Customer\Contributions\ContributionRepository;

use Log;
use Request;
use Response;
use Session;
use Validator;
use Exception;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use Wgroup\Traits\UserSecurity;

class ContributionController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new ContributionRepository();
        $this->request = app('Input');

        $this->run();
    }


    public function getGeneralBalance()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'year', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'contributions', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'commissions', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'reinvesments', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'sales', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'balance', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->getGeneralBalance($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($request->get('draw'));

        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function getDetailBalance()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
                array("field" => 'period', "operator" => 'eq'),
                array("field" => 'type', "operator" => 'eq'),
                array("field" => 'activity', "operator" => 'eq'),
                array("field" => 'concept', "operator" => 'eq'),
                array("field" => 'classification', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'period', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'concept', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'classification', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'total', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->getDetailBalance($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($request->get('draw'));

        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function generateReportPdf()
    {

        set_time_limit(0);
        $content = $this->request->get("data", "");
        try
        {
            $data = HttpHelper::parse($content, true);
            return $this->repository->generateReportPdf($data);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }
}
