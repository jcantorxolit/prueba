<?php

/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\Covid\Daily\Http\Controllers;

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
use Carbon\Carbon;

use AdeN\Api\Modules\Customer\Covid\Daily\CustomerCovidDailyRepository;

class CustomerCovidDailyController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerCovidDailyRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerCovidHeadId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'id', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'registrationDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'healthCondition', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'riskLevel', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'origin', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdBy', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $criteria->selectedMonth = $request->input("selectedMonth");
            $result = $this->repository->all($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        }
         catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    
    public function indexIndicator()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'riskLevel', "operator" => 'eq'),
                array("field" => 'day', "operator" => 'eq'),
                array("field" => 'period', "operator" => 'eq'),
                array("field" => 'workplaceId', "operator" => 'eq'),
                array("field" => 'contractorId', "operator" => 'eq'),
                array("field" => 'isExternal', "operator" => 'eq'),
            ];
            
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            
            $defaultFilters = [
                array("field" => 'createdAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'questions', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'riskLevelText', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->allIndicator($criteria);

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
            if($entity->id == 0) {
                $entity->registrationDate = Carbon::parse($entity->registrationDate)->subHours(5);
            }
            if (!$this->repository->canInsert($entity)) {
                throw new \Exception('No es posible adicionar la información, ya existe un registro para ese día.');
            }

            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            Log::error($ex);
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

    public function show()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->parseModelWithRelations($this->repository->find($id));
            $this->response->setResult($result);
        } catch (Exception $ex) {
            Log::error($ex);
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
                array("field" => 'customerCovidHeadId', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'period', "operator" => 'eq')
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
