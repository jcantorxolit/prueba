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
use Wgroup\CustomerOccupationalReportAl\CustomerOccupationalReport;
use Wgroup\CustomerOccupationalReportAl\CustomerOccupationalReportDTO;
use Wgroup\CustomerOccupationalReportAl\CustomerOccupationalReportService;
use Wgroup\Models\CustomerDto;
use PDF;
use Excel;
use Barryvdh\Snappy\Facades\SnappyPdf as SnappyPdf;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Helpers\CmsHelper;
use Illuminate\Support\Facades\Config;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerOccupationalReportALController extends BaseController {

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
        $this->service = new CustomerOccupationalReportService();
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

            //Si es un usuario de un cliente
            $user = $this->user();
            $isCustomer = false;

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser" ) {
                $isCustomer = true;
                if ($user->company != $customerId) {
                    $customerId = -1;
                }
            }


            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            //$data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $customerId);
            $data = $this->service->getOccupationalReportDataByCustomer(@$search['value'], $length, $currentPage, $customerId);

            // Counts
            $recordsTotal = $this->service->getOccupationalReportDataByCustomerCount("", $customerId);
            $recordsFiltered = $this->service->getOccupationalReportDataByCustomerCount(@$search['value'], $customerId);

            // extract info
            //$result = CustomerOccupationalReportDTO::parse($data);

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

    public function getReport(){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", "0");
        $workCenter = $this->request->get("workCenter", "");
        $classification = $this->request->get("classification", "");
        $reportName = $this->request->get("name", "");

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

            switch ($reportName) {
                case "eventNumber":
                    $data = $this->service->getEventNumberReport($customerId, $year, $workCenter, $classification);
                    break;
                case "disabilityDays":
                    $data = $this->service->getDisabilityDaysReport($customerId, $year, $workCenter, $classification);
                    break;
                case "IF":
                    $data = $this->service->getIFReport($customerId, $year, $workCenter, $classification);
                    break;
                case "IS":
                    $data = $this->service->getISReport($customerId, $year, $workCenter, $classification);
                    break;
                case "ILI":
                    $data = $this->service->getILIReport($customerId, $year, $workCenter, $classification);
                    break;
                default:
                    $data = $this->service->getEventNumberReport($customerId, $year, $workCenter, $classification);
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

        try {

            $resultPieAccidentType = $this->service->getDashboardPieAccidentType($customerId, $year);
            $resultPieDeathCause = $this->service->getDashboardPieDeathCause($customerId, $year);
            $resultPieLocation = $this->service->getDashboardPieLocation($customerId, $year);
            $resultBarLink = $this->service->getDashboardBarLink($customerId, $year);
            $resultBarWorkTime = $this->service->getDashboardWorkTime($customerId, $year);
            $resultBarWeekDay = $this->service->getDashboardWeekDay($customerId, $year);
            $resultBarPlace = $this->service->getDashboardPlace($customerId, $year);
            $resultBarLesion = $this->service->getDashboardLesion($customerId, $year);
            $resultBarBody = $this->service->getDashboardBody($customerId, $year);
            $resultBarFactor = $this->service->getDashboardFactor($customerId, $year);

            $colors[] = "#5cb85c";
            $colors[] = "#e0d653";
            $colors[] = "#F7464A";
            $colors[] = "#46BFBD";
            $colors[] = "#46BEBE";
            $colors[] = "#5cb855";

            $colors2[] = "#5cb855";
            $colors2[] = "#F7464A";
            $colors2[] = "#46BFBD";
            $colors2[] = "#5cb85c";
            $colors2[] = "#e0d653";
            $colors2[] = "#46BEBE";

            if (!empty($resultPieAccidentType)) {

                $index = 0;
                foreach ($resultPieAccidentType as $record) {
                    if ($index  == count($colors)) {
                        $index = 0;
                    }
                    $record->color = $colors[$index];
                    $record->value = (int) $record->value;
                    $index++;
                }
            }

            if (!empty($resultPieDeathCause)) {

                $index = 0;
                foreach ($resultPieDeathCause as $record) {
                    if ($index  == count($colors)) {
                        $index = 0;
                    }
                    $record->color = $colors[$index];
                    $record->value = (int) $record->value;
                    $index++;
                }
            }

            if (!empty($resultPieLocation)) {

                $index = 0;
                foreach ($resultPieLocation as $record) {
                    if ($index  == count($colors)) {
                        $index = 0;
                    }
                    $record->color = $colors[$index];
                    $record->value = (int) $record->value;
                    $index++;
                }
            }

            if (!empty($resultBarLink)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartProgramDataSet = array();

                $index = 0;
                foreach ($resultBarLink as $line) {
                    if ($index  == count($colors)) {
                        $index = 0;
                    }
                    $lineChartProgramDataSet[] = array(
                        "label" => $line->abbreviation,
                        "fillColor" => $this->hex2rgba($colors[$index], 0.5),
                        "strokeColor" => $this->hex2rgba($colors[$index], 1),
                        "pointColor" => $this->hex2rgba($colors[$index], 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                    $index++;
                }

                $lineChartProgram = array();

                $lineChartProgram["labels"] = $label;
                $lineChartProgram["datasets"] = $lineChartProgramDataSet;
            } else {
                $lineChartProgram = array();
            }

            if (!empty($resultBarWorkTime)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartWorkTimeDataSet = array();

                $index = 0;
                foreach ($resultBarWorkTime as $line) {
                    if ($index  == count($colors)) {
                        $index = 0;
                    }
                    $lineChartWorkTimeDataSet[] = array(
                        "label" => $line->abbreviation,
                        "fillColor" => $this->hex2rgba($colors2[$index], 0.5),
                        "strokeColor" => $this->hex2rgba($colors2[$index], 1),
                        "pointColor" => $this->hex2rgba($colors2[$index], 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                    $index++;
                }

                $lineChartWorkTime = array();

                $lineChartWorkTime["labels"] = $label;
                $lineChartWorkTime["datasets"] = $lineChartWorkTimeDataSet;
            } else {
                $lineChartWorkTime = array();
            }

            if (!empty($resultBarWeekDay)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartWeekDayDataSet = array();

                $index = 0;
                foreach ($resultBarWeekDay as $line) {
                    if ($index  == count($colors)) {
                        $index = 0;
                    }
                    $lineChartWeekDayDataSet[] = array(
                        "label" => $line->abbreviation,
                        "fillColor" => $this->hex2rgba($colors[$index], 0.5),
                        "strokeColor" => $this->hex2rgba($colors[$index], 1),
                        "pointColor" => $this->hex2rgba($colors[$index], 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                    $index++;
                }

                $lineChartWeekDay = array();

                $lineChartWeekDay["labels"] = $label;
                $lineChartWeekDay["datasets"] = $lineChartWeekDayDataSet;
            } else {
                $lineChartWeekDay = array();
            }

            if (!empty($resultBarPlace)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartPlaceDataSet = array();

                $index = 0;
                foreach ($resultBarPlace as $line) {
                    if ($index  == count($colors)) {
                        $index = 0;
                    }
                    $lineChartPlaceDataSet[] = array(
                        "label" => $line->abbreviation,
                        "fillColor" => $this->hex2rgba($colors2[$index], 0.5),
                        "strokeColor" => $this->hex2rgba($colors2[$index], 1),
                        "pointColor" => $this->hex2rgba($colors2[$index], 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                    $index++;
                }

                $lineChartPlace = array();

                $lineChartPlace["labels"] = $label;
                $lineChartPlace["datasets"] = $lineChartPlaceDataSet;
            } else {
                $lineChartPlace = array();
            }

            if (!empty($resultBarLesion)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartLesionDataSet = array();

                $index = 0;
                foreach ($resultBarLesion as $line) {
                    if ($index  == count($colors)) {
                        $index = 0;
                    }
                    $lineChartLesionDataSet[] = array(
                        "label" => $line->abbreviation,
                        "fillColor" => $this->hex2rgba($colors[$index], 0.5),
                        "strokeColor" => $this->hex2rgba($colors[$index], 1),
                        "pointColor" => $this->hex2rgba($colors[$index], 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                    $index++;
                }

                $lineChartLesion = array();

                $lineChartLesion["labels"] = $label;
                $lineChartLesion["datasets"] = $lineChartLesionDataSet;
            } else {
                $lineChartLesion = array();
            }

            if (!empty($resultBarBody)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartBodyDataSet = array();
                $index = 0;
                foreach ($resultBarBody as $line) {
                    if ($index  == count($colors)) {
                        $index = 0;
                    }
                    $lineChartBodyDataSet[] = array(
                        "label" => $line->abbreviation,
                        "fillColor" => $this->hex2rgba($colors2[$index], 0.5),
                        "strokeColor" => $this->hex2rgba($colors2[$index], 1),
                        "pointColor" => $this->hex2rgba($colors2[$index], 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );
                    $index++;
                }

                $lineChartBody = array();

                $lineChartBody["labels"] = $label;
                $lineChartBody["datasets"] = $lineChartBodyDataSet;
            } else {
                $lineChartBody = array();
            }

            if (!empty($resultBarFactor)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartFactorDataSet = array();
                $index = 0;
                foreach ($resultBarFactor as $line) {

                    if ($index  == count($colors)) {
                        $index = 0;
                    }

                    $lineChartFactorDataSet[] = array(
                        "label" => $line->abbreviation,
                        "fillColor" => $this->hex2rgba($colors[$index], 0.5),
                        "strokeColor" => $this->hex2rgba($colors[$index], 1),
                        "pointColor" => $this->hex2rgba($colors[$index], 1),
                        "pointStrokeColor" => '#fff',
                        "pointHighlightFill" => '#fff',
                        "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                        "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                    );

                    $index++;
                }

                $lineChartFactor = array();

                $lineChartFactor["labels"] = $label;
                $lineChartFactor["datasets"] = $lineChartFactorDataSet;
            } else {
                $lineChartFactor = array();
            }

            $result = array();

            ////Log::info($programs);
            // extract info
            $result["dataPieAccidentType"] = $resultPieAccidentType;
            $result["dataPieDeathCause"] = $resultPieDeathCause;
            $result["dataPieLocation"] = $resultPieLocation;
            $result["dataBarLink"] = $lineChartProgram;
            $result["dataPieWorkTIme"] = $lineChartWorkTime;
            $result["dataBarWeekDay"] = $lineChartWeekDay;
            $result["dataBarPlace"] = $lineChartPlace;
            $result["dataBarLesionType"] = $lineChartLesion;
            $result["dataBarBody"] = $lineChartBody;
            $result["dataBarFactor"] = $lineChartFactor;


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

    private function hex2rgba($color, $opacity = false) {

        $default = 'rgb(0,0,0)';

        //Return default if no color provided
        if(empty($color))
            return $default;

        //Sanitize $color if "#" is provided
        if ($color[0] == '#' ) {
            $color = substr( $color, 1 );
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
            $hex = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
        } elseif ( strlen( $color ) == 3 ) {
            $hex = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
        } else {
            return $default;
        }

        //Convert hexadec to rgb
        $rgb =  array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if($opacity){
            if(abs($opacity) > 1)
                $opacity = 1.0;
            $output = 'rgba('.implode(",",$rgb).','.$opacity.')';
        } else {
            $output = 'rgb('.implode(",",$rgb).')';
        }

        //Return rgb(a) color string
        return $output;
    }


    public function summaryByLesion()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $data = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {
            if ($data != "") {
                $json = base64_decode($data);

                //Log::info($json);

                $audit = json_decode($json);
            } else {
                $audit = null;
            }
            // get all tracking by customer with pagination
            $data = $this->service->getAllSummaryByLesion($audit->customerId, $audit->year);

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

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

    public function summaryByLesionExport()
    {
        $customerId = $this->request->get("id", "0");
        $data = $this->request->get("data", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $result = $this->service->getAllSummaryByLesionExport($audit->customerId, $audit->year);


            Excel::create('Resumen_Lesiones_REPORTE_AL', function($excel) use($result) {

                // Set the title
                $excel->setCreator('sylogic')
                    ->setCompany('waygroup');

                $excel->sheet('PFR', function($sheet) use($result) {

                    $resultArray = json_decode(json_encode($result), true);

                    $sheet->fromArray($resultArray, null, 'A1', true, true);
                });
            })->export('xlsx');

        } catch (Exception $exc) {


            // Log the full exception
            var_dump($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function summaryByIndicator()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $data = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {
            if ($data != "") {
                $json = base64_decode($data);

                //Log::info($json);

                $audit = json_decode($json);
            } else {
                $audit = null;
            }
            // get all tracking by customer with pagination
            $data = $this->service->getAllSummaryByLesion($audit->customerId, $audit->year);

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

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

            $model = CustomerOccupationalReportDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerOccupationalReportDTO::parse($model);

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

            if (!($model = CustomerOccupationalReport::find($traking))) {
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

            if (!($model = CustomerOccupationalReport::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerOccupationalReportDTO::parse($model);

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


    public function download()
    {

        $id = $this->request->get("id", "");

        try {
/*
            $data = array("participant" => 'David Blandon');

            $pdf = PDF::loadView("aden.pdf::html.reportal", $data)->setWarnings(false);
            return  $pdf->download();
*/


            $report = $this->service->getOccupationalReportData($id);
            $lesion = $this->convertToArray($this->service->getOccupationalReportLesionData($id));
            $body = $this->convertToArray($this->service->getOccupationalReportBodyData($id));
            $factor = $this->convertToArray($this->service->getOccupationalReportFactorData($id));
            $mechanism = $this->convertToArray($this->service->getOccupationalReportMechanismData($id));
            $witness = $this->convertWitnessToArray($this->service->getOccupationalReportWitnessData($id));

            $report = (array)($report[0]);

            $model = SystemParameter::find($report['arl_id']);

            if ($model && \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo)) {
                $fileInstance = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo);
				$report['arl_url'] = $fileInstance && $fileInstance->exists() ? $fileInstance->path : '';
            } else {
				$report['arl_url'] = '';
			}


            $report['themeUrl'] = CmsHelper::getThemeUrl();
            $report['themePath'] = CmsHelper::getThemePath();

            $data = array_merge ( $report,  $lesion, $body, $factor, $mechanism, $witness );

            $pdf = SnappyPdf::loadView("aden.pdf::html.reportal", $data)->setPaper('legal')->setOrientation('portrait')->setWarnings(false);
            return  $pdf->download('Reporte_AL.pdf');

        } catch (Exception $exc) {
            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

    }

    public function preview()
    {

        $id = $this->request->get("id", "");

        try {
/*
            if (!($participant = CertificateGradeParticipant::find($id))) {
                throw new Exception("CertificateGrade not found to delete.");
            }*/

            $report = $this->service->getOccupationalReportData($id);
            $lesion = $this->convertToArray($this->service->getOccupationalReportLesionData($id));
            $body = $this->convertToArray($this->service->getOccupationalReportBodyData($id));
            $factor = $this->convertToArray($this->service->getOccupationalReportFactorData($id));
            $mechanism = $this->convertToArray($this->service->getOccupationalReportMechanismData($id));
            $witness = $this->convertWitnessToArray($this->service->getOccupationalReportWitnessData($id));

            $report = (array)($report[0]);

            $model = SystemParameter::find($report['arl_id']);

            if ($model && \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo)) {
                $fileInstance = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo);
				$report['arl_url'] = $fileInstance && $fileInstance->exists() ? $fileInstance->path : '';
            } else {
				$report['arl_url'] = '';
			}

            $report['themeUrl'] = CmsHelper::getThemeUrl();
            $report['themePath'] = CmsHelper::getThemePath();

            $data = array_merge ( $report,  $lesion, $body, $factor, $mechanism, $witness );

            $pdf = SnappyPdf::loadView("aden.pdf::html.reportal", $data)->setPaper('legal')->setOrientation('portrait')->setWarnings(false);
            return  $pdf->stream();

            //return SnappyPdf::loadFile('http://www.github.com')->stream('github.pdf');

        } catch (Exception $exc) {

            // Log the full exception
            var_dump($exc->getMessage());
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

    }

    public function getYearFilter()
    {
        $customerId = $this->request->get("customer_id", "0");

        try {

            // get all tracking by customer with pagination
            $data = $this->service->getYearFilter($customerId);

            $result["data"] = $data;

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

    private function convertToArray($data)
    {
        $result = array();

        foreach ($data as $record) {
            $result[$record->value] = $record->selected;
        }

        return $result;
    }

    private function convertWitnessToArray($data)
    {
        $result = array();
        $index = 1;
        foreach ($data as $record) {
            $result["witness_name_".$index] = $record->witness_name;
            $result["witness_document_type_".$index] = $record->witness_document_type;
            $result["witness_document_number_".$index] = $record->witness_document_number;
            $result["witness_job_".$index] = $record->witness_job;
            $index++;
        }

        return $result;
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
