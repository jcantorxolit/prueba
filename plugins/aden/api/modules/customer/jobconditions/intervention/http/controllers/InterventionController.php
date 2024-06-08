<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Intervention\Http\Controllers;

use DB;
use Request;
use Illuminate\Support\Facades\Input;
use Response;
use Exception;
use Validator;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\HttpHelper;
use Wgroup\Traits\UserSecurity;

use AdeN\Api\Modules\Customer\JobConditions\Intervention\InterventionRepository;


class InterventionController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new InterventionRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function index() {
        try {
            $content = $this->request->get("data", "");
            $data = HttpHelper::parse($content, true);

            $result = $this->repository->getQuestions($data->evaluationId, $data->classificationId, $data->isHistorical);
            $this->response->setResult($result);

        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
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
        try {
            $content = $this->request->get("data", "");
            $id = HttpHelper::parse($content, true)->id;

            $model = $this->repository->find($id);
            $result = $this->repository->parseModelWithRelations($model);

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
                $fileInfo = $this->repository->checkUploadPostBack($file, $model, 'documents');
                if (!isset($fileInfo['error'])) {
                    $this->repository->updateFiles($model, $fileInfo);
                }
            }

            $model = $this->repository->find($id);
            $response = $this->repository->parseModelWithRelations($model);
            $this->response->setResult($response);

        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function closeIntervention()
    {
        try {
            $content = $this->request->get("data", "");
            $entity = HttpHelper::parse($content, true);
            $result = null;

            if (!empty($entity->id)) {
                $result = $this->repository->closeIntervention($entity->id);
            }

            $this->response->setResult($result);

        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

}
