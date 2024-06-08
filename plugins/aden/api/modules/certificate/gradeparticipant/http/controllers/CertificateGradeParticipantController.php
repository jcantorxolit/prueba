<?php
/**
 * User: DAB
 * Date: 25/09/2018
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Certificate\GradeParticipant\Http\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\Certificate\GradeParticipant\CertificateGradeParticipantRepository;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Wgroup\Traits\UserSecurity;

class CertificateGradeParticipantController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CertificateGradeParticipantRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'id', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'certificateGradeId', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'customerId', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documenttype', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'identificationnumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastname', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workcenter', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'amount', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'channel', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'countryOriginId', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'countryResidenceId', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isapproved', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'hascertificate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'countdownloads', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'validatecodecertificate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'certificatecreatedat', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'generatedby', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdby', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'updatedby', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'createdAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'updatedAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isdeleted', "operator" => 'like', "value" => $criteria->search),
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

    public function indexSearch()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'customer', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'grade', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'certificateCreatedAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'certificateExpirationAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'origin', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allSearch($criteria);

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

    public function indexExpiration()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customer', "operator" => 'eq'),
                array("field" => 'year', "operator" => 'eq'),
                array("field" => 'month', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'lastName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'customer', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'grade', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'certificateCreatedAt', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'certificateExpirationAt', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allExpiration($criteria);

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
