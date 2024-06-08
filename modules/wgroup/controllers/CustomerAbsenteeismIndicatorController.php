<?php

namespace Wgroup\Controllers;

use Carbon\Carbon;
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
use Wgroup\CustomerAbsenteeismIndicator\CustomerAbsenteeismIndicator;
use Wgroup\CustomerAbsenteeismIndicator\CustomerAbsenteeismIndicatorDTO;
use Wgroup\CustomerAbsenteeismIndicator\CustomerAbsenteeismIndicatorService;
use Wgroup\Models\CustomerDto;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerAbsenteeismIndicatorController extends BaseController {

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

    public function __construct() {

        //set service
        $this->service = new CustomerAbsenteeismIndicatorService();
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


    public function index(){

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


        // get all tracking by customer with pagination
        $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $customerId);

        // Counts
        $recordsTotal = $this->service->getCount("", $customerId);
        $recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

        // extract info
        $result = CustomerAbsenteeismIndicatorDTO::parse($data);

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

    public function summary(){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $cause = $this->request->get("cause", "");

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
            $data = $this->service->getSummaryDisability($length, $currentPage, $customerId, $cause);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
            $this->response->setRecordsTotal(count($data));
            $this->response->setRecordsFiltered(count($data));
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

    public function indicators()
    {
        $indicatorId = $this->request->get("indicator_id", "0");

        try {

            $data = $this->service->getIndicators($indicatorId);

            // set count total ideas
            $this->response->setDraw(1);
            $this->response->setData($data);
            $this->response->setRecordsTotal(count($data));
            $this->response->setRecordsFiltered(count($data));
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

    public function getReport(){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", "0");
        $workCenter = $this->request->get("workCenter", "");
        $classification = $this->request->get("classification", "");
        $reportName = $this->request->get("name", "");
        $resolution = $this->request->get("resolution", "1111");

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
                    //$customerId = -1;
                }
            }

            switch ($reportName) {
                case "eventNumber":
                    $data = $this->service->getEventNumberReport($customerId, $year, $workCenter, $classification, $resolution);
                    break;
                case "disabilityDays":
                    $data = $this->service->getDisabilityDaysReport($customerId, $year, $workCenter, $classification, $resolution);
                    break;
                case "IF":
                    $data = $this->service->getIFReport($customerId, $year, $workCenter, $classification, $resolution);
                    break;
                case "IS":
                    $data = $this->service->getISReport($customerId, $year, $workCenter, $classification, $resolution);
                    break;
                case "ILI":
                    $data = $this->service->getILIReport($customerId, $year, $workCenter, $classification, $resolution);
                    break;
                default:
                    $data = $this->service->getEventNumberReport($customerId, $year, $workCenter, $classification, $resolution);
            }

            // extract info
            $result = $data;

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal(count($data));
            $this->response->setRecordsFiltered(count($data));
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

    public function getCharts()
    {
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", "0");
        $workCenter = $this->request->get("workCenter", "");
        $classification = $this->request->get("classification", "");

        $year = $year == "" ? Carbon::now('America/Bogota')->year : $year;

        try {

            $resultEventNumber = $this->service->getEventNumberChart($customerId, $year, $workCenter, $classification);
            $resultDisabilityDays = $this->service->getDisabilityDaysChart($customerId, $year, $workCenter, $classification);
            $resultIF = $this->service->getIFChart($customerId, $year, $workCenter, $classification);
            $resultIS = $this->service->getISChart($customerId, $year, $workCenter, $classification);
            $resultILI = $this->service->getILIChart($customerId, $year, $workCenter, $classification);

            if (!empty($resultEventNumber)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartProgramDataSet = array();

                foreach ($resultEventNumber as $line) {
//218,79,74,0.2
                    $lineChartProgramDataSet[] = array(
                        "label" => $line->label,
                        "fillColor" => $this->hex2rgba($line->color, 0.1),
                        "strokeColor" => $this->hex2rgba($line->color, 1),
                        "pointColor" => $this->hex2rgba($line->color, 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                }

                $lineChartProgram = array();

                $lineChartProgram["labels"] = $label;
                $lineChartProgram["datasets"] = $lineChartProgramDataSet;
            } else {
                $lineChartProgram = array();
            }

            if (!empty($resultDisabilityDays)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartProgramDataSet = array();

                foreach ($resultDisabilityDays as $line) {
//218,79,74,0.2
                    $lineChartProgramDataSet[] = array(
                        "label" => $line->label,
                        "fillColor" => $this->hex2rgba($line->color, 0.1),
                        "strokeColor" => $this->hex2rgba($line->color, 1),
                        "pointColor" => $this->hex2rgba($line->color, 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                }

                $chartDisabilityDays = array();

                $chartDisabilityDays["labels"] = $label;
                $chartDisabilityDays["datasets"] = $lineChartProgramDataSet;
            } else {
                $chartDisabilityDays = array();
            }

            if (!empty($resultIF)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartProgramDataSet = array();

                foreach ($resultIF as $line) {
//218,79,74,0.2
                    $lineChartProgramDataSet[] = array(
                        "label" => $line->label,
                        "fillColor" => $this->hex2rgba($line->color, 0.1),
                        "strokeColor" => $this->hex2rgba($line->color, 1),
                        "pointColor" => $this->hex2rgba($line->color, 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                }

                $chartIF = array();

                $chartIF["labels"] = $label;
                $chartIF["datasets"] = $lineChartProgramDataSet;
            } else {
                $chartIF = array();
            }

            if (!empty($resultIS)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartProgramDataSet = array();

                foreach ($resultIS as $line) {
//218,79,74,0.2
                    $lineChartProgramDataSet[] = array(
                        "label" => $line->label,
                        "fillColor" => $this->hex2rgba($line->color, 0.1),
                        "strokeColor" => $this->hex2rgba($line->color, 1),
                        "pointColor" => $this->hex2rgba($line->color, 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                }

                $chartIS = array();

                $chartIS["labels"] = $label;
                $chartIS["datasets"] = $lineChartProgramDataSet;
            } else {
                $chartIS = array();
            }

            if (!empty($resultILI)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartProgramDataSet = array();

                foreach ($resultILI as $line) {
//218,79,74,0.2
                    $lineChartProgramDataSet[] = array(
                        "label" => $line->label,
                        "fillColor" => $this->hex2rgba($line->color, 0.1),
                        "strokeColor" => $this->hex2rgba($line->color, 1),
                        "pointColor" => $this->hex2rgba($line->color, 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                }

                $chartILI = array();

                $chartILI["labels"] = $label;
                $chartILI["datasets"] = $lineChartProgramDataSet;
            } else {
                $chartILI = array();
            }

            //$chartEventNumber = $this->getChart("No EVENTOS POR EG / AL", $resultEventNumber);
            //$chartDisabilityDays = $this->getChart(" DIAS DE INCAPACIDAD POR EG / AL", $resultDisabilityDays);
            //$chartIF = $this->getChart("Indice de frecuencia (IF)", $resultIF);
            //$chartIS = $this->getChart("Indice de severidad (IS)", $resultIS);
            //$chartILI = $this->getChart("Indice de ILI", $resultILI);

            $result = array();

            ////Log::info($programs);
            // extract info
            $result["chart_eventNumber"] = $lineChartProgram;//CustomerDto::parse($chartEventNumber, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["chart_disabilityDays"] = $chartDisabilityDays;//CustomerDto::parse($chartDisabilityDays, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["chart_IF"] = $chartIF;//CustomerDto::parse($chartIF, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["chart_IS"] = $chartIS;//CustomerDto::parse($chartIS, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["chart_ILI"] = $chartILI;//CustomerDto::parse($chartILI, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras


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

    private function hex2rgba($color, $opacity = false)
    {

        $default = 'rgb(0,0,0)';

        //Return default if no color provided
        if (empty($color))
            return $default;

        //Sanitize $color if "#" is provided
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $default;
        }

        //Convert hexadec to rgb
        $rgb = array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if ($opacity) {
            if (abs($opacity) > 1)
                $opacity = 1.0;
            $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(",", $rgb) . ')';
        }

        //Return rgb(a) color string
        return $output;
    }

    public function getWorkPlaces()
    {
        $customerId = $this->request->get("customer_id", "0");

        try {

            $resultLine = $this->service->getSummaryWorkCenter($customerId);

            // set count total ideas
            $this->response->setResult($resultLine);

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

    private function getChart($labelTitle, $result)
    {

        $colorPrg1 = $this->getRandomColor();

        $programs = [
            "result" => [
                "labels" => ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre", "Total"],
                "datasets" => [
                    [
                        "label" => $labelTitle,
                        "fillColor" => array("r" => "151", "g" => "187","b" => "205"),
                        "strokeColor" => array("r" => "151", "g" => "187","b" => "205"),
                        "highlightFill" => array("r" => "151", "g" => "187","b" => "205"),
                        "highlightStroke" => $colorPrg1,
                        "data" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                    ]
                ]
            ]
        ];

        if (!empty($result)) {
            $programs = null;
            $programs = [
                "result" => [
                    "labels" => ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre", "Total"],
                    "datasets" => [
                        [
                            "label" => $labelTitle,
                            "fillColor" => array("r" => "151", "g" => "187","b" => "205"),
                            "strokeColor" => array("r" => "151", "g" => "187","b" => "205"),
                            "highlightFill" => array("r" => "151", "g" => "187","b" => "205"),
                            "highlightStroke" => $colorPrg1,
                            "data" => [$result[0]->Enero, $result[0]->Febrero, $result[0]->Marzo
                                , $result[0]->Abril, $result[0]->Mayo, $result[0]->Junio
                                , $result[0]->Julio, $result[0]->Agosto, $result[0]->Septiembre
                                , $result[0]->Octubre, $result[0]->Noviembre, $result[0]->Diciembre, $result[0]->Total]
                        ]
                    ]
                ]
            ];
        }

        return $programs;
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

            $model = CustomerAbsenteeismIndicatorDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerAbsenteeismIndicatorDTO::parse($model);

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

            if (!($model = CustomerAbsenteeismIndicator::find($traking))) {
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

    public function consolidate()
    {

        // Preapre parameters for query
        $customerId = $this->request->get("id", "0");

        try {
            $userId = $this->user()->id;
            $this->service->consolidate($customerId, $userId);
            $this->response->setResult(1);
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

            if (!($model = CustomerAbsenteeismIndicator::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerAbsenteeismIndicatorDTO::parse($model);

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
