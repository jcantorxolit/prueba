<?php

namespace Wgroup\Controllers;

use Controller as BaseController;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Redirect;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\Country;
use Response;
use Session;
use System\Models\File;
use System\Models\Parameters;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\RandomColor;
use Wgroup\Classes\ServiceApi;
use Wgroup\CustomerContractDetailActionPlanResp\CustomerContractDetailActionPlanResp;
use Wgroup\CustomerDiagnosticPreventionActionPlanResp\CustomerDiagnosticPreventionActionPlanResp;
use Wgroup\CustomerPeriodicRequirement\CustomerPeriodicRequirementDTO;
use Wgroup\Models\Agent;
use Wgroup\Models\Contact;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDto;
use Wgroup\Models\CustomerManagementDetailActionPlanResp;
use Wgroup\Models\InfoDetail;
use Wgroup\Models\State;
use Validator;
use Wgroup\NephosIntegration\NephosIntegration;
use Wgroup\Traits\UserSecurity;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerController extends BaseController
{

    use UserSecurity;

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

        $this->run();

        //set service
        $this->service = new ServiceApi();
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
        $id = $this->request->get("id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());
        $filter = "";
        $ideascount = 0;
        $ideascountFilter = 0;

        try {

            // Validate permissions
            /*if (!UserGroup::hasRole('admin')) {
                throw new Exception(Message::trans("messages.error.notauthorized", array()));
            }*/

            $user = Auth::getUser();
            $joinAgent = false;
            $agentId = 0;

            if ($user) {

                $redirectUrl = "/app/clientes/list";

                if ($user->wg_type == "customer") {
                    $redirectUrl = "/app/clientes/view/" . $user->company;

                    //return Redirect::to($redirectUrl, 301);
                    //if ($redirectUrl = post('redirect', $redirectUrl))
                    //return Redirect::intended($redirectUrl);
                } else if ($user->wg_type == "agent") {
                    $joinAgent = true;

                    $agentModel = Agent::whereUserId($user->id)->first();

                    if ($agentModel != null) {
                        $agentId = $agentModel->id;
                    }
                }

            }

            //var_dump('pass');

            $currentPage = $currentPage + 1;

            $exclude = 0;

            // get all ideas with pagination
            $data = $this->service->getAllCustomers(@$search['value'], $length, $currentPage, $orders, "", $joinAgent, $agentId);

            //var_dump(count($data));

            // Counts
            $ideascount = $this->service->getAllCustomersCount("", $joinAgent, $agentId);
            $ideascountFilter = $this->service->getAllCustomersCount(@$search['value'], $joinAgent, $agentId);

            // extract info
            $result = CustomerDto::parse($data);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal($ideascount);
            $this->response->setRecordsFiltered($ideascountFilter);
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

    public function indexContractor()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customerId", "all");


        $id = $this->request->get("id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());
        $filter = "";
        $ideascount = 0;
        $ideascountFilter = 0;

        try {

            $joinAgent = false;

            $currentPage = $currentPage + 1;

            // get all ideas with pagination
            $data = $this->service->getAllCustomerContractor(@$search['value'], $length, $currentPage, $orders, $customerId);

            // Counts
            $count = $this->service->getAllCustomerContractorCount("", $customerId);
            $countFilter = $this->service->getAllCustomerContractorCount(@$search['value'], $joinAgent, $customerId);


            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
            $this->response->setRecordsTotal(count($count));
            $this->response->setRecordsFiltered(count($countFilter));
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

    public function indexEconomicGroup()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customerId", "all");


        $id = $this->request->get("id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());
        $filter = "";
        $ideascount = 0;
        $ideascountFilter = 0;

        try {

            $joinAgent = false;

            $currentPage = $currentPage + 1;

            // get all ideas with pagination
            $data = $this->service->getAllCustomerEconomicGroup(@$search['value'], $length, $currentPage, $orders, $customerId);

            // Counts
            $count = $this->service->getAllCustomerEconomicGroupCount("", $customerId);
            $countFilter = $this->service->getAllCustomerEconomicGroupCount(@$search['value'], $joinAgent, $customerId);


            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
            $this->response->setRecordsTotal(count($count));
            $this->response->setRecordsFiltered(count($countFilter));
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

    public function indexContractAndEconomicGroup()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customerId", "all");


        $id = $this->request->get("id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());
        $filter = "";
        $ideascount = 0;
        $ideascountFilter = 0;

        try {

            $joinAgent = false;

            $currentPage = $currentPage + 1;

            // get all ideas with pagination
            $data = $this->service->getAllCustomerContractAndEconomicGroup(@$search['value'], $length, $currentPage, $orders, $customerId);

            // Counts
            $count = $this->service->getAllCustomerContractAndEconomicGroupCount("", $customerId);
            $countFilter = $this->service->getAllCustomerContractAndEconomicGroupCount(@$search['value'], $joinAgent, $customerId);


            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
            $this->response->setRecordsTotal(count($count));
            $this->response->setRecordsFiltered(count($countFilter));
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

    public function getCustomers()
    {
        $operation = $this->request->get("operation", "all");
        $id = $this->request->get("id", "0");

        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $orders = $this->request->get("order", array());

        try {

            $data = $this->service->getCustomers($id);

            // extract info
            $result["customers"] = CustomerDto::parse($data, '5');
            $result["periods"] = CustomerPeriodicRequirementDTO::getPeriods();

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal(count($data));
            $this->response->setRecordsFiltered(count($data));
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

    public function states()
    {

        $id = $this->request->get("cid", "0");

        try {
            $states = [];
            if ($model = Country::find($id)) {
                foreach ($model->states as $state) {
                    $states[] = $state;
                }
            }

            $result = $states;

            // set count total ideas
            $this->response->setResult($result);

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

    public function towns()
    {

        $id = $this->request->get("sid", "0");

        try {
            $towns = [];
            if ($model = State::find($id)) {
                $towns = $model->towns;
                foreach ($towns as $town) {
                    $towns[] = $town;
                }
            }

            $result = $towns;

            // set count total ideas
            $this->response->setResult($result);

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

    public function getAgents()
    {
        $id = $this->request->get("customerId", 0);

        try {
            // get all tracking by customer with pagination
            $data = $this->service->getAgents($id);

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

    public function report()
    {
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", "0");

        try {
            $colorPrg1 = $this->getRandomColor();

            $resultLine = $this->service->getDashboardContributionBy($customerId, $year);
            $resulYear = $this->service->getContributionYears($customerId);

            $programs = [
                "result" => [
                    "labels" => ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
                    "datasets" => [
                        [
                            "label" => "Variación mensual",
                            "fillColor" => array("r" => "151", "g" => "187", "b" => "205"),
                            "strokeColor" => array("r" => "151", "g" => "187", "b" => "205"),
                            "highlightFill" => array("r" => "151", "g" => "187", "b" => "205"),
                            "highlightStroke" => $colorPrg1,
                            "data" => [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]
                        ]
                    ]
                ]
            ];

            if (!empty($resultLine)) {
                $programs = null;
                $programs = [
                    "result" => [
                        "labels" => ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"],
                        "datasets" => [
                            [
                                "label" => "Variación mensual",
                                "fillColor" => array("r" => "151", "g" => "187", "b" => "205"),
                                "strokeColor" => array("r" => "151", "g" => "187", "b" => "205"),
                                "highlightFill" => array("r" => "151", "g" => "187", "b" => "205"),
                                "highlightStroke" => $colorPrg1,
                                "data" => [$resultLine[0]->Enero, $resultLine[0]->Febrero, $resultLine[0]->Marzo
                                    , $resultLine[0]->Abril, $resultLine[0]->Mayo, $resultLine[0]->Junio
                                    , $resultLine[0]->Julio, $resultLine[0]->Agosto, $resultLine[0]->Septiembre
                                    , $resultLine[0]->Octubre, $resultLine[0]->Noviembre, $resultLine[0]->Diciembre]
                            ]
                        ]
                    ]
                ];
            }

            $result = array();

            //Log::info($programs);
            // extract info
            $result["report_contribution"] = CustomerDTO::parse($programs, "2")[0]; // 2 = Prepara la respuesta para la grafica de barras
            $result["report_years"] = CustomerDTO::parse($resulYear, "3"); // 2 = Prepara la respuesta para la grafica de barras

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

            //Log::info($json);

            // parse
            $info = json_decode($json);

            // Parse to model

            $model = CustomerDto::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerDto::parse($model);

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

    public function saveUnit()
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

            $model = CustomerDto::fillAndSaveModelUnit($info);

            // Parse to send on response
            $result = CustomerDto::parseUnit($model);

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

    public function saveContacts()
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

            $model = CustomerDto::fillAndSaveModelContacts($info);

            // Parse to send on response
            $result = CustomerDto::parse($model);

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

    public function saveInfoDetail()
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

            $model = CustomerDto::fillAndSaveModelInfoDetail($info);

            // Parse to send on response
            $result = CustomerDto::parse($model);

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

    public function saveParameters()
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

            $model = CustomerDto::fillAndSaveModelParameters($info);

            // Parse to send on response
            $result = CustomerDto::parse($model);

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

    public function saveDocumentTypeParameters()
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

            $model = CustomerDto::fillAndSaveModelDocumentTypeParameters($info);

            // Parse to send on response
            $result = (new CustomerDto())->getInfoBasicEmployeeDocumentType($model);

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

    public function saveQuick()
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

            $model = CustomerDto::fillAndSaveModelQuick($info);

            // Parse to send on response
            $result = CustomerDto::parse($model);

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

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            // Parse to model

            $model = CustomerDto::fillAndInsert($info);

            // Parse to send on response
            $result = CustomerDto::parse($model);

            $user = Auth::getUser();

            if ($user != null) {
                $user->company = $model->id;
                $user->save();

                $nephos = NephosIntegration::where('adminUser', $user->email)->first();

                if ($nephos != null) {
                    $nephos->customer_id = $model->id;
                    $nephos->save();
                }
            }

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
                throw new \Exception("invalid parameters", 405);
            }

            //Si es un usuario de un cliente
            if (!$this->canOpenCustomer($id)) {
                throw new \Exception("invalid parameters", 405);
            }

            if (!($model = Customer::find($id))) {
                throw new \Exception("Customer not found", 404);
            }


            //Get data
            $result = CustomerDto::parse($model);

            $this->response->setResult($result);

        } catch (Exception $ex) {

            // Log the full exception
            Log::error($ex);

            // error on server
            if ($ex->getCode()) {
                $this->response->setStatuscode($ex->getCode());
            } else {
                $this->response->setStatuscode(500);
            }
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function getUnit()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");
        Log::info($id);
        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 400);
            }

            //Si es un usuario de un cliente
            if (!$this->canOpenCustomer($id)) {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = Customer::find($id))) {
                throw new \Exception("Customer not found", 404);
            }


            //Get data
            $result = CustomerDto::parseUnit($model);

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

    public function upload()
    {

        // Preapre parameters for query
        $customer = $this->request->get("id", "0");

        try {

           // var_dump("CUSTOMER::". $customer);

            $allFiles = Input::file();

            //Log::info("customer [" . $customer . "]s::");

            $model = Customer::find($customer);

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostback($file, $model);
            }

            $model = Customer::find($customer);

            $this->response->setResult(\AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo));
            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    protected function checkUploadPostback($uploadedFile, $model)
    {

        //if (!post('X_BLOG_IMAGE_UPLOAD'))
        //  return;

        $uploadedFileName = null;
        $result = array();
        try {
            //  $uploadedFile = Input::file('file');

            if ($uploadedFile)
                $uploadedFileName = $uploadedFile->getClientOriginalName();

            $validationRules = ['max:' . File::getMaxFilesize()];
            $validationRules[] = 'mimes:jpg,png,jpeg,bmp,gif';

            $validation = Validator::make(
                ['file_data' => $uploadedFile], ['file_data' => $validationRules]
            );

            if ($validation->fails())
                throw new ValidationException($validation);

            if (!$uploadedFile->isValid())
                throw new SystemException('File is not valid');

            $fileRelation = $model->logo();

            $file = new File();
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->save();

            $fileRelation->add($file);

            $result = [
                'file' => $uploadedFileName,
                'path' => $file->getPath()
            ];

            //$response = Response::make()->setContent($result);
            //$response->send();
            //die();
        } catch (Exception $ex) {
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName
            ];
        }

        return $result;
    }

    public function delete()
    {

        // Preapre parameters for query
        $customer = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            //Log::info("customer [" . $customer . "]s::");

            if (!($model = Customer::find($customer))) {
                throw new Exception("Customer not found to delete.");
            }

            // Elimina el logo
            \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->logo)()->delete();

            // elimina los contactos
            foreach ($model->infoDetail() as $id) {
                $id->delete();
            }

            // limpiar los contactos
            foreach ($model->maincontacts as $mc) {
                foreach ($mc->infoDetail() as $id) {
                    $id->delete();
                }
                //$mc->delete();
            }

            $model->maincontacts()->delete();

            // elimina las unidades de negocio
            $model->unities()->delete();


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

    public function deleteInfoDetail()
    {
        $id = $this->request->get("id", "0");

        try {

            if (!($model = InfoDetail::find($id))) {
                throw new Exception("Info not found to delete.");
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

    public function deleteContact()
    {
        $id = $this->request->get("id", "0");

        try {

            if (!($model = Contact::find($id))) {
                throw new Exception("Contact not found to delete.");
            }

            $countDiagnosticAP = CustomerDiagnosticPreventionActionPlanResp::whereContactId($id)->count();
            $countManagementAP = CustomerManagementDetailActionPlanResp::whereContactId($id)->count();
            $countContractAP = CustomerContractDetailActionPlanResp::whereContactId($id)->count();

            if ($countDiagnosticAP > 0 || $countManagementAP > 0 || $countContractAP > 0) {
                throw new Exception("Action plan related.");
            }

            foreach ($model->infoDetail() as $info) {
                $info->delete();
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
