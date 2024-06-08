<?php	
/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\Http\Controllers;

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

use AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\ExperienceAnswerRepository;

class ExperienceAnswerController extends BaseController
{
    use UserSecurity;
    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new ExperienceAnswerRepository();
        $this->request = app('Input');
        $this->run();
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

    public function getQuestion()
    {
        $content = $this->request->get("data", "");
        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->getQuestion($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function getAllExperiencesWithScenes()
    {
        $content = $this->request->get("data", "");
        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->getAllExperiencesWithScenes($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function getObservations()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerVrEmployeeId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'experience', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observationType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observation', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->observations($criteria);

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

    public function getCountObservations()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'selectedYear', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'experience', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observationType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'total', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->countObservations($criteria);

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

    public function getObservationsDetail()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'selectedYear', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'date', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'experience', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observationType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observation', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'firstName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->observationsDetail($criteria);

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


    public function export()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");
        try {
            $mandatoryFilters = [
                array("field" => 'selectedYear', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq'),
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