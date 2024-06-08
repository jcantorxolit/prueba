<?php

namespace Wgroup\Controllers;

use Wgroup\Classes\ApiResponse;
use Controller as BaseController;
use Exception;
use Log;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Models\Parameters;

use Wgroup\CustomerConfig\ConfigGeneral;
use Wgroup\CustomerConfig\ConfigJobActivityHazardClassification;
use Wgroup\CustomerConfig\ConfigJobActivityHazardDescription;
use Wgroup\CustomerConfig\ConfigJobActivityHazardEffect;
use Wgroup\CustomerConfig\ConfigJobActivityHazardType;
use Wgroup\CustomerConfig\CustomerConfigService;
use Wgroup\CustomerConfigJob\CustomerConfigJob;
use Wgroup\CustomerConfigJob\CustomerConfigJobDTO;
use Wgroup\CustomerConfigJob\CustomerConfigJobService;
use Wgroup\CustomerConfigJobActivity\CustomerConfigJobActivityService;
use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcessesService;
use Wgroup\CustomerConfigProcesses\CustomerConfigProcessesService;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlaceService;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerConfigController extends BaseController
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
        $this->service = new CustomerConfigService();
        $this->translate = Translator::instance();

        $this->request = app('Input');

        // set response
        $this->response = new ApiResponse();
        $this->response->setMessage("1");
        $this->response->setStatuscode(200);
    }


    public function listIndex()
    {
        $customerId = $this->request->get("customerId", "0");

        try {
            $data = $this->service->getSummary($customerId);

            foreach ($data as $record) {
                $record->data = $this->getChart($record);
                /*switch ($record->name) {
                    case "CENTROS DE TRABAJO";
                        break;
                    case "MACROPROCESOS";
                        break;
                    case "PROCESOS";
                        break;
                    case "CARGOS";
                        break;
                    case "ACTIVIDADES";
                        break;
                }*/
            }

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

    public function listIndexHazardClassification()
    {
        try {
            $data = ConfigJobActivityHazardClassification::all();

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

    public function listIndexHazardType()
    {
        $classificationId = $this->request->get("classificationId", "0");

        try {
            $data = ConfigJobActivityHazardType::whereClassificationId($classificationId)->get();

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

    public function listIndexHazardDescription()
    {
        $typeId = $this->request->get("typeId", "0");

        try {
            $data = ConfigJobActivityHazardDescription::whereTypeId($typeId)->get();

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

    public function listIndexHazardEffect()
    {
        $typeId = $this->request->get("typeId", "0");

        try {
            $data = ConfigJobActivityHazardEffect::whereTypeId($typeId)->get();

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

    public function listIndexHazardLevel()
    {
        try {
            $data["ND"] = ConfigGeneral::whereType("ND")->get();
            $data["NE"] = ConfigGeneral::whereType("NE")->get();
            $data["NC"] = ConfigGeneral::whereType("NC")->get();

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

    private function getChart($data) {

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

        $chart = array();

        if (!empty($data)) {
            $chart[] = $this->getChartItem("Creados", $data->created, $colors[1], $colors2[2]);
            $chart[] = $this->getChartItem("Configurados", $data->configured, $colors[2], $colors2[3]);
            $chart[] = $this->getChartItem("Pendientes", $data->pending, $colors[3], $colors2[4]);
        }

        return $chart;
    }

    private function getChartItem($label, $value, $color, $highlight) {
        $item = new \stdClass();
        $item->label = $label;
        $item->value = (int)$value;
        $item->color = $color;
        $item->highlight = $highlight;
        return $item;
    }


}
