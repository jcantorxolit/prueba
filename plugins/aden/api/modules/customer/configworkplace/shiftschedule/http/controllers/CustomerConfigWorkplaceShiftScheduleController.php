<?php

/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\Http\Controllers;

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

use AdeN\Api\Modules\Customer\ConfigWorkplace\ShiftSchedule\CustomerConfigWorkplaceShiftScheduleRepository;
use Carbon\Carbon;

class CustomerConfigWorkplaceShiftScheduleController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerConfigWorkplaceShiftScheduleRepository();
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
                array("field" => 'customerWorkplaceId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'startDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'qtyEligibleEmployee', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'qtySuggestedShift', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'qtySelectedEmployee', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'qtyAvailableEmployee', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
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

            if (!$this->repository->canInsert($entity)) {
                throw new \Exception('No es posible adicionar la información, ya existe un turno para esas fechas.');
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

    public function updateStatus()
    {
        $id = $this->request->get("id", "");
        $isSendEmail = $this->request->get("isSendEmail", false);

        try {
            $this->repository->updateScheduledStatus($id, $isSendEmail);
            $this->response->setResult(1);
        } catch (\Exception $ex) {
            Log::error($ex);
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
            $this->repository->updateEmployeeQty($id);
            $result = $this->repository->parseModelWithRelations($this->repository->find($id));
            $this->response->setResult($result);
        } catch (Exception $ex) {
            \Log::error($ex);
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

    public function exportExcel()
    {
        set_time_limit(0);

        $content = $this->request->get("data", "");
        try {
            $criteria = HttpHelper::parse($content, true);

            $start = Carbon::now();
            $result = $this->repository->exportExcel($criteria);
            $end = Carbon::now();

            $result = array_merge($result, ['elapseTime' => $end->diffInSeconds($start), 'endTime' => $end->timestamp]);

            $this->response->setResult($result);
        } catch (\Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }
}
