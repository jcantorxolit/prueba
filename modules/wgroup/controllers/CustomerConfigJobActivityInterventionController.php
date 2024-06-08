<?php

namespace Wgroup\Controllers;

use AdeN\Api\Helpers\CmsHelper;
use October\Rain\Support\ValidationException;
use System\Classes\SystemException;
use Wgroup\Classes\ApiResponse;
use Controller as BaseController;
use Exception;
use Log;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Models\Parameters;

use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityIntervention;
use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityInterventionDTO;
use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityInterventionService;

use DB;
use Validator;
use Input;
use Excel;
use System\Models\File;
use Carbon\Carbon;
use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityInterventionStaging;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerConfigJobActivityInterventionController extends BaseController
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
        $this->service = new CustomerConfigJobActivityInterventionService();
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
        $customerId = $this->request->get("customer_id", "0");
        $data = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        //$length = 10000;
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
            $data = $this->service->getAllMatrix(@$search['value'], $length, $currentPage, $customerId, $audit);

            // Counts
            $recordsTotal = $this->service->getAllMatrixCount(@$search['value'], $length, $currentPage, $customerId, null);
            $recordsFiltered = $this->service->getAllMatrixCount(@$search['value'], $length, $currentPage, $customerId, $audit);

            // extract info
            $result = $data;

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

    public function indexPrioritize()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $workPlaceId = $this->request->get("workplace_id", "0");
        $levelIR = $this->request->get("level_ir", "");
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
            $data = $this->service->getAllMatrixPrioritize(@$search['value'], $length, $currentPage, $customerId, $workPlaceId, $levelIR, $audit);

            // Counts
            $recordsTotal = $this->service->getAllMatrixPrioritizeCount(@$search['value'], $length, $currentPage, $customerId, 0, 0, null);
            $recordsFiltered = $this->service->getAllMatrixPrioritizeCount(@$search['value'], $length, $currentPage, $customerId, $workPlaceId, $levelIR, $audit);

            // extract info
            $result = $data;

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

    public function indexHistorical()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $workPlaceId = $this->request->get("workplace_id", "0");
        $levelIR = $this->request->get("level_ir", "");
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
            $data = $this->service->getAllMatrixHistorical(@$search['value'], $length, $currentPage, $customerId, $workPlaceId, $levelIR, $audit);

            // Counts
            $recordsTotal = $this->service->getAllMatrixHistoricalCount($customerId, 0, 0, null);
            $recordsFiltered = $this->service->getAllMatrixHistoricalCount($customerId, $workPlaceId, $levelIR, $audit);

            // extract info
            $result = $data;

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

    public function listIndex()
    {
        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $jobId = $this->request->get("customerId", "0");
        $workPlaceId = $this->request->get("workPlaceId", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());

        try {

            $data = CustomerConfigJobActivityIntervention::whereJobId($jobId)->whereStatus('Activo')->get();

            // set count total ideas
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

            ////Log::info($json);

            // parse
            $info = json_decode($json);

            //Get data
            $model = CustomerConfigJobActivityInterventionDTO::fillAndSaveModel($info);

            $result = CustomerConfigJobActivityInterventionDTO::parse($model);

            $this->response->setResult($result);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

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
                $result = new CustomerConfigJobActivityInterventionDTO();
            } else {
                if (!($model = CustomerConfigJobActivityIntervention::find($id))) {
                    throw new \Exception("Customer not found");
                }

                //Get data
                $result = CustomerConfigJobActivityInterventionDTO::parse($model);
            }


            $this->response->setResult($result);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

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

            //Log::info("risk [" . $id . "]s::");

            if (!($model = CustomerConfigJobActivityIntervention::find($id))) {
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

    public function exportExcelSummary()
    {
        $customerId = $this->request->get("id", "0");
        $content = $this->request->get("data", "");

        try {
            Log::info($content);

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));
            $data = $this->service->getAllMatrixExport($customerId, $criteria);
            $filename = 'Resumen_Matriz' . Carbon::now()->timestamp;
            ExportHelper::excel($filename, 'Matriz', $data);
        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportExcelPrioritize()
    {
        $customerId = $this->request->get("id", "0");
        $workPlaceId = $this->request->get("workplace_id", "0");
        $levelIR = $this->request->get("level_ir", "");
        $content = $this->request->get("data", "");

        try {
            Log::info($content);

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));
            $data = $this->service->getAllMatrixPrioritizeExport($customerId, $workPlaceId, $levelIR, $criteria);
            $filename = 'Resumen_Matriz_Priorización' . Carbon::now()->timestamp;
            ExportHelper::excel($filename, 'Matriz', $data);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportExcelHistorical()
    {
        $customerId = $this->request->get("id", "0");
        $workPlaceId = $this->request->get("workplace_id", "0");
        $levelIR = $this->request->get("level_ir", "");
        $content = $this->request->get("data", "");

        try {

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));
            $data = $this->service->getAllMatrixHistoricalExport($customerId, $workPlaceId, $levelIR, $criteria);
            $filename = 'Resumen_Matriz_Historial' . Carbon::now()->timestamp;
            ExportHelper::excel($filename, 'Matriz', $data);
        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function import()
    {
        $jobId = $this->request->get("jobId", "0");

        try {

            $allFiles = Input::file();

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkImportPostBack($file, $jobId);
            }

            $this->response->setResult($allFiles);
            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    protected function checkImportPostBack($uploadedFile, $jobId)
    {
        ini_set('memory_limit', '128M');
        set_time_limit(240);

        $uploadedFileName = null;
        $result = array();
        try {
            //  $uploadedFile = Input::file('file');

            if ($uploadedFile)
                $uploadedFileName = $uploadedFile->getClientOriginalName();

            $validationRules = ['max:' . File::getMaxFilesize()];
            $validationRules[] = 'mimes:xls,xlsx';

            $validation = Validator::make(
                ['file_data' => $uploadedFile],
                ['file_data' => $validationRules]
            );

            if ($validation->fails())
                throw new ValidationException($validation);

            if (!$uploadedFile->isValid())
                throw new SystemException('File is not valid');


            Excel::load($uploadedFile, function ($file) use ($jobId) {

                $results = $file->all();

                $data = array();

                $now = Carbon::now('America/Bogota')->toDateTimeString();

                foreach ($results as $sheet) {
                    foreach ($sheet as $row) {
                        $data[] = array(
                            'id' => "", 'job_id' => $jobId, 'name' => $row->actividad, 'status' => $row->estado, 'isRoutine' => $row->recurrente, 'createdBy' => 2, 'created_at' => $now
                        );
                    }
                    break;
                }

                if (count($data) > 0) {
                    // var_dump($data);
                    CustomerConfigJobActivityInterventionStaging::truncate();
                    CustomerConfigJobActivityInterventionStaging::insert($data);
                    DB::statement('CALL TL_JobActivity()');
                }
            });
        } catch (Exception $ex) {

            //var_dump($ex->getMessage());
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName
            ];


            //Log::info('Message text.' . sprintf($message, $uploadedFileName, $ex->getMessage()));
        }

        return $result;
    }

    public function download()
    {

        try {

            $file = CmsHelper::getStorageTemplateDir("/templates/PlantillaActividades.xlsx");

            $headers = array(
                'Content-Type:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition:attachment; filename="PlantillaActividades.xlsx"',
                'Content-Transfer-Encoding:binary',
            );
            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());
            $this->response->setResult(0);
            // error on server
            $this->response->setStatuscode(404);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::download($file, "PlantillaActividades.xlsx", $headers);
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
}
