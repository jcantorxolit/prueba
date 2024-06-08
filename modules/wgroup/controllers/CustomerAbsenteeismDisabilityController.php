<?php

namespace Wgroup\Controllers;

use Controller as BaseController;
use Exception;
use Log;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Models\Parameters;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\RandomColor;
use Wgroup\Classes\ServiceApi;
use Wgroup\CustomerAbsenteeismDisability\CustomerAbsenteeismDisability;
use Wgroup\CustomerAbsenteeismDisability\CustomerAbsenteeismDisabilityDTO;
use Wgroup\CustomerAbsenteeismDisability\CustomerAbsenteeismDisabilityService;
use Wgroup\CustomerAbsenteeismDisabilityDocument\CustomerAbsenteeismDisabilityDocumentDTO;
use Wgroup\CustomerEmployee\CustomerEmployee;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeService;
use Wgroup\Models\CustomerDto;
use Excel;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerAbsenteeismDisabilityController extends BaseController {

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

    public function __construct() {

        //set service
        $this->service = new CustomerAbsenteeismDisabilityService();
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
        $customerId = $this->request->get("customer_id", "0");
        $data = $this->request->get("data", "");

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
            $user = $this->user();
            $isCustomer = false;

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser" ) {
                $isCustomer = true;
                if ($user->company != $customerId) {
                    //$customerId = -1;
                }
            }

            /*
            if ($model = $this->serviceCustomer->getCustomerIdByUserGroup())
            {
                if ($model->id != $customerId)
                    $customerId = -1;
            }
            **/

            $currentPage = $currentPage + 1;

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            // get all tracking by customer with pagination
            //$data = $this->service->getAll(@$search['value'], $length, $currentPage, $orders, "", $customerId);
            $data = $this->service->getAll(@$search['value'], $length, $currentPage, $customerId, $audit);

            // Counts
            //$recordsTotal = $this->service->getCount("", $customerId);
            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            $recordsTotal = $this->service->getAllCountBy(@$search['value'], $length, $currentPage, $customerId, null);
            $recordsFiltered = $this->service->getAllCountBy(@$search['value'], $length, $currentPage, $customerId, $audit);

            // extract info
            //$result = CustomerAbsenteeismDisabilityDTO::parse($data);

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


    public function diagnosticAnalysis()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $data = $this->request->get("data", "");

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
            $user = $this->user();
            $isCustomer = false;

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser" ) {
                $isCustomer = true;
                if ($user->company != $customerId) {
                    //$customerId = -1;
                }
            }

            /*
            if ($model = $this->serviceCustomer->getCustomerIdByUserGroup())
            {
                if ($model->id != $customerId)
                    $customerId = -1;
            }
            **/

            $currentPage = $currentPage + 1;

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            // get all tracking by customer with pagination
            //$data = $this->service->getAll(@$search['value'], $length, $currentPage, $orders, "", $customerId);
            $data = $this->service->getAllDiagnostic(@$search['value'], $length, $currentPage, $customerId, $audit);

            // Counts
            //$recordsTotal = $this->service->getCount("", $customerId);
            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            $recordsTotal = $this->service->getAllDiagnosticCountBy(@$search['value'], $length, $currentPage, $customerId, null);
            $recordsFiltered = $this->service->getAllDiagnosticCountBy(@$search['value'], $length, $currentPage, $customerId, $audit);

            // extract info
            //$result = CustomerAbsenteeismDisabilityDTO::parse($data);

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

    public function diagnosticAnalysisExport()
    {

        $data = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getAllDiagnosticExport($customerId);

            Excel::create('Análisis-Diagnóstico-Reporte-Excel', function($excel) use($data) {
                // Call them separately
                $excel->setDescription('Análisis Diagnóstico');

                $excel->sheet('Reporte', function($sheet) use($data) {

                    $resultArray = json_decode(json_encode($data), true);

                    $sheet->fromArray($resultArray, null, 'A1', true, true);
                });

            })->export('xlsx');

        } catch (Exception $exc) {

            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function daysAnalysis()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $data = $this->request->get("data", "");

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
            $user = $this->user();
            $isCustomer = false;

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser" ) {
                $isCustomer = true;
                if ($user->company != $customerId) {
                    //$customerId = -1;
                }
            }

            /*
            if ($model = $this->serviceCustomer->getCustomerIdByUserGroup())
            {
                if ($model->id != $customerId)
                    $customerId = -1;
            }
            **/

            $currentPage = $currentPage + 1;

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            // get all tracking by customer with pagination
            //$data = $this->service->getAll(@$search['value'], $length, $currentPage, $orders, "", $customerId);
            $data = $this->service->getAllDays(@$search['value'], $length, $currentPage, $customerId, $audit);

            // Counts
            //$recordsTotal = $this->service->getCount("", $customerId);
            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            $recordsTotal = $this->service->getAllDaysCountBy(@$search['value'], $length, $currentPage, $customerId, null);
            $recordsFiltered = $this->service->getAllDaysCountBy(@$search['value'], $length, $currentPage, $customerId, $audit);

            // extract info
            //$result = CustomerAbsenteeismDisabilityDTO::parse($data);

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

    public function daysAnalysisExport()
    {

        $data = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getAllDaysDiagnosticExport($customerId);

            Excel::create('Análisis-Por-Dias-Incapacidad-Reporte-Excel', function($excel) use($data) {
                // Call them separately
                $excel->setDescription('Análisis Diagnóstico');

                $excel->sheet('Reporte', function($sheet) use($data) {

                    $resultArray = json_decode(json_encode($data), true);

                    $sheet->fromArray($resultArray, null, 'A1', true, true);
                });

            })->export('xlsx');

        } catch (Exception $exc) {

            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function personAnalysis()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $diagnosticId = $this->request->get("diagnostic_id", "0");
        $type = $this->request->get("type", "");
        $data = $this->request->get("data", "");

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
            $user = $this->user();
            $isCustomer = false;

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser" ) {
                $isCustomer = true;
                if ($user->company != $customerId) {
                    //$customerId = -1;
                }
            }

            /*
            if ($model = $this->serviceCustomer->getCustomerIdByUserGroup())
            {
                if ($model->id != $customerId)
                    $customerId = -1;
            }
            **/

            $currentPage = $currentPage + 1;

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            // get all tracking by customer with pagination
            //$data = $this->service->getAll(@$search['value'], $length, $currentPage, $orders, "", $customerId);
            $data = $this->service->getAllPerson(@$search['value'], $length, $currentPage, $customerId, $audit, $diagnosticId, $type);

            // Counts
            //$recordsTotal = $this->service->getCount("", $customerId);
            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            $recordsTotal = $this->service->getAllPersonCountBy(@$search['value'], $length, $currentPage, $customerId, null, $diagnosticId, $type);
            $recordsFiltered = $this->service->getAllPersonCountBy(@$search['value'], $length, $currentPage, $customerId, $audit, $diagnosticId, $type);

            // extract info
            //$result = CustomerAbsenteeismDisabilityDTO::parse($data);

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

    public function personAnalysisExport()
    {

        $data = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getAllPersonDiagnosticExport($customerId);

            Excel::create('Análisis-Por-Persona-Reporte-Excel', function($excel) use($data) {
                // Call them separately
                $excel->setDescription('Análisis Diagnóstico');

                $excel->sheet('Reporte', function($sheet) use($data) {

                    $resultArray = json_decode(json_encode($data), true);

                    $sheet->fromArray($resultArray, null, 'A1', true, true);
                });

            })->export('xlsx');

        } catch (Exception $exc) {

            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function getBilling()
    {
        $customerId = $this->request->get("customer_id", "0");
        try {

            $data = $this->service->getAllByBilling($customerId);

            $this->response->setData($data);
            $this->response->setRecordsTotal(0);
            $this->response->setRecordsFiltered(0);
        } catch (Exception $exc) {

            Log::error($exc->getMessage());

            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function getEmployee()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $user = $this->user();

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser" ) {
                if ($user->company != $customerId) {
                    $customerId = -1;
                }
            }

            $currentPage = $currentPage + 1;
            // get all tracking by customer with pagination
            $data = CustomerEmployee::where("isActive", 1)->where("customer_id", $customerId)->get();

            // extract info
            $result = CustomerEmployeeDTO::parse($data);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal(0);
            $this->response->setRecordsFiltered(0);
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



    public function summary(){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $cause = $this->request->get("cause", "");
        $year = $this->request->get("year", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            //Si es un usuario de un cliente
            $user = $this->user();

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser" ) {
                if ($user->company != $customerId) {
                    $customerId = -1;
                }
            }

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getSummaryDisability($length, $currentPage, $customerId, $cause, $year);
            $total = $this->service->getSummaryDisabilityCount($customerId, $cause, $year);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
            $this->response->setRecordsTotal($total);
            $this->response->setRecordsFiltered($total);
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

    public function summaryReport()
    {
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", "0");
        $cause = $this->request->get("cause", "0");

        try {
            $colorPrg1 = $this->getRandomColor();

            $resultLine = $this->service->getSummaryDisabilityReport($customerId, $year, $cause);
            $resultYear = $this->service->getSummaryDisabilityReportYears($customerId);

            $programs = [
                "result" => [
                    "labels" => ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
                    "datasets" => [
                        [
                            "label" => "Variacion mensual",
                            "fillColor" => array("r" => "151", "g" => "187","b" => "205"),
                            "strokeColor" => array("r" => "151", "g" => "187","b" => "205"),
                            "highlightFill" => array("r" => "151", "g" => "187","b" => "205"),
                            "highlightStroke" => $colorPrg1,
                            "data" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                        ]
                    ]
                ]
            ];

            if (!empty($resultLine)) {
                $programs = null;
                $programs = [
                    "result" => [
                        "labels" => ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
                        "datasets" => [
                            [
                                "label" => "Variacion mensual",
                                "fillColor" => array("r" => "151", "g" => "187","b" => "205"),
                                "strokeColor" => array("r" => "151", "g" => "187","b" => "205"),
                                "highlightFill" => array("r" => "151", "g" => "187","b" => "205"),
                                "highlightStroke" => $colorPrg1,
                                "data" => [$resultLine[0]->Enero, $resultLine[0]->Febrero, $resultLine[0]->Marzo
                                    , $resultLine[0]->Abril, $resultLine[0]->Mayo, $resultLine[0]->Junio
                                    , $resultLine[0]->Julio, $resultLine[0]->Agosto, $resultLine[0]->Septiembre
                                    , $resultLine[0]->Octubre, $resultLine[0]->Noviembre, $resultLine[0]->Diciembre]
                            ]
                        ]
                    ]
                ];
            }


            $result = array();

            ////Log::info($programs);
            // extract info
            $result["report_contribution"] = CustomerDto::parse($programs, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["report_years"] = CustomerDto::parse($resultYear, "3"); // 2 = Prepara la respuesta para la grafica de barras

            // set count total ideas
            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    private function getRandomColor()
    {
        return RandomColor::one(array(
            'luminosity' => 'bright',
            'hue' => 'green',  // red, orange, yellow, green, blue, purple, pink, monochrome
            'format' => 'rgb' // e.g. 'rgb(225,200,20)'
        ));
    }

    public function save() {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            // Parse to model

            $model = CustomerAbsenteeismDisabilityDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerAbsenteeismDisabilityDTO::parse($model);

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

    public function update() {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            if (isset($info->disabilities)) {
                foreach ($info->disabilities as $disability) {
                    $model = CustomerAbsenteeismDisabilityDTO::fillAndUpdateModel($disability);
                }
            }

            // Parse to model


            // Parse to send on response
            $result = CustomerAbsenteeismDisabilityDTO::parse($model);

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
        $traking = $this->request->get("id", "0");

        try {

            //Log::info("customer [" . $traking . "]s::");

            if (!($model = CustomerAbsenteeismDisability::find($traking))) {
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

    public function get() {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerAbsenteeismDisability::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerAbsenteeismDisabilityDTO::parse($model);

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

    public function upload() {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            // Parse to model

            $model = CustomerAbsenteeismDisabilityDocumentDTO ::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerAbsenteeismDisabilityDocumentDTO::parse($model);

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

    /**
     *  PRIVATED METHODS
     */

    /**
     * Returns the logged in user, if available
     */
    private function user() {
        if (!Auth::check())
            return null;


        return Auth::getUser();
    }

    private function getTokenSession($encode = false) {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }

    public function loadLocaleFromSession() {

        if ($sessionLocale = $this->getSessionLocale()) {
            return $sessionLocale;
        } else {
            if ($localeNegotiated = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $localeNegotiated = substr($localeNegotiated, 0, 2);
                return $localeNegotiated;
            }
        }
    }

    protected function getSessionLocale() {
        if (!Session::has(self::SESSION_LOCALE))
            return null;

        return Session::get(self::SESSION_LOCALE);
    }

    // Metdos pilotos
    private function random_numbers($digits) {
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;
        return mt_rand($min, $max);
    }

    private function download_file($url, $path) {

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

    function debug($message, $param = null) {
        if (!$param) {
            //Log::info($message);
        } else if (is_array($param)) {
            //Log::info(vsprintf($message, $param));
        } else {
            //Log::info(sprintf($message, $param));
        }
    }
}
