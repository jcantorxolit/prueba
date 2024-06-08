<?php

namespace Wgroup\Controllers;

use Carbon\Carbon;
use Controller as BaseController;
use Exception;
use Log;
use October\Rain\Support\ValidationException;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Classes\SystemException;
use System\Models\Parameters;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\RandomColor;
use Wgroup\Classes\ServiceApi;
use Wgroup\CustomerHealthDamageQualificationSourceDocument\CustomerHealthDamageQualificationSourceDocument;
use Wgroup\CustomerHealthDamageQualificationSourceDocument\CustomerHealthDamageQualificationSourceDocumentDTO;
use Wgroup\CustomerHealthDamageQualificationSourceDocument\CustomerHealthDamageQualificationSourceDocumentService;
use Wgroup\CustomerEmployee\CustomerEmployee;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeService;
use Wgroup\Models\CustomerDto;
use System\Models\File;
use Illuminate\Support\Facades\Input;
use Validator;
use Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

use October\Rain\Support\Facades\Config;
use AdeN\Api\Helpers\CmsHelper;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerHealthDamageQualificationSourceDocumentController extends BaseController
{

    const SESSION_LOCALE = 'rainlab.translate.locale';

    private $translate;
    private $service;
    private $serviceEmployee;
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
        $this->service = new CustomerHealthDamageQualificationSourceDocumentService();
        $this->serviceEmployee = new CustomerEmployeeService();
        $this->serviceCustomer = new ServiceApi();
        $this->translate = Translator::instance();

        // set user
        $this->user = $this->user();

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
        $customerHealthDamageQsId = $this->request->get("customer_health_damage_qs_id", "0");
        $entityCode = $this->request->get("entity_code", "0");
        $entityId = $this->request->get("entity_id", "0");
        $data = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {


            $currentPage = $currentPage + 1;

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            // get all tracking by customer with pagination
            //$data = $this->service->getAll(@$search['value'], $length, $currentPage, $orders, "", $customerId);
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, array(), $customerHealthDamageQsId, $entityCode, $entityId, $audit);

            // Counts
            //$recordsTotal = $this->service->getCount("", $customerId);
            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            $recordsTotal = $this->service->getCount("", $customerHealthDamageQsId, $entityCode, $entityId, null);
            $recordsFiltered = $this->service->getCount(@$search['value'], $customerHealthDamageQsId, $entityCode, $entityId, $audit);

            // extract info
            $result = CustomerHealthDamageQualificationSourceDocumentDTO::parse($data);

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

    public function indexAll()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerHealthDamageQsId = $this->request->get("customer_health_damage_qs_id", "0");
        $data = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $currentPage = $currentPage + 1;

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            // get all tracking by customer with pagination
            //$data = $this->service->getAll(@$search['value'], $length, $currentPage, $orders, "", $customerId);
            $data = $this->service->getAllByHealthDamageQs(@$search['value'], $length, $currentPage, array(), $customerHealthDamageQsId, $audit);

            // Counts
            //$recordsTotal = $this->service->getCount("", $customerId);
            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            $recordsTotal = $this->service->getCountHealthDamageQs("", $customerHealthDamageQsId, null);
            $recordsFiltered = $this->service->getCountHealthDamageQs(@$search['value'], $customerHealthDamageQsId, $audit);

            // extract info
            $result = CustomerHealthDamageQualificationSourceDocumentDTO::parse($data);

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

            $model = CustomerHealthDamageQualificationSourceDocumentDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerHealthDamageQualificationSourceDocumentDTO::parse($model);

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

    public function delete()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            //Log::info("customer [" . $id . "]s::");

            if (!($model = CustomerHealthDamageQualificationSourceDocument::find($id))) {
                throw new Exception("Customer not found to delete.");
            }

            $model->delete();

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

    public function get()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerHealthDamageQualificationSourceDocument::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerHealthDamageQualificationSourceDocumentDTO::parse($model);

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

    public function upload()
    {

        // Preapre parameters for query
        $document = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            $model = CustomerHealthDamageQualificationSourceDocument::find($document);

            //$uploadedFile = Input::file('file_data');

            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostback($file, $model);
            }

            $model = CustomerHealthDamageQualificationSourceDocument::find($document);

            $this->response->setResult($model);

            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(404);
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
                ['file_data' => $uploadedFile], ['file_data' => $validationRules]
            );

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

            //Log::info($ex->getMessage() . $uploadedFileName);
            //$response = Response::make()->setContent($result);
            //$response->send();
            //die();
        }

        return $result;
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

            if (!($model = CustomerHealthDamageQualificationSourceDocument::find($id))) {
                throw new \Exception("CustomerEmployee not found", 404);
            }

            //Get data
            $result = CustomerHealthDamageQualificationSourceDocumentDTO::parse($model);

            //$this->response->setResult($result);
            //$file = str_replace("/beta", "",public_path()). $result->document->path;
            //$file = $result->document->path;
            $file = $result->document->getDiskPath();

            $headers = array(
                'Content-Type:' . $result->document->content_type,
                'Content-Disposition:attachment; filename="' . $result->document->file_name . '"',
                'Content-Transfer-Encoding:binary',
                'Content-Length:' . $result->document->file_size,
            );

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
        //return Response::json($file, $this->response->getStatuscode());
        //return Response::download($file, $result->document->file_name, $headers);
        return $result->document->download();

    }

    public function downloadAll()
    {
        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        $file = "";
        $headers = array();

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            // $documents = CustomerHealthDamageQualificationSourceDocument::whereCustomerHealthDamageQualificationSourceId($id)->get();

            // //Get data

            // $path = $this->getStorageDirectory();

            // if (!$this->makeDir($path)) {
            //     throw new \Exception("Can create folder", 403);
            // }

            // $file = Carbon::now('America/Bogota')->timestamp . ".zip";

            // $fileToDownload = "Anexos_Calificacion_Origen" . $id . ".zip";

            // $filePath = $path . $file;

            // $zip = new \ZipArchive();

            // if ($zip->open($filePath, \ZipArchive::CREATE) !== true) {
            //     throw new \Exception("Could not open archive", 403);
            // }

            // foreach ($documents as $document) {
            //     $result = CustomerHealthDamageQualificationSourceDocumentDTO::parse($document);

            //     if ($result->document != null) {
            //         $fileName = $result->document->getDiskPath();
            //         $realFileName = $result->document->id . "_" . str_replace(' ', '_', $result->entityName) . '_' . $result->document->file_name;
            //         $zip->addFile($fileName, $realFileName);
            //     }
            // }

            // $zip->close();

            // $headers = array(
            //     'Content-Type:application/zip',
            //     'Content-Disposition:attachment; filename="' . $fileToDownload . '"',
            //     'Content-Transfer-Encoding:binary',
            //     'Content-Length:' . filesize($filePath),
            // );
        } catch (Exception $exc) {

            // Log the full exception
            var_dump($exc->getMessage());

            // error on server
            if ($exc->getCode()) {
                $this->response->setStatuscode($exc->getCode());
            } else {
                $this->response->setStatuscode(500);
            }
            $this->response->setMessage($exc->getMessage());
        }
        //return Response::json($file, $this->response->getStatuscode());
        //return Response::download($filePath, $fileToDownload, $headers);

        $fileToDownload = "Anexos_Calificacion_Origen" . $id . ".zip";

        return new StreamedResponse(function() use ($id, $fileToDownload) {

            $documents = CustomerHealthDamageQualificationSourceDocument::whereCustomerHealthDamageQualificationSourceId($id)->get();

            // $options = new \ZipStream\Option\Archive();

            // $options->setContentType('application/octet-stream');
            // $options->setZeroHeader(true);

            $zip = new \ZipStream\ZipStream($fileToDownload);

            foreach ($documents as $document) {
                //$result = CustomerInvestigationAlDocumentDTO::parse($document);
                $result = $document;
                if ($result->document != null) {
                    try {
                        $realFileName = $result->document->id. "_Anexos_" .$result->document->file_name;
                        \Log::info($realFileName);
                        $zip->addFileFromStream($realFileName, $result->document->getStream());
                    } catch (\Exception $ex) {
                        \Log::error($ex);
                    }
                }
            }

            $zip->finish();
        }, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $fileToDownload . '"',
        ]);

    }

    /**
     *  PRIVATED METHODS
     */

    /**
     * Returns the logged in user, if available
     */
    private function user()
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

    private function getStorageDirectory()
    {
        $uploadsDir = Config::get('cms.uploadsDir');

        return base_path() . $uploadsDir . '/zip/';
    }

    private function makeDir($dirPath, $mode = 0777)
    {
        return is_dir($dirPath) || mkdir($dirPath, $mode, true);
    }
}
