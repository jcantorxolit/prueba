<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 25/11/2017
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\ConfigActivityHazard\Http\Controllers;

use DB;
use Excel;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use Validator;
use Exception;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\KendoCriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use Wgroup\Traits\UserSecurity;

use AdeN\Api\Modules\Customer\ConfigActivityHazard\CustomerConfigActivityHazardRepository;

class CustomerConfigActivityHazardController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerConfigActivityHazardRepository();
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
                array("field" => 'workPlaceId', "operator" => 'eq'),
                array("field" => 'levelIR', "operator" => 'eq'),
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                /*array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'firstName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'contractType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'typeText', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'category', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'causeItem', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDateFormat', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDateFormat', "operator" => 'like', "value" => $criteria->search)*/
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

    public function indexIntervention()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'jobActivityHazardId', "operator" => 'eq'),
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                /*array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'firstName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'contractType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'typeText', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'category', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'causeItem', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDateFormat', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDateFormat', "operator" => 'like', "value" => $criteria->search)*/
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allIntervention($criteria);

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

    public function indexPriorization()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'workPlaceId', "operator" => 'eq'),
                array("field" => 'levelIR', "operator" => 'eq'),
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                /*array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'firstName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'contractType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'typeText', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'category', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'causeItem', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDateFormat', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDateFormat', "operator" => 'like', "value" => $criteria->search)*/
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allPriorization($criteria);

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

    public function indexHistorical()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'jobActivityHazardId', "operator" => 'eq'),
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                /*array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'firstName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'contractType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'typeText', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'category', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'causeItem', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDateFormat', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDateFormat', "operator" => 'like', "value" => $criteria->search)*/
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allHistorical($criteria);

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

    public function indexHistoricalReason()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'jobActivityHazardId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'reason', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'reasonObservation', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdBy', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allHistoricalReason($criteria);

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

    public function indexCharacterization()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'workplaceId', "operator" => 'eq'),
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allCharacterization($criteria);

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

    public function indexCharacterizationDetail()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'classificationId', "operator" => 'eq'),
                array("field" => 'workplaceId', "operator" => 'eq'),
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allCharacterizationDetail($criteria);

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

    public function update()
    {
        $content = $this->request->get("data", "");;

        try {
            $entity = HttpHelper::parse($content, true);
            $result = [];//$this->repository->insertOrUpdateInfoDetail($entity);
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

    public function upload()
    {
        $id = $this->request->get("id", "0");
        try {

            $allFiles = Input::file();

            $model = $this->repository->find($id);

            foreach ($allFiles as $file) {
                $this->repository->checkUploadPostBack($file, $model);
            }
            $model = $this->repository->find($id);
            $this->response->setResult($model);
        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function download()
    {
        $id = $this->request->get("id", "0");
        try {

            $model = $this->repository->find($id);

            $file = $model->document->getDiskPath();

            $headers = $this->repository->getDownloadHeaders($model->document);

            //return Response::download($file, $model->document->file_name, $headers);\n\t
            return $model->document->download();

        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }
    }

    public function export()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerId', "operator" => 'eq', "value" => $customerId)
            ]);

            $this->repository->exportExcel($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportPriorization()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerId', "operator" => 'eq', "value" => $customerId)
            ]);

            $this->repository->exportPriorizationExcel($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportHistorical()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerId', "operator" => 'eq', "value" => $customerId)
            ]);

            $this->repository->exportHistoricalExcel($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportCharacterization()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerId', "operator" => 'eq', "value" => $customerId)
            ]);

            $this->repository->exportCharacterizationExcel($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }
}
