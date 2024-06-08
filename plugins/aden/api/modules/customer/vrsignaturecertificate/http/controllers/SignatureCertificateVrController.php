<?php

namespace AdeN\Api\Modules\Customer\VrSignatureCertificate\Http\Controllers;

use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\Customer\VrSignatureCertificate\SignatureCertificateVrModel;
use AdeN\Api\Modules\Customer\VrSignatureCertificate\SignatureCertificateVrRepository;
use Exception;
use Request;
use Log;
use Response;
use Input;
use Validator;

use System\Models\File;
use Wgroup\Classes\ApiResponse;
use Wgroup\Traits\UserSecurity;

use Controller as BaseController;

class SignatureCertificateVrController extends BaseController
{
    use UserSecurity;

    private $request;

    private $repository;

    public function __construct()
    {
        $this->request = app('Input');

        // set response
        $this->response = new ApiResponse();
        $this->response->setMessage("1");
        $this->response->setStatuscode(200);

        $this->repository = new SignatureCertificateVrRepository();
    }


    public function show($customerId)
    {
        try {
            $result = $this->repository->parseModelWithRelations($customerId);
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


    public function upload($customerId)
    {
        try {
            $allFiles = Input::file();

            $model = SignatureCertificateVrModel::where("customer_id", $customerId)->first();

            foreach ($allFiles as $file) {
                $this->checkUploadPostBack($file, $model);
            }

            $model = SignatureCertificateVrModel::where("customer_id", $customerId)->first();

            $this->response->setResult($model->signature);

        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
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

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new SystemException('File is not valid');
            }

            $fileRelation = $model->signature();

            $file = new File();
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->save();

            $fileRelation->add($file);

            $result = [
                'file' => $uploadedFileName,
                'path' => $file->getPath(),
            ];

        } catch (Exception $ex) {
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName,
            ];

        }

        return $result;
    }


    public function uploadLogo($customerId)
    {
        try {
            $allFiles = Input::file();

            $model = SignatureCertificateVrModel::where("customer_id", $customerId)->first();

            foreach ($allFiles as $file) {
                $this->checkUploadPostbackLogo($file, $model);
            }

            $model = SignatureCertificateVrModel::where("customer_id", $customerId)->first();

            $this->response->setResult($model->signature);

        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    protected function checkUploadPostbackLogo($uploadedFile, $model)
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

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new SystemException('File is not valid');
            }

            $fileRelation = $model->logo();

            $file = new File();
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->save();

            $fileRelation->add($file);

            $result = [
                'file' => $uploadedFileName,
                'path' => $file->getPath(),
            ];

        } catch (Exception $ex) {
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName,
            ];

        }

        return $result;
    }


}
