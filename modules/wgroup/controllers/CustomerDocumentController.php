<?php

namespace Wgroup\Controllers;

use Controller as BaseController;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use October\Rain\Support\ValidationException;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Models\File;
use System\Models\Parameters;
use Validator;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\ServiceApi;
use Wgroup\Classes\ServiceCustomerDocument;
use Wgroup\CustomerAudit\CustomerAudit;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDocument;
use Wgroup\Models\CustomerDocumentDTO;
use Wgroup\Models\CustomerDocumentSecurityDTO;
use Carbon\Carbon;
use System\Classes\SystemException;
use AdeN\Api\Helpers\CmsHelper;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerDocumentController extends BaseController
{

    const SESSION_LOCALE = 'rainlab.translate.locale';

    private $translate;
    private $service;
    private $serviceCustomer;
    private $request;
    private $user;
    private $response;
    protected $groupStatusCache = false;
    protected $selectedFilesCache = false;

    /**
     * @var string Message to display when there are no records in the list.
     */
    public $noRecordsMessage = 'No files found';

    /**
     * @var string Message to display when the Delete button is clicked.
     */
    public $deleteConfirmation = 'Do you really want to delete selected files or directories?';

    /**
     * @var array A list of default allowed file types.
     * This parameter can be overridden with the cms.allowedAssetTypes configuration option.
     */
    public $allowedAssetTypes = ['jpg', 'jpeg', 'bmp', 'png', 'gif', 'css', 'js', 'woff', 'svg', 'ttf', 'eot', 'json', 'md', 'less', 'sass', 'scss'];

    public function __construct()
    {

        //set service
        $this->service = new ServiceCustomerDocument();
        $this->serviceCustomer = new ServiceApi();
        $this->translate = Translator::instance();

        // set user
        $this->user = $this->getUser();

        // @todo validate user and permisions
        // set request
        $this->request = app('Input');

        // set response
        $this->response = new ApiResponse();
        $this->response->setMessage("1");
        $this->response->setStatuscode(200);
    }


    public function index()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $program = $this->request->get("program", "");
        $hideCanceled = $this->request->get("hideCanceled", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            // Validate permissions
            /*if (!UserGroup::hasRole('admin')) {
                throw new Exception(Message::trans("messages.error.notauthorized", array()));
            }*/

            //Si es un usuario de un cliente
            $user = $this->getUser();
            $isCustomer = false;

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                $isCustomer = true;
                if ($user->company != $customerId) {
                    //$customerId = -1;
                }
            }

            //Log::info("Antes de");

            $currentPage = $currentPage + 1;

            $hideCanceled = $hideCanceled == '1' ? true : false;

            // get all tracking by customer with pagination
            $data = $this->service->getAllByPermission(@$search['value'], $length, $currentPage, $orders, "", $customerId, $this->user->id, $program, $hideCanceled);

            //$data = array();

            //Log::info("Despues de");
            // Counts
            $recordsTotal = $this->service->getCount("", $customerId, $this->user->id, $program, $hideCanceled);
            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerDocumentDTO::parse($data, "2");

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal(count($recordsTotal));
            $this->response->setRecordsFiltered(count($recordsTotal));
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function users()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $documentType = $this->request->get("document_type", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            // Validate permissions
            /*if (!UserGroup::hasRole('admin')) {
                throw new Exception(Message::trans("messages.error.notauthorized", array()));
            }*/

            //Si es un usuario de un cliente
            $user = $this->getUser();
            $isCustomer = false;

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                $isCustomer = true;
                if ($user->company != $customerId) {
                    $customerId = -1;
                }
            }

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllUsersPermissionByDocType($customerId, $documentType);

            // Counts
            $recordsTotal = $this->service->getCount("", $customerId, 0);
            $recordsFiltered = $this->service->getCount(@$search['value'], $customerId, 0);

            // extract info
            $result = CustomerDocumentDTO::parse($data, "3");

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal($recordsTotal);
            $this->response->setRecordsFiltered($recordsFiltered);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function save()
    {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            // Parse to model

            $model = CustomerDocumentDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerDocumentDTO::parse($model);

            $this->response->setResult($result);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());
            Log::error($exc->getLine());
            Log::error($exc->getFile());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function savePermission()
    {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            // Parse to model

            $model = CustomerDocumentSecurityDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerDocumentSecurityDTO::parse($model);

            $this->response->setResult($result);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());
            Log::error($exc->getLine());
            Log::error($exc->getFile());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function upload()
    {

        // Preapre parameters for query
        $document = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            //Log::info("customer document[" . $document . "]s::");

            $model = CustomerDocument::find($document);

            //$uploadedFile = Input::file('file_data');

            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostback($file, $model);
            }

            $model = CustomerDocument::find($document);

            $this->response->setResult(\AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document));
            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            var_dump($exc->getMessage());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    protected function checkUploadPostback($uploadedFile, $model)
    {

        //if (!post('X_BLOG_IMAGE_UPLOAD'))
        //  return;

        $uploadedFileName = null;
        $result = array();
        try {
            //  $uploadedFile = Input::file('file');

            if ($uploadedFile)
                $uploadedFileName = $uploadedFile->getClientOriginalName();

            $validationRules = ['max:' . File::getMaxFilesize()];
            $validationRules[] = 'mimes:' . CmsHelper::getMimeTypes();

            $validation = Validator::make(
                ['file_data' => $uploadedFile],
                ['file_data' => $validationRules]
            );

            //var_dump($uploadedFile->getMimeType());

            if ($uploadedFile->getClientOriginalExtension() != 'msg') {
                if ($validation->fails()) {
                    throw new ValidationException($validation);
                }
            }

            if (!$uploadedFile->isValid())
                throw new SystemException('File is not valid');

            $fileRelation = $model->document();

            $file = new File();
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->save();

            $fileRelation->add($file);

            $result = [
                'file' => $uploadedFileName,
                'path' => $file->getPath()
            ];

            //$response = Response::make()->setContent($result);
            //$response->send();
            //die();
        } catch (Exception $ex) {
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName
            ];
        }

        return $result;
    }

    public function get()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerDocument::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerDocumentDTO::parse($model);

            $this->response->setResult($result);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
            if ($exc->getCode()) {
                $this->response->setStatuscode($exc->getCode());
            } else {
                $this->response->setStatuscode(500);
            }
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function delete()
    {
        // Preapre parameters for query
        $customer = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            //Log::info("customer [" . $customer . "]s::");

            if (!($model = CustomerDocument::find($customer))) {
                throw new Exception("Customer not found to delete.");
            }

            // Elimina el documento
            //$model->document()->delete();

            $model->status = 2;
            $model->save();

            $this->response->setResult(1);
            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());
            $this->response->setResult(0);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function download()
    {
        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        $file = "";
        $headers = array();

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerDocument::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            $userAdmn = $this->getUser();

            //Get data
            $result = CustomerDocumentDTO::parse($model);

            $file =  $result->document;

            $customerAudit = new CustomerAudit();
            $customerAudit->customer_id = $model->customer_id;
            $customerAudit->model_name = "Anexos";
            $customerAudit->model_id = $model->customer_id;
            $customerAudit->user_type = $userAdmn->wg_type;
            $customerAudit->user_id = $userAdmn->id;
            $customerAudit->action = "Descargar";
            $customerAudit->observation = "Se realiza descarga exitosa del anexo: (" . $result->description . " - " . $result->document->file_name . ")";
            $customerAudit->date = Carbon::now('America/Bogota');
            $customerAudit->save();

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
            if ($exc->getCode()) {
                $this->response->setStatuscode($exc->getCode());
            } else {
                $this->response->setStatuscode(500);
            }
            $this->response->setMessage($exc->getMessage());
        }

        return $file->download();
    }

    /**
     *  PRIVATED METHODS
     */

    /**
     * Returns the logged in user, if available
     */
    private function getUser()
    {
        if (!Auth::check())
            return null;


        return Auth::getUser();
    }

    private function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }

    public function loadLocaleFromSession()
    {

        if ($sessionLocale = $this->getSessionLocale()) {
            return $sessionLocale;
        } else {
            if ($localeNegotiated = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $localeNegotiated = substr($localeNegotiated, 0, 2);
                return $localeNegotiated;
            }
        }
    }

    protected function getSessionLocale()
    {
        if (!Session::has(self::SESSION_LOCALE))
            return null;

        return Session::get(self::SESSION_LOCALE);
    }

    // Metdos pilotos
    private function random_numbers($digits)
    {
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;
        return mt_rand($min, $max);
    }

    private function download_file($url, $path)
    {

        $newfilename = $path;
        $file = fopen($url, "rb");
        if ($file) {
            $newfile = fopen($newfilename, "wb");

            if ($newfile)
                while (!feof($file)) {
                    fwrite($newfile, fread($file, 1024 * 8), 1024 * 8);
                }
        }

        if ($file) {
            fclose($file);
        }
        if ($newfile) {
            fclose($newfile);
        }
    }

    function debug($message, $param = null)
    {
        if (!$param) {
            //Log::info($message);
        } else if (is_array($param)) {
            //Log::info(vsprintf($message, $param));
        } else {
            //Log::info(sprintf($message, $param));
        }
    }
}