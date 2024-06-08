<?php

namespace Wgroup\Controllers;

use Controller as BaseController;
use Excel;
use Exception;
use Log;
use PDF;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\RandomColor;
use Wgroup\Classes\ServiceApi;
use Wgroup\CustomerHealthDamageAnalysis\CustomerHealthDamageAnalysis;
use Wgroup\CustomerHealthDamageAnalysis\CustomerHealthDamageAnalysisDTO;
use Wgroup\CustomerHealthDamageAnalysis\CustomerHealthDamageAnalysisService;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerHealthDamageAnalysisController extends BaseController
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
        $this->service = new CustomerHealthDamageAnalysisService();
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
                    if ($index == count($colors)) {
                        $index = 0;
                    }
                    $record->color = $colors[$index];
                    $record->value = (int)$record->value;
                    $index++;
                }
            }

            if (!empty($resultPieDeathCause)) {

                $index = 0;
                foreach ($resultPieDeathCause as $record) {
                    if ($index == count($colors)) {
                        $index = 0;
                    }
                    $record->color = $colors[$index];
                    $record->value = (int)$record->value;
                    $index++;
                }
            }

            if (!empty($resultPieLocation)) {

                $index = 0;
                foreach ($resultPieLocation as $record) {
                    if ($index == count($colors)) {
                        $index = 0;
                    }
                    $record->color = $colors[$index];
                    $record->value = (int)$record->value;
                    $index++;
                }
            }

            if (!empty($resultBarLink)) {

                $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

                $lineChartProgramDataSet = array();

                $index = 0;
                foreach ($resultBarLink as $line) {
                    if ($index == count($colors)) {
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
                    if ($index == count($colors)) {
                        $index = 0;
                    }
                    $lineChartWorkTimeDataSet[] = array(
                        "label" => $line->code,
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
                    if ($index == count($colors)) {
                        $index = 0;
                    }
                    $lineChartWeekDayDataSet[] = array(
                        "label" => $line->name,
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
                    if ($index == count($colors)) {
                        $index = 0;
                    }
                    $lineChartPlaceDataSet[] = array(
                        "label" => $line->code,
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
                    if ($index == count($colors)) {
                        $index = 0;
                    }
                    $lineChartLesionDataSet[] = array(
                        "label" => $line->name,
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
                    if ($index == count($colors)) {
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

                    if ($index == count($colors)) {
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

    private function getChart($labelTitle, $result)
    {

        $colorPrg1 = $this->getRandomColor();

        $programs = [
            "result" => [
                "labels" => ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre", "Total"],
                "datasets" => [
                    [
                        "label" => $labelTitle,
                        "fillColor" => array("r" => "151", "g" => "187", "b" => "205"),
                        "strokeColor" => array("r" => "151", "g" => "187", "b" => "205"),
                        "highlightFill" => array("r" => "151", "g" => "187", "b" => "205"),
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
                            "fillColor" => array("r" => "151", "g" => "187", "b" => "205"),
                            "strokeColor" => array("r" => "151", "g" => "187", "b" => "205"),
                            "highlightFill" => array("r" => "151", "g" => "187", "b" => "205"),
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
