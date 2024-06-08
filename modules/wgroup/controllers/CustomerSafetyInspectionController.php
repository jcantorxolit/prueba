<?php

namespace Wgroup\Controllers;

use Controller as BaseController;
use Excel;
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
use Wgroup\CustomerSafetyInspection\CustomerSafetyInspection;
use Wgroup\CustomerSafetyInspection\CustomerSafetyInspectionDTO;
use Wgroup\CustomerSafetyInspection\CustomerSafetyInspectionService;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerSafetyInspectionController extends BaseController
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
        $this->service = new CustomerSafetyInspectionService();
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

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                $isCustomer = true;
                if ($user->company != $customerId) {
                    $customerId = -1;
                }
            }


            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $customerId);

            // Counts
            $recordsTotal = $this->service->getCount("", $customerId);
            $recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerSafetyInspectionDTO::parse($data);

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

    public function summaryIndex()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customerId", "0");
        $safetyInspectionId = $this->request->get("safetyInspectionId", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $this->service->insertHeaderFields($safetyInspectionId, $this->user->id);
            $this->service->insertListItem($safetyInspectionId, $this->user->id);

            // get all tracking by customer with pagination
            $data = $this->service->getAllSummaryBy($orders, $safetyInspectionId);

            $recordsTotal = count($data);
            $recordsFiltered = count($data);

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

    public function actionReport()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customerId", "0");
        $safetyInspectionId = $this->request->get("safetyInspectionId", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {


            // get all tracking by customer with pagination
            $data = $this->service->getAllSummaryBy($orders, $safetyInspectionId);

            $recordsTotal = count($data);
            $recordsFiltered = count($data);

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

    public function activityReport()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customerId", "0");
        $safetyInspectionId = $this->request->get("safetyInspectionId", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            // get all tracking by customer with pagination
            $data = $this->service->getAllSummaryBy($orders, $safetyInspectionId);

            $recordsTotal = count($data);
            $recordsFiltered = count($data);

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

    public function chart()
    {
        $safetyInspectionId = $this->request->get("safetyInspectionId", "0");
        $customerId = $this->request->get("customerId", "0");

        try {

            // Validate permissions
            /*if (!UserGroup::hasRole('admin')) {
                throw new Exception(Message::trans("messages.error.notauthorized", array()));
            }*/

            $colorPrg1 = $this->getRandomColor();
            $colorPrg2 = $this->getRandomColor();
            $colorPrg3 = $this->getRandomColor();
            $colorPrg4 = $this->getRandomColor();
            $colorPrg5 = $this->getRandomColor();
            $colorPrg6 = $this->getRandomColor();

            // Aqui se debe hacer la consulta a db para programas


            $colors[] = "#5cb85c";
            $colors[] = "#e0d653";
            $colors[] = "#F7464A";
            $colors[] = "#46BFBD";
            $colors[] = "#46BEBE";
            $colors[] = "#5cb855";

            $colors[] = "#5cb85c";
            $colors[] = "#e0d653";
            $colors[] = "#F7464A";
            $colors[] = "#46BFBD";
            $colors[] = "#46BEBE";
            $colors[] = "#5cb855";

            // Aqui se debe hacer la otra consulta para reportes
            /* $resultBar = $this->service->getDashboardBarMonthly($diagnosticId, $year);
             $resultProgramLine = $this->service->getDashboardProgramLineMonthly($diagnosticId, $year);
             $resultTotalLine = $this->service->getDashboardTotalLineMonthly($diagnosticId, $year);
             $resultAvgLine = $this->service->getDashboardAvgLineMonthly($diagnosticId, $year);

             $programs = [
                 "result" => [
                     "labels" => ["ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC"],
                     "datasets" => [
                         [
                             "label" => "Cumple",
                             "fillColor" => array("r" => "70", "g" => "191","b" => "189"),
                             "strokeColor" => $colorPrg1,
                             "highlightFill" => $colorPrg1,
                             "highlightStroke" => $colorPrg1,
                             "data" => [0, 0, 0, 0, 0, 0],
                         ],
                         [
                             "label" => "Cumple Pacial",
                             "fillColor" => array("r" => "224", "g" => "214","b" => "83"),
                             "strokeColor" => $colorPrg2,
                             "highlightFill" => $colorPrg2,
                             "highlightStroke" => $colorPrg2,
                             "data" => [0, 0, 0, 0, 0, 0],
                         ],
                         [
                             "label" => "No Cumple",
                             "fillColor" => array("r" => "247", "g" => "70","b" => "74"),
                             "strokeColor" => $colorPrg3,
                             "highlightFill" => $colorPrg3,
                             "highlightStroke" => $colorPrg3,
                             "data" => [0, 0, 0, 0, 0, 0],
                         ],
                         [
                             "label" => "No Aplica",
                             "fillColor" => array("r" => "92", "g" => "184","b" => "85"),
                             "strokeColor" => $colorPrg4,
                             "highlightFill" => $colorPrg4,
                             "highlightStroke" => $colorPrg4,
                             "data" => [0, 0, 0, 0, 0, 0],
                         ],
                         [
                             "label" => "Sin Contestar",
                             "fillColor" => array("r" => "245", "g" => "130","b" => "32"),
                             "strokeColor" => $colorPrg5,
                             "highlightFill" => $colorPrg5,
                             "highlightStroke" => $colorPrg5,
                             "data" => [0, 0, 0, 0, 0, 0],
                         ]
                     ]
                 ]
             ];

             if (!empty($resultBar)) {
                 $label = array();
                 $cumple = array();
                 $parcial = array();
                 $noCumple = array();
                 $noAplica = array();
                 $noContesta = array();

                 foreach ($resultBar as $bar) {
                     $label[] = $bar->name;
                     $cumple[] = $bar->accomplish;
                     $parcial[] = $bar->partial_accomplish;
                     $noCumple[] = $bar->no_accomplish;
                     $noAplica[] = $bar->no_apply;
                     $noContesta[] = $bar->no_answer;
                 }

                 $programs = null;
                 $programs = [
                     "result" => [
                         "labels" => $label,
                         "datasets" => [
                             [
                                 "label" => "Cumple",
                                 "fillColor" => array("r" => "70", "g" => "191","b" => "189"),
                                 "strokeColor" => $colorPrg1,
                                 "highlightFill" => $colorPrg1,
                                 "highlightStroke" => $colorPrg1,
                                 "data" => $cumple,
                             ],
                             [
                                 "label" => "Cumple Pacial",
                                 "fillColor" => array("r" => "224", "g" => "214","b" => "83"),
                                 "strokeColor" => $colorPrg2,
                                 "highlightFill" => $colorPrg2,
                                 "highlightStroke" => $colorPrg2,
                                 "data" => $parcial,
                             ],
                             [
                                 "label" => "No Cumple",
                                 "fillColor" => array("r" => "247", "g" => "70","b" => "74"),
                                 "strokeColor" => $colorPrg3,
                                 "highlightFill" => $colorPrg3,
                                 "highlightStroke" => $colorPrg3,
                                 "data" => $noCumple,
                             ],
                             [
                                 "label" => "No Aplica",
                                 "fillColor" => array("r" => "92", "g" => "184","b" => "85"),
                                 "strokeColor" => $colorPrg4,
                                 "highlightFill" => $colorPrg4,
                                 "highlightStroke" => $colorPrg4,
                                 "data" => $noAplica,
                             ],
                             [
                                 "label" => "Sin Contestar",
                                 "fillColor" => array("r" => "245", "g" => "130","b" => "32"),
                                 "strokeColor" => $colorPrg5,
                                 "highlightFill" => $colorPrg5,
                                 "highlightStroke" => $colorPrg5,
                                 "data" => $noContesta,
                             ]
                         ]
                     ]
                 ];
             }

             if (!empty($resultProgramLine)) {

                 $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                 $lineChartProgramDataSet = array();

                 foreach ($resultProgramLine as $line) {

                     $lineChartProgramDataSet[] = array(
                         "label" => $line->abbreviation,
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

             if (!empty($resultTotalLine)) {

                 $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                 $lineChartTotalDataSet = array();

                 foreach ($resultTotalLine as $line) {

                     $lineChartTotalDataSet[] = array(
                         "label" => $line->indicator,
                         "fillColor" => $this->hex2rgba("#3395FF", 0.2),
                         "strokeColor" => $this->hex2rgba("#3395FF", 1),
                         "pointColor" => $this->hex2rgba("#3395FF", 1),
                         "pointStrokeColor" => '#fff',
                         "pointHighlightFill" => '#fff',
                         "pointHighlightStroke" => $this->hex2rgba("#3395FF", 1),
                         "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                     );
                 }

                 $lineChartTotal = array();

                 $lineChartTotal["labels"] = $label;
                 $lineChartTotal["datasets"] = $lineChartTotalDataSet;
             } else {
                 $lineChartTotal = null;
             }

             if (!empty($resultAvgLine)) {

                 $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                 $lineChartAvgDataSet = array();

                 foreach ($resultAvgLine as $line) {

                     $lineChartAvgDataSet[] = array(
                         "label" => $line->indicator,
                         "fillColor" => $this->hex2rgba("#DA4F4A", 0.2),
                         "strokeColor" => $this->hex2rgba("#DA4F4A", 1),
                         "pointColor" => $this->hex2rgba("#DA4F4A", 1),
                         "pointStrokeColor" => '#fff',
                         "pointHighlightFill" => '#fff',
                         "pointHighlightStroke" => $this->hex2rgba("#DA4F4A", 1),
                         "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                     );
                 }

                 $lineChartAvg = array();

                 $lineChartAvg["labels"] = $label;
                 $lineChartAvg["datasets"] = $lineChartAvgDataSet;
             } else {
                 $lineChartAvg = null;
             }*/

            $result = array();


            // extract info
            $result["chartPieData"] = array();
            $result["chartBarData"] = array("datasets" => [], "labels" => []);
            $result["totalAvg"] = 0;

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

            $model = CustomerSafetyInspectionDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerSafetyInspectionDTO::parse($model);

            $this->response->setResult($result);

        } catch (Exception $exc) {
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

            if (!($model = CustomerSafetyInspection::find($id))) {
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

            if (!($model = CustomerSafetyInspection::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerSafetyInspectionDTO::parse($model);

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

    public function export()
    {

        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            $audit = json_decode($json);

            $contractorId = $audit->contract_id;
            $period = $audit->period;

            // get all tracking by customer with pagination
            $data = $this->service->getSummaryByPeriodExport($contractorId, $period);


            Excel::create('Contrato_Resumen', function ($excel) use ($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Resumen', function ($sheet) use ($data) {

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

    public function summaryExportExcel()
    {

        $id = $this->request->get("id", "");

        try {


            // get all tracking by customer with pagination
            $data = $this->service->getAllSummaryExport([], $id);


            Excel::create('Resumen_Gestión_Inspecciones_Seguridad', function ($excel) use ($data) {

                // Set the title
                $excel->setTitle('Resumen Gestión');

                // Chain the setters
                $excel->setCreator('Sylogi')
                    ->setCompany('Waygroup');



                $excel->sheet('Resumen', function ($sheet) use ($data) {

                    $resultArray = json_decode(json_encode($data), true);

                    $sheet->fromArray($resultArray, null, 'A1', true, true);

                });

            })->export('xlsx');

        } catch (Exception $exc) {

            // Log the full exception
            //Log::error($exc->getTraceAsString());
            var_dump($exc->getMessage());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

    }

    private function getRandomColor()
    {
        return RandomColor::one(array(
            'luminosity' => 'bright',
            'hue' => 'green',  // red, orange, yellow, green, blue, purple, pink, monochrome
            'format' => 'rgb' // e.g. 'rgb(225,200,20)'
        ));
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
