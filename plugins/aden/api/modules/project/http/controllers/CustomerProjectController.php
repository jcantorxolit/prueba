<?php

/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Project\Http\Controllers;

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
use AdeN\Api\Helpers\KendoCriteriaHelper;
use AdeN\Api\Modules\Project\CustomerProjectRepository;

class CustomerProjectController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerProjectRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'id', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'customerId', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'serviceorder', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'defaultskill', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'estimatedhours', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'deliverydate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isrecurrent', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isbilled', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'invoicenumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'previousId', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'item', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdby', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'updatedby', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'updatedAt', "operator" => 'like', "value" => $criteria->search),
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

    public function indexActivities()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'type', "operator" => 'eq'),
                array("field" => 'projectType', "operator" => 'eq'),
                array("field" => 'administrator', "operator" => 'eq'),
                array("field" => 'isBilled', "operator" => 'eq'),
                array("field" => 'odes', "operator" => 'eq'),
                array("field" => 'agentId', "operator" => 'eq'),
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'month', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'customerName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'serviceOrder', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'agentName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'administrator', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'assignedHours', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'scheduledHours', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'runningHours', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'statusText', "operator" => 'like', "value" => $criteria->search)                
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $type = CriteriaHelper::getMandatoryFilter($criteria, "type");
            $agentId = CriteriaHelper::getMandatoryFilter($criteria, "agentId");
            $customerId = CriteriaHelper::getMandatoryFilter($criteria, "customerId");
    
            if ($type->value == 'agent') {
                $this->run();
                $agentId->value = !empty($agentId->value) ? $agentId->value : $this->isUserRelatedAgent();
            } else if ($type->value == 'customerAdmin' || $type->value == 'customerUser') {
                $this->run();
                $customerId->value = $customerId->value ? $customerId->value : $this->getUserRelatedCustomer();
            }
            
            $result = $this->repository->allActivities($criteria);

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
}
