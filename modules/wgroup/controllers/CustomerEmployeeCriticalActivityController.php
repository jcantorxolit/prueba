<?php

namespace Wgroup\Controllers;

use AdeN\Api\Helpers\CmsHelper;
use Controller as BaseController;
use Excel;
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
use Wgroup\CustomerEmployeeCriticalActivity\CustomerEmployeeCriticalActivity;
use Wgroup\CustomerEmployeeCriticalActivity\CustomerEmployeeCriticalActivityDTO;
use Wgroup\CustomerEmployeeCriticalActivity\CustomerEmployeeCriticalActivityService;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerEmployeeCriticalActivityController extends BaseController
{

    const SESSION_LOCALE = 'rainlab.translate.locale';

    private $translate;
    private $service;
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
        $this->service = new CustomerEmployeeCriticalActivityService();
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

        $customerEmployeeId = $this->request->get("customer_employee_id", "0");
        $jobId = $this->request->get("job_id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());

        try {

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllBySearch(@$search['value'], $length, $currentPage, $orders, "", $customerEmployeeId, $jobId);

            // Counts
            $recordsTotal = $this->service->getAllCount("", $customerEmployeeId, $jobId);
            $recordsFiltered = $this->service->getAllCount(@$search['value'], $customerEmployeeId, $jobId);

            // extract info
            //$result = CustomerEmployeeCriticalActivityDTO::parse($data, "2");

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
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

    public function filterExpiration()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerEmployeeId = $this->request->get("customer_employee_id", "0");
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", 0);
        $month = $this->request->get("month", 0);

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());

        try {

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $resultData = $this->service->getAllByExpiration(@$search['value'], $length, $currentPage, $year, $month, $customerEmployeeId, $customerId);

            // Counts
            $recordsTotal = $this->service->getAllByExpirationCount(@$search['value'], $length, $currentPage, $year, $month, $customerEmployeeId, $customerId);

            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerEmployeeCriticalActivityDTO::parse($resultData, "2");

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

    public function filterSearchExpiration()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", 0);
        $month = $this->request->get("month", 0);

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());

        try {

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $resultData = $this->service->getAllBySearchExpiration(@$search['value'], $length, $currentPage, $year, $month, $customerId);

            // Counts
            $recordsTotal = $this->service->getAllBySearchExpirationCount(@$search['value'], $length, $currentPage, $year, $month, $customerId);

            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerEmployeeCriticalActivityDTO::parse($resultData, "2");

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

    public function required()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $customerEmployeeId = $this->request->get("customer_employee_id", "0");
        $customerId = $this->request->get("customer_id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());

        try {

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllByRequired(@$search['value'], $length, $currentPage, $orders, "", $customerEmployeeId);

            // Counts
            $recordsTotal = $this->service->getAllByRequiredCount("", $customerEmployeeId);
            $recordsFiltered = $this->service->getAllByRequiredCount(@$search['value'], $customerEmployeeId);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
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

    public function requiredValidate()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $customerEmployeeId = $this->request->get("id", "0");
        $customerId = $this->request->get("id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());

        try {

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllByRequiredValidate(@$search['value'], $length, $currentPage, $orders, "", $customerEmployeeId, $customerId);

            // Counts
            $recordsTotal = $this->service->getCount("", $customerEmployeeId, $customerId);
            $recordsFiltered = $this->service->getCount(@$search['value'], $customerEmployeeId, $customerId);

            // extract info
            //$result = CustomerEmployeeCriticalActivityDTO::parse($data, "2");

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
            $this->response->setRecordsTotal(count($recordsTotal));
            $this->response->setRecordsFiltered(count($recordsFiltered));
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

    public function export()
    {

        $customerEmployeeId = $this->request->get("id", "0");

        try {

            // decodify

            // get all tracking by customer with pagination
            $data = $this->service->getAllByRequiredExport("", 0, 0, null, "", $customerEmployeeId);

            Excel::create('Documentos_Soporte_Empleado', function ($excel) use ($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Documentos', function ($sheet) use ($data) {

                    $resultArray = json_decode(json_encode($data), true);

                    $sheet->fromArray($resultArray, null, 'A1', true, true);

                });

            })->export('xlsx');

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

    }

    public function listIndex()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $customerEmployeeId = $this->request->get("id", "0");
        $jobId = $this->request->get("jobId", "0");
        $customerId = $this->request->get("customerId", "0");

        try {

            $data = $this->service->getAllCriticalActivity($customerEmployeeId, $jobId, $customerId);

            $this->response->setData($data);
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

    public function duplicate()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $customerEmployeeId = $this->request->get("id", "0");
        $jobId = $this->request->get("job_id", "0");

        try {

            $data = $this->service->insertJobActivityCritical($customerEmployeeId, $jobId);

            $this->response->setData($data);
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

            $model = CustomerEmployeeCriticalActivityDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerEmployeeCriticalActivityDTO::parse($model);

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

    public function denied()
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

            $model = CustomerEmployeeCriticalActivityDTO::denied($info);

            // Parse to send on response
            $result = CustomerEmployeeCriticalActivityDTO::parse($model);

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

    public function approve()
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

            $model = CustomerEmployeeCriticalActivityDTO::approve($info);

            // Parse to send on response
            $result = CustomerEmployeeCriticalActivityDTO::parse($model);

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

            //Log::info("agent document[" . $document . "]s::");

            $model = CustomerEmployeeCriticalActivity::find($document);

            //$uploadedFile = Input::file('file_data');

            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostback($file, $model);
            }

            $model = CustomerEmployeeCriticalActivity::find($document);

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

            if ($uploadedFile) {
                $uploadedFileName = $uploadedFile->getClientOriginalName();
            }

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

            if (!$uploadedFile->isValid()) {
                throw new SystemException('File is not valid');
            }

            $fileRelation = $model->document();

            $file = new File();
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->save();

            $fileRelation->add($file);

            $result = [
                'file' => $uploadedFileName,
                'path' => $file->getPath(),
            ];

            //$response = Response::make()->setContent($result);
            //$response->send();
            //die();
        } catch (Exception $ex) {
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName,
            ];

            //Log::info($ex->getMessage().$uploadedFileName);
            //$response = Response::make()->setContent($result);
            //$response->send();
            //die();
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

            if (!($model = CustomerEmployeeCriticalActivity::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerEmployeeCriticalActivityDTO::parse($model);

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
        $id = $this->request->get("id", "0");

        try {

            if (!($model = CustomerEmployeeCriticalActivity::find($id))) {
                throw new Exception("Record not found to delete.");
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

    public function download()
    {
        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        $file = "";
        $headers = array(

        );

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerEmployeeCriticalActivity::find($id))) {
                throw new \Exception("CustomerEmployee not found", 404);
            }

            //Get data
            $result = CustomerEmployeeCriticalActivityDTO::parse($model);

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

    /**
     *  PRIVATED METHODS
     */

    /**
     * Returns the logged in user, if available
     */
    private function user()
    {
        if (!Auth::check()) {
            return null;
        }

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
        if (!Session::has(self::SESSION_LOCALE)) {
            return null;
        }

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

            if ($newfile) {
                while (!feof($file)) {
                    fwrite($newfile, fread($file, 1024 * 8), 1024 * 8);
                }
            }

        }

        if ($file) {
            fclose($file);
        }
        if ($newfile) {
            fclose($newfile);
        }
    }

    public function debug($message, $param = null)
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
