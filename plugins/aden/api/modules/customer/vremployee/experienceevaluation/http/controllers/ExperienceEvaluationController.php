<?php


namespace AdeN\Api\Modules\Customer\VrEmployee\ExperienceEvaluation\Http\Controllers;

use DB;
use Excel;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;

use Wgroup\Traits\UserSecurity;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;

use AdeN\Api\Modules\Customer\VrEmployee\ExperienceEvaluation\ExperienceEvaluationRepository;

class ExperienceEvaluationController extends BaseController
{
    use UserSecurity;
    private $repository;

    public function __construct()
    {
        parent::__construct();
        $this->repository = new ExperienceEvaluationRepository();
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

    public function upload()
    {
        $id = $this->request->get("id", "0");
        try {

            $file = current(Input::file());
            $model = $this->repository->find($id);
            $this->repository->checkUploadPostBack($file, $model);
            $model = $this->repository->find($id);
            $this->repository->generateCert($model);
            $this->response->setResult($model);
        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function destroyCertificate()
    {
        try {
            $content = $this->request->get("data", "");
            $entity = HttpHelper::parse($content, true);

            $result = $this->repository->deleteCertificatesBySessionImport($entity->sessionId);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function generateMassiveCertificates()
    {
        set_time_limit(0);
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq')
            ];

            $criteria = CriteriaHelper::parse($entity, $mandatoryFilters);

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'average', "operator" => 'gt', "value" => '0'),
                array("field" => 'isActive', "operator" => 'eq', "value" => 'En Progreso'),
            ]);

            return $this->repository->generateMassiveCertificates($criteria);
        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }
}
