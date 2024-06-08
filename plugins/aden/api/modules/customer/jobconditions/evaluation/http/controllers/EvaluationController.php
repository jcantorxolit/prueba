<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Evaluation\Http\Controllers;

use AdeN\Api\Modules\Customer\JobConditions\Evaluation\EvaluationRepository;
use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionEvaluationEvidenceModel;use Carbon\Carbon;use DB;
use Illuminate\Support\Facades\Input;
use Request;
use Response;
use Exception;
use Validator;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;

use System\Models\File;

use Wgroup\Traits\UserSecurity;

class EvaluationController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new EvaluationRepository();
        $this->request = app('Input');
        $this->run();
    }

    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'jobConditionId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'date', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workmodel', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'location', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'occupation', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'workplace', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'risk', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'state', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $otherFilters = [
                array("field" => 'period', "operator" => 'eq'),
            ];
            $filters = CriteriaHelper::parse($content, $otherFilters);

            $result = $this->repository->allEvaluations($criteria, $filters);
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

            $this->repository->canSave($entity);
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


    public function getQuestions() {
        try {
            $content = $this->request->get("data", "");
            $data = HttpHelper::parse($content, true);

            $result = $this->repository->getQuestions($data->classificationId, $data->evaluationId);
            $this->response->setResult($result);

        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function storeAnswers()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->insertOrUpdateAnswers($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function getPeriods() {
        try {
            $result = $this->repository->getPeriods();
            $this->response->setResult($result);

        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function getStats() {
        try {
            $content = $this->request->get("data", "");
            $data = HttpHelper::parse($content, true);

            $result = $this->repository->getStats($data->id);
            $this->response->setResult($result);

        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }





    public function getEvidences() {
        try {
            $content = $this->request->get("data", "");
            $data = HttpHelper::parse($content, true);

            $result = $this->repository->getEvidences($data->evaluationId, $data->classificationId);
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
        $evaluationId = $this->request->get("evaluationId", "0");
        $classificationId = $this->request->get("classificationId", "0");

        try {

            $allFiles = Input::file();

            $model = JobConditionEvaluationEvidenceModel::where('self_evaluation_id', $evaluationId)->where('classification_id', $classificationId)->first();
            if (empty($model)) {
                $model = new JobConditionEvaluationEvidenceModel();
                $model->self_evaluation_id = $evaluationId;
                $model->classification_id = $classificationId;
                $model->save();
            }

            foreach ($allFiles as $file) {
                $this->checkUploadPostback($file, $model);
            }

            $model = JobConditionEvaluationEvidenceModel::where('self_evaluation_id', $evaluationId)->where('classification_id', $classificationId)->first();
            $this->response->setResult(\AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->photos ?? [] ));

        } catch (Exception $exc) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    protected function checkUploadPostback($uploadedFile, $model)
    {
        $uploadedFileName = null;
        try {
            if ($uploadedFile) {
                $uploadedFileName = $uploadedFile->getClientOriginalName();
            }

            $validationRules = ['max:' . File::getMaxFilesize()];
            $validationRules[] = 'mimes:jpg,png,jpeg,bmp,gif';

            $validation = Validator::make(
                ['file_data' => $uploadedFile], ['file_data' => $validationRules]
            );

            if ($uploadedFile->getClientOriginalExtension() != 'msg' && $validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new SystemException('File is not valid');
            }

            $fileRelation = $model->photos();

            $file = new File();
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->save();

            $fileRelation->add($file);

            $result = [
                'file' => $uploadedFileName,
                'path' => $file->getPath()
            ];

            $photos = null;
            $imageUrl = $file->getDiskPath();

            $newPhoto = new \stdClass();
            $newPhoto->default = true;
            $newPhoto->url = $imageUrl;
            $newPhoto->path = $imageUrl;
            $newPhoto->src = $imageUrl;
            $newPhoto->imageUrl = $imageUrl;
            $newPhoto->date = Carbon::now("America/Bogota");
            $newPhoto->id = $file->id;
            $newPhoto->title = Carbon::now("America/Bogota")->format('d/m/Y H:i');
            $newPhoto->sub = null;

            if ($model->imageUrl != "") {
                $photos = json_decode($model->imageUrl);
            }

            if ($photos == null) {
                $photos = [$newPhoto];
            } else {
                $photos[] = $newPhoto;
            }

            $model->imageUrl = json_encode($photos);
            $model->save();

        } catch (Exception $ex) {
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';
            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName
            ];
        }

        return $result;
    }


    public function downloadEvidencesZip(){
        set_time_limit(0);

        try {
            $content = $this->request->get("data", "");
            $data = HttpHelper::parse($content, true);

            $evaluationId = $data->evaluationId;
            $classificationId = $data->classificationId;

            $this->repository->exportEvidencesZip($evaluationId, $classificationId);

        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

}
