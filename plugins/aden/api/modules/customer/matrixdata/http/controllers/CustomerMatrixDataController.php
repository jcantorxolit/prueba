<?php
/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\MatrixData\Http\Controllers;

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

use AdeN\Api\Modules\Customer\MatrixData\CustomerMatrixDataRepository;

class CustomerMatrixDataController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerMatrixDataRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerMatrixId', "operator" => 'eq'),     
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'id', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'project', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'activity', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'aspect', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'impact', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'environmentalImpactIn', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'environmentalImpactEx', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'environmentalImpactPr', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'environmentalImpactRe', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'environmentalImpactRv', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'environmentalImpactSe', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'environmentalImpactFr', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'nia', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'legalImpactE', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'legalImpactC', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'legalImpactCriterion', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'interestedPartAc', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'interestedPartGe', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'interestedPartCriterion', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'totalAspect', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'nature', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'emergencyConditionIn', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'emergencyConditionEx', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'emergencyConditionPr', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'emergencyConditionRe', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'emergencyConditionRv', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'emergencyConditionSe', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'emergencyConditionFr', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'emergencyNia', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'controlTypeE', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'controlTypeS', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'controlTypeCI', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'controlTypeCA', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'controlTypeSL', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'controlTypeEPP', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'associateProgram', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'registry', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'responsible', "operator" => 'like', "value" => $criteria->search),
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

            //return Response::download($file, $model->document->file_name, $headers);
            return $model->document->download();
        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }
    }
}
