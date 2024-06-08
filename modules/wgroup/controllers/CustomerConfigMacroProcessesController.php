<?php

namespace Wgroup\Controllers;

use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Modules\Customer\CustomerRepository;
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

use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcesses;
use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcessesDTO;
use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcessesService;
use DB;
use Validator;
use Input;
use Excel;
use System\Models\File;
use Carbon\Carbon;
use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcessesStaging;
use Illuminate\Database\Eloquent\Collection;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerConfigMacroProcessesController extends BaseController
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
        $this->service = new CustomerConfigMacroProcessesService();
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
        $customerId = $this->request->get("customerId", "0");

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

            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $customerId);

            // Counts
            $recordsTotal = $this->service->getCount("", $customerId);
            $recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerConfigMacroProcessesDTO::parse($data);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal($recordsTotal);
            $this->response->setRecordsFiltered($recordsFiltered);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc);

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
        $customerId = $this->request->get("customerId", "0");
        $workPlaceId = $this->request->get("workPlaceId", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());

        try {

            $data = CustomerConfigMacroProcesses::whereCustomerId($customerId)->whereWorkplaceId($workPlaceId)->whereStatus('Activo')->get();

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
            $model = CustomerConfigMacroProcessesDTO::fillAndSaveModel($info);

            $result = CustomerConfigMacroProcessesDTO::parse($model);

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
                $result = new CustomerConfigMacroProcessesDTO();
            } else {
                if (!($model = CustomerConfigMacroProcesses::find($id))) {
                    throw new \Exception("Customer not found");
                }

                //Get data
                $result = CustomerConfigMacroProcessesDTO::parse($model);
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

            if (!($model = CustomerConfigMacroProcesses::find($id))) {
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

    public function import()
    {
        $customerId = $this->request->get("customerId", "0");
        $workPlaceId = $this->request->get("workPlaceId", "0");

        try {

            $allFiles = Input::file();

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkImportPostBack($file, $customerId, $workPlaceId);
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


    protected function checkImportPostBack($uploadedFile, $customerId, $workPlaceId)
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

            Excel::load($uploadedFile, function ($file) use ($customerId, $workPlaceId) {

                $results = $file->all();

                $data = array();

                $now = Carbon::now('America/Bogota')->toDateTimeString();

                foreach ($results as $sheet) {
                    foreach ($sheet as $row) {
                        if ($row->macroproceso != '') {
                            $data[] = array(
                                'id' => "", 'customer_id' => $customerId, 'workplace_id' => $workPlaceId, 'name' => $row->macroproceso, 'status' => $row->estado == '' ? "Activo" : $row->estado, 'createdBy' => 2, 'created_at' => $now
                            );
                        }
                    }
                    break;
                }

                if (count($data) > 0) {
                    // var_dump($data);
                    CustomerConfigMacroProcessesStaging::truncate();
                    CustomerConfigMacroProcessesStaging::insert($data);
                    DB::statement('CALL TL_MacroProcess()');
                }
            });
        } catch (Exception $ex) {

            //var_dump($ex->getMessage());
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName
            ];


            //Log::info('Message text.'.sprintf($message, $uploadedFileName, $ex->getMessage()));
        }

        return $result;
    }

    public function download()
    {

        try {

            $customerId = $this->request->get("customerId", "0");
            $instance = CmsHelper::getInstance();
            $file = "templates/$instance/PlantillaMacroProcesos.xlsx";

            $data = array_map(function ($row) {
                return [
                    'NOMBRE' => mb_strtoupper($row->name)
                ];
            }, (new CustomerRepository)->getWorkplaceList($customerId));

            Excel::load(CmsHelper::getAppPath($file), function ($file) use ($data) {
                $sheet = $file->setActiveSheetIndex(1);

                $sheet->fromArray($data, null, 'A1', false);

                $sheet = $file->setActiveSheetIndex(0);

                $cels = [
                    'A2' => ['range' => 'A2:A5000', 'formula' => 'CentroTrabajo'],
                    'C2' => ['range' => 'C2:C5000', 'formula' => 'Estado'],
                ];

                foreach ($cels as $cell => $info) {
                    $validation = $sheet->getCell($cell)->getDataValidation();
                    $validation->setType(\PHPExcel_Cell_DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(\PHPExcel_Cell_DataValidation::STYLE_STOP);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setShowDropDown(true);
                    $validation->setErrorTitle('Error de entrada');
                    $validation->setError('El valor no está en la lista.');
                    //$validation->setPromptTitle('Elegir de la lista');
                    //$validation->setPrompt('Por favor, elija un valor de la lista desplegable');
                    $validation->setFormula1($info['formula']);
                    $sheet->setDataValidation($info['range'], $validation);
                }
            })->download('xlsx');

            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());
            $this->response->setResult(0);
            // error on server
            $this->response->setStatuscode(404);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
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
