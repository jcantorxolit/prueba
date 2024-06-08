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
use Wgroup\Budget\Budget;
use Wgroup\Budget\BudgetDTO;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\ServiceApi;
use Wgroup\CustomerEmployee\CustomerEmployeeService;
use Wgroup\CustomerImprovementPlan\CustomerImprovementPlan;
use Wgroup\CustomerImprovementPlanActionPlan\CustomerImprovementPlanActionPlan;
use Wgroup\CustomerImprovementPlanActionPlan\CustomerImprovementPlanActionPlanDTO;
use Wgroup\CustomerImprovementPlanActionPlan\CustomerImprovementPlanActionPlanService;
use Wgroup\CustomerImprovementPlanCause\CustomerImprovementPlanCause;
use Wgroup\CustomerImprovementPlanCause\CustomerImprovementPlanCauseDTO;
use Wgroup\CustomerImprovementPlanCauseRootCause\CustomerImprovementPlanCauseRootCause;
use Wgroup\CustomerImprovementPlanCauseRootCause\CustomerImprovementPlanCauseRootCauseDTO;
use Wgroup\ImprovementPlanCause\ImprovementPlanCause;
use Wgroup\ImprovementPlanCause\ImprovementPlanCauseDTO;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategory;
use Wgroup\Models\Customer;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerImprovementPlanActionPlanController extends BaseController
{

    const SESSION_LOCALE = 'rainlab.translate.locale';

    private $translate;
    private $service;
    private $serviceEmployee;
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
        $this->service = new CustomerImprovementPlanActionPlanService();
        $this->serviceEmployee = new CustomerEmployeeService();
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
        $customerImprovementPlanId = $this->request->get("improvement_id", "0");
        $customerImprovementPlanCauseRootCauseId = $this->request->get("root_cause_id", "0");
        $data = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $currentPage = $currentPage + 1;

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            if ($customerImprovementPlanId != 0) {
                $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, $customerImprovementPlanId, $audit);
                $recordsTotal = $this->service->getCount("", $customerImprovementPlanId, null);
                $recordsFiltered = $this->service->getCount(@$search['value'], $customerImprovementPlanId, $audit);
            } else {
                $data = $this->service->getAllByRootCause(@$search['value'], $length, $currentPage, $orders, $customerImprovementPlanCauseRootCauseId, $audit);
                $recordsTotal = $this->service->getCountByRootCause("", $customerImprovementPlanCauseRootCauseId, null);
                $recordsFiltered = $this->service->getCountByRootCause(@$search['value'], $customerImprovementPlanCauseRootCauseId, $audit);
            }


            // extract info
            $result = CustomerImprovementPlanActionPlanDTO::parse($data);

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

    public function listData()
    {
        $customerId = $this->request->get("customer_id", "0");
        $causeId = $this->request->get("cause_id", "0");
        $improvementPlanId = $this->request->get("improvement_id", "0");

        try {
            $data['responsible'] = Customer::getAgentsAndUsers($customerId);
            $data['causeList'] = ImprovementPlanCauseCategory::all();
            $data['subCauseList'] = ImprovementPlanCauseDTO::parse(ImprovementPlanCause::all());
            $data['rootCauseList'] = CustomerImprovementPlanCauseRootCauseDTO::parse(CustomerImprovementPlanCauseRootCause::whereCustomerImprovementPlanCauseId($causeId)->get());
            $data['entry'] = BudgetDTO::parse(Budget::all());
            $data['improvementCauseList'] = CustomerImprovementPlanCauseDTO::parse(CustomerImprovementPlanCause::whereCustomerImprovementPlanId($improvementPlanId)->get());

            // set count total ideas
            $this->response->setData($data);
        } catch (Exception $exc) {
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

            $model = CustomerImprovementPlanActionPlanDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerImprovementPlanActionPlanDTO::parse($model);

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
        $id = $this->request->get("id", "0");

        try {

            //Log::info("customer [" . $id . "]s::");

            if (!($model = CustomerImprovementPlanActionPlan::find($id))) {
                throw new Exception("Record not found to delete.");
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

            if (!($model = CustomerImprovementPlanActionPlan::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerImprovementPlanActionPlanDTO::parse($model);

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

    public function getEntity()
    {

        // Preapre parameters for query
        $entityName = $this->request->get("type", "");
        $entityId = $this->request->get("id", "0");

        try {

            if ($entityId == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            $model = CustomerImprovementPlanActionPlan::where('entityId', $entityId)->where('entityName', $entityName)->first();

            $result = CustomerImprovementPlanActionPlanDTO::parse($model);

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
