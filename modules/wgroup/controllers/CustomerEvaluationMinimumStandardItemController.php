<?php

namespace Wgroup\Controllers;

use Barryvdh\Snappy\Facades\SnappyPdf as SnappyPdf;
use Carbon\Carbon;
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
use Wgroup\ConfigMinimumStandardCycle\ConfigMinimumStandardCycle;
use Wgroup\ConfigMinimumStandardRate\ConfigMinimumStandardRate;
use Wgroup\ConfigMinimumStandardRate\ConfigMinimumStandardRateDTO;
use Wgroup\CustomerEvaluationMinimumStandard\CustomerEvaluationMinimumStandard;
use Wgroup\CustomerEvaluationMinimumStandardItem\CustomerEvaluationMinimumStandardItemDTO;
use Wgroup\CustomerEvaluationMinimumStandardItem\CustomerEvaluationMinimumStandardItemService;
use Wgroup\CustomerImprovementPlanActionPlan\CustomerImprovementPlanActionPlan;
use Wgroup\CustomerImprovementPlanActionPlan\CustomerImprovementPlanActionPlanDTO;
use Wgroup\MinimumStandard\MinimumStandardDTO;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDto;
use Wgroup\CustomerEvaluationMinimumStandardItem\CustomerEvaluationMinimumStandardItem;
use AdeN\Api\Helpers\CmsHelper;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package WGroup\api
 * @author David Blandon
 */
class CustomerEvaluationMinimumStandardItemController extends BaseController
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
        $this->service = new CustomerEvaluationMinimumStandardItemService();
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
        $standardId = $this->request->get("standard_id", 0);

        try {
            // get all tracking by customer with pagination

            $standardList = $this->service->getMinimumStandardParents($cycleId);
            $standardItemList = $this->service->getMinimumStandardItems($standardId, $cycleId);

            $dashboardStandard = $this->service->getDashboardMinimumStandardGroupByParent($standardId, $cycleId);
            $dashboardCycle = $this->service->getDashboardMinimumStandardGroupByCycle($standardId);
            $dashboardEvaluation = $this->service->getDashboardMinimumStandard($standardId);

            // Por ahora tendremos que enviar la informacion organizada desde el backend
            $cats = $this->prepareCategories($standardList, $standardItemList, $dashboardStandard);

            $data["standards"] = $cats;
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

    public function report()
    {

        $cycleId = $this->request->get("cycle_id", 0);
        $standardId = $this->request->get("standard_id", 0);
        $rateId = $this->request->get("rate_id", 0);

        try {
            // get all tracking by customer with pagination

            $standardList = $this->service->getPrograms($standardId);
            $standardItemList = $this->service->getMinimumStandardItemsByStatus($standardId, $cycleId, $rateId);
            $dashboardEvaluation = $this->service->getDashboardMinimumStandard($standardId);

            //var_dump($standardItemList);

            // Por ahora tendremos que enviar la informacion organizada desde el backend
            $cats = $this->preparePrograms($standardList, $standardItemList);

            //var_dump($cats);

            $data["standards"] = $cats;
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

    public function getActionPlan()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            /*
            //Si es un usuario de un cliente
            if ($model = $this->service->getCustomerIdByUserGroup())
            {
                if ($model->id != $id)
                    throw new \Exception("invalid parameters", 403);
            }
            */

            if (!($model = CustomerDiagnosticPreventionActionPlan::find($id))) {
                throw new \Exception("Customer not found", 404);
            }


            //Get data
            $result = CustomerDiagnosticPreventionActionPlanDTO::parse($model);

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

    public function getInformationReport($cycle_id = 0, $standard_id = 0, $rate_id = 0)
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $cycleId = $this->request->get("cycle_id", $cycle_id);
        $standardId = $this->request->get("standard_id", $standard_id);
        $rateId = $this->request->get("rate_id", $rate_id);

        try {
            // get all tracking by customer with pagination

            $programs = $this->service->getPrograms($standardId);
            $questions = $this->service->getQuestionsByStatus($standardId, $cycleId, $rateId);
            $dashboardDiagnostic = $this->service->getDashboardByDiagnostic($standardId);

            // Por ahora tendremos que enviar la informacion organizada desde el backend
            $programs = $this->preparePrograms($programs, $questions);

            $data["programs"] = $programs;

            $data["dashboardDiagnostic"] = $dashboardDiagnostic;
            // set count total ideas
            $this->response->setData($data);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());
            //Log::error($exc->getLine());
            //Log::error($exc->getFile());
            //Log::error($exc->getCode());
            //Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function export()
    {

        $data = $this->request->get("data", "");
        $standardId = $this->request->get("id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getExport($standardId);

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
        $standardId = $this->request->get("id", "");

        try {

            $customerEvaluation = CustomerEvaluationMinimumStandard::find($standardId);

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
            $cycles =  CmsHelper::parseToStdClass(ConfigMinimumStandardCycle::all()->toJson());

            $plans = $this->service->getMinimumStandardItemsImprovementPlan($standardId);

            foreach ($cycles as $cycle) {
                $standardList = $this->service->getMinimumStandardParents($cycle->id);
                $standardItemList = $this->service->getMinimumStandardItems($standardId, $cycle->id);

                $dashboardStandard = $this->service->getDashboardMinimumStandardGroupByParent($standardId, $cycleId);


                // // Por ahora tendremos que enviar la informacion organizada desde el backend
                $cats = $this->prepareCategories($standardList, $standardItemList, $dashboardStandard);

                $cycle->standards = $cats;

                foreach ($cycle->standards as $standard) {
                    $standard->total = 0;
                    if (isset($standard->children) && is_array($standard->children)) {
                        foreach ($standard->children as $child) {
                            $standard->total += isset($child->items) ? count($child->items) : 0;
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

                $cycle->items = count($standardItemList);
            }

            foreach ($plans as $plan) {
                $plan->actions = CustomerImprovementPlanActionPlanDTO::parse(CustomerImprovementPlanActionPlan::where('customer_improvement_plan_id', $plan->improvement_plan_id)->get());
            }

            $pdfData = [
                "cycles" => $cycles,
                "plans" => $plans,
                "customer" => $customer,
                "themeUrl" => CmsHelper::getThemeUrl(),
                "themePath" => CmsHelper::getThemePath()
            ];

            $fileName = "Tabla_Evaluación" . $standardId . ".pdf";

            //$pdf = SnappyPdf::loadView("aden.pdf::html.investigational", $data)->setPaper('legal')->setOrientation('portrait')->setWarnings(false);
            $pdf = SnappyPdf::loadView("aden.pdf::html.minimum_standard", $pdfData)->setPaper('A4')
                ->setOption('margin-top', '2.5cm')
                ->setOption('margin-bottom', 10)
                ->setOption('margin-left', 15)
                ->setOption('margin-right', 15)
                ->setOrientation('portrait')->setWarnings(false);
            return $pdf->download($fileName);

        } catch (Exception $ex) {
            Log::error($ex);

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportAll()
    {

        $data = $this->request->get("data", "");
        $standardId = $this->request->get("standard_id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getExportAll($standardId);

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

    private function prepareCategories($data, $items, $dashboardStandard)
    {

        if (!$data || !count($data)) {
            return false;
        }

        // Primero parseamos la informacion a DTO
        $standardList = MinimumStandardDTO::parseWitChildren($data);
        //$standardList = $data;

        // Preparamos cada objeto de tipo category
        foreach ($standardList as $standard) {

            //var_dump($standard->id);
            //var_dump($standard->children);

            foreach ($items as $item) {
                if ($item->minimum_standard_parent_id == $standard->id) {
                    $item->rate = null;
                    if (($mdlRate = ConfigMinimumStandardRate::find($item->rate_id))) {
                        $item->rate = ConfigMinimumStandardRateDTO::parse($mdlRate);
                    }
                    $standard->items[] = $item;
                }
            }

            //var_dump('START::');

            // Asigo informacion adicional
            if (!empty($dashboardStandard)) {
                foreach ($dashboardStandard as $dashboard) {
                    if ($dashboard->minimum_standard_id == $standard->id) {
                        $standard->advance = $dashboard->advance;
                        $standard->checked = $dashboard->checked;
                        $standard->average = $dashboard->average;
                        $standard->itemsCount = $dashboard->items;
                        $standard->total = $dashboard->total;
                        break;
                    }
                }
            }

            //var_dump('END::');
            //var_dump(count($standard->children));

            if (isset($standard->children)) {
                //var_dump('Children::');
                $standard->children = $this->prepareCategories($standard->children, $items, $dashboardStandard);
            }
        }

        return $standardList;
    }

    private function preparePrograms($data, $items)
    {
        //var_dump($data);
        if (!$data || !count($data)) {
            return false;
        }

        // Primero parseamos la informacion a DTO
        //$standardList = MinimumStandardDTO::parseWitChildren($data);
        //$standardList = $data;

        // Preparamos cada objeto de tipo category
        foreach ($data as $standard) {

            foreach ($items as $item) {
                if ($item->cycle_id == $standard->id) {
                    $item->rate = null;
                    if (($mdlRate = ConfigMinimumStandardRate::find($item->rate_id))) {
                        $item->rate = ConfigMinimumStandardRateDTO::parse($mdlRate);
                    }
                    $standard->children[] = $item;
                }
            }

            /*if (isset($standard->children)) {
                $standard->children = $this->preparePrograms($standard->children, $items);
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
            $model = CustomerEvaluationMinimumStandardItemDTO::fillAndSaveModel($info);

            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;

            //TODO
            //$this->service->fillMissingMonthlyReport($info->standard_id, $this->user->id);
            //$this->service->saveMonthlyReport($info->standard_id, $currentYear, $currentMonth, $this->user->id);
            //$this->service->updateMonthlyReport($info->standard_id, $currentYear, $currentMonth, $this->user->id);

            // Parse to send on response
            //$result = CustomerDiagnosticPreventionDTO::parse($model);
            $result["data"] = 'testing';
            $this->response->setResult($result);
            //return $this->index($info->programId, $info->standard_id);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function saveActionPlan()
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
            $model = CustomerDiagnosticPreventionActionPlanDTO::fillAndSaveModel($info);

            $result = CustomerDiagnosticPreventionActionPlanDTO::parse($model);

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

    public function saveComment()
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
            $model = CustomerDiagnosticPreventionCommentDTO::fillAndSaveModel($info);

            $result = CustomerDiagnosticPreventionCommentDTO::parse($model);

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

    public function getComments()
    {
        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $diagnosticDetailId = $this->request->get("diagnostic_detail_id", 0);

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $currentPage = $currentPage + 1;

            $data = $this->service->getAllComment(@$search['value'], $length, $currentPage, $diagnosticDetailId);

            // Counts
            $recordsTotal = $this->service->getAllCommentCount("", $diagnosticDetailId);
            $recordsFiltered = $this->service->getAllCommentCount(@$search['value'], $diagnosticDetailId);

            $result = CustomerDiagnosticPreventionCommentDTO::parse($data);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal($recordsTotal);
            $this->response->setRecordsFiltered($recordsFiltered);
        } catch (Exception $exc) {

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
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

            if (!($model = CustomerEvaluationMinimumStandardItem::find($id))) {
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerEvaluationMinimumStandardItemDTO::parse($model);

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

    public function getQuestion()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerDiagnosticPrevention::find($id))) {
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = ProgramPreventionQuestionDTO::parse(ProgramPreventionQuestion::find($model->question_id));

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
