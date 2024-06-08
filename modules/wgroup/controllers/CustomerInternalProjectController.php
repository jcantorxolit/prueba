<?php

namespace Wgroup\Controllers;

use AdeN\Api\Classes\Chart;
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
use Wgroup\Classes\ServiceCustomerProject;
use Wgroup\CustomerInternalProject\CustomerInternalProjectService;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\CustomerParameter\CustomerParameterDTO;
use Wgroup\CustomerUser\CustomerUser;
use Wgroup\CustomerUser\CustomerUserDTO;
use Wgroup\Models\Agent;
use Wgroup\Models\CustomerManagement;
use Wgroup\Models\CustomerManagementDetailActionPlan;
use Wgroup\Models\CustomerManagementDetailActionPlanDTO;
use Wgroup\Models\CustomerManagementProgramDTO;
use Wgroup\CustomerInternalProject\CustomerInternalProject;
use Wgroup\CustomerInternalProjectAgent\CustomerInternalProjectAgent;
use Wgroup\CustomerInternalProjectAgentTask\CustomerInternalProjectAgentTask;
use Wgroup\CustomerInternalProjectAgentTask\CustomerInternalProjectAgentTaskDTO;
use Wgroup\CustomerInternalProject\CustomerInternalProjectDTO;
use Wgroup\Models\CustomerTracking;
use Wgroup\Models\CustomerTrackingDTO;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerInternalProjectController extends BaseController
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
        $this->service = new CustomerInternalProjectService();
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

            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $customerId);

            // Counts
            $recordsTotal = $this->service->getCount();
            $recordsFiltered = $this->service->getCount(@$search['value']);

            // extract info
            $result = CustomerInternalProjectDTO::parse($data);

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

    public function setting()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $agentId = $this->request->get("agent_id", "0");
        $month = $this->request->get("month", "0");
        $year = $this->request->get("year", "0");
        $customerId = $this->request->get("customer_id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());
        $arl = $this->request->get("arl", "0");
        $os = $this->request->get("os", "-1");

        try {

            // Validate permissions
            /*if (!UserGroup::hasRole('admin')) {
                throw new Exception(Message::trans("messages.error.notauthorized", array()));
            }*/

            $currentPage = $currentPage + 1;

            $user = $this->user();



            // get all tracking by customer with pagination
            //$data = $this->service->getAllSettingBy($orders, $agentId);

            ////Log::info(count($data));
            ////////////////

            $user = $this->user();

            /*if ($currentAgentId == 0)
            {
                $agentModel = Agent::whereUserId($user->id)->first();

                if ($agentModel != null)
                {
                    $agentId = $agentModel->id;
                }
            }*/

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            //Log::info($user->wg_type);

            if ($user->wg_type == "customerAdmin") {
                //$data = $this->service->getAllSummaryBy($orders, $agentId, $customerId, $month);
                $data = $this->service->getAllSettingBy($orders, $agentId, $customerId, $month, $year);
            } else if ($user->wg_type == "customerUser") {
                if ($agentId == 0)
                {
                    $agentModel = CustomerUser::whereUserId($user->id)->first();

                    if ($agentModel != null)
                    {
                        $agentId = $agentModel->id;
                    }
                }
                //$data = $this->service->getAllSummaryByAgent($orders, $agentId, $month);
                $data = $this->service->getAllSettingByAgent($orders, $agentId, $month, $year);
            } else if ($user->wg_type == "customer") {
                $data = $this->service->getAllSettingByCustomerId($orders, $user->company);
            } else {
                $data = $this->service->getAllSettingBy($orders, $agentId, $customerId, $month, $year);
            }

            ///////////////


            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            //$result = CustomerInternalProjectDTO::parse($data, "2");

            $result = array_map(function ($row) {
                $chart = [
                    $this->parseChartPieData("Programadas", $row ? $row->scheduledHours : 0),
                    $this->parseChartPieData("Ejecutadas", $row ? $row->runningHours : 0)
                ];

                $row->chart = (new Chart)->getChartPie($chart);

                return $row;
            }, CustomerInternalProjectDTO::parse($data, "2"));

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal($recordsTotal);
            $this->response->setRecordsFiltered($recordsFiltered);
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

    public function summary()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $currentAgentId = $this->request->get("agent_id", "0");
        $customerId = $this->request->get("customer_id", "0");
        $month = $this->request->get("month", "0");
        $year = $this->request->get("year", "0");
        $arl = $this->request->get("arl", "0");
        $os = $this->request->get("os", "-1");
        $type = $this->request->get("type", null);

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
            $user = $this->user();

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            if ($user->wg_type == "customerAdmin") {
                $data = $this->service->getAllSummaryBy($orders, $currentAgentId, $customerId, $month, $year, $arl, $os, $type);
            } else if ($user->wg_type == "customerUser") {
                $agentModel = CustomerUser::whereUserId($user->id)->first();

                $agentId = 0;

                if ($agentModel != null)
                {
                    $agentId = $agentModel->id;
                }

                $data = $this->service->getAllSummaryByAgent($orders, $agentId, $month, $year, $arl, $os, $type);
            } else if ($user->wg_type == "customer") {
                $data = $this->service->getAllSummaryByCustomer($orders, $user->company, $month, $year, $os, $type);
            } else {
                $data = $this->service->getAllSummaryBy($orders, $currentAgentId, $customerId, $month, $year, $arl, $os, $type);
            }

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            $result = array_map(function ($row) {
                $chart = [
                    $this->parseChartPieData("Programadas", $row ? $row->scheduledHours : 0),
                    $this->parseChartPieData("Ejecutadas", $row ? $row->runningHours : 0)
                ];

                $row->chart = (new Chart)->getChartPie($chart);

                return $row;
            }, CustomerInternalProjectDTO::parse($data));

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

    private function parseChartPieData($label, $value)
    {
        $data = new \stdClass();
        $data->label = $label;
        $data->value = $value;
        return $data;
    }

    public function fillList()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $currentAgentId = $this->request->get("agent_id", "0");
        $customerId = $this->request->get("customer_id", "0");
        $month = $this->request->get("month", "0");
        $year = $this->request->get("year", "0");
        $arl = $this->request->get("arl", "0");
        $os = $this->request->get("os", "-1");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $data = $this->service->getAllYears();
            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            $result["years"] = $data;
            $result["currentYear"] = Carbon::now('America/Bogota')->year;
            $result["currentMonth"] = Carbon::now('America/Bogota')->month;

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

    public function gantt()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $query = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $json = base64_decode($query);

            //Log::info($json);

            $querySearch = json_decode($json);

            $user = $this->user();

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination

            $data = [];
            if ($user->wg_type == "customerAdmin") {
                if ($querySearch->type == "GE") {
                    $data = $this->service->getAllGanttEconomicGroup($orders, $querySearch->currentAgentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                } else {
                    $data = $this->service->getAllGanttCustomer($orders, $querySearch->currentAgentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                }
            } else if ($user->wg_type == "customerUser") {
                $agentModel = CustomerUser::whereUserId($user->id)->first();

                $agentId = 0;

                if ($agentModel != null)
                {
                    $agentId = $agentModel->id;
                }

                if ($querySearch->type == "GE") {
                    $data = $this->service->getAllGanttEconomicGroup($orders, $agentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                } else {
                    $data = $this->service->getAllGanttCustomer($orders, $agentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                }
            }

            $result = CustomerInternalProjectDTO::parse($data, '11');

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            //$result = CustomerProjectDTO::parse($data);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal($recordsTotal);
            $this->response->setRecordsFiltered($recordsFiltered);
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

    public function ganttResource()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $query = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $json = base64_decode($query);

            //Log::info($json);

            $querySearch = json_decode($json);

            $user = $this->user();

            $currentPage = $currentPage + 1;

            $data = [];
            if ($user->wg_type == "customerAdmin") {
                if ($querySearch->type == "GE") {
                    $data = $this->service->getAllGanttEconomicGroupResource($orders, $querySearch->currentAgentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                } else {
                    $data = $this->service->getAllGanttCustomerResource($orders, $querySearch->currentAgentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                }
            } else if ($user->wg_type == "customerUser") {
                $agentModel = CustomerUser::whereUserId($user->id)->first();

                $agentId = 0;

                if ($agentModel != null)
                {
                    $agentId = $agentModel->id;
                }

                if ($querySearch->type == "GE") {
                    $data = $this->service->getAllGanttEconomicGroupResource($orders, $agentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                } else {
                    $data = $this->service->getAllGanttCustomerResource($orders, $agentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                }
            }

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            //$result = CustomerProjectDTO::parse($data);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
            $this->response->setRecordsTotal($recordsTotal);
            $this->response->setRecordsFiltered($recordsFiltered);
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

    public function ganttResourceAssignment()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $query = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $json = base64_decode($query);

            //Log::info($json);

            $querySearch = json_decode($json);

            $user = $this->user();

            $currentPage = $currentPage + 1;
            $data = [];
            if ($user->wg_type == "customerAdmin") {
                if ($querySearch->type == "GE") {
                    $data = $this->service->getAllGanttEconomicGroupResourceAssignment($orders, $querySearch->currentAgentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                } else {
                    $data = $this->service->getAllGanttCustomerResourceAssignment($orders, $querySearch->currentAgentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                }
            } else if ($user->wg_type == "customerUser") {
                $agentModel = CustomerUser::whereUserId($user->id)->first();

                $agentId = 0;

                if ($agentModel != null)
                {
                    $agentId = $agentModel->id;
                }

                if ($querySearch->type == "GE") {
                    $data = $this->service->getAllGanttEconomicGroupResourceAssignment($orders, $agentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                } else {
                    $data = $this->service->getAllGanttCustomerResourceAssignment($orders, $agentId, $querySearch->customerId, $querySearch->month, $querySearch->year);
                }
            }

            //$result = CustomerProjectDTO::parse($data, '11');

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            //$result = CustomerProjectDTO::parse($data);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
            $this->response->setRecordsTotal($recordsTotal);
            $this->response->setRecordsFiltered($recordsFiltered);
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

    public function report()
    {
        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $agentId = $this->request->get("agent_id", "0");

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
            $user = $this->user();

            if ($agentId == 0)
            {
                $agentModel = CustomerUser::whereUserId($user->id)->first();

                if ($agentModel != null)
                {
                    $agentId = $agentModel->id;
                }
            }

            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            $data = $this->service->getAllTaskBy($orders, $agentId);

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            $result = CustomerInternalProjectDTO::parse($data, "3");

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

    public function agent()
    {
        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $skill = $this->request->get("skill", "0");
        $customerId = $this->request->get("customerId", "0");

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

            $user = $this->user();


            $agentModel = CustomerUser::whereUserId($user->id)->first();

            if ($agentModel != null)
            {
                $agentId = $agentModel->id;
            }

            if ($user->wg_type == "customerAdmin") {
                $data = $this->service->getAllAgentBy($orders, $skill, $user->company);
            } else if ($user->wg_type == "customerUser") {
                $data = $this->service->getAllAgentBy($orders, $skill, $user->company);
            } else {
                $data = $this->service->getAllAgentBy($orders, $skill, $customerId);
            }

            // get all tracking by customer with pagination


            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            $result = CustomerInternalProjectDTO::parse($data, "4");

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

    public function customer()
    {
        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $skill = $this->request->get("skill", "0");
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
            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            $data = $this->service->getAllCustomerBy($orders, $customerId);

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            $result = CustomerInternalProjectDTO::parse($data, "5");

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

    public function task()
    {
        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $agentId = $this->request->get("agent_id", "0");

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

            $user = $this->user();

            if ($user->wg_type == "customerAdmin") {
                if ($agentId == 0) {
                    $agentId = $user->id;
                }
                $data = $this->service->getAllTaskByPlaner($orders, $agentId);
            } else if ($user->wg_type == "customerUser") {
                if ($agentId == 0) {
                    $agentId = $user->id;
                }
                $data = $this->service->getAllTaskByPlaner($orders, $agentId);
            } else if ($user->wg_type == "customer") {
                $data = $this->service->getAllTaskByPlanerCustomer($orders, $user->company);
            } else {
                $data = $this->service->getAllTaskByPlaner($orders, $agentId);
            }
            // get all tracking by customer with pagination


            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // extract info
            $result = CustomerInternalProjectDTO::parse($data, "6");

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

    public function projectAgentTasks()
    {
        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $projectAgentId = $this->request->get("project_agent_id", "0");

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

            // get all tracking by customer with pagination
            $data = $this->service->getAllTaskByProjectAgent(@$search['value'], $length, $currentPage,  $projectAgentId);
            $recordsTotal = $this->service->getAllTaskByProjectAgentCount("", $length, $currentPage,  $projectAgentId);
            $recordsFiltered = $this->service->getAllTaskByProjectAgentCount(@$search['value'], $length, $currentPage,  $projectAgentId);



            // extract info
            $result = CustomerInternalProjectDTO::parse($data, "8");

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

    public function projectTasks()
    {
        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $projectId = $this->request->get("project_id", "0");

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

            // get all tracking by customer with pagination
            $data = $this->service->getAllTaskByProject(@$search['value'], $length, $currentPage, $projectId);
            $recordsTotal = $this->service->getAllTaskByProjectCount("", $length, $currentPage, $projectId);
            $recordsFiltered = $this->service->getAllTaskByProjectCount(@$search['value'], $length, $currentPage, $projectId);

            // extract info
            $result = CustomerInternalProjectDTO::parse($data, "10");

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal(count($recordsTotal));
            $this->response->setRecordsFiltered(count($recordsFiltered));
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

    public function ListByCustomer(){

        $customerId = $this->request->get("customerId", "0");

        try {
            // get all tracking by customer with pagination
            $users = CustomerUser::whereCustomerId($customerId)->get();
            $projectTypes = $this->getParameterByValue($customerId, "projectType");
            $userSkills = $this->getParameterByValue($customerId, "userSkill");
            $projectTaskTypes = $this->getParameterByValue($customerId, "projectTaskType");

            $result{"users"} = CustomerUserDTO::parse($users);
            $result{"projectTypes"} = CustomerParameterDTO::parse($projectTypes);
            $result{"userSkills"} = CustomerParameterDTO::parse($userSkills);
            $result{"taskType"} = CustomerParameterDTO::parse($projectTaskTypes);

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

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return CustomerParameter::whereNamespace($ns)->whereGroup($group)->whereCustomerId($value)->get();
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
            $model = CustomerInternalProjectDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerInternalProjectDTO::parse($model);

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

    public function sendAndSaveStatus()
    {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            ////Log::info($json);

            // parse
            $info = json_decode($json);

            $data = $this->service->getAllSummaryByStatus($info->agentId, $info->customerId, $info->month, $info->year, $info->arl, $info->os);

            $result = CustomerInternalProjectDTO::parse($data);

            $info->projects = $result;

            //Get data
            $model = CustomerInternalProjectDTO::sendAndSaveStatus($info);

            // Parse to send on response
            $result = CustomerInternalProjectDTO::parse($model);

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

    public function taskSave()
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
            $model = CustomerInternalProjectAgentTaskDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerInternalProjectAgentTaskDTO::parse($model);

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

    public function taskUpdate()
    {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            $model = CustomerInternalProjectAgentTaskDTO::fillAndUpdateModel($info);

            // Parse to send on response
            $result = CustomerInternalProjectAgentTaskDTO::parse($model);

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

    public function eventUpdate()
    {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            if ($info->tableName == "agentTask")
            {
                $model = CustomerInternalProjectAgentTask::find($info->id);
                $infoModel = CustomerInternalProjectAgentTaskDTO::parse($model);
                $infoModel->status = $info->status;
                $model = CustomerInternalProjectAgentTaskDTO::fillAndSaveModel($infoModel);
            }
            else if ($info->tableName == "actionPlan")
            {
                $model = CustomerManagementDetailActionPlan::find($info->id);
                //Log::info($model);
                $infoModel = CustomerManagementDetailActionPlanDTO::parse($model);
                $infoModel->status = $info->status;
                $model = CustomerManagementDetailActionPlanDTO::UpdateModel($infoModel);
            }
            else
            {
                $model = CustomerTracking::find($info->id);
                $infoModel = CustomerTrackingDTO::parse($model);
                $infoModel->status = $info->status;
                $model = CustomerTrackingDTO::fillAndSaveModel($infoModel);
            }

            $this->response->setResult($info);

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
            $model = CustomerInternalProjectDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerInternalProjectDTO::parse($model);

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

            if (!($model = CustomerInternalProject::find($id))) {
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerInternalProjectDTO::parse($model);

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

    public function getTask()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerInternalProjectAgentTask::find($id))) {
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerInternalProjectAgentTaskDTO::parse($model);

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

    public function agentDelete()
    {
        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            //Log::info("risk [" . $id . "]s::");

            if (!($model = CustomerInternalProjectAgent::find($id))) {
                throw new Exception("Customer not found to delete.");
            }

            $tasks = CustomerInternalProjectAgentTask::where('project_agent_id', $id);


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
            $result = CustomerInternalProjectDTO::parse($model);

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
