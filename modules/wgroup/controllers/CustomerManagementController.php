<?php

namespace Wgroup\Controllers;

use Controller as BaseController;
use Exception;
use Illuminate\Support\Facades\Log;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Models\Parameters;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\RandomColor;
use Wgroup\Classes\ServiceApi;
use Wgroup\Classes\ServiceCustomerManagement;
use Wgroup\Classes\ServiceCustomerManagementDetail;
use Wgroup\Models\CustomerManagement;
use Wgroup\Models\CustomerManagementDTO;
use Wgroup\Models\CustomerManagementProgramDTO;
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
class CustomerManagementController extends BaseController
{

    const SESSION_LOCALE = 'rainlab.translate.locale';

    private $translate;
    private $service;
    private $serviceDetail;
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
        $this->service = new ServiceCustomerManagement();
        $this->serviceDetail = new ServiceCustomerManagementDetail();
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
            if ($model = $this->serviceCustomer->getCustomerIdByUserGroup()) {
                if ($model->id != $customerId)
                    $customerId = -1;
            }

            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $customerId);

            // Counts
            $recordsTotal = $this->service->getCount();
            $recordsFiltered = $this->service->getCount(@$search['value']);

            // extract info
            $result = CustomerManagementDTO::parse($data);

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

    public function canCreate()
    {

        $customerId = $this->request->get("customer_id", "0");

        try {

            $result = CustomerManagement::whereStatus("iniciado")->where("customer_id", $customerId)->count() == 0;

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

    public function setting()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $managementId = $this->request->get("management_id", "0");

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

            $model = CustomerManagement::find($managementId);

            //Se generan todas las prguntas para la gestion
            $this->service->saveManagementProgram($model);

            //Se generan todas las prguntas para la gestion
            $this->service->saveManagementQuestion($model);

            // get all tracking by customer with pagination
            $data = $this->service->getAllSettingBy($orders, $managementId);

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            $result = CustomerManagementDTO::parse($data, "4");

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
        $managementId = $this->request->get("management_id", "0");

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


            $this->serviceDetail->fillMissingReportMonthly($managementId, $this->user->id);
            // get all tracking by customer with pagination
            $data = $this->service->getAllSummaryBy($orders, $managementId);

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            $result = CustomerManagementDTO::parse($data);

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

    public function getYearFilter()
    {
        $managementId = $this->request->get("management_id", "0");

        try {

            // get all tracking by customer with pagination
            $data = $this->service->getYearFilter($managementId);
            $programs = $this->service->getProgramFilter($managementId);

            $result["years"] = $data;
            $result["programs"] = $programs;

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
            $data = $this->service->getAllSummaryByProgram($orders, $audit->managementId, $audit->year);

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
        $data = $this->service->getAllSummaryByProgramExport(null, $audit->managementId, $audit->year);

        try {

            // decodify

            // get all tracking by customer with pagination

            Excel::create('Resumen_Programas_Mensual', function($excel) use($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Programas_Mensual', function($sheet) use($data) {

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
            $data = $this->service->getAllSummaryByIndicator($orders, $audit->managementId, $audit->year, $audit->program);

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
        $data = $this->service->getAllSummaryByIndicatorExport(null, $audit->managementId, $audit->year, $audit->program);


        try {

            // decodify

            // get all tracking by customer with pagination

            Excel::create('Resumen_Indicador_Mensual', function($excel) use($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Indicadores_Mensual', function($sheet) use($data) {

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

        $managementId = $this->request->get("id", "0");

        //$orders = $this->request->get("program_id", "0");

        $result = $this->service->getAllSummaryByExport(/*$orders*/ 0, $managementId);

        try {

            // decodify

            // get all tracking by customer with pagination

            Excel::create('Resumen_Programas_Empresariales', function($excel) use($result) {

                $excel->sheet('Programas_Empresariales', function($sheet) use($result) {

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

    public function report()
    {


        $managementId = $this->request->get("management_id", "0");
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
            $resultPie = $this->service->getDashboardPie($managementId);
            $resultBar = $this->service->getDashboardBar($managementId);

            foreach ($resultPie as $pie) {
                $advances[][] = $pie;
            }

            //Log::info($resultBar);

            $programs = [
                "result" => [
                    "labels" => ["POL", "ORG", "PLA", "EVA", "ARA", "ACM"],
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
                    $label[] = $bar->abbreviation;
                    $cumple[] = $bar->cumple;
                    $parcial[] = $bar->parcial;
                    $noCumple[] = $bar->nocumple;
                    $noAplica[] = $bar->noaplica;
                    $noContesta[] = $bar->nocontesta;
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

            /*
            foreach ($resultBar as $bar) {
                $programs["labels"] = $bar->name;

                $programs["datasets"] = array("label" => "Cumple", "fillColor" => $bar->color, "strokeColor" => $bar->highlightColor, "highlightFill" => $bar->color
                                                , "highlightStroke" => $bar->highlightColor, "data" => $bar->cumple);

                $programs["datasets"] = array("label" => "Cumple Pacial", "fillColor" => "#5cb85c", "strokeColor" => "#e0d653", "highlightFill" => "#5cb85c"
                , "highlightStroke" => "#e0d653", "data" => $bar->parcial);

                $programs["datasets"] = array("label" => "Cumple Pacial", "fillColor" => "##5AD3D1", "strokeColor" => "##46BFBD", "highlightFill" => "##5AD3D1"
                , "highlightStroke" => "##46BFBD", "data" => $bar->nocumple);

                $programs["datasets"] = array("label" => "Cumple Pacial", "fillColor" => "#F7464A", "strokeColor" => "##F7464A", "highlightFill" => "#F7464A"
                , "highlightStroke" => "##F7464A", "data" => $bar->noaplica);

                $programs["datasets"] = array("label" => "Cumple Pacial", "fillColor" => "#5cb855", "strokeColor" => "#46BEBE", "highlightFill" => "#5cb855"
                , "highlightStroke" => "#46BEBE", "data" => $bar->nocontesta);
            }*/

            //Log::info($programs);
            /*$advances = [
                [
                    "value" => 40,
                    "color" => "#5cb85c",
                    "highlight" => "#60c460",
                    "label" => "Cumple",
                ],
                [
                    "value" => 10,
                    "color" => "#e0d653",
                    "highlight" => "#FBF25A",
                    "label" => "Parcial",
                ],
                [
                    "value" => 30,
                    "color" => "#F7464A",
                    "highlight" => "#FF5A5E",
                    "label" => "No cumple",
                ],
                [
                    "value" => 20,
                    "color" => "#46BFBD",
                    "highlight" => "#5AD3D1",
                    "label" => "No Aplica",
                ]
            ];*/

            $result = array();


            // extract info
            $result["report_programs"] = CustomerManagementDTO::parse($programs, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["report_advances"] = CustomerManagementDTO::parse($resultPie, "3"); // 2 = Prepara la respuesta para la grafica de donughts

            $totalAvg = $this->service->getDashboardByManagement($managementId);

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


        $managementId = $this->request->get("management_id", "0");
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
            $resultBar = $this->service->getDashboardBarMonthly($managementId, $year);
            $resultProgramLine = $this->service->getDashboardProgramLineMonthly($managementId, $year);
            $resultTotalLine = $this->service->getDashboardTotalLineMonthly($managementId, $year);
            $resultAvgLine = $this->service->getDashboardAvgLineMonthly($managementId, $year);

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
            }

            $result = array();


            // extract info
            $result["report_programs"] = CustomerManagementDTO::parse($programs, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["line_programs"] = $lineChartProgram;
            $result["line_total"] = $lineChartAvg;
            $result["line_avg"] = $lineChartTotal;

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
            $model = CustomerManagementDTO::fillAndSaveModel($info);

            //Se generan todas las prguntas para la gestion
            $this->service->saveManagementProgram($model);

            //Se generan todas las prguntas para la gestion
            $this->service->saveManagementQuestion($model);

            // Parse to send on response
            $result = CustomerManagementDTO::parse($model);

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
            $model = CustomerManagementDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerManagementDTO::parse($model);

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

            if (!($model = CustomerManagement::find($id))) {
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerManagementDTO::parse($model);

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

            if (!($model = CustomerManagement::find($id))) {
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

            if (!($model = CustomerManagement::find($id))) {
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

    public function activate()
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
            $model = CustomerManagementProgramDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerManagementDTO::parse($model);

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
}
