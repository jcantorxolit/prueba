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
use Wgroup\CustomerAbsenteeismDisabilityActionPlan\CustomerAbsenteeismDisabilityActionPlan;
use Wgroup\CustomerContractDetail\CustomerContractDetailService;
use Wgroup\CustomerAbsenteeismDisabilityActionPlan\CustomerAbsenteeismDisabilityActionPlanDTO;
use Wgroup\CustomerAbsenteeismDisabilityActionPlan\CustomerAbsenteeismDisabilityActionPlanService;
use Wgroup\CustomerContractor\CustomerContractor;
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
class CustomerAbsenteeismDisabilityActionPlanController extends BaseController {

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

    public function __construct() {

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


    public function index($programId = 0, $managementId = 0){

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
            $data["questions"] = CustomerAbsenteeismDisabilityActionPlanDTO::parse($questions);

           // $modelCategories = ProgramPreventionCategoryDTO::parse($categories);
            //$modelQuestions = CustomerAbsenteeismDisabilityActionPlanDTO::parse($questions);

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

    public function getInformation($programId = 0, $managementId = 0){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $contractorId = $this->request->get("contract_id", $managementId);

        try {
            // get all tracking by customer with pagination

            $categories = $this->service->getPeriods($contractorId);
            $questions = $this->service->getRequirementsBy($contractorId);
            $dashboardContract = $this->service->getDashboardByManagement($contractorId);

            // Por ahora tendremos que enviar la informacion organizada desde el backend
            $cats = $this->prepareCategories($categories, $questions);

            $data["categories"] = $cats;


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

            if (!($model = CustomerAbsenteeismDisabilityActionPlan::find($id))) {
                throw new \Exception("Customer not found", 404);
            }


            //Get data
            $result = CustomerAbsenteeismDisabilityActionPlanDTO::parse($model);

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

    public function getInformationReport($programId = 0, $managementId = 0, $rate_id = 0){

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

    private function prepareCategories($data, $questions, $issubitems = false){

        if (!$data || !count($data)){
            return false;
        }

        // Preparamos cada objeto de tipo category
        foreach($data as $category){

            // Asigno los questions
            foreach($questions as $question){
                if ($question->period == $category->period){

                    // Asigno informacion de rate a la pregunta
                    $question->rate = new RateDto();
                    if (($mdlRate = Rate::find($question->rate_id))) {
                        $question->rate = RateDto::parse($mdlRate);
                    }

                    $category->questions[] =  $question;
                }
            }

            $category->items = array();
        }

        return $data;
    }

    private function preparePrograms($data, $questions, $issubitems = false){

        if(!$data || !count($data)){
            return false;
        }

        // Primero parseamos la informacion a DTO
        $dtos = ProgramPreventionDTO::parse($data, 2);

        // Preparamos cada objeto de tipo category
        foreach($dtos as $program){

            foreach($questions as $question){
                if($question->program_id == $program->id){
                    $question->plan = new CustomerAbsenteeismDisabilityActionPlanDTO();
                    if (($mdlActionPlan = CustomerAbsenteeismDisabilityActionPlan::find($question->actionPlanId))) {
                        $question->plan = CustomerAbsenteeismDisabilityActionPlanDTO::parse($mdlActionPlan);
                        $program->questions[] =  $question;
                    }


                }
            }
        }

        return $dtos;
    }

    public function save() {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = json_encode(base64_decode($text));


            //Log::info($json);

            //Get data
            $model = CustomerAbsenteeismDisabilityActionPlanDTO::fillAndSaveModel($json);

            $result = CustomerAbsenteeismDisabilityActionPlanDTO::parse($model);

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

    public function bulkInsert()
    {
        $id = $this->request->get("id", "0");

        try {
            // decodify
            if (!($model = CustomerContractor::find($id))) {
                throw new Exception("Customer contractor not found.");
            }

            $result = $this->service->bulkInsert($model->id, $this->user->id);

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

    public function get() {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if($id == "0"){
                throw new \Exception("invalid parameters", 403);
            }

            if(!($model = CustomerAbsenteeismDisabilityActionPlan::find($id))){
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerAbsenteeismDisabilityActionPlanDTO::parse($model);

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
    private function getUser() {
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
}
