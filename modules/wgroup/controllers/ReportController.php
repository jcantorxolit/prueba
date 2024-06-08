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
use Wgroup\Classes\ApiResponse;
use Wgroup\CollectionData\CollectionData;
use Wgroup\CollectionData\CollectionDataDTO;
use Wgroup\Models\Agent;
use Wgroup\Report\Report;
use Wgroup\Report\ReportDTO;
use Wgroup\Report\ReportService;
use Excel;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class ReportController extends BaseController
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
        $this->service = new ReportService();
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
        $id = $this->request->get("id", "0");
        $module = $this->request->get("module", "customer");

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

            $user = $this->getUser();

            $isAgent = false;
            $isCustomer = false;

            if ($user->wg_type == "agent") {
                $isAgent = true;
            } else if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                $isCustomer = true;
            }

            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, '', $isAgent, $isCustomer, $module);

            // Counts
            $recordsTotal = $this->service->getCount('', $isAgent, $isCustomer, $module);
            $recordsFiltered = $this->service->getCount(@$search['value'], $isAgent, $isCustomer, $module);

            // extract info
            $result = ReportDTO::parse($data);
            //$result= array();
            // set count total ideas
            //Log::info($data);

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

    public function generate()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $id = $this->request->get("id", "0");
        $reportId = $this->request->get("report_id", "0");
        $text = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            $currentPage = $currentPage + 1;

            $user = $this->getUser();

            $mandatoryFilters = null;

            if ($user->wg_type == "system") {

            } else if ($user->wg_type == "agent") {

                $agentModel = Agent::whereUserId($user->id)->first();

                if ($agentModel != null) {
                    $agentId = $agentModel->id;
                }

                //$mandatoryFilters[] = $this->getFilter('customerId', $user->company, '=', 'AND');

            } else if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                $mandatoryFilters[] = $this->getFilter('customer_id', $user->company, '=', 'AND');
            }

            // get all tracking by customer with pagination
            $data = $this->service->getAllByGenerate(@$search['value'], $length, $currentPage, $orders, "", $reportId, $info, $mandatoryFilters);

            $model = ReportDTO::parse(Report::find($reportId));

            $hiddenFields = array();

            foreach ($model->collection->fields as $field) {
                if ($field->visible == 0) {
                    $hiddenFields[$field->alias] = $field->name;
                }
            }

            $table = "";

            if (count($data) > 0) {
                $resultArray = json_decode(json_encode($data), true);

                $keys = array_keys($resultArray[0]);

                $thead = "";
                $tbody = "";
                $cols = "";
                $rows = "";

                foreach ($keys as $key) {

                    if (array_key_exists($key, $hiddenFields)) {
                        continue;
                    }

                    $cols .= '<th class="sorting" tabindex="0" aria-controls="dtReportDyn" rowspan="1" colspan="1" style="width: 0px;" ria-sort="descending">' . $key . '</th>';
                }
                $thead = '<thead><tr role="row">' . $cols . '</tr></thead>';

                $isAlterRow = false;

                foreach ($resultArray as $data) {
                    $class = $isAlterRow ? "odd" : "even";

                    $rows .= '<tr role="row" class="' . $class . '">';
                    foreach ($data as $key => $value) {
                        if (array_key_exists($key, $hiddenFields)) {
                            continue;
                        }
                        $rows .= '<td class="ng-scope">' . $value . '</td>';
                    }
                    $rows .= '</tr>';

                    $isAlterRow = !$isAlterRow;
                }

                $tbody = '<tbody>' . $rows . '</tbody>';


                $table = '<div id="dtPollResultOptions_wrapper" class="dataTables_wrapper form-inline no-footer"><table datatable="" dt-options="dtPollResultOptions" id="dtPollResultOptions" dt-columns="dtPollResultColumns" class="table table-bordered table-hover ng-isolate-scope no-footer dataTable" style="display: table; width: 100%;" role="grid" aria-describedby="dtPollResultOptions_info">' . $thead . $tbody . '</table></div>';
            }

            $model = CollectionData::find($info->collection->id);

            $model->html = $table;

            //$result = ReportDTO::parse($model);

            $this->response->setResult($model);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc);

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function dynamically()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $id = $this->request->get("id", "0");
        $text = $this->request->get("data", "");

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
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            if (count($info->fields) > 0) {
                $data = $this->service->getAllByDynamically(@$search['value'], $length, $currentPage, $orders, "", 0, $info);
            } else {
                $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders);
            }

            $table = "";

            if (count($data) > 0) {
                $resultArray = json_decode(json_encode($data), true);

                $keys = array_keys($resultArray[0]);

                $thead = "";
                $tbody = "";
                $cols = "";
                $rows = "";

                foreach ($keys as $key) {
                    $cols .= '<th class="sorting" tabindex="0" aria-controls="dtReportDyn" rowspan="1" colspan="1" style="width: 0px;" ria-sort="descending">' . $key . '</th>';
                }
                $thead = '<thead><tr role="row">' . $cols . '</tr></thead>';

                $isAlterRow = false;

                foreach ($resultArray as $data) {

                    $class = $isAlterRow ? "odd" : "even";

                    $rows .= '<tr role="row" class="' . $class . '">';
                    foreach ($data as $key => $value) {
                        $rows .= '<td class="ng-scope">' . $value . '</td>';
                    }
                    $rows .= '</tr>';

                    $isAlterRow = !$isAlterRow;
                }

                $tbody = '<tbody>' . $rows . '</tbody>';


                $table = '<div id="dtPollResultOptions_wrapper" class="dataTables_wrapper form-inline no-footer"><table datatable="" dt-options="dtPollResultOptions" id="dtPollResultOptions" dt-columns="dtPollResultColumns" class="table table-bordered table-hover ng-isolate-scope no-footer dataTable" style="display: table; width: 100%;" role="grid" aria-describedby="dtPollResultOptions_info">' . $thead . $tbody . '</table></div>';
            }


            $model = CollectionData::find($info->collection->id);

            $model->html = $table;

            //$result = ReportDTO::parse($model);

            $this->response->setResult($model);

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

            $model = ReportDTO::fillAndSaveModel($info);

            $result = ReportDTO::parse($model);

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

            if (!($model = Report::find($id))) {
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = ReportDTO::parse($model);

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

    public function export()
    {

        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            $info = json_decode($json);

            $user = $this->getUser();

            $mandatoryFilters = null;

            if ($user->wg_type == "system") {

            } else if ($user->wg_type == "agent") {

                $agentModel = Agent::whereUserId($user->id)->first();

                if ($agentModel != null) {
                    $agentId = $agentModel->id;
                }

                //$mandatoryFilters[] = $this->getFilter('customerId', $user->company, '=', 'AND');

            } else if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                $mandatoryFilters[] = $this->getFilter('customer_id', $user->company, '=', 'AND');
            }

            $data = $this->service->getAllByGenerate("", 0, 1, null, "", $info->id, $info, $mandatoryFilters);

            Excel::create('Reporte', function ($excel) use ($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Sheetname', function ($sheet) use ($data) {

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


    public function exportDynamic()
    {
        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $id = $this->request->get("id", "0");
        $text = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $json = base64_decode($text);

            //Log::info($json);

            // parse
            //Log::info("Antes");
            $info = json_decode($json);

            $currentPage = $currentPage + 1;

            $model = CollectionData::find($info->collectionId);

            //Log::info("Despues");


            // get all tracking by customer with pagination
            if (count($info->fields) > 0) {
                $data = $this->service->getAllByDynamicallyExport($info, $model->viewName);
            } else {
                $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders);
            }

            //$data  = array();


            Excel::create('Reporte', function ($excel) use ($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Reporte', function ($sheet) use ($data) {

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


    private function getFilter($filedName, $fieldValue, $criteria, $condition)
    {
        $filter = new \stdClass();
        $filter->value = $fieldValue;
        $filter->field = $this->getField($filedName);
        $filter->criteria = $this->getCriteria($criteria);
        $filter->condition = $this->getCondition($condition);

        return $filter;
    }

    private function getCondition($value)
    {
        $condition = new \stdClass();
        $condition->value = $value;

        return $condition;
    }

    private function getCriteria($value)
    {
        $criteria = new \stdClass();
        $criteria->value = $value;

        return $criteria;
    }

    private function getField($name)
    {
        $field = new \stdClass();
        $field->name = $name;

        return $field;
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
