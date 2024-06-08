<?php

namespace AdeN\Api\Modules\Customer\Licenses\Http\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;use Request;
use Response;
use Exception;

use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Classes\BaseController;

use AdeN\Api\Modules\Customer\Licenses\LicenseRepository;

use Wgroup\Traits\UserSecurity;

class LicenseController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new LicenseRepository();
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
                array("field" => 'license', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'agent', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'value', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'state', "operator" => 'like', "value" => $criteria->search),
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
        $content = $this->request->get('data');
        $entity = HttpHelper::parse($content, true);

        try {
            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function finish()
    {
        $content = $this->request->get('data');
        $entity = HttpHelper::parse($content, true);

        try {
            $result = $this->repository->finish($entity);
            $this->response->setResult($result);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function show()
    {
        try {
            $content = $this->request->get('data');
            $id = HttpHelper::parse($content, true)->id;

            $model = $this->repository->find($id);
            if (empty($model)) {
                throw new ModelNotFoundException();
            }

            $result = $this->repository->parseModelWithRelations($model);
            $this->response->setResult($result);

        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function currentLicense()
    {
        $content = $this->request->get('data');
        $entity = HttpHelper::parse($content, true);

        try {
            $result = $this->repository->getCurrentLicense($entity->customerId);
            $this->response->setResult($result);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function logs()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'licenseId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'date', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'field', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'beforeValue', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'afterValue', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'user', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'reason', "operator" => 'like', "value" => $criteria->search),
            ];
            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->getLogs($criteria);

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



    public function validateLicense() {
        try {
            $content = $this->request->get("data");
            $data = HttpHelper::parse($content, true);

            $result = $this->repository->validateLicense($data->customerId);
            $this->response->setResult($result);

        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

}
