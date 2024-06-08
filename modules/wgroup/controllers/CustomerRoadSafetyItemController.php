<?php

namespace Wgroup\Controllers;

use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\KendoCriteriaHelper;
use Barryvdh\Snappy\Facades\SnappyPdf as SnappyPdf;
use Carbon\Carbon;
use Controller as BaseController;
use Excel;
use Exception;
use Log;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Request;
use Session;
use System\Models\Parameters;
use Wgroup\Classes\ApiResponse;
use Wgroup\ConfigRoadSafetyCycle\ConfigRoadSafetyCycle;
use Wgroup\ConfigRoadSafetyRate\ConfigRoadSafetyRate;
use Wgroup\ConfigRoadSafetyRate\ConfigRoadSafetyRateDTO;
use Wgroup\CustomerRoadSafety\CustomerRoadSafety;
use Wgroup\CustomerRoadSafety\CustomerRoadSafetyService;
use Wgroup\CustomerRoadSafetyItem\CustomerRoadSafetyItem;
use Wgroup\CustomerRoadSafetyItem\CustomerRoadSafetyItemDTO;
use Wgroup\CustomerRoadSafetyItem\CustomerRoadSafetyItemService;
use Wgroup\CustomerImprovementPlanActionPlan\CustomerImprovementPlanActionPlan;
use Wgroup\CustomerImprovementPlanActionPlan\CustomerImprovementPlanActionPlanDTO;
use Wgroup\RoadSafety\RoadSafetyDTO;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDto;
use Illuminate\Support\Facades\Input;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package WGroup\api
 * @author David Blandon
 */
class CustomerRoadSafetyItemController extends BaseController
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
        $this->service = new CustomerRoadSafetyItemService();
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

        $cycleId = $this->request->get("cycle_id", 0);
        $roadSafetyId = $this->request->get("road_safety_id", 0);
        $pageSize = $this->request->get("page_size", 10);
        $currentPage = $this->request->get("current_page", 1);

        try {
            // get all tracking by customer with pagination

            $roadSafetyList = $this->service->getRoadSafetyParents($cycleId);
            $roadSafetyItemList = $this->service->getRoadSafetyItems($roadSafetyId, $cycleId, $pageSize, $currentPage);

            $dashboardRoadSafety = $this->service->getDashboardRoadSafetyGroupByParent($roadSafetyId, $cycleId);
            $dashboardCycle = $this->service->getDashboardRoadSafetyGroupByCycle($roadSafetyId);
            $dashboardEvaluation = $this->service->getDashboardRoadSafety($roadSafetyId);

            // Por ahora tendremos que enviar la informacion organizada desde el backend
            $cats = $this->prepareCategories($roadSafetyList, $roadSafetyItemList, $dashboardRoadSafety);

            $data["totalItems"] = $questions = $this->service->getRoadSafetyItemsCount($roadSafetyId, $cycleId);
            $data["roadSafetyList"] = $cats;
            $data["cycles"] = $dashboardCycle;
            $data["evaluation"] = $dashboardEvaluation;

            $this->response->setData($data);

        } catch (Exception $exc) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexParent()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerRoadSafetyId', "operator" => 'eq'),
                array("field" => 'cycleId', "operator" => 'eq')
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);

            $result = $this->service->allParent($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["total"]);
            $this->response->setRecordsFiltered($result["total"]);
        } catch (Exception $exc) {
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexItem()
    {
        $request = Request::instance();
        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerRoadSafetyId', "operator" => 'eq'),
                array("field" => 'cycleId', "operator" => 'eq'),
                array("field" => 'roadSafetyId', "operator" => 'eq')
            ];

            $criteria = KendoCriteriaHelper::parse($content, $mandatoryFilters);
            $criteria->pageSize = 0;

            $result = $this->service->allItem($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["total"]);
            $this->response->setRecordsFiltered($result["total"]);
        } catch (Exception $exc) {
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function report()
    {

        $cycleId = $this->request->get("cycle_id", 0);
        $roadSafetyId = $this->request->get("road_safety_id", 0);
        $rateId = $this->request->get("rate_id", 0);

        try {
            // get all tracking by customer with pagination

            $roadSafetyList = $this->service->getPrograms($roadSafetyId);
            $roadSafetyItemList = $this->service->getRoadSafetyItemsByStatus($roadSafetyId, $cycleId, $rateId);
            $dashboardEvaluation = $this->service->getDashboardRoadSafety($roadSafetyId);

            //var_dump($roadSafetyItemList);

            // Por ahora tendremos que enviar la informacion organizada desde el backend
            $cats = $this->preparePrograms($roadSafetyList, $roadSafetyItemList);

            //var_dump($cats);

            $data["roadSafetyList"] = $cats;
            //$data["cycles"] = $dashboardCycle;
            $data["evaluation"] = $dashboardEvaluation;

            $this->response->setData($data);

        } catch (Exception $exc) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function export()
    {

        $data = $this->request->get("data", "");
        $roadSafetyId = $this->request->get("id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getExport($roadSafetyId);

            //var_dump($data);

            Excel::create('AutoEvaluactionEMExcel', function ($excel) use ($data) {
                // Call them separately
                $excel->setDescription('Gestion');

                $excel->sheet('Reporte', function ($sheet) use ($data) {

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

    public function exportPdf()
    {

        $data = $this->request->get("data", "");
        $roadSafetyId = $this->request->get("id", "");

        try {
            $serviceSafetyRoad = new CustomerRoadSafetyService();

            $customerEvaluation = CustomerRoadSafety::find($roadSafetyId);

            $customerModel = CustomerDto::parse(Customer::find($customerEvaluation->customer_id));

            $customer = new \stdClass();
            $customer->name = $customerModel->businessName;
            $customer->documentNumber = $customerModel->documentNumber;
            $customer->address = $customerModel->address ? $customerModel->address->value : '';
            $customer->phone = $customerModel->phone ? $customerModel->phone->value : '';
            $customer->date = Carbon::now('America/Bogota')->format('d/m/Y');

            foreach($customerModel->contacts as $info) {
                if (isset($info->type) && $info->type != null && $info->type->value == 'dir') {
                    $customer->address = $info->value;
                    break;
                }
            }

            foreach($customerModel->contacts as $info) {
                if (isset($info->type) && $info->type != null && $info->type->value == 'tel') {
                    $customer->phone = $info->value;
                    break;
                }
            }

            $cycleId = 1;
            $cycles = ConfigRoadSafetyCycle::all();

            $plans = $this->service->getRoadSafetyItemsImprovementPlan($roadSafetyId);

            $weightedValues = $serviceSafetyRoad->getAllSummaryWeighted([], $roadSafetyId);

            foreach ($cycles as $cycle) {
                $roadSafetyList = $this->service->getRoadSafetyParents($cycle->id);
                $roadSafetyItemList = $this->service->getRoadSafetyItems($roadSafetyId, $cycle->id, 10000, 1);

                $dashboardRoadSafety = $this->service->getDashboardRoadSafetyGroupByParent($roadSafetyId, $cycleId);
                $dashboardCycle = $this->service->getDashboardRoadSafetyGroupByCycle($roadSafetyId);
                $dashboardEvaluation = $this->service->getDashboardRoadSafety($roadSafetyId);

                // Por ahora tendremos que enviar la informacion organizada desde el backend
                $cats = $this->prepareCategories($roadSafetyList, $roadSafetyItemList, $dashboardRoadSafety);

                //var_dump($cats);

                $cycle->roadSafetyList = $cats;

                foreach ($cycle->roadSafetyList as $roadSafety) {
                    $roadSafety->total = 0;
                    if (isset($roadSafety->children) && is_array($roadSafety->children)) {
                        foreach ($roadSafety->children as $child) {
                            $roadSafety->total += isset($child->items) ? count($child->items) : 0;
                            if (isset($child->items) && is_array($child->items)) {
                                $child->weight = 0;
                                $child->totalAverage = 0;
                                foreach ($child->items as $item) {
                                    $child->weight += floatval($item->value);
                                    if ($item != null && $item->rate != null && ($item->rate->code == 'cp' || $item->rate->code == 'nac')) {
                                        $child->totalAverage += floatval($item->value);
                                    }
                                }
                            }
                        }
                    }
                }

                $cycle->items = count($roadSafetyItemList);


            }

            foreach ($plans as $plan) {
                $plan->actions = CustomerImprovementPlanActionPlanDTO::parse(CustomerImprovementPlanActionPlan::where('customer_improvement_plan_id', $plan->improvement_plan_id)->get());
            }

            $template = [];
            $template["cycles"] = $cycles;
            $template["plans"] = $plans;
            $template["weightedValues"] = $weightedValues;
            $template["customer"] = $customer;

            /*$data["roadSafetyList"] = $cats;
            $data["cycles"] = $dashboardCycle;
            $data["evaluation"] = $dashboardEvaluation;

            $data = [];*/

            $fileName = "Tabla_Evaluación" . $roadSafetyId . ".pdf";

            //$pdf = SnappyPdf::loadView("aden.pdf::html.investigational", $data)->setPaper('legal')->setOrientation('portrait')->setWarnings(false);
            $pdf = SnappyPdf::loadView("aden.pdf::html.road_safety", $template)->setPaper('A4')
                ->setOption('margin-top', '2.5cm')
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 15)
                ->setOption('margin-right', 15)
                ->setOrientation('portrait')->setWarnings(false);
            return $pdf->download($fileName);

        } catch (Exception $exc) {

            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function exportAll()
    {

        $data = $this->request->get("data", "");
        $roadSafetyId = $this->request->get("road_safety_id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getExportAll($roadSafetyId);

            Excel::create('SG-SST', function ($excel) use ($data) {
                // Call them separately
                $excel->setDescription('Gestion');

                $excel->sheet('Reporte', function ($sheet) use ($data) {

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

    private function prepareCategories($data, $items, $dashboardRoadSafety)
    {

        if (!$data || !count($data)) {
            return false;
        }

        // Primero parseamos la informacion a DTO
        $roadSafetyList = RoadSafetyDTO::parseWitChildren($data);
        //$roadSafetyList = $data;

        // Preparamos cada objeto de tipo category
        foreach ($roadSafetyList as $roadSafety) {

            //var_dump($roadSafety->id);
            //var_dump($roadSafety->children);

            foreach ($items as $item) {
                if ($item->road_safety_parent_id == $roadSafety->id) {
                    $item->rate = null;
                    if (($mdlRate = ConfigRoadSafetyRate::find($item->rate_id))) {
                        $item->rate = ConfigRoadSafetyRateDTO::parse($mdlRate);
                    }
                    $roadSafety->items[] = $item;
                }
            }

            //var_dump('START::');

            // Asigo informacion adicional
            if (!empty($dashboardRoadSafety)) {
                foreach ($dashboardRoadSafety as $dashboard) {
                    if ($dashboard->road_safety_id == $roadSafety->id) {
                        $roadSafety->advance = $dashboard->advance;
                        $roadSafety->checked = $dashboard->checked;
                        $roadSafety->average = $dashboard->average;
                        $roadSafety->itemsCount = $dashboard->items;
                        $roadSafety->total = $dashboard->total;
                        break;
                    }
                }
            }

            //var_dump('END::');
            //var_dump(count($roadSafety->children));

            if (isset($roadSafety->children)) {
                //var_dump('Children::');
                $roadSafety->children = $this->prepareCategories($roadSafety->children, $items, $dashboardRoadSafety);
            }
        }

        return $roadSafetyList;
    }

    private function preparePrograms($data, $items)
    {
        //var_dump($data);
        if (!$data || !count($data)) {
            return false;
        }

        // Primero parseamos la informacion a DTO
        //$roadSafetyList = RoadSafetyDTO::parseWitChildren($data);
        //$roadSafetyList = $data;

        // Preparamos cada objeto de tipo category
        foreach ($data as $roadSafety) {

            foreach ($items as $item) {
                if ($item->cycle_id == $roadSafety->id) {
                    $item->rate = null;
                    if (($mdlRate = ConfigRoadSafetyRate::find($item->rate_id))) {
                        $item->rate = ConfigRoadSafetyRateDTO::parse($mdlRate);
                    }
                    $roadSafety->children[] = $item;
                }
            }

            /*if (isset($roadSafety->children)) {
                $roadSafety->children = $this->preparePrograms($roadSafety->children, $items);
            }*/
        }

        return $data;
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

            //Get data
            $model = CustomerRoadSafetyItemDTO::fillAndSaveModel($info);

            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;

            //TODO
            $this->service->fillMissingMonthlyReport($info->roadSafety_id, $this->user->id);
            $this->service->saveMonthlyReport($info->roadSafety_id, $currentYear, $currentMonth, $this->user->id);
            $this->service->updateMonthlyReport($info->roadSafety_id, $currentYear, $currentMonth, $this->user->id);

            // Parse to send on response
            $result = CustomerRoadSafetyItemDTO::parse($model);
            //$result["data"] = 'testing';
            $this->response->setResult($result);
            //return $this->index($info->programId, $info->roadSafety_id);

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

            if (!($model = CustomerRoadSafetyItem::find($id))) {
                throw new \Exception("Record not found");
            }

            //Get data
            $result = CustomerRoadSafetyItemDTO::parse($model);

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
