<?php

namespace Wgroup\Controllers;

use RainLab\User\Models\Country;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\RandomColor;
use Wgroup\Classes\ServiceApi;
use Wgroup\Classes\ServiceCustomerDiagnostic;
use Controller as BaseController;
use Exception;
use Log;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Models\Parameters;
use Wgroup\Classes\ServiceCustomerDiagnosticPrevention;
use Wgroup\Models\CustomerDiagnostic;
use Wgroup\Models\CustomerDiagnosticDTO;
use Wgroup\Models\State;
use PDF;
use Excel;
use PHPExcel;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerDiagnosticController extends BaseController
{

    const SESSION_LOCALE = 'rainlab.translate.locale';

    private $translate;
    private $service;
    private $servicePrevention;
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
        $this->service = new ServiceCustomerDiagnostic();
        $this->servicePrevention = new ServiceCustomerDiagnosticPrevention();
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
            /*
            if ($model = $this->serviceCustomer->getCustomerIdByUserGroup()) {
                if ($model->id != $customerId)
                    $customerId = -1;
            }
            */
            $user = $this->getUser();

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                if ($user->company != $customerId) {
                    // $customerId = -1;
                }
            }

            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $customerId);

            // Counts
            $recordsTotal = $this->service->getCount();
            $recordsFiltered = $this->service->getCount(@$search['value']);

            //var_dump($data);
            // extract info
            $result = CustomerDiagnosticDTO::parse($data);

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


    public function summary()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $diagnosticId = $this->request->get("diagnostic_id", "0");

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
            //Log::info('Before');
            if (!($model = CustomerDiagnostic::find($diagnosticId))) {
                throw new \Exception("Customer not found");
            }

            $this->service->saveDiagnosticQuestion($model);

            $this->servicePrevention->fillMissingReportMonthly($diagnosticId, $this->user->id);

            //Log::info('After');
            // get all tracking by customer with pagination
            $data = $this->service->getAllSummryBy($orders, $diagnosticId);

            //Log::info('Last');
            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            $result = CustomerDiagnosticDTO::parse($data);

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

    public function summaryExportPdf()
    {
        $data = array();

        $pdf = PDF::loadView("aden.pdf::html.diagnostic", $data)->setPaper('a4')->setOrientation('landscape')->setWarnings(false);

        return $pdf->download('invoice.pdf');
    }

    public function summaryExportExcel()
    {


        ////Log::info(var_dump($data));

        /*Excel::load('/uploads/public/templates/GridTemplate1.xlsx', function ($file) use ($result) {

            $file->setActiveSheetIndex(0)->setCellValue('B1', "Arquitectos de Negcio");
            //$file->setActiveSheetIndex(0)->setCellValue('C6', $model->created_at);

            //$chapters = $model->getChapters();

            $row = 9;
            $col = 1;

            $isFirstChapter = true;
            $isFirstActivity = true;

            foreach ($result as $data) {

                $r = get_object_vars($data);

                foreach (array_keys($r) as $key) {
                    if ($col > 2) {
                        $columnLetter = $this->stringFromColumnIndex($col);
                        // $file->getActiveSheet()->duplicateStyle($file->getActiveSheet()->getStyle('B5'),"$columnLetter"."5");
                    }

                    $file->getActiveSheet()->setCellValueByColumnAndRow($col, 5, $key);

                    $col++;
                }
            }




        })->export('xlsx');*/


        $diagnosticId = $this->request->get("id", "0");

        $orders = $this->request->get("order", array());

        $result = $this->service->getAllSummryByExport($orders, $diagnosticId);

        try {

            // decodify

            // get all tracking by customer with pagination

            Excel::create('Resumen_Diagnostico', function ($excel) use ($result) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Diagnostico', function ($sheet) use ($result) {

                    $resultArray = json_decode(json_encode($result), true);

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

    private function stringFromColumnIndex($num)
    {
        $numeric = ($num - 1) % 26;
        $letter = chr(65 + $numeric);
        $num2 = intval(($num - 1) / 26);
        if ($num2 > 0) {
            return $this->stringFromColumnIndex($num2) . $letter;
        } else {
            return $letter;
        }
    }

    public function canCreate()
    {

        $customerId = $this->request->get("customer_id", "0");

        try {

            $result = CustomerDiagnostic::whereStatus("iniciado")->where("customer_id", $customerId)->count() == 0;

            // set count total ideas
            $this->response->setData($result);
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

    public function getYearFilter()
    {
        $diagnosticId = $this->request->get("diagnostic_id", "0");

        try {

            // get all tracking by customer with pagination
            $data = $this->service->getYearFilter($diagnosticId);

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

    public function summaryByProgram()
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
            $data = $this->service->getAllSummaryByProgram($orders, $audit->diagnosticId, $audit->year);

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

    public function summaryByProgramExport()
    {
        $data = $this->request->get("data", "");

        if ($data != "") {
            $json = base64_decode($data);

            //Log::info($json);

            $audit = json_decode($json);
        } else {
            $audit = null;
        }
        // get all tracking by customer with pagination
        $data = $this->service->getAllSummaryByProgramExport(null, $audit->diagnosticId, $audit->year);

        try {

            // decodify

            // get all tracking by customer with pagination

            Excel::create('Resumen_Programas_Mensual', function ($excel) use ($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Programas_Mensual', function ($sheet) use ($data) {

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
            $data = $this->service->getAllSummaryByIndicator($orders, $audit->diagnosticId, $audit->year);

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

    public function summaryByIndicatorExport()
    {

        $data = $this->request->get("data", "");

        if ($data != "") {
            $json = base64_decode($data);

            //Log::info($json);

            $audit = json_decode($json);
        } else {
            $audit = null;
        }
        // get all tracking by customer with pagination
        $data = $this->service->getAllSummaryByIndicatorExport(null, $audit->diagnosticId, $audit->year);


        try {

            // decodify

            // get all tracking by customer with pagination

            Excel::create('Resumen_Indicador_Mensual', function ($excel) use ($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Indicadores_Mensual', function ($sheet) use ($data) {

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

    public function report()
    {


        $diagnosticId = $this->request->get("diagnostic_id", "0");
        $customerId = $this->request->get("customer_id", "0");

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
            $resultPie = $this->service->getDashboardPie($diagnosticId);
            $resultBar = $this->service->getDashboardBar($diagnosticId);

            //var_dump($resultBar);

            foreach ($resultPie as $pie) {
                $advances[][] = $pie;
            }

            $programs = [
                "result" => [
                    "labels" => ["POL", "ORG", "PLA", "EVA", "ARA", "ACM"],
                    "datasets" => [
                        [
                            "label" => "Cumple",
                            "fillColor" => array("r" => "70", "g" => "191", "b" => "189"),
                            "strokeColor" => $colorPrg1,
                            "highlightFill" => $colorPrg1,
                            "highlightStroke" => $colorPrg1,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "Cumple Pacial",
                            "fillColor" => array("r" => "224", "g" => "214", "b" => "83"),
                            "strokeColor" => $colorPrg2,
                            "highlightFill" => $colorPrg2,
                            "highlightStroke" => $colorPrg2,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "No Cumple",
                            "fillColor" => array("r" => "247", "g" => "70", "b" => "74"),
                            "strokeColor" => $colorPrg3,
                            "highlightFill" => $colorPrg3,
                            "highlightStroke" => $colorPrg3,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "No Aplica",
                            "fillColor" => array("r" => "92", "g" => "184", "b" => "85"),
                            "strokeColor" => $colorPrg4,
                            "highlightFill" => $colorPrg4,
                            "highlightStroke" => $colorPrg4,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "Sin Contestar",
                            "fillColor" => array("r" => "245", "g" => "130", "b" => "32"),
                            "strokeColor" => $colorPrg5,
                            "highlightFill" => $colorPrg5,
                            "highlightStroke" => $colorPrg5,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ]
                    ]
                ]
            ];

            if (!empty($resultBar)) {
                $programs = null;

                // var_dump("Entro");

                foreach ($resultBar as $bar) {
                    $label[] = $bar->name;
                    $accomplish[] = intval($bar->cumple);
                    $noAccomplish[] = intval($bar->parcial);
                    $noApplyWith[] = intval($bar->nocumple);
                    $noApplyWithout[] = intval($bar->noaplica);
                    $noChecked[] = intval($bar->nocontesta);
                }

                $programs = [
                    "result" => [
                        "labels" => $label,
                        "datasets" => [
                            [
                                "label" => "Cumple",
                                "fillColor" => array("r" => "70", "g" => "191", "b" => "189"),
                                "strokeColor" => $colorPrg1,
                                "highlightFill" => $colorPrg1,
                                "highlightStroke" => $colorPrg1,
                                "data" => $accomplish,
                            ],
                            [
                                "label" => "Cumple Pacial",
                                "fillColor" => array("r" => "224", "g" => "214", "b" => "83"),
                                "strokeColor" => $colorPrg2,
                                "highlightFill" => $colorPrg2,
                                "highlightStroke" => $colorPrg2,
                                "data" => $noAccomplish,
                            ],
                            [
                                "label" => "No Cumple",
                                "fillColor" => array("r" => "247", "g" => "70", "b" => "74"),
                                "strokeColor" => $colorPrg3,
                                "highlightFill" => $colorPrg3,
                                "highlightStroke" => $colorPrg3,
                                "data" => $noApplyWith,
                            ],
                            [
                                "label" => "No Aplica",
                                "fillColor" => array("r" => "92", "g" => "184", "b" => "85"),
                                "strokeColor" => $colorPrg4,
                                "highlightFill" => $colorPrg4,
                                "highlightStroke" => $colorPrg4,
                                "data" => $noApplyWithout,
                            ],
                            [
                                "label" => "Sin Contestar",
                                "fillColor" => array("r" => "245", "g" => "130", "b" => "32"),
                                "strokeColor" => $colorPrg5,
                                "highlightFill" => $colorPrg5,
                                "highlightStroke" => $colorPrg5,
                                "data" => $noChecked,
                            ]
                        ]
                    ]
                ];
            }

            $result = array();


            // extract info
            $result["report_programs"] = CustomerDiagnosticDTO::parse($programs, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["report_advances"] = CustomerDiagnosticDTO::parse($resultPie, "3"); // 2 = Prepara la respuesta para la grafica de donughts

            $totalAvg = $this->service->getDashboardByDiagnostic($diagnosticId);

            if (!empty($totalAvg)) {
                $result["totalAvg"] = (float)$totalAvg[0]->average;
            } else
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

    public function reportMonthly()
    {


        $diagnosticId = $this->request->get("diagnostic_id", "0");
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", "0");

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
            $resultBar = $this->service->getDashboardBarMonthly($diagnosticId, $year);
            $resultProgramLine = $this->service->getDashboardProgramLineMonthly($diagnosticId, $year);
            $resultTotalLine = $this->service->getDashboardTotalLineMonthly($diagnosticId, $year);
            $resultAvgLine = $this->service->getDashboardAvgLineMonthly($diagnosticId, $year);

            $programs = [
                "result" => [
                    "labels" => ["ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC"],
                    "datasets" => [
                        [
                            "label" => "Cumple",
                            "fillColor" => array("r" => "70", "g" => "191", "b" => "189"),
                            "strokeColor" => $colorPrg1,
                            "highlightFill" => $colorPrg1,
                            "highlightStroke" => $colorPrg1,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "Cumple Pacial",
                            "fillColor" => array("r" => "224", "g" => "214", "b" => "83"),
                            "strokeColor" => $colorPrg2,
                            "highlightFill" => $colorPrg2,
                            "highlightStroke" => $colorPrg2,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "No Cumple",
                            "fillColor" => array("r" => "247", "g" => "70", "b" => "74"),
                            "strokeColor" => $colorPrg3,
                            "highlightFill" => $colorPrg3,
                            "highlightStroke" => $colorPrg3,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "No Aplica",
                            "fillColor" => array("r" => "92", "g" => "184", "b" => "85"),
                            "strokeColor" => $colorPrg4,
                            "highlightFill" => $colorPrg4,
                            "highlightStroke" => $colorPrg4,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "Sin Contestar",
                            "fillColor" => array("r" => "245", "g" => "130", "b" => "32"),
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
                                "fillColor" => array("r" => "70", "g" => "191", "b" => "189"),
                                "strokeColor" => $colorPrg1,
                                "highlightFill" => $colorPrg1,
                                "highlightStroke" => $colorPrg1,
                                "data" => $cumple,
                            ],
                            [
                                "label" => "Cumple Pacial",
                                "fillColor" => array("r" => "224", "g" => "214", "b" => "83"),
                                "strokeColor" => $colorPrg2,
                                "highlightFill" => $colorPrg2,
                                "highlightStroke" => $colorPrg2,
                                "data" => $parcial,
                            ],
                            [
                                "label" => "No Cumple",
                                "fillColor" => array("r" => "247", "g" => "70", "b" => "74"),
                                "strokeColor" => $colorPrg3,
                                "highlightFill" => $colorPrg3,
                                "highlightStroke" => $colorPrg3,
                                "data" => $noCumple,
                            ],
                            [
                                "label" => "No Aplica",
                                "fillColor" => array("r" => "92", "g" => "184", "b" => "85"),
                                "strokeColor" => $colorPrg4,
                                "highlightFill" => $colorPrg4,
                                "highlightStroke" => $colorPrg4,
                                "data" => $noAplica,
                            ],
                            [
                                "label" => "Sin Contestar",
                                "fillColor" => array("r" => "245", "g" => "130", "b" => "32"),
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
            }

            $result = array();


            // extract info
            $result["report_programs"] = CustomerDiagnosticDTO::parse($programs, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["line_programs"] = $lineChartProgram;
            $result["line_total"] = $lineChartTotal;
            $result["line_avg"] = $lineChartAvg;

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

    private function getRandomColor()
    {
        return RandomColor::one(array(
            'luminosity' => 'bright',
            'hue' => 'green',  // red, orange, yellow, green, blue, purple, pink, monochrome
            'format' => 'rgb' // e.g. 'rgb(225,200,20)'
        ));
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
            $model = CustomerDiagnosticDTO::fillAndSaveModel($info);

            //Se generan todas las prguntas para el diagnostico.
            $this->service->saveDiagnosticQuestion($model);

            $this->service->saveDiagnosticAccident($model);
            // Parse to send on response
            $result = CustomerDiagnosticDTO::parse($model);

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

    public function update()
    {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            //Get data
            $model = CustomerDiagnosticDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerDiagnosticDTO::parse($model);

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
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerDiagnostic::find($id))) {
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerDiagnosticDTO::parse($model);

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

            if (!($model = CustomerDiagnostic::find($id))) {
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

    public function cancel()
    {
        $userAdmn = Auth::getUser();

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {
            //Log::info("diagnostic [" . $id . "]s::");

            if (!($model = CustomerDiagnostic::find($id))) {
                throw new Exception("Customer not found to delete.");
            }

            $model->updatedBy = $userAdmn->id;
            $model->status = "cancelado";
            $model->save();
            $model->touch();

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


    //TODO

    public function listEconomicGroup()
    {

        try {
            $isCustomer = $this->request->get("isCustomer", false);
            $customerId = $this->request->get("customerId", 0);

            $data["economicGroup"] = $isCustomer == "false" ? $this->service->findAllEconomicGroup() : $this->service->findCustomerEconomicGroup($customerId);
            $data["years"] = [
                [
                    "value" => "2014",
                    "item" => "2014"
                ],
                [
                    "value" => "2015",
                    "item" => "2015"
                ],
                [
                    "value" => "2016",
                    "item" => "2016"
                ],
                [
                    "value" => "2017",
                    "item" => "2017"
                ],
                [
                    "value" => "2018",
                    "item" => "2018"
                ],
            ];

            $this->response->setData($data);
        } catch (Exception $exc) {

            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function listEconomicGroupCustomer()
    {

        $parentId = $this->request->get("parentId", "0");

        try {

            $data = $this->service->findAllCustomerEconomicGroup($parentId);

            $this->response->setData($data);
        } catch (Exception $exc) {

            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function reportEconomicGroup()
    {
        $parentId = $this->request->get("parent_id", "0");

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
            $resultPie = $this->service->getDashboardPieEconomicGroup($parentId);
            $resultBar = $this->service->getDashboardBarEconomicGroup($parentId);

            $programs = [
                "result" => [
                    "labels" => ["POL", "ORG", "PLA", "EVA", "ARA", "ACM"],
                    "datasets" => [
                        [
                            "label" => "Cumple",
                            "fillColor" => array("r" => "70", "g" => "191", "b" => "189"),
                            "strokeColor" => $colorPrg1,
                            "highlightFill" => $colorPrg1,
                            "highlightStroke" => $colorPrg1,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "Cumple Pacial",
                            "fillColor" => array("r" => "224", "g" => "214", "b" => "83"),
                            "strokeColor" => $colorPrg2,
                            "highlightFill" => $colorPrg2,
                            "highlightStroke" => $colorPrg2,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "No Cumple",
                            "fillColor" => array("r" => "247", "g" => "70", "b" => "74"),
                            "strokeColor" => $colorPrg3,
                            "highlightFill" => $colorPrg3,
                            "highlightStroke" => $colorPrg3,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "No Aplica",
                            "fillColor" => array("r" => "92", "g" => "184", "b" => "85"),
                            "strokeColor" => $colorPrg4,
                            "highlightFill" => $colorPrg4,
                            "highlightStroke" => $colorPrg4,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "Sin Contestar",
                            "fillColor" => array("r" => "245", "g" => "130", "b" => "32"),
                            "strokeColor" => $colorPrg5,
                            "highlightFill" => $colorPrg5,
                            "highlightStroke" => $colorPrg5,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ]
                    ]
                ]
            ];

            if (!empty($resultBar)) {
                $programs = null;
                $programs = [
                    "result" => [
                        "labels" => ["POL", "ORG", "PLA", "EVA", "ARA", "ACM"],
                        "datasets" => [
                            [
                                "label" => "Cumple",
                                "fillColor" => array("r" => "70", "g" => "191", "b" => "189"),
                                "strokeColor" => $colorPrg1,
                                "highlightFill" => $colorPrg1,
                                "highlightStroke" => $colorPrg1,
                                "data" => [$resultBar[0]->cumple, $resultBar[1]->cumple, $resultBar[2]->cumple, $resultBar[3]->cumple, $resultBar[4]->cumple],
                            ],
                            [
                                "label" => "Cumple Pacial",
                                "fillColor" => array("r" => "224", "g" => "214", "b" => "83"),
                                "strokeColor" => $colorPrg2,
                                "highlightFill" => $colorPrg2,
                                "highlightStroke" => $colorPrg2,
                                "data" => [$resultBar[0]->parcial, $resultBar[1]->parcial, $resultBar[2]->parcial, $resultBar[3]->parcial, $resultBar[4]->parcial],
                            ],
                            [
                                "label" => "No Cumple",
                                "fillColor" => array("r" => "247", "g" => "70", "b" => "74"),
                                "strokeColor" => $colorPrg3,
                                "highlightFill" => $colorPrg3,
                                "highlightStroke" => $colorPrg3,
                                "data" => [$resultBar[0]->nocumple, $resultBar[1]->nocumple, $resultBar[2]->nocumple, $resultBar[3]->nocumple, $resultBar[4]->nocumple],
                            ],
                            [
                                "label" => "No Aplica",
                                "fillColor" => array("r" => "92", "g" => "184", "b" => "85"),
                                "strokeColor" => $colorPrg4,
                                "highlightFill" => $colorPrg4,
                                "highlightStroke" => $colorPrg4,
                                "data" => [$resultBar[0]->noaplica, $resultBar[1]->noaplica, $resultBar[2]->noaplica, $resultBar[3]->noaplica, $resultBar[4]->noaplica],
                            ],
                            [
                                "label" => "Sin Contestar",
                                "fillColor" => array("r" => "245", "g" => "130", "b" => "32"),
                                "strokeColor" => $colorPrg5,
                                "highlightFill" => $colorPrg5,
                                "highlightStroke" => $colorPrg5,
                                "data" => [$resultBar[0]->nocontesta, $resultBar[1]->nocontesta, $resultBar[2]->nocontesta, $resultBar[3]->nocontesta, $resultBar[4]->nocontesta],
                            ]
                        ]
                    ]
                ];
            }

            $result = array();

            // extract info
            $result["report_programs"] = CustomerDiagnosticDTO::parse($programs, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["report_advances"] = CustomerDiagnosticDTO::parse($resultPie, "3"); // 2 = Prepara la respuesta para la grafica de donughts

            $totalAvg = $this->service->getDashboardBarEconomicGroupTotalAverage($parentId);

            if ($totalAvg != null) {
                $result["totalAvg"] = (float)$totalAvg->average;
            } else {
                $result["totalAvg"] = 0;
            }

            // set count total ideas
            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            var_dump($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function reportEconomicGroupIndicator()
    {
        $parentId = $this->request->get("parent_id", "0");
        $year = $this->request->get("year", "0");

        try {


            // Aqui se debe hacer la otra consulta para reportes
            $resultPie = $this->service->getDashboardPieEconomicGroup($parentId);
            $resultBar = $this->service->getDashboardBarEconomicGroup($parentId);

            $minimumStandardEconomicGroup = $this->service->getMinimumStandardEconomicGroup($parentId);
            $minimumStandardEconomicGroupContractor = $this->service->getMinimumStandardEconomicGroupContractor($parentId);

            $diagnosticEconomicGroup = $this->service->getDiagnosticEconomicGroup($parentId);
            $diagnosticEconomicGroupContractor = $this->service->getDiagnosticEconomicContractor($parentId);

            $employeeEconomicGroup = $this->service->getEmployeesEconomicGroup($parentId);
            $employeeEconomicGroupContractor = $this->service->getEmployeesEconomicGroupContractor($parentId);
            $economicGroupContractor = $this->service->getEconomicGroupContractor($parentId);

            $disabilityDaysEconomicGroup = $this->service->getEconomicGroupDisabilityDays($parentId, $year, 'EG');
            $eventsEconomicGroup = $this->service->getEconomicGroupEvents($parentId, $year, 'EG');
            $eventsEconomicGroupDiseaseRate = $this->service->getEconomicGroupDiseaseRate($parentId, $year, 'EG');
            $eventsEconomicGroupFrequencyIndex = $this->service->getEconomicGroupFrequencyIndex($parentId, $year, 'EG');
            $eventsEconomicGroupSeverityIndex = $this->service->getEconomicGroupSeverityIndex($parentId, $year, 'EG');
            $chartEconomicGroup = $this->service->getEconomicGroupEventsChart($parentId, $year, 'EG');

            $disabilityDaysALEconomicGroup = $this->service->getEconomicGroupDisabilityDays($parentId, $year, 'AL');
            $eventsEconomicALGroup = $this->service->getEconomicGroupEvents($parentId, $year, 'AL');
            $eventsEconomicGroupALDiseaseRate = $this->service->getEconomicGroupDiseaseRate($parentId, $year, 'AL');
            $eventsEconomicGroupALFrequencyIndex = $this->service->getEconomicGroupFrequencyIndex($parentId, $year, 'AL');
            $eventsEconomicGroupALSeverityIndex = $this->service->getEconomicGroupSeverityIndex($parentId, $year, 'AL');
            $chartEconomicALGroup = $this->service->getEconomicGroupEventsChart($parentId, $year, 'AL');

            $economicGroupGender = $this->service->getEconomicGroupGender($parentId, $year);
            $economicGroupWorkingDay = $this->service->getEconomicGroupWorkingDay($parentId, $year);
            $economicGroupWorkingTime = $this->service->getEconomicGroupWorkingTime($parentId, $year);
            $economicGroupInjury = $this->service->getEconomicGroupInjury($parentId, $year);
            $economicGroupFactor = $this->service->getEconomicGroupFactor($parentId, $year);
            $economicGroupLink = $this->service->getEconomicGroupLink($parentId, $year);
            $economicGroupAccidentType = $this->service->getEconomicGroupAccidentType($parentId, $year);
            $economicGroupLocation = $this->service->getEconomicGroupLocation($parentId, $year);
            $economicGroupBody = $this->service->getEconomicGroupBody($parentId, $year);
            $economicGroupMechanism = $this->service->getEconomicGroupMechanism($parentId, $year);
            $economicGroupZone = $this->service->getEconomicGroupZone($parentId, $year);
            $economicGroupRegularWork = $this->service->getEconomicGroupRegularWork($parentId, $year);
            $economicGroupPlace = $this->service->getEconomicGroupPlace($parentId, $year);

            $questions = $this->getTotalField($diagnosticEconomicGroup, "questions");
            $questions = $questions == 0 ? 1 : $questions;

            $questionsContractor = $this->getTotalField($diagnosticEconomicGroupContractor, "questions");
            $questionsContractor = $questionsContractor == 0 ? 1 : $questionsContractor;

            $totalChild = $this->getTotalField($minimumStandardEconomicGroup, "valoration");
            $levelText = "Nivel Aceptable";

            if ($totalChild <= 60) {
                $levelText = "Nivel Crítico";
            } else if ($totalChild >= 61 && $totalChild <= 85) {
                $levelText = "Nivel Moderadamente Aceptable";
            } else if ($totalChild > 85) {
                $levelText = "Nivel Aceptable";
            }

            $result["charts"] = [
                "1" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 1,
                    "name" => "AUTO EVALUACION EM",
                    "tiles" => [
                        [
                            "name" => $levelText,
                            "value" => $this->getTotalField($minimumStandardEconomicGroup, "valoration"),
                            "symbol" => "%",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $this->getTotalField($minimumStandardEconomicGroup, "valoration"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $this->getTotalField($minimumStandardEconomicGroupContractor, "valoration"),
                                    "symbol" => "%",
                                ]
                            ],
                        ]
                    ],
                    "items" => $this->getMinimumStandardItems($minimumStandardEconomicGroup, "%")
                ],
                "2" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 1,
                    "name" => "SG-SST",
                    "tiles" => [
                        [
                            "name" => "Valoración Total",
                            "value" => round($this->getTotalField($diagnosticEconomicGroup, "total") / $questions, 2),
                            "symbol" => "%",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => round($this->getTotalField($diagnosticEconomicGroup, "total") / $questions, 2),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => round($this->getTotalField($diagnosticEconomicGroupContractor, "total") / $questionsContractor, 2),
                                    "symbol" => "%",
                                ]
                            ],
                        ]
                    ],
                    "items" => $this->getDiagnosticItems($diagnosticEconomicGroup, "%")
                ],
                "3" => [
                    "class" => "col-sm-6",
                    "type" => 1,
                    "order" => 3,
                    "name" => "EMPLEADOS",
                    "tiles" => [
                        [
                            "name" => "Empleados Activos",
                            "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalActive ? $employeeEconomicGroup->totalActive : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalActive ? $employeeEconomicGroup->totalActive : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalActive ? $employeeEconomicGroupContractor->totalActive : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ],
                        [
                            "name" => "Empleados Autorizados",
                            "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalAuthorized ? $employeeEconomicGroup->totalAuthorized : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalAuthorized ? $employeeEconomicGroup->totalAuthorized : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalAuthorized ? $employeeEconomicGroupContractor->totalAuthorized : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ],
                        [
                            "name" => "NA",
                            "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalNoAuthorized ? $employeeEconomicGroup->totalNoAuthorized : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalNoAuthorized ? $employeeEconomicGroup->totalNoAuthorized : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalNoAuthorized ? $employeeEconomicGroupContractor->totalNoAuthorized : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ]
                    ],
                    "items" => [
                    ]
                ],
                "4" => [
                    "class" => "col-sm-6",
                    "type" => 1,
                    "order" => 4,
                    "name" => "CONTRATISTAS",
                    "tiles" => [
                        [
                            "name" => "Número de contratistas",
                            "value" => $economicGroupContractor ? $economicGroupContractor->total : 0,
                            "symbol" => "",
                            "items" => [
                            ],
                        ]
                    ],
                    "items" => [
                    ]
                ],
                "5" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 4,
                    "name" => "AUSENTISMO EG",
                    "tiles" => [
                        [
                            "name" => "Días incapacitantes",
                            "value" => $this->getTotalField($disabilityDaysEconomicGroup, 'disabilityDays'),
                            "symbol" => "",
                            "items" => [
                            ],
                        ],
                        [
                            "name" => "Eventos",
                            "value" => $this->getTotalField($eventsEconomicGroup, 'eventNumber'),
                            "symbol" => "",
                            "items" => [
                            ],
                        ]
                    ],
                    "data" => $this->getChartReport($chartEconomicGroup),
                    "items" => [
                        [
                            "title" => null,
                            "results" => [
                                [
                                    "name" => "Tasa de enfermedad",
                                    "value" => $this->getTotalField($eventsEconomicGroupDiseaseRate, "diseaseRate"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IF",
                                    "value" => $this->getTotalField($eventsEconomicGroupFrequencyIndex, "frequencyIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IS",
                                    "value" => $this->getTotalField($eventsEconomicGroupSeverityIndex, "severityIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Incidencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Prevalencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ]
                            ]
                        ]
                    ]
                ],
                "6" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 4,
                    "name" => "AUSENTISMO AL",
                    "tiles" => [
                        [
                            "name" => "Días incapacitantes",
                            "value" => $this->getTotalField($disabilityDaysALEconomicGroup, 'disabilityDays'),
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ],
                        [
                            "name" => "Grupo",
                            "value" => "",
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ],
                        [
                            "name" => "Contratistas",
                            "value" => "",
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ]
                    ],
                    "data" => $this->getChartReport($chartEconomicALGroup),
                    "items" => [
                        [
                            "title" => null,
                            "results" => [
                                [
                                    "name" => "Tasa de enfermedad",
                                    "value" => $this->getTotalField($eventsEconomicGroupALDiseaseRate, "diseaseRate"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IF",
                                    "value" => $this->getTotalField($eventsEconomicGroupALFrequencyIndex, "frequencyIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IS",
                                    "value" => $this->getTotalField($eventsEconomicGroupALSeverityIndex, "severityIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Incidencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Prevalencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ]
                            ]
                        ]
                    ]
                ],
                "7" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 7,
                    "name" => "CARACTERIZACIÓN DE LA ACCIDENTALIDAD",
                    "tiles" => [
                    ],
                    "items" => [
                    ],
                    "counters" => [
                        [
                            "name" => "Genero",
                            "items" => $this->getResultsItems($economicGroupGender, ''),
                        ],
                        [
                            "name" => "Día de la semana",
                            "items" => $this->getResultsItems($economicGroupWorkingDay, ''),
                        ],
                        [
                            "name" => "Jornada",
                            "items" => $this->getResultsItems($economicGroupWorkingTime, ''),
                        ],
                        [
                            "name" => "Tipo de lesión",
                            "items" => $this->getResultsItems($economicGroupInjury, ''),
                        ],
                        [
                            "name" => "Agente del accidente",
                            "items" => $this->getResultsItems($economicGroupFactor, ''),
                        ],
                        [
                            "name" => "Zona",
                            "items" => $this->getResultsItems($economicGroupZone, ''),
                        ],
                        [
                            "name" => "Tipo de accidente",
                            "items" => $this->getResultsItems($economicGroupAccidentType, ''),
                        ],
                        [
                            "name" => "Lugar del accidente",
                            "items" => $this->getResultsItems($economicGroupLocation, ''),
                        ],
                        [
                            "name" => "Parte del cuerpo afectada",
                            "items" => $this->getResultsItems($economicGroupBody, ''),
                        ],
                        [
                            "name" => "Mecanismo o forma del accidente",
                            "items" => $this->getResultsItems($economicGroupMechanism, ''),
                        ],
                        [
                            "name" => "Tipo de vinculación",
                            "items" => $this->getResultsItems($economicGroupLink, ''),
                        ],
                        [
                            "name" => "Labor habitual",
                            "items" => $this->getResultsItems($economicGroupRegularWork, ''),
                        ],
                        [
                            "name" => "Lugar donde ocurrió",
                            "items" => $this->getResultsItems($economicGroupPlace, ''),
                        ],
                    ]
                ]
            ];

            // set count total ideas
            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            var_dump($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function reportEconomicGroupCustomerIndicator()
    {
        $parentId = $this->request->get("parent_id", "0");
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", "0");

        try {


            // Aqui se debe hacer la otra consulta para reportes
            $resultPie = $this->service->getDashboardPieEconomicGroup($parentId);
            $resultBar = $this->service->getDashboardBarEconomicGroup($parentId);

            $minimumStandardEconomicGroup = $this->service->getMinimumStandardEconomicGroup($parentId);
            $minimumStandardEconomicGroupContractor = $this->service->getMinimumStandardEconomicGroupContractor($parentId);
            $minimumStandardEconomicGroupCustomer = $this->service->getMinimumStandardEconomicGroupCustomer($customerId);

            $diagnosticEconomicGroup = $this->service->getDiagnosticEconomicGroup($parentId);
            $diagnosticEconomicGroupContractor = $this->service->getDiagnosticEconomicContractor($parentId);
            $diagnosticEconomicGroupCustomer = $this->service->getDiagnosticCustomer($customerId);

            $employeeEconomicGroupCustomer = $this->service->getEmployeesCustomer($customerId);
            $employeeEconomicGroup = $this->service->getEmployeesEconomicGroup($parentId);
            $employeeEconomicGroupContractor = $this->service->getEmployeesEconomicGroupContractor($parentId);

            $economicGroupContractor = $this->service->getCustomerContractor($customerId);

            $disabilityDaysEconomicGroup = $this->service->getEconomicGroupCustomerDisabilityDays($customerId, $year, 'EG');
            $eventsEconomicGroup = $this->service->getEconomicGroupEvents($parentId, $year, 'EG');
            $eventsEconomicGroupContractor = $this->service->getEconomicGroupContractorEvents($parentId, $year, 'EG');
            $eventsEconomicCustomerGroup = $this->service->getEconomicGroupCustomerEvents($customerId, $year, 'EG');
            $eventsEconomicGroupDiseaseRate = $this->service->getEconomicGroupCustomerDiseaseRate($customerId, $year, 'EG');
            $eventsEconomicGroupFrequencyIndex = $this->service->getEconomicGroupCustomerFrequencyIndex($customerId, $year, 'EG');
            $eventsEconomicGroupSeverityIndex = $this->service->getEconomicGroupCustomerSeverityIndex($customerId, $year, 'EG');
            $chartEconomicGroup = $this->service->getEconomicGroupCustomerEventsChart($customerId, $year, 'EG');

            $disabilityDaysALEconomicGroup = $this->service->getEconomicGroupCustomerDisabilityDays($customerId, $year, 'AL');
            $eventsEconomicALGroup = $this->service->getEconomicGroupEvents($parentId, $year, 'AL');
            $eventsEconomicCustomerALGroup = $this->service->getEconomicGroupCustomerEvents($customerId, $year, 'AL');
            $eventsEconomicGroupALDiseaseRate = $this->service->getEconomicGroupCustomerDiseaseRate($customerId, $year, 'AL');
            $eventsEconomicGroupALFrequencyIndex = $this->service->getEconomicGroupCustomerFrequencyIndex($customerId, $year, 'AL');
            $eventsEconomicGroupALSeverityIndex = $this->service->getEconomicGroupCustomerSeverityIndex($customerId, $year, 'AL');
            $chartEconomicALGroup = $this->service->getEconomicGroupCustomerEventsChart($customerId, $year, 'AL');

            $economicGroupGender = $this->service->getEconomicGroupCustomerGender($customerId, $year);
            $economicGroupWorkingDay = $this->service->getEconomicGroupCustomerWorkingDay($customerId, $year);
            $economicGroupWorkingTime = $this->service->getEconomicGroupCustomerWorkingTime($customerId, $year);
            $economicGroupInjury = $this->service->getEconomicGroupCustomerInjury($customerId, $year);
            $economicGroupFactor = $this->service->getEconomicGroupCustomerFactor($customerId, $year);
            $economicGroupLink = $this->service->getEconomicGroupCustomerLink($customerId, $year);
            $economicGroupAccidentType = $this->service->getEconomicGroupCustomerAccidentType($customerId, $year);
            $economicGroupLocation = $this->service->getEconomicGroupCustomerLocation($customerId, $year);
            $economicGroupBody = $this->service->getEconomicGroupCustomerBody($customerId, $year);
            $economicGroupMechanism = $this->service->getEconomicGroupCustomerMechanism($customerId, $year);
            $economicGroupZone = $this->service->getEconomicGroupCustomerZone($customerId, $year);
            $economicGroupRegularWork = $this->service->getEconomicGroupCustomerRegularWork($customerId, $year);
            $economicGroupPlace = $this->service->getEconomicGroupCustomerPlace($customerId, $year);

            $questionsGroup = $this->getTotalField($diagnosticEconomicGroup, "questions");
            $questionsGroup = $questionsGroup == 0 ? 1 : $questionsGroup;

            $questions = $this->getTotalField($diagnosticEconomicGroupCustomer, "questions");
            $questions = $questions == 0 ? 1 : $questions;

            $questionsContractor = $this->getTotalField($diagnosticEconomicGroupContractor, "questions");
            $questionsContractor = $questionsContractor == 0 ? 1 : $questionsContractor;

            $totalChild = $this->getTotalField($minimumStandardEconomicGroupCustomer, "valoration");
            $levelText = "Nivel Aceptable";

            if ($totalChild <= 60) {
                $levelText = "Nivel Crítico";
            } else if ($totalChild >= 61 && $totalChild <= 85) {
                $levelText = "Nivel Moderadamente Aceptable";
            } else if ($totalChild > 85) {
                $levelText = "Nivel Aceptable";
            }

            $result["charts"] = [
                "1" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 1,
                    "name" => "AUTO EVALUACION EM",
                    "tiles" => [
                        [
                            "name" => $levelText,
                            "value" => $this->getTotalField($minimumStandardEconomicGroupCustomer, "valoration"),
                            "symbol" => "%",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $this->getTotalField($minimumStandardEconomicGroup, "valoration"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $this->getTotalField($minimumStandardEconomicGroupContractor, "valoration"),
                                    "symbol" => "%",
                                ]
                            ],
                        ]
                    ],
                    "items" => $this->getMinimumStandardItems($minimumStandardEconomicGroupCustomer, "%")
                ],
                "2" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 1,
                    "name" => "SG-SST",
                    "tiles" => [
                        [
                            "name" => "Valoración Total",
                            "value" => round($this->getTotalField($diagnosticEconomicGroupCustomer, "total") / $questions, 2),
                            "symbol" => "%",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => round($this->getTotalField($diagnosticEconomicGroup, "total") / $questionsGroup, 2),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => round($this->getTotalField($diagnosticEconomicGroupContractor, "total") / $questionsContractor, 2),
                                    "symbol" => "%",
                                ]
                            ],
                        ]
                    ],
                    "items" => $this->getDiagnosticItems($diagnosticEconomicGroupCustomer, "%")
                ],
                "3" => [
                    "class" => "col-sm-6",
                    "type" => 1,
                    "order" => 3,
                    "name" => "EMPLEADOS",
                    "tiles" => [
                        [
                            "name" => "Empleados Activos",
                            "value" => $employeeEconomicGroupCustomer && $employeeEconomicGroupCustomer->totalActive ? $employeeEconomicGroupCustomer->totalActive : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalActive ? $employeeEconomicGroup->totalActive : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalActive ? $employeeEconomicGroupContractor->totalActive : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ],
                        [
                            "name" => "Empleados Autorizados",
                            "value" => $employeeEconomicGroupCustomer && $employeeEconomicGroupCustomer->totalAuthorized ? $employeeEconomicGroupCustomer->totalAuthorized : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalAuthorized ? $employeeEconomicGroup->totalAuthorized : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalAuthorized ? $employeeEconomicGroupContractor->totalAuthorized : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ],
                        [
                            "name" => "NA",
                            "value" => $employeeEconomicGroupCustomer && $employeeEconomicGroupCustomer->totalNoAuthorized ? $employeeEconomicGroupCustomer->totalNoAuthorized : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalNoAuthorized ? $employeeEconomicGroup->totalNoAuthorized : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalNoAuthorized ? $employeeEconomicGroupContractor->totalNoAuthorized : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ]
                    ],
                    "items" => [
                    ]
                ],
                "4" => [
                    "class" => "col-sm-6",
                    "type" => 1,
                    "order" => 4,
                    "name" => "CONTRATISTAS",
                    "tiles" => [
                        [
                            "name" => "Número de contratistas",
                            "value" => $economicGroupContractor ? $economicGroupContractor->total : 0,
                            "symbol" => "",
                            "items" => [
                            ],
                        ]
                    ],
                    "items" => [
                    ]
                ],
                "5" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 4,
                    "name" => "AUSENTISMO EG",
                    "tiles" => [
                        [
                            "name" => "Días incapacitantes",
                            "value" => $this->getTotalField($disabilityDaysEconomicGroup, 'disabilityDays'),
                            "symbol" => "",
                            "items" => [
                            ],
                        ],
                        [
                            "name" => "Eventos",
                            "value" => $this->getTotalField($eventsEconomicCustomerGroup, 'eventNumber'),
                            "symbol" => "",
                            "items" => [
                            ],
                        ]
                    ],
                    "data" => $this->getChartReport($chartEconomicGroup),
                    "items" => [
                        [
                            "title" => null,
                            "results" => [
                                [
                                    "name" => "Tasa de enfermedad",
                                    "value" => $this->getTotalField($eventsEconomicGroupDiseaseRate, "diseaseRate"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IF",
                                    "value" => $this->getTotalField($eventsEconomicGroupFrequencyIndex, "frequencyIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IS",
                                    "value" => $this->getTotalField($eventsEconomicGroupSeverityIndex, "severityIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Incidencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Prevalencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ]
                            ]
                        ]
                    ]
                ],
                "6" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 4,
                    "name" => "AUSENTISMO AL",
                    "tiles" => [
                        [
                            "name" => "Días incapacitantes",
                            "value" => $this->getTotalField($disabilityDaysALEconomicGroup, 'disabilityDays'),
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicCustomerALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ],
                        [
                            "name" => "Grupo",
                            "value" => "",
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ],
                        [
                            "name" => "Contratistas",
                            "value" => "",
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ]
                    ],
                    "data" => $this->getChartReport($chartEconomicALGroup),
                    "items" => [
                        [
                            "title" => null,
                            "results" => [
                                [
                                    "name" => "Tasa de enfermedad",
                                    "value" => $this->getTotalField($eventsEconomicGroupALDiseaseRate, "diseaseRate"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IF",
                                    "value" => $this->getTotalField($eventsEconomicGroupALFrequencyIndex, "frequencyIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IS",
                                    "value" => $this->getTotalField($eventsEconomicGroupALSeverityIndex, "severityIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Incidencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Prevalencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ]
                            ]
                        ]
                    ]
                ],
                "7" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 7,
                    "name" => "CARACTERIZACIÓN DE LA ACCIDENTALIDAD",
                    "tiles" => [
                    ],
                    "items" => [
                    ],
                    "counters" => [
                        [
                            "name" => "Genero",
                            "items" => $this->getResultsItems($economicGroupGender, ''),
                        ],
                        [
                            "name" => "Día de la semana",
                            "items" => $this->getResultsItems($economicGroupWorkingDay, ''),
                        ],
                        [
                            "name" => "Jornada",
                            "items" => $this->getResultsItems($economicGroupWorkingTime, ''),
                        ],
                        [
                            "name" => "Tipo de lesión",
                            "items" => $this->getResultsItems($economicGroupInjury, ''),
                        ],
                        [
                            "name" => "Agente del accidente",
                            "items" => $this->getResultsItems($economicGroupFactor, ''),
                        ],
                        [
                            "name" => "Zona",
                            "items" => $this->getResultsItems($economicGroupZone, ''),
                        ],
                        [
                            "name" => "Tipo de accidente",
                            "items" => $this->getResultsItems($economicGroupAccidentType, ''),
                        ],
                        [
                            "name" => "Lugar del accidente",
                            "items" => $this->getResultsItems($economicGroupLocation, ''),
                        ],
                        [
                            "name" => "Parte del cuerpo afectada",
                            "items" => $this->getResultsItems($economicGroupBody, ''),
                        ],
                        [
                            "name" => "Mecanismo o forma del accidente",
                            "items" => $this->getResultsItems($economicGroupMechanism, ''),
                        ],
                        [
                            "name" => "Tipo de vinculación",
                            "items" => $this->getResultsItems($economicGroupLink, ''),
                        ],
                        [
                            "name" => "Labor habitual",
                            "items" => $this->getResultsItems($economicGroupRegularWork, ''),
                        ],
                        [
                            "name" => "Lugar donde ocurrió",
                            "items" => $this->getResultsItems($economicGroupPlace, ''),
                        ],
                    ]
                ]
            ];

            // set count total ideas
            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            var_dump($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function reportContractingIndicator()
    {
        $parentId = $this->request->get("parent_id", "0");
        $year = $this->request->get("year", "0");

        try {


            // Aqui se debe hacer la otra consulta para reportes
            $resultPie = $this->service->getDashboardPieEconomicGroup($parentId);
            $resultBar = $this->service->getDashboardBarEconomicGroup($parentId);

            $minimumStandardEconomicGroup = $this->service->getMinimumStandardContracting($parentId);
            $minimumStandardEconomicGroupContractor = $this->service->getMinimumStandardEconomicGroupContractor($parentId);

            $diagnosticEconomicGroup = $this->service->getDiagnosticContracting($parentId);
            $diagnosticEconomicGroupContractor = $this->service->getDiagnosticContractingContractor($parentId);

            $employeeEconomicGroup = $this->service->getEmployeesContracting($parentId);
            $employeeEconomicGroupContractor = $this->service->getEmployeesEconomicGroupContractor($parentId);
            $economicGroupContractor = $this->service->getEconomicGroupContractor($parentId);

            $disabilityDaysEconomicGroup = $this->service->getContractingDisabilityDays($parentId, $year, 'EG');
            $eventsEconomicGroup = $this->service->getContractingEvents($parentId, $year, 'EG');
            $eventsEconomicGroupDiseaseRate = $this->service->getContractingDiseaseRate($parentId, $year, 'EG');
            $eventsEconomicGroupFrequencyIndex = $this->service->getContractingFrequencyIndex($parentId, $year, 'EG');
            $eventsEconomicGroupSeverityIndex = $this->service->getContractingSeverityIndex($parentId, $year, 'EG');
            $chartEconomicGroup = $this->service->getContractingEventsChart($parentId, $year, 'EG');

            $disabilityDaysALEconomicGroup = $this->service->getContractingDisabilityDays($parentId, $year, 'AL');
            $eventsEconomicALGroup = $this->service->getContractingEvents($parentId, $year, 'AL');
            $eventsEconomicGroupALDiseaseRate = $this->service->getContractingDiseaseRate($parentId, $year, 'AL');
            $eventsEconomicGroupALFrequencyIndex = $this->service->getContractingFrequencyIndex($parentId, $year, 'AL');
            $eventsEconomicGroupALSeverityIndex = $this->service->getContractingSeverityIndex($parentId, $year, 'AL');
            $chartEconomicALGroup = $this->service->getContractingEventsChart($parentId, $year, 'AL');

            $economicGroupGender = $this->service->getContractingGender($parentId, $year);
            $economicGroupWorkingDay = $this->service->getContractingWorkingDay($parentId, $year);
            $economicGroupWorkingTime = $this->service->getContractingWorkingTime($parentId, $year);
            $economicGroupInjury = $this->service->getContractingInjury($parentId, $year);
            $economicGroupFactor = $this->service->getContractingFactor($parentId, $year);
            $economicGroupLink = $this->service->getContractingLink($parentId, $year);
            $economicGroupAccidentType = $this->service->getContractingAccidentType($parentId, $year);
            $economicGroupLocation = $this->service->getContractingLocation($parentId, $year);
            $economicGroupBody = $this->service->getContractingBody($parentId, $year);
            $economicGroupMechanism = $this->service->getContractingMechanism($parentId, $year);
            $economicGroupZone = $this->service->getContractingZone($parentId, $year);
            $economicGroupRegularWork = $this->service->getContractingRegularWork($parentId, $year);
            $economicGroupPlace = $this->service->getContractingPlace($parentId, $year);

            $questions = $this->getTotalField($diagnosticEconomicGroup, "questions");
            $questions = $questions == 0 ? 1 : $questions;

            $questionsContractor = $this->getTotalField($diagnosticEconomicGroupContractor, "questions");
            $questionsContractor = $questionsContractor == 0 ? 1 : $questionsContractor;

            $totalChild = $this->getTotalField($minimumStandardEconomicGroup, "valoration");
            $levelText = "Nivel Aceptable";

            if ($totalChild <= 60) {
                $levelText = "Nivel Crítico";
            } else if ($totalChild >= 61 && $totalChild <= 85) {
                $levelText = "Nivel Moderadamente Aceptable";
            } else if ($totalChild > 85) {
                $levelText = "Nivel Aceptable";
            }

            $result["charts"] = [
                "1" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 1,
                    "name" => "AUTO EVALUACION EM",
                    "tiles" => [
                        [
                            "name" => $levelText,
                            "value" => $this->getTotalField($minimumStandardEconomicGroup, "valoration"),
                            "symbol" => "%",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $this->getTotalField($minimumStandardEconomicGroup, "valoration"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $this->getTotalField($minimumStandardEconomicGroupContractor, "valoration"),
                                    "symbol" => "%",
                                ]
                            ],
                        ]
                    ],
                    "items" => $this->getMinimumStandardItems($minimumStandardEconomicGroup, "%")
                ],
                "2" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 1,
                    "name" => "SG-SST",
                    "tiles" => [
                        [
                            "name" => "Valoración Total",
                            "value" => round($this->getTotalField($diagnosticEconomicGroup, "total") / $questions, 2),
                            "symbol" => "%",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => round($this->getTotalField($diagnosticEconomicGroup, "total") / $questions, 2),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => round($this->getTotalField($diagnosticEconomicGroupContractor, "total") / $questionsContractor, 2),
                                    "symbol" => "%",
                                ]
                            ],
                        ]
                    ],
                    "items" => $this->getDiagnosticItems($diagnosticEconomicGroup, "%")
                ],
                "3" => [
                    "class" => "col-sm-6",
                    "type" => 1,
                    "order" => 3,
                    "name" => "EMPLEADOS",
                    "tiles" => [
                        [
                            "name" => "Empleados Activos",
                            "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalActive ? $employeeEconomicGroup->totalActive : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalActive ? $employeeEconomicGroup->totalActive : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalActive ? $employeeEconomicGroupContractor->totalActive : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ],
                        [
                            "name" => "Empleados Autorizados",
                            "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalAuthorized ? $employeeEconomicGroup->totalAuthorized : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalAuthorized ? $employeeEconomicGroup->totalAuthorized : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalAuthorized ? $employeeEconomicGroupContractor->totalAuthorized : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ],
                        [
                            "name" => "NA",
                            "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalNoAuthorized ? $employeeEconomicGroup->totalNoAuthorized : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalNoAuthorized ? $employeeEconomicGroup->totalNoAuthorized : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalNoAuthorized ? $employeeEconomicGroupContractor->totalNoAuthorized : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ]
                    ],
                    "items" => [
                    ]
                ],
                "4" => [
                    "class" => "col-sm-6",
                    "type" => 1,
                    "order" => 4,
                    "name" => "CONTRATISTAS",
                    "tiles" => [
                        [
                            "name" => "Número de contratistas",
                            "value" => $economicGroupContractor ? $economicGroupContractor->total : 0,
                            "symbol" => "",
                            "items" => [
                            ],
                        ]
                    ],
                    "items" => [
                    ]
                ],
                "5" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 4,
                    "name" => "AUSENTISMO EG",
                    "tiles" => [
                        [
                            "name" => "Días incapacitantes",
                            "value" => $this->getTotalField($disabilityDaysEconomicGroup, 'disabilityDays'),
                            "symbol" => "",
                            "items" => [
                            ],
                        ],
                        [
                            "name" => "Eventos",
                            "value" => $this->getTotalField($eventsEconomicGroup, 'eventNumber'),
                            "symbol" => "",
                            "items" => [
                            ],
                        ]
                    ],
                    "data" => $this->getChartReport($chartEconomicGroup),
                    "items" => [
                        [
                            "title" => null,
                            "results" => [
                                [
                                    "name" => "Tasa de enfermedad",
                                    "value" => $this->getTotalField($eventsEconomicGroupDiseaseRate, "diseaseRate"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IF",
                                    "value" => $this->getTotalField($eventsEconomicGroupFrequencyIndex, "frequencyIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IS",
                                    "value" => $this->getTotalField($eventsEconomicGroupSeverityIndex, "severityIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Incidencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Prevalencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ]
                            ]
                        ]
                    ]
                ],
                "6" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 4,
                    "name" => "AUSENTISMO AL",
                    "tiles" => [
                        [
                            "name" => "Días incapacitantes",
                            "value" => $this->getTotalField($disabilityDaysALEconomicGroup, 'disabilityDays'),
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ],
                        [
                            "name" => "Grupo",
                            "value" => "",
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ],
                        [
                            "name" => "Contratistas",
                            "value" => "",
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ]
                    ],
                    "data" => $this->getChartReport($chartEconomicALGroup),
                    "items" => [
                        [
                            "title" => null,
                            "results" => [
                                [
                                    "name" => "Tasa de enfermedad",
                                    "value" => $this->getTotalField($eventsEconomicGroupALDiseaseRate, "diseaseRate"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IF",
                                    "value" => $this->getTotalField($eventsEconomicGroupALFrequencyIndex, "frequencyIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IS",
                                    "value" => $this->getTotalField($eventsEconomicGroupALSeverityIndex, "severityIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Incidencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Prevalencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ]
                            ]
                        ]
                    ]
                ],
                "7" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 7,
                    "name" => "CARACTERIZACIÓN DE LA ACCIDENTALIDAD",
                    "tiles" => [
                    ],
                    "items" => [
                    ],
                    "counters" => [
                        [
                            "name" => "Genero",
                            "items" => $this->getResultsItems($economicGroupGender, ''),
                        ],
                        [
                            "name" => "Día de la semana",
                            "items" => $this->getResultsItems($economicGroupWorkingDay, ''),
                        ],
                        [
                            "name" => "Jornada",
                            "items" => $this->getResultsItems($economicGroupWorkingTime, ''),
                        ],
                        [
                            "name" => "Tipo de lesión",
                            "items" => $this->getResultsItems($economicGroupInjury, ''),
                        ],
                        [
                            "name" => "Agente del accidente",
                            "items" => $this->getResultsItems($economicGroupFactor, ''),
                        ],
                        [
                            "name" => "Zona",
                            "items" => $this->getResultsItems($economicGroupZone, ''),
                        ],
                        [
                            "name" => "Tipo de accidente",
                            "items" => $this->getResultsItems($economicGroupAccidentType, ''),
                        ],
                        [
                            "name" => "Lugar del accidente",
                            "items" => $this->getResultsItems($economicGroupLocation, ''),
                        ],
                        [
                            "name" => "Parte del cuerpo afectada",
                            "items" => $this->getResultsItems($economicGroupBody, ''),
                        ],
                        [
                            "name" => "Mecanismo o forma del accidente",
                            "items" => $this->getResultsItems($economicGroupMechanism, ''),
                        ],
                        [
                            "name" => "Tipo de vinculación",
                            "items" => $this->getResultsItems($economicGroupLink, ''),
                        ],
                        [
                            "name" => "Labor habitual",
                            "items" => $this->getResultsItems($economicGroupRegularWork, ''),
                        ],
                        [
                            "name" => "Lugar donde ocurrió",
                            "items" => $this->getResultsItems($economicGroupPlace, ''),
                        ],
                    ]
                ]
            ];

            // set count total ideas
            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            var_dump($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function reportContractingCustomerIndicator()
    {
        $parentId = $this->request->get("parent_id", "0");
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", "0");

        try {


            // Aqui se debe hacer la otra consulta para reportes
            $resultPie = $this->service->getDashboardPieEconomicGroup($parentId);
            $resultBar = $this->service->getDashboardBarEconomicGroup($parentId);

            $minimumStandardEconomicGroup = $this->service->getMinimumStandardContracting($parentId);
            $minimumStandardEconomicGroupContractor = $this->service->getMinimumStandardEconomicGroupContractor($parentId);
            $minimumStandardEconomicGroupCustomer = $this->service->getMinimumStandardEconomicGroupCustomer($customerId);

            $diagnosticEconomicGroup = $this->service->getDiagnosticContracting($parentId);
            $diagnosticEconomicGroupContractor = $this->service->getDiagnosticContractingContractor($parentId);
            $diagnosticEconomicGroupCustomer = $this->service->getDiagnosticCustomer($customerId);

            $employeeEconomicGroupCustomer = $this->service->getEmployeesCustomer($customerId);
            $employeeEconomicGroup = $this->service->getEmployeesContracting($parentId);
            $employeeEconomicGroupContractor = $this->service->getEmployeesEconomicGroupContractor($parentId);

            $economicGroupContractor = $this->service->getCustomerContractor($customerId);

            $disabilityDaysEconomicGroup = $this->service->getEconomicGroupCustomerDisabilityDays($customerId, $year, 'EG');
            $eventsEconomicGroup = $this->service->getEconomicGroupEvents($parentId, $year, 'EG');
            $eventsEconomicGroupContractor = $this->service->getEconomicGroupContractorEvents($parentId, $year, 'EG');
            $eventsEconomicCustomerGroup = $this->service->getEconomicGroupCustomerEvents($customerId, $year, 'EG');
            $eventsEconomicGroupDiseaseRate = $this->service->getEconomicGroupCustomerDiseaseRate($customerId, $year, 'EG');
            $eventsEconomicGroupFrequencyIndex = $this->service->getEconomicGroupCustomerFrequencyIndex($customerId, $year, 'EG');
            $eventsEconomicGroupSeverityIndex = $this->service->getEconomicGroupCustomerSeverityIndex($customerId, $year, 'EG');
            $chartEconomicGroup = $this->service->getEconomicGroupCustomerEventsChart($customerId, $year, 'EG');

            $disabilityDaysALEconomicGroup = $this->service->getEconomicGroupCustomerDisabilityDays($customerId, $year, 'AL');
            $eventsEconomicALGroup = $this->service->getEconomicGroupEvents($parentId, $year, 'AL');
            $eventsEconomicCustomerALGroup = $this->service->getEconomicGroupCustomerEvents($customerId, $year, 'AL');
            $eventsEconomicGroupALDiseaseRate = $this->service->getEconomicGroupCustomerDiseaseRate($customerId, $year, 'AL');
            $eventsEconomicGroupALFrequencyIndex = $this->service->getEconomicGroupCustomerFrequencyIndex($customerId, $year, 'AL');
            $eventsEconomicGroupALSeverityIndex = $this->service->getEconomicGroupCustomerSeverityIndex($customerId, $year, 'AL');
            $chartEconomicALGroup = $this->service->getEconomicGroupCustomerEventsChart($customerId, $year, 'AL');

            $economicGroupGender = $this->service->getContractingGender($customerId, $year);
            $economicGroupWorkingDay = $this->service->getContractingWorkingDay($customerId, $year);
            $economicGroupWorkingTime = $this->service->getContractingWorkingTime($customerId, $year);
            $economicGroupInjury = $this->service->getContractingInjury($customerId, $year);
            $economicGroupFactor = $this->service->getContractingFactor($customerId, $year);
            $economicGroupLink = $this->service->getContractingLink($customerId, $year);
            $economicGroupAccidentType = $this->service->getContractingAccidentType($customerId, $year);
            $economicGroupLocation = $this->service->getContractingLocation($customerId, $year);
            $economicGroupBody = $this->service->getContractingBody($customerId, $year);
            $economicGroupMechanism = $this->service->getContractingMechanism($customerId, $year);
            $economicGroupZone = $this->service->getContractingZone($customerId, $year);
            $economicGroupRegularWork = $this->service->getContractingRegularWork($customerId, $year);
            $economicGroupPlace = $this->service->getContractingPlace($customerId, $year);

            $questionsGroup = $this->getTotalField($diagnosticEconomicGroup, "questions");
            $questionsGroup = $questionsGroup == 0 ? 1 : $questionsGroup;

            $questions = $this->getTotalField($diagnosticEconomicGroupCustomer, "questions");
            $questions = $questions == 0 ? 1 : $questions;

            $questionsContractor = $this->getTotalField($diagnosticEconomicGroupContractor, "questions");
            $questionsContractor = $questionsContractor == 0 ? 1 : $questionsContractor;

            $totalChild = $this->getTotalField($minimumStandardEconomicGroupCustomer, "valoration");
            $levelText = "Nivel Aceptable";

            if ($totalChild <= 60) {
                $levelText = "Nivel Crítico";
            } else if ($totalChild >= 61 && $totalChild <= 85) {
                $levelText = "Nivel Moderadamente Aceptable";
            } else if ($totalChild > 85) {
                $levelText = "Nivel Aceptable";
            }

            $result["charts"] = [
                "1" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 1,
                    "name" => "AUTO EVALUACION EM",
                    "tiles" => [
                        [
                            "name" => $levelText,
                            "value" => $this->getTotalField($minimumStandardEconomicGroupCustomer, "valoration"),
                            "symbol" => "%",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $this->getTotalField($minimumStandardEconomicGroup, "valoration"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $this->getTotalField($minimumStandardEconomicGroupContractor, "valoration"),
                                    "symbol" => "%",
                                ]
                            ],
                        ]
                    ],
                    "items" => $this->getMinimumStandardItems($minimumStandardEconomicGroupCustomer, "%")
                ],
                "2" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 1,
                    "name" => "SG-SST",
                    "tiles" => [
                        [
                            "name" => "Valoración Total",
                            "value" => round($this->getTotalField($diagnosticEconomicGroupCustomer, "total") / $questions, 2),
                            "symbol" => "%",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => round($this->getTotalField($diagnosticEconomicGroup, "total") / $questionsGroup, 2),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => round($this->getTotalField($diagnosticEconomicGroupContractor, "total") / $questionsContractor, 2),
                                    "symbol" => "%",
                                ]
                            ],
                        ]
                    ],
                    "items" => $this->getDiagnosticItems($diagnosticEconomicGroupCustomer, "%")
                ],
                "3" => [
                    "class" => "col-sm-6",
                    "type" => 1,
                    "order" => 3,
                    "name" => "EMPLEADOS",
                    "tiles" => [
                        [
                            "name" => "Empleados Activos",
                            "value" => $employeeEconomicGroupCustomer && $employeeEconomicGroupCustomer->totalActive ? $employeeEconomicGroupCustomer->totalActive : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalActive ? $employeeEconomicGroup->totalActive : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalActive ? $employeeEconomicGroupContractor->totalActive : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ],
                        [
                            "name" => "Empleados Autorizados",
                            "value" => $employeeEconomicGroupCustomer && $employeeEconomicGroupCustomer->totalAuthorized ? $employeeEconomicGroupCustomer->totalAuthorized : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalAuthorized ? $employeeEconomicGroup->totalAuthorized : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalAuthorized ? $employeeEconomicGroupContractor->totalAuthorized : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ],
                        [
                            "name" => "NA",
                            "value" => $employeeEconomicGroupCustomer && $employeeEconomicGroupCustomer->totalNoAuthorized ? $employeeEconomicGroupCustomer->totalNoAuthorized : 0,
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Grupo",
                                    "value" => $employeeEconomicGroup && $employeeEconomicGroup->totalNoAuthorized ? $employeeEconomicGroup->totalNoAuthorized : 0,
                                    "symbol" => "",
                                ],
                                [
                                    "name" => "Contratistas",
                                    "value" => $employeeEconomicGroupContractor && $employeeEconomicGroupContractor->totalNoAuthorized ? $employeeEconomicGroupContractor->totalNoAuthorized : 0,
                                    "symbol" => "",
                                ]
                            ],
                        ]
                    ],
                    "items" => [
                    ]
                ],
                "4" => [
                    "class" => "col-sm-6",
                    "type" => 1,
                    "order" => 4,
                    "name" => "CONTRATISTAS",
                    "tiles" => [
                        [
                            "name" => "Número de contratistas",
                            "value" => $economicGroupContractor ? $economicGroupContractor->total : 0,
                            "symbol" => "",
                            "items" => [
                            ],
                        ]
                    ],
                    "items" => [
                    ]
                ],
                "5" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 4,
                    "name" => "AUSENTISMO EG",
                    "tiles" => [
                        [
                            "name" => "Días incapacitantes",
                            "value" => $this->getTotalField($disabilityDaysEconomicGroup, 'disabilityDays'),
                            "symbol" => "",
                            "items" => [
                            ],
                        ],
                        [
                            "name" => "Eventos",
                            "value" => $this->getTotalField($eventsEconomicCustomerGroup, 'eventNumber'),
                            "symbol" => "",
                            "items" => [
                            ],
                        ]
                    ],
                    "data" => $this->getChartReport($chartEconomicGroup),
                    "items" => [
                        [
                            "title" => null,
                            "results" => [
                                [
                                    "name" => "Tasa de enfermedad",
                                    "value" => $this->getTotalField($eventsEconomicGroupDiseaseRate, "diseaseRate"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IF",
                                    "value" => $this->getTotalField($eventsEconomicGroupFrequencyIndex, "frequencyIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IS",
                                    "value" => $this->getTotalField($eventsEconomicGroupSeverityIndex, "severityIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Incidencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Prevalencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ]
                            ]
                        ]
                    ]
                ],
                "6" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 4,
                    "name" => "AUSENTISMO AL",
                    "tiles" => [
                        [
                            "name" => "Días incapacitantes",
                            "value" => $this->getTotalField($disabilityDaysALEconomicGroup, 'disabilityDays'),
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicCustomerALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ],
                        [
                            "name" => "Grupo",
                            "value" => "",
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ],
                        [
                            "name" => "Contratistas",
                            "value" => "",
                            "symbol" => "",
                            "items" => [
                                [
                                    "name" => "Eventos",
                                    "value" => $this->getTotalField($eventsEconomicALGroup, 'eventNumber'),
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Graves",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ],
                                [
                                    "name" => "Mortales",
                                    "value" => 0,
                                    "symbol" => "",
                                    "items" => [
                                    ],
                                ]
                            ],
                        ]
                    ],
                    "data" => $this->getChartReport($chartEconomicALGroup),
                    "items" => [
                        [
                            "title" => null,
                            "results" => [
                                [
                                    "name" => "Tasa de enfermedad",
                                    "value" => $this->getTotalField($eventsEconomicGroupALDiseaseRate, "diseaseRate"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IF",
                                    "value" => $this->getTotalField($eventsEconomicGroupALFrequencyIndex, "frequencyIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "IS",
                                    "value" => $this->getTotalField($eventsEconomicGroupALSeverityIndex, "severityIndex"),
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Incidencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ],
                                [
                                    "name" => "Prevalencia",
                                    "value" => 0,
                                    "symbol" => "%",
                                ]
                            ]
                        ]
                    ]
                ],
                "7" => [
                    "class" => "col-sm-12",
                    "type" => 1,
                    "order" => 7,
                    "name" => "CARACTERIZACIÓN DE LA ACCIDENTALIDAD",
                    "tiles" => [
                    ],
                    "items" => [
                    ],
                    "counters" => [
                        [
                            "name" => "Genero",
                            "items" => $this->getResultsItems($economicGroupGender, ''),
                        ],
                        [
                            "name" => "Día de la semana",
                            "items" => $this->getResultsItems($economicGroupWorkingDay, ''),
                        ],
                        [
                            "name" => "Jornada",
                            "items" => $this->getResultsItems($economicGroupWorkingTime, ''),
                        ],
                        [
                            "name" => "Tipo de lesión",
                            "items" => $this->getResultsItems($economicGroupInjury, ''),
                        ],
                        [
                            "name" => "Agente del accidente",
                            "items" => $this->getResultsItems($economicGroupFactor, ''),
                        ],
                        [
                            "name" => "Zona",
                            "items" => $this->getResultsItems($economicGroupZone, ''),
                        ],
                        [
                            "name" => "Tipo de accidente",
                            "items" => $this->getResultsItems($economicGroupAccidentType, ''),
                        ],
                        [
                            "name" => "Lugar del accidente",
                            "items" => $this->getResultsItems($economicGroupLocation, ''),
                        ],
                        [
                            "name" => "Parte del cuerpo afectada",
                            "items" => $this->getResultsItems($economicGroupBody, ''),
                        ],
                        [
                            "name" => "Mecanismo o forma del accidente",
                            "items" => $this->getResultsItems($economicGroupMechanism, ''),
                        ],
                        [
                            "name" => "Tipo de vinculación",
                            "items" => $this->getResultsItems($economicGroupLink, ''),
                        ],
                        [
                            "name" => "Labor habitual",
                            "items" => $this->getResultsItems($economicGroupRegularWork, ''),
                        ],
                        [
                            "name" => "Lugar donde ocurrió",
                            "items" => $this->getResultsItems($economicGroupPlace, ''),
                        ],
                    ]
                ]
            ];

            // set count total ideas
            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            var_dump($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    private function getChartReport($data)
    {

        if (!empty($data)) {

            $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

            $lineChartProgramDataSet = array();

            foreach ($data as $line) {
                //218,79,74,0.2
                $lineChartProgramDataSet[] = array(
                    "label" => $line->label,
                    /*"fillColor" => array("r" => "189", "g" => "191","b" => "70"),
                    "highlightFill" => array("r" => "60", "g" => "109","b" => "0"),
                    "highlightStroke" => array("r" => "60", "g" => "109","b" => "0"),*/

                    "fillColor" => 'rgb(189, 191, 70)',
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

        return $lineChartProgram;
    }

    private function getTotalField($data, $field)
    {
        $result = 0;

        foreach ($data as $row) {
            $result += floatval($row->{$field});
        }

        return $result;
    }

    private function getMinimumStandardItems($data, $symbol)
    {
        $results = [];

        $columnMapping = [
            "target" => "Meta",
            "valoration" => "Valoración",
            "accomplish" => "Cumple",
            "noAccomplish" => "No Cumple",
            "noApplyWith" => "NA con just",
            "noApplyWithout" => "NA sin just",
            "noChecked" => "Sin contestar"
        ];

        foreach ($data as $row) {
            $items = [];
            foreach ($columnMapping as $key => $title) {
                $items[] = [
                    "name" => $title,
                    "value" => $row->{$key},
                    "symbol" => $symbol,
                ];
            }
            $results[] = [
                "title" => $row->name,
                "results" => $items
            ];
        }

        return $results;
    }

    private function getDiagnosticItems($data, $symbol)
    {
        $results = [];

        $columnMapping = [
            "target" => "Meta",
            "valoration" => "Valoración",
            "cumple" => "Cumple",
            "nocumple" => "No Cumple",
            "noaplica" => "NA con just"
        ];

        foreach ($data as $row) {
            $items = [];
            foreach ($columnMapping as $key => $title) {
                $items[] = [
                    "name" => $title,
                    "value" => $row->{$key},
                    "symbol" => $symbol,
                ];
            }
            $results[] = [
                "title" => $row->name,
                "results" => $items
            ];
        }

        return $results;
    }

    private function getResultsItems($data, $symbol)
    {
        $results = [];

        foreach ($data as $row) {
            $results[] = [
                "name" => $row->label,
                "value" => $row->value,
                "symbol" => $symbol,
            ];
        }

        return $results;
    }

    public function economicGroupSummary()
    {
        $operation = $this->request->get("operation", "customer");
        $parentId = $this->request->get("parentId", "-1");

        $draw = $this->request->get("draw", "1");

        $orders = $this->request->get("order", array());


        try {

            if ($operation == 'customer') {
                $data = $this->service->getAllSummryBy($orders, $parentId);
            } else {
                $data = $this->service->findAllSummaryEconomicGroup($parentId);
            }

            $recordsTotal = count($data);
            $recordsFiltered = count($data);

            // extract info
            $result = CustomerDiagnosticDTO::parse($data);

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

    public function listCustomer()
    {

        try {
            $isCustomer = $this->request->get("isCustomer", false);
            $customerId = $this->request->get("customerId", 0);

            $data["economicGroup"] = $isCustomer == "false" ? $this->service->findAllCustomer() : $this->service->findCustomer($customerId);
            $data["years"] = [
                [
                    "value" => "2014",
                    "item" => "2014"
                ],
                [
                    "value" => "2015",
                    "item" => "2015"
                ],
                [
                    "value" => "2016",
                    "item" => "2016"
                ],
                [
                    "value" => "2017",
                    "item" => "2017"
                ],
                [
                    "value" => "2018",
                    "item" => "2018"
                ],
            ];

            $this->response->setData($data);
        } catch (Exception $exc) {

            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function listContracting()
    {

        try {
            $isCustomer = $this->request->get("isCustomer", false);
            $customerId = $this->request->get("customerId", 0);

            $data["economicGroup"] = $isCustomer == "false" ? $this->service->findAllContracting() : $this->service->findCustomerContracting($customerId);
            $data["years"] = [
                [
                    "value" => "2014",
                    "item" => "2014"
                ],
                [
                    "value" => "2015",
                    "item" => "2015"
                ],
                [
                    "value" => "2016",
                    "item" => "2016"
                ],
                [
                    "value" => "2017",
                    "item" => "2017"
                ],
                [
                    "value" => "2018",
                    "item" => "2018"
                ],
            ];

            $this->response->setData($data);
        } catch (Exception $exc) {

            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function listContractingCustomer()
    {

        $parentId = $this->request->get("parentId", "0");

        try {

            $data = $this->service->findAllCustomerContracting($parentId);

            $this->response->setData($data);
        } catch (Exception $exc) {

            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function reportContracting()
    {
        $parentId = $this->request->get("parent_id", "0");

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
            $resultPie = $this->service->getDashboardPieContracting($parentId);
            $resultBar = $this->service->getDashboardBarContracting($parentId);

            $programs = [
                "result" => [
                    "labels" => ["POL", "ORG", "PLA", "EVA", "ARA", "ACM"],
                    "datasets" => [
                        [
                            "label" => "Cumple",
                            "fillColor" => array("r" => "70", "g" => "191", "b" => "189"),
                            "strokeColor" => $colorPrg1,
                            "highlightFill" => $colorPrg1,
                            "highlightStroke" => $colorPrg1,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "Cumple Pacial",
                            "fillColor" => array("r" => "224", "g" => "214", "b" => "83"),
                            "strokeColor" => $colorPrg2,
                            "highlightFill" => $colorPrg2,
                            "highlightStroke" => $colorPrg2,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "No Cumple",
                            "fillColor" => array("r" => "247", "g" => "70", "b" => "74"),
                            "strokeColor" => $colorPrg3,
                            "highlightFill" => $colorPrg3,
                            "highlightStroke" => $colorPrg3,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "No Aplica",
                            "fillColor" => array("r" => "92", "g" => "184", "b" => "85"),
                            "strokeColor" => $colorPrg4,
                            "highlightFill" => $colorPrg4,
                            "highlightStroke" => $colorPrg4,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ],
                        [
                            "label" => "Sin Contestar",
                            "fillColor" => array("r" => "245", "g" => "130", "b" => "32"),
                            "strokeColor" => $colorPrg5,
                            "highlightFill" => $colorPrg5,
                            "highlightStroke" => $colorPrg5,
                            "data" => [0, 0, 0, 0, 0, 0],
                        ]
                    ]
                ]
            ];

            if (!empty($resultBar)) {
                $programs = null;
                $programs = [
                    "result" => [
                        "labels" => ["POL", "ORG", "PLA", "EVA", "ARA", "ACM"],
                        "datasets" => [
                            [
                                "label" => "Cumple",
                                "fillColor" => array("r" => "70", "g" => "191", "b" => "189"),
                                "strokeColor" => $colorPrg1,
                                "highlightFill" => $colorPrg1,
                                "highlightStroke" => $colorPrg1,
                                "data" => [$resultBar[0]->cumple, $resultBar[1]->cumple, $resultBar[2]->cumple, $resultBar[3]->cumple, $resultBar[4]->cumple],
                            ],
                            [
                                "label" => "Cumple Pacial",
                                "fillColor" => array("r" => "224", "g" => "214", "b" => "83"),
                                "strokeColor" => $colorPrg2,
                                "highlightFill" => $colorPrg2,
                                "highlightStroke" => $colorPrg2,
                                "data" => [$resultBar[0]->parcial, $resultBar[1]->parcial, $resultBar[2]->parcial, $resultBar[3]->parcial, $resultBar[4]->parcial],
                            ],
                            [
                                "label" => "No Cumple",
                                "fillColor" => array("r" => "247", "g" => "70", "b" => "74"),
                                "strokeColor" => $colorPrg3,
                                "highlightFill" => $colorPrg3,
                                "highlightStroke" => $colorPrg3,
                                "data" => [$resultBar[0]->nocumple, $resultBar[1]->nocumple, $resultBar[2]->nocumple, $resultBar[3]->nocumple, $resultBar[4]->nocumple],
                            ],
                            [
                                "label" => "No Aplica",
                                "fillColor" => array("r" => "92", "g" => "184", "b" => "85"),
                                "strokeColor" => $colorPrg4,
                                "highlightFill" => $colorPrg4,
                                "highlightStroke" => $colorPrg4,
                                "data" => [$resultBar[0]->noaplica, $resultBar[1]->noaplica, $resultBar[2]->noaplica, $resultBar[3]->noaplica, $resultBar[4]->noaplica],
                            ],
                            [
                                "label" => "Sin Contestar",
                                "fillColor" => array("r" => "245", "g" => "130", "b" => "32"),
                                "strokeColor" => $colorPrg5,
                                "highlightFill" => $colorPrg5,
                                "highlightStroke" => $colorPrg5,
                                "data" => [$resultBar[0]->nocontesta, $resultBar[1]->nocontesta, $resultBar[2]->nocontesta, $resultBar[3]->nocontesta, $resultBar[4]->nocontesta],
                            ]
                        ]
                    ]
                ];
            }

            $result = array();


            // extract info
            $result["report_programs"] = CustomerDiagnosticDTO::parse($programs, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["report_advances"] = CustomerDiagnosticDTO::parse($resultPie, "3"); // 2 = Prepara la respuesta para la grafica de donughts

            $totalAvg = $this->service->getDashboardBarContractingTotalAverage($parentId);

            if ($totalAvg != null) {
                $result["totalAvg"] = (float)$totalAvg->average;
            } else {
                $result["totalAvg"] = 0;
            }

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

    public function contractingSummary()
    {
        $operation = $this->request->get("operation", "customer");
        $parentId = $this->request->get("parentId", "-1");

        $draw = $this->request->get("draw", "1");

        $orders = $this->request->get("order", array());


        try {

            if ($operation == 'customer') {
                $data = $this->service->getAllSummryBy($orders, $parentId);
            } else {
                $data = $this->service->findAllSummaryContracting($parentId);
            }

            $recordsTotal = count($data);
            $recordsFiltered = count($data);

            // extract info
            $result = CustomerDiagnosticDTO::parse($data);

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
}
