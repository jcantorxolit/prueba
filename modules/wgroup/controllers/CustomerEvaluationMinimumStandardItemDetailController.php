<?php

namespace Wgroup\Controllers;

use Carbon\Carbon;
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
use Wgroup\CustomerEvaluationMinimumStandard\CustomerEvaluationMinimumStandardService;
use Wgroup\CustomerEvaluationMinimumStandardItem\CustomerEvaluationMinimumStandardItemDTO;
use Wgroup\CustomerEvaluationMinimumStandardItem\CustomerEvaluationMinimumStandardItemService;
use Wgroup\CustomerEvaluationMinimumStandardItemDetail\CustomerEvaluationMinimumStandardItemDetail;
use Wgroup\CustomerEvaluationMinimumStandardItemDetail\CustomerEvaluationMinimumStandardItemDetailDTO;
use Wgroup\CustomerEvaluationMinimumStandardItemDetail\CustomerEvaluationMinimumStandardItemDetailService;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerEvaluationMinimumStandardItemDetailController extends BaseController
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
        $this->service = new CustomerEvaluationMinimumStandardItemDetailService();
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
        $customerEvaluationMinimumStandardItemId = $this->request->get("customer_evaluation_minimum_standard_item_id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, $customerEvaluationMinimumStandardItemId);

            // Counts
            $recordsTotal = $this->service->getCount('', $customerEvaluationMinimumStandardItemId);
            $recordsFiltered = $this->service->getCount(@$search['value'], $customerEvaluationMinimumStandardItemId);

            //var_dump($data);
            // extract info
            $result = CustomerEvaluationMinimumStandardItemDetailDTO::parse($data);

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
        $customerEvaluationMinimumStandardItemId = $this->request->get("customer_evaluation_minimum_standard_item_id", "0");

        try {
            $data['verificationList'] = $this->service->getAll($customerId, $customerEvaluationMinimumStandardItemId);
            $data['questionList'] = $this->service->getAllQuestion($customerEvaluationMinimumStandardItemId);
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

            ////Log::info($json);

            // parse
            $info = json_decode($json);

            //Get data
            $model = CustomerEvaluationMinimumStandardItemDetailDTO::fillAndSaveModel($info);


            // Parse to send on response
            $result = CustomerEvaluationMinimumStandardItemDetailDTO::parse($model);

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

    public function insert()
    {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {
            $json = base64_decode($text);

            $info = json_decode($json);

            CustomerEvaluationMinimumStandardItemDTO::update($info);

            CustomerEvaluationMinimumStandardItemDetailDTO::bulkInsert($info->verificationList, $info->customerEvaluationMinimumStandardItemId);


            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;

            $this->serviceItem->fillMissingMonthlyReport($info->customerEvaluationMinimumStandardId, $this->user->id);
            $this->serviceItem->saveMonthlyReport($info->customerEvaluationMinimumStandardId, $currentYear, $currentMonth, $this->user->id);
            $this->serviceItem->updateMonthlyReport($info->customerEvaluationMinimumStandardId, $currentYear, $currentMonth, $this->user->id);

            $info->verificationList = $this->service->getAll($info->customerId, $info->customerEvaluationMinimumStandardItemId);

            $result = $info;

            $this->response->setResult($result);

        } catch (Exception $exc) {


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

            if (!($model = CustomerEvaluationMinimumStandardItemDetail::find($id))) {
                throw new \Exception("Record not found");
            }

            //Get data
            $result = CustomerEvaluationMinimumStandardItemDetailDTO::parse($model);

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

            if (!($model = CustomerEvaluationMinimumStandardItemDetail::find($id))) {
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
