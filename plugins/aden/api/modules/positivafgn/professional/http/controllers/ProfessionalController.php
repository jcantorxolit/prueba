<?php

namespace AdeN\Api\Modules\PositivaFgn\Professional\Http\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\PositivaFgn\Professional\ProfessionalRepository;
use Exception;
use Log;
use Request;
use Response;
use Wgroup\Traits\UserSecurity;

class ProfessionalController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new ProfessionalRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $criteria = CriteriaHelper::parse($content, []);
            $defaultFilters = [
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'job', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'telphone', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'email', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isActive', "operator" => 'like', "value" => $criteria->search),
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
    /*List sectional for professional*/
    public function indexSectional()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'professionalId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'regional', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'sectional', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isActive', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->allSectional($criteria);

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

    public function show()
    {
        $id = $this->request->get("id", "");

        try {
            $professional = $this->repository->find($id);
            $result = $this->repository->parseModelWithRelations($professional);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function destroy()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->delete($id);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    /*-------------------------------------------------------- Sectional ------------------------------------------------------------------------*/
    /*Almacenar información de la seccional enlazada al profesional*/
    public function storeSectional()
    {
        $content = $this->request->get("data", "");
        try {
            $entity = HttpHelper::parse($content, true);
            $this->repository->canSave($entity);
            $result = $this->repository->insertOrUpdateSectional($entity);
            if ($entity->modulo === "seccionales") {
                $this->repository->insertOrUpdate($entity);
            }
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function destroySectional()
    {
        $id = $this->request->get("id", "");
        try {
            $result = $this->repository->deleteSectional($id);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

}
