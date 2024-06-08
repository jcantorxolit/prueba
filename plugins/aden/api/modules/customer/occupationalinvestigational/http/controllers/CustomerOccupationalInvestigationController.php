<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 25/11/2017
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\OccupationalInvestigationAl\Http\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\Customer\OccupationalInvestigationAl\CustomerOccupationalInvestigationRepository;
use Barryvdh\Snappy\Facades\SnappyPdf as SnappyPdf;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Wgroup\Traits\UserSecurity;

class CustomerOccupationalInvestigationController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerOccupationalInvestigationRepository();
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
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'id', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'accidentDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'accidentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'businessName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'job', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
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

    public function updateStatus()
    {
        $id = $this->request->get("id", "");

        try {
            $this->repository->updateStatus($id);
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
            $result = $this->repository->parseModelWithFormatRelations($this->repository->findOne($id));
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

            //return Response::download($file, $model->document->file_name, $headers);
            return $model->document->download();

        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }
    }

    public function exportPdf()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        $customerOccupationalInvestigationAlId = $this->request->get("id", "");

        try {
            $entity = $this->repository->find($customerOccupationalInvestigationAlId);

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerOccupationalInvestigationAlId', "operator" => 'eq', "value" => $customerOccupationalInvestigationAlId)
            ]);

            $criteria->customerId = $entity ? $entity->customer_id : 0;
            $criteria->customerEmployeeId = $entity ? $entity->customer_employee_id : 0;

            $defaultFilters = [];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            return $this->repository->exportPdf($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function streamPdf()
    {

        set_time_limit(0);

        $content = $this->request->get("data", "");
        $customerOccupationalInvestigationAlId = $this->request->get("id", "");

        try {
            $entity = $this->repository->find($customerOccupationalInvestigationAlId);

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerOccupationalInvestigationAlId', "operator" => 'eq', "value" => $customerOccupationalInvestigationAlId),
            ]);

            $criteria->customerId = $entity ? $entity->customer_id : 0;
            $criteria->customerEmployeeId = $entity ? $entity->customer_employee_id : 0;

            $defaultFilters = [];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            return $this->repository->streamPdf($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }
}
