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
use Wgroup\Classes\ServiceCustomerManagementDetail;
use Wgroup\CustomerContractDetail\CustomerContractDetailDTO;
use Wgroup\CustomerContractDetail\CustomerContractDetailService;
use Wgroup\CustomerContractDetailActionPlan\CustomerContractDetailActionPlan;
use Wgroup\CustomerContractDetailActionPlan\CustomerContractDetailActionPlanDTO;
use Wgroup\CustomerContractor\CustomerContractor;
use Wgroup\Models\CustomerManagementDetailActionPlan;
use Wgroup\Models\CustomerManagementDetailActionPlanDTO;
use Wgroup\Models\CustomerManagementDetailDTO;
use Wgroup\Models\ProgramPreventionCategoryDTO;
use Wgroup\Models\ProgramPreventionDTO;
use Wgroup\Models\Rate;
use Wgroup\Models\RateDto;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package WGroup\api
 * @author David Blandon
 */
class CustomerContractDetailController extends BaseController
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
        $this->service = new CustomerContractDetailService();
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


    public function index($programId = 0, $managementId = 0)
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $programId = $this->request->get("program_id", $programId);
        $diagnosticId = $this->request->get("management_id", $managementId);

        try {
            // get all tracking by customer with pagination

            $categories = $this->service->getCategoriesBy($programId);
            $questions = $this->service->getQuestionsBy($diagnosticId, $programId);
            $dashboardCategory = $this->service->getDashboardByCategory($diagnosticId, $programId);
            $dashboardProgram = $this->service->getDashboardByProgram($diagnosticId);
            $dashboardManagement = $this->service->getDashboardByManagement($diagnosticId);

            $data["dashboardCategory"] = $dashboardCategory;
            $data["dashboardProgram"] = $dashboardProgram;
            $data["dashboardManagement"] = $dashboardManagement;
            $data["categories"] = ProgramPreventionCategoryDTO::parse($categories);
            $data["questions"] = CustomerContractDetailDTO::parse($questions);

            // $modelCategories = ProgramPreventionCategoryDTO::parse($categories);
            //$modelQuestions = CustomerContractDetailDTO::parse($questions);

            /*
                        foreach ($modelCategories as $category) {
                            foreach ($modelQuestions as $question) {
                                if ($question->categoryId == $category->id)
                                {
                                    $category->questions[] = $question;
                                }
                            }
                        }


                        foreach ($modelCategories as $category) {
                            if (count($category->items) > 0) {
                                foreach ($category->items as $item) {
                                    foreach ($modelQuestions as $question) {
                                        if ($question->categoryId == $item->id) {
                                            $item->questions[] = $question;
                                        }
                                    }
                                }
                            }
                        }
            */
            //Log::info($categories);

            //$data["categories"] = $modelCategories;

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

    public function getInformation()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $contractorId = $this->request->get("contract_id", 0);
        $pageSize = $this->request->get("page_size", 10);
        $currentPage = $this->request->get("current_page", 1);
        $period = $this->request->get("period", 0);

        try {
            // get all tracking by customer with pagination

            $categories = $this->service->getPeriods($contractorId);
            $questions = $this->service->getRequirementsBy($contractorId, $period, $pageSize, $currentPage);
            $dashboardContract = $this->service->getDashboardByManagement($contractorId);

            // Por ahora tendremos que enviar la informacion organizada desde el backend
            $cats = $this->prepareCategories($categories, $questions);

            $data["categories"] = $cats;
            $data["periods"] = $categories;
            $data["totalItems"] = $this->service->getRequirementsByCount($contractorId, $period);

            // set count total ideas
            $this->response->setData($data);

        } catch (Exception $exc) {
            Log::error($exc->getMessage());

            // error on server
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

            if (!($model = CustomerContractDetailActionPlan::find($id))) {
                throw new \Exception("Customer not found", 404);
            }


            //Get data
            $result = CustomerContractDetailActionPlanDTO::parse($model);

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

    public function getInformationReport($programId = 0, $managementId = 0, $rate_id = 0)
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $programId = $this->request->get("program_id", $programId);
        $diagnosticId = $this->request->get("management_id", $managementId);
        $rateId = $this->request->get("rate_id", $rate_id);

        try {
            // get all tracking by customer with pagination

            $programs = $this->service->getPrograms($diagnosticId);
            $questions = $this->service->getQuestionsByStatus($diagnosticId, $programId, $rateId);
            $dashboardManagement = $this->service->getDashboardByManagement($diagnosticId);

            // Por ahora tendremos que enviar la informacion organizada desde el backend
            $programs = $this->preparePrograms($programs, $questions);

            $data["programs"] = $programs;

            $data["dashboardManagement"] = $dashboardManagement;
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

    private function prepareCategories($data, $questions, $issubitems = false)
    {

        if (!$data || !count($data)) {
            return false;
        }

        $categories = array();
        $categoriesInQuestions = array();

        foreach ($questions as $q) {
            if (!in_array($q->period, $categoriesInQuestions)) {
                $categoriesInQuestions[] = $q->period;
            }
        }

        foreach ($data as $category) {
            if (in_array($category->period, $categoriesInQuestions)) {
                $categories[] = $category;
            }
        }

        // Preparamos cada objeto de tipo category
        foreach ($categories as $category) {

            // Asigno los questions
            foreach ($questions as $question) {
                if ($question->period == $category->period) {
                    $question->isActive = $question->isActive == 1;
                    // Asigno informacion de rate a la pregunta
                    $question->rate = new RateDto();
                    if (($mdlRate = Rate::find($question->rate_id))) {
                        $question->rate = RateDto::parse($mdlRate);
                    }

                    $category->questions[] = $question;
                }
            }

            $category->items = array();
        }

        return $data;
    }

    private function preparePrograms($data, $questions, $issubitems = false)
    {

        if (!$data || !count($data)) {
            return false;
        }

        // Primero parseamos la informacion a DTO
        $dtos = ProgramPreventionDTO::parse($data, 2);

        // Preparamos cada objeto de tipo category
        foreach ($dtos as $program) {

            foreach ($questions as $question) {
                if ($question->program_id == $program->id) {
                    $question->plan = new CustomerContractDetailActionPlanDTO();
                    if (($mdlActionPlan = CustomerContractDetailActionPlan::find($question->actionPlanId))) {
                        $question->plan = CustomerContractDetailActionPlanDTO::parse($mdlActionPlan);
                        $program->questions[] = $question;
                    }


                }
            }
        }

        return $dtos;
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
            $model = CustomerContractDetailDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerContractDetailDTO::parse($model);

            $this->response->setResult($result);
            //return $this->index($info->programId, $info->management_id);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function bulkInsert()
    {
        $id = $this->request->get("id", "0");

        try {
            // decodify
            if (!($model = CustomerContractor::find($id))) {
                throw new Exception("Customer contractor not found.");
            }

            $periodTime = Carbon::now('America/Bogota');

            $result = $this->service->bulkInsert($model->id, $this->user->id, $periodTime->format('Ym'), $periodTime->year, $periodTime->month);
            $this->service->bulkInsertSafety($model->id, $this->user->id, $periodTime->format('Ym'), $periodTime->year, $periodTime->month);


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
            $model = CustomerContractDetailActionPlanDTO::fillAndSaveModel($info);

            $result = CustomerContractDetailActionPlanDTO::parse($model);

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

            if (!($model = CustomerContractDetailDTO::find($id))) {
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerContractDetailDTO::parse($model);

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
}
