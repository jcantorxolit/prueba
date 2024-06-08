<?php

namespace Wgroup\Controllers;

use Controller as BaseController;
use Excel;
use Exception;
use Log;
use PDF;
use PHPExcel;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Models\Parameters;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\RandomColor;
use Wgroup\ConfigMinimumStandardCycle\ConfigMinimumStandardCycle;
use Wgroup\ConfigMinimumStandardRate\ConfigMinimumStandardRate;
use Wgroup\ConfigMinimumStandardRate\ConfigMinimumStandardRateDTO;
use Wgroup\CustomerEvaluationMinimumStandard\CustomerEvaluationMinimumStandard;
use Wgroup\CustomerEvaluationMinimumStandard\CustomerEvaluationMinimumStandardDTO;
use Wgroup\CustomerEvaluationMinimumStandard\CustomerEvaluationMinimumStandardService;
use Wgroup\CustomerEvaluationMinimumStandardItem\CustomerEvaluationMinimumStandardItemService;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerEvaluationMinimumStandardController extends BaseController
{

    const SESSION_LOCALE = 'rainlab.translate.locale';

    private $translate;
    private $service;
    private $serviceItem;
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
        $this->service = new CustomerEvaluationMinimumStandardService();
        $this->serviceItem = new CustomerEvaluationMinimumStandardItemService();

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
            $recordsTotal = $this->service->getCount(@$search['value']);
            $recordsFiltered = $this->service->getCount(@$search['value']);

            //var_dump($data);
            // extract info
            $result = CustomerEvaluationMinimumStandardDTO::parse($data);

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
        $customerId = $this->request->get("customer_id", 0);
        $standardId = $this->request->get("standard_id", 0);
        $draw = $this->request->get("draw", "1");
        $orders = $this->request->get("order", array());

        try {

            $standardId = $standardId ? $standardId : $this->getLastStandardId($customerId);

            $model = CustomerEvaluationMinimumStandard::find($standardId);

            if ($model != null) {
                $this->service->insertMinimumStandardItems($model);
            }
            //Se generan todas las prguntas para el diagnostico.

            //TODO
            $this->serviceItem->insertVerificationMode($standardId);
            $this->serviceItem->fillMissingMonthlyReport($standardId, $this->user->id);

            // get all tracking by customer with pagination
            $data = $this->service->getAllSummary($orders, $standardId);

            $recordsTotal = 0;
            $recordsFiltered = 0;

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
            $this->response->setRecordsTotal($recordsTotal);
            $this->response->setRecordsFiltered($recordsFiltered);
        } catch (Exception $exc) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function listData()
    {
        $customerId = $this->request->get("customer_id", 0);
        $standardId = $this->request->get("standard_id", 0);

        try {
            $standardId = $standardId ? $standardId : $this->getLastStandardId($customerId);

            $data['cycle'] = $this->service->getDashboardCycle($standardId);
            $data['rate'] = ConfigMinimumStandardRateDTO::parse(ConfigMinimumStandardRate::where('id', '>', 2)->get());
            $data['rateReal'] = ConfigMinimumStandardRateDTO::parse(ConfigMinimumStandardRate::all());
            $data['years'] = $this->service->getYearFilter($standardId);


            // set count total ideas
            $this->response->setData($data);
        } catch (Exception $exc) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function summaryExportExcel()
    {
        $standardId = $this->request->get("id", "0");

        $orders = $this->request->get("order", array());

        $result = $this->service->getAllSummaryExport($orders, $standardId);

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
            var_dump($exc->getTraceAsString());

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
            $result = CustomerEvaluationMinimumStandard::whereStatus("iniciado")->where("customer_id", $customerId)->count() == 0;
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
        $standardId = $this->request->get("standard_id", "0");

        try {

            // get all tracking by customer with pagination
            $data = $this->service->getYearFilter($standardId);

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
            $data = $this->service->getAllSummaryByProgram($orders, $audit->standardId, $audit->year);

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
        $data = $this->service->getAllSummaryByProgramExport(null, $audit->standardId, $audit->year);

        try {

            // decodify

            // get all tracking by customer with pagination

            Excel::create('Resumen_Ciclos_Mensual', function ($excel) use ($data) {

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
            $data = $this->service->getAllSummaryByIndicator($orders, $audit->standardId, $audit->year);

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
        $data = $this->service->getAllSummaryByIndicatorExport(null, $audit->standardId, $audit->year);


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

    public function chartReport()
    {
        $standardId = $this->request->get("standard_id", 0);
        $customerId = $this->request->get("customer_id", "0");

        try {

            $standardId = $standardId ? $standardId : $this->getLastStandardId($customerId);

            $resultPie = $this->service->getDashboardPie($standardId);
            $resultBar = $this->service->getDashboardBar($standardId);
            $cycles = ConfigMinimumStandardCycle::whereStatus('activo')->get();

            $programs = [];
            $label = array();
            $accomplish = array();
            $noAccomplish = array();
            $noApplyWith = array();
            $noApplyWithout = array();
            $noChecked = array();

            if (!empty($resultBar)) {
                foreach ($resultBar as $bar) {
                    $label[] = $bar->name;
                    $accomplish[] = intval($bar->accomplish);
                    $noAccomplish[] = intval($bar->noAccomplish);
                    $noApplyWith[] = intval($bar->noApplyWith);
                    $noApplyWithout[] = intval($bar->noApplyWithout);
                    $noChecked[] = intval($bar->noChecked);
                }

                $programs = [
                    "labels" => $label,
                    "datasets" => [
                        $this->getDataSetChart('Cumple', '#3395FF', $accomplish),
                        $this->getDataSetChart('No Cumple', '#e0d653', $noAccomplish),
                        $this->getDataSetChart('N/A Sin Justificación', '#F7464A', $noApplyWithout),
                        $this->getDataSetChart('N/A Con Justificación', '#5cb855', $noApplyWith),
                        $this->getDataSetChart('Sin Evaluar', '#5AD3D1', $noChecked),
                    ]
                ];
            } else {

                foreach ($cycles as $cycle) {
                    $label[] = $cycle->name;
                    $accomplish[] = 0;
                    $noAccomplish[] = 0;
                    $noApplyWith[] = 0;
                    $noApplyWithout[] = 0;
                    $noChecked[] = 0;
                }

                $programs = [
                    "labels" => $label,
                    "datasets" => [
                        $this->getDataSetChart('Cumple', '#3395FF', $accomplish),
                        $this->getDataSetChart('No Cumple', '#e0d653', $noAccomplish),
                        $this->getDataSetChart('N/A Sin Justificación', '#F7464A', $noApplyWithout),
                        $this->getDataSetChart('N/A Con Justificación', '#5cb855', $noApplyWith),
                        $this->getDataSetChart('Sin Evaluar', '#5AD3D1', $noChecked),
                    ]
                ];
            }

            if (!empty($resultPie)) {
                foreach ($resultPie as $pie) {
                    $pie->value = (float)$pie->value;
                }
            }

            $result["cyclesDataBar"] = $programs;
            $result["progressDataPie"] = $resultPie;
            $result["totalAvg"] = $this->service->totalAvg($standardId);
            $result["standardId"] = $standardId;

            $this->response->setResult($result);

        } catch (Exception $exc) {

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function monthlyReport()
    {
        $standardId = $this->request->get("standard_id", "0");
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
            $resultBar = $this->service->getDashboardBarMonthly($standardId, $year);
            $resultProgramLine = $this->service->getDashboardProgramLineMonthly($standardId, $year);
            $resultTotalLine = $this->service->getDashboardTotalLineMonthly($standardId, $year);
            $resultAvgLine = $this->service->getDashboardAvgLineMonthly($standardId, $year);

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
            $result["report_programs"] = $programs;//CustomerEvaluationMinimumStandardDTO::parse($programs, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["line_programs"] = $lineChartProgram;
            $result["line_total"] = $lineChartTotal;
            $result["line_avg"] = $lineChartAvg;

            // set count total ideas
            $this->response->setResult($result);

        } catch (Exception $exc) {
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
            $model = CustomerEvaluationMinimumStandardDTO::fillAndSaveModel($info);

            //Se generan todas las prguntas para el diagnostico.
            $this->service->saveDiagnosticQuestion($model);

            $this->service->saveDiagnosticAccident($model);
            // Parse to send on response
            $result = CustomerEvaluationMinimumStandardDTO::parse($model);

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
            $model = CustomerEvaluationMinimumStandardDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerEvaluationMinimumStandardDTO::parse($model);

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

            if (!($model = CustomerEvaluationMinimumStandard::find($id))) {
                throw new \Exception("Record not found");
            }

            //Get data
            $result = CustomerEvaluationMinimumStandardDTO::parse($model);

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

            if (!($model = CustomerEvaluationMinimumStandard::find($id))) {
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

            if (!($model = CustomerEvaluationMinimumStandard::find($id))) {
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

    private function getLastStandardId($customerId)
    {
        try {
            if (CustomerEvaluationMinimumStandard::whereCustomerId($customerId)->count() == 0) {
                $model = $this->createStandard($customerId);
                if ($model != null) {
                    return $model->id;
                }
            } else {
                return CustomerEvaluationMinimumStandard::whereCustomerId($customerId)->whereStatus('iniciado')->max('id');
            }
        } catch (Exception $exc) {
            return 0;
        }
    }

    private function createStandard($customerId)
    {
        try {
            $model = new \stdClass();

            $model->id = 0;
            $model->customerId = $customerId;
            $model->startDate = null;
            $model->endDate = null;
            $model->status = "iniciado";
            $model->type = "EM";
            $model->description = "Auto Evaluación";

            $model = CustomerEvaluationMinimumStandardDTO::fillAndSaveModel($model);

            //Se generan todas las prguntas para el diagnostico.
            $this->service->insertMinimumStandardItems($model);

            return $model;

        } catch (Exception $ex) {
            var_dump($ex->getMessage());
            return null;
        }
    }

    private function getDataSetChart($label, $color, $data)
    {
        return [
            "label" => $label,
            "fillColor" => $this->hex2rgba($color, 1),
            "strokeColor" => $this->hex2rgba($color, 0.2),
            "pointColor" => $this->hex2rgba($color, 1),
            "pointStrokeColor" => '#fff',
            "pointHighlightFill" => '#fff',
            "pointHighlightStroke" => $this->hex2rgba($color, 1),
            "data" => $data,
        ];
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
