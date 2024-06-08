<?php	

namespace AdeN\Api\Modules\Customer\VrEmployee\Satisfactionindicators\Http\Controllers;

use AdeN\Api\Helpers\CriteriaHelper;use DB;
use Log;
use Excel;
use Request;
use Response;

use Wgroup\Traits\UserSecurity;
use AdeN\Api\Classes\BaseController;

use AdeN\Api\Modules\Customer\VrEmployee\Satisfactionindicators\SatisfactionIndicatorRepository;

class SatisfactionIndicatorController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new SatisfactionIndicatorRepository();
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
                array("field" => 'year', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'date', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'participants', "operator" => 'like', "value" => $criteria->search),
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


    public function downloadTemplate()
    {
        try {
            $customerId = $this->request->get("customerId");

            $result = $this->repository->downloadTemplate($customerId);
            $this->response->setResult($result);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }




    public function valuationList()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'date', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $criteria = CriteriaHelper::addFilters($criteria, []);

            $result = $this->repository->valuationList($criteria);

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