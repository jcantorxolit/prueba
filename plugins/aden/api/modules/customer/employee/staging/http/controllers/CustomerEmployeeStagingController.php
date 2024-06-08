<?php

namespace AdeN\Api\Modules\Customer\Employee\Staging\Http\Controllers;

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

use AdeN\Api\Modules\Customer\Employee\Staging\CustomerEmployeeStagingRepository;

class CustomerEmployeeStagingController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerEmployeeStagingRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customer_id', "operator" => 'eq'),
                array("field" => 'session_id', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'index', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'expeditionPlace', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'expeditionDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'birthdate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'gender', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'firstName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'contractType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'profession', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'occupation', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'job', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workPlace', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'salary', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'eps', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'afp', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'arl', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'country_id', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'state_id', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'city_id', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'rh', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'riskLevel', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'neighborhood', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observation', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'mobil', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'address', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'telephone', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'email', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'active', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isAuthorized', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isValid', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workShift', "operator" => 'like', "value" => $criteria->search),
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

}
