<?php

namespace Wgroup\Controllers;

use Carbon\Carbon;
use Controller as BaseController;
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
use Wgroup\CustomerSafetyInspectionConfigListValidation\CustomerSafetyInspectionConfigListValidation;
use Wgroup\CustomerSafetyInspectionConfigListValidation\CustomerSafetyInspectionConfigListValidationDTO;
use Wgroup\CustomerSafetyInspectionListItem\CustomerSafetyInspectionListItem;
use Wgroup\CustomerSafetyInspectionListItem\CustomerSafetyInspectionListItemDTO;
use Wgroup\CustomerSafetyInspectionListItem\CustomerSafetyInspectionListItemService;
use Excel;
use Wgroup\Models\Customer;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerSafetyInspectionListItemController extends BaseController {

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

    public function __construct() {

        //set service
        $this->service = new CustomerSafetyInspectionListItemService();
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


    public function index(){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $listId = $this->request->get("listId", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            //Si es un usuario de un cliente
            $user = $this->user();


            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $listId);

            // Counts
            $recordsTotal = $this->service->getCount("", $listId);
            $recordsFiltered = $this->service->getCount(@$search['value'], $listId);

            // extract info
            $result = CustomerSafetyInspectionListItemDTO::parse($data);

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

    public function wizard()
    {
        $customerId = $this->request->get("customerId", "0");
        $safetyInspectionId = $this->request->get("safetyInspectionId", "0");

        try {
            $lists = $this->service->getLists($safetyInspectionId);
            $groups = $this->service->getListGroups($safetyInspectionId);
            $listItems = $this->service->getListItems($safetyInspectionId);
            $headerFields = $this->service->getHeaderFields($safetyInspectionId);
            $validationList = $this->service->getValidationList($safetyInspectionId);

            foreach ($headerFields as $field) {
                if ($field->dataType == "date" && $field->dateValue != '') {
                    $field->dateValue = Carbon::parse($field->dateValue);
                } else if ($field->dataType == "int" && $field->numericValue != '') {
                    $field->numericValue = floatval($field->numericValue);
                }
            }

            $result["wizard"] = $this->prepareList($lists, $groups, $listItems, $validationList);
            $result["headerFields"] = $headerFields;

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

    public function report()
    {
        $customerId = $this->request->get("customerId", "0");
        $safetyInspectionId = $this->request->get("safetyInspectionId", "0");
        $action = $this->request->get("action", "");

        try {
            $lists = $this->service->getLists($safetyInspectionId);
            $groups = $this->service->getListGroups($safetyInspectionId);
            $listItems = $this->service->getListItems($safetyInspectionId, $action);
            $validationList = $this->service->getValidationList($safetyInspectionId);

            $result["wizard"] = $this->prepareList($lists, $groups, $listItems, $validationList);

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

    private function prepareList($lists, $groups, $listItems, $validationList)
    {
        if (!$lists || !count($lists)) {
            return false;
        }

        foreach($lists as $list){

            // Asigno los questions

            $this->prepareDangerousness($list, $validationList);
            $this->prepareExistingControl($list, $validationList);
            $this->preparePriority($list, $validationList);

            foreach ($groups as $group) {
                if ($group->customerSafetyInspectionConfigListId == $list->id) {
                    $list->groups[] = $this->prepareGroup($group, $listItems);
                }
            }
        }

        return $lists;
    }

    private function prepareGroup($group, $questions)
    {
        if (!$questions || !count($questions)) {
            return false;
        }

        foreach ($questions as $question) {

            if ($question->groupId == $group->id) {

                if ($question->dangerousnessValue != null) {
                    $question->dangerousness = new CustomerSafetyInspectionConfigListValidation();
                    if (($model = CustomerSafetyInspectionConfigListValidation::find($question->dangerousnessValue))) {
                        $question->dangerousness = CustomerSafetyInspectionConfigListValidationDTO::parse($model);
                    }
                }

                if ($question->existingControlValue != null) {
                    $question->existingControl = new CustomerSafetyInspectionConfigListValidation();
                    if (($model = CustomerSafetyInspectionConfigListValidation::find($question->existingControlValue))) {
                        $question->existingControl = CustomerSafetyInspectionConfigListValidationDTO::parse($model);
                    }
                }

                $question->result = 0;

                if (isset($question->existingControl) && $question->existingControl && isset($question->dangerousness) && $question->dangerousness) {
                    $question->result = floatval($question->existingControl->value) *  floatval($question->dangerousness->value);
                }

                $question->action = CustomerSafetyInspectionListItem::getAction($question->action);

                $group->questions[] = $question;

                /*$question->plan = new CustomerDiagnosticPreventionActionPlanDTO();
                if (($mdlActionPlan = CustomerDiagnosticPreventionActionPlan::find($question->actionPlanId))) {
                    $question->plan = CustomerDiagnosticPreventionActionPlanDTO::parse($mdlActionPlan);
                }*/
            }
        }


        return $group;
    }

    private function prepareDangerousness($list, $validationList)
    {
        if (!$validationList || !count($validationList)) {
            return false;
        }

        foreach ($validationList as $validation) {

            if ($validation->customerSafetyInspectionConfigListId == $list->id && $validation->type == 'dangerousness') {

                $list->dangerousnessList[] = $validation;

            }
        }


        return $list;
    }

    private function prepareExistingControl($list, $validationList)
    {
        if (!$validationList || !count($validationList)) {
            return false;
        }

        foreach ($validationList as $validation) {

            if ($validation->customerSafetyInspectionConfigListId == $list->id && $validation->type == 'existingControl') {

                $list->existingControlList[] = $validation;

            }
        }


        return $list;
    }

    private function preparePriority($list, $validationList)
    {
        if (!$validationList || !count($validationList)) {
            return false;
        }

        foreach ($validationList as $validation) {

            if ($validation->customerSafetyInspectionConfigListId == $list->id && $validation->type == 'priority') {

                $list->priorityList[] = $validation;

            }
        }


        return $list;
    }

    public function listIndex()
    {
        $operation = $this->request->get("operation", "all");
        $listId = $this->request->get("listId", "0");

        try {

            $data = CustomerSafetyInspectionListItemDTO::parse(CustomerSafetyInspectionListItem::whereCustomerSafetyInspectionConfigListId($listId)
                ->whereIsActive(1)->get());

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

    public function save() {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            // Parse to model

            $model = CustomerSafetyInspectionListItemDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerSafetyInspectionListItemDTO::parse($model);

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

            if (!($model = CustomerSafetyInspectionListItem::find($id))) {
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

    public function get() {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerSafetyInspectionListItem::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerSafetyInspectionListItemDTO::parse($model);

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


            Excel::create('Contrato_Resumen', function($excel) use($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Resumen', function($sheet) use($data) {

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
    private function user() {
        if (!Auth::check())
            return null;


        return Auth::getUser();
    }

    private function getTokenSession($encode = false) {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }

    public function loadLocaleFromSession() {

        if ($sessionLocale = $this->getSessionLocale()) {
            return $sessionLocale;
        } else {
            if ($localeNegotiated = locale_accept_from_http($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $localeNegotiated = substr($localeNegotiated, 0, 2);
                return $localeNegotiated;
            }
        }
    }

    protected function getSessionLocale() {
        if (!Session::has(self::SESSION_LOCALE))
            return null;

        return Session::get(self::SESSION_LOCALE);
    }

    // Metdos pilotos
    private function random_numbers($digits) {
        $min = pow(10, $digits - 1);
        $max = pow(10, $digits) - 1;
        return mt_rand($min, $max);
    }

    private function download_file($url, $path) {

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

    function debug($message, $param = null) {
        if (!$param) {
            //Log::info($message);
        } else if (is_array($param)) {
            //Log::info(vsprintf($message, $param));
        } else {
            //Log::info(sprintf($message, $param));
        }
    }
}
