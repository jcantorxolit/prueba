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
use Wgroup\Classes\ServiceCustomerDiagnosticPrevention;
use Wgroup\CustomerDiagnosticPreventionActionPlan\CustomerDiagnosticPreventionActionPlan;
use Wgroup\CustomerDiagnosticPreventionActionPlan\CustomerDiagnosticPreventionActionPlanDTO;
use Wgroup\CustomerDiagnosticPreventionComment\CustomerDiagnosticPreventionCommentDTO;
use Wgroup\Models\CustomerDiagnosticPrevention;
use Wgroup\Models\CustomerDiagnosticPreventionDTO;
use Wgroup\Models\ProgramPreventionCategoryDTO;
use Wgroup\Models\ProgramPreventionDTO;
use Wgroup\Models\Rate;
use Wgroup\Models\RateDto;
use Excel;
use Wgroup\ProgramPreventionQuestion\ProgramPreventionQuestion;
use Wgroup\ProgramPreventionQuestion\ProgramPreventionQuestionDTO;


/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package WGroup\api
 * @author David Blandon
 */
class CustomerDiagnosticPreventionController extends BaseController {

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
        $this->service = new ServiceCustomerDiagnosticPrevention();
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


    public function index($program_id = 0, $diagnostic_id = 0){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $programId = $this->request->get("program_id", $program_id);
        $diagnosticId = $this->request->get("diagnostic_id", $diagnostic_id);

        try {
            // get all tracking by customer with pagination

            $categories = $this->service->getCategoriesBy($programId);
            $questions = $this->service->getQuestionsBy($diagnosticId, $programId);
            $dashboardCategory = $this->service->getDashboardByCategory($diagnosticId, $programId);
            $dashboardProgram = $this->service->getDashboardByProgram($diagnosticId);
            $dashboardDiagnostic = $this->service->getDashboardByDiagnostic($diagnosticId);

            $data["dashboardCategory"] = $dashboardCategory;
            $data["dashboardProgram"] = $dashboardProgram;
            $data["dashboardDiagnostic"] = $dashboardDiagnostic;
            $data["categories"] = ProgramPreventionCategoryDTO::parse($categories);
            $data["questions"] = CustomerDiagnosticPreventionDTO::parse($questions);

           // $modelCategories = ProgramPreventionCategoryDTO::parse($categories);
            //$modelQuestions = CustomerDiagnosticPreventionDTO::parse($questions);

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

    public function getInformation($program_id = 0, $diagnostic_id = 0){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $programId = $this->request->get("program_id", $program_id);
        $diagnosticId = $this->request->get("diagnostic_id", $diagnostic_id);

        try {
            // get all tracking by customer with pagination

            $categories = $this->service->getCategoriesBy($programId);
            $questions = $this->service->getQuestionsBy($diagnosticId, $programId);
            $dashboardCategory = $this->service->getDashboardByCategory($diagnosticId, $programId);
            $dashboardProgram = $this->service->getDashboardByProgram($diagnosticId);
            $dashboardDiagnostic = $this->service->getDashboardByDiagnostic($diagnosticId);

            // Por ahora tendremos que enviar la informacion organizada desde el backend
            $cats = $this->prepareCategories($categories, $questions, $dashboardCategory);

            $data["categories"] = $cats;

            //$data["dashboardCategory"] = $dashboardCategory;
            $data["dashboardProgram"] = $dashboardProgram;
            $data["dashboardDiagnostic"] = $dashboardDiagnostic;
            //$data["categories"] = ProgramPreventionCategoryDTO::parse($categories);
            //$data["questions"] = CustomerDiagnosticPreventionDTO::parse($questions);


            ////Log::info($categories);

            //$data["categories"] = $modelCategories;

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

    public function getInformationReport($program_id = 0, $diagnostic_id = 0, $rate_id = 0){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $programId = $this->request->get("program_id", $program_id);
        $diagnosticId = $this->request->get("diagnostic_id", $diagnostic_id);
        $rateId = $this->request->get("rate_id", $rate_id);

        try {
            // get all tracking by customer with pagination

            $programs = $this->service->getPrograms($diagnosticId);
            $questions = $this->service->getQuestionsByStatus($diagnosticId, $programId, $rateId);
            $dashboardDiagnostic = $this->service->getDashboardByDiagnostic($diagnosticId);

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
        $diagnosticId = $this->request->get("diagnostic_id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getExport($diagnosticId);

            Excel::create('DiagnosticoReporteExcel', function($excel) use($data) {
                // Call them separately
                $excel->setDescription('Gestion');

                $excel->sheet('Reporte', function($sheet) use($data) {

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

    public function exportAll()
    {

        $data = $this->request->get("data", "");
        $diagnosticId = $this->request->get("diagnostic_id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getExportAll($diagnosticId);

            Excel::create('SG-SST', function($excel) use($data) {
                // Call them separately
                $excel->setDescription('Gestion');

                $excel->sheet('Reporte', function($sheet) use($data) {

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

    private function prepareCategories($data, $questions, $infoextent, $issubitems = false){

        if(!$data || !count($data)){
            return false;
        }

        // Primero parseamos la informacion a DTO
        $dtos = ProgramPreventionCategoryDTO::parse($data);

        // Preparamos cada objeto de tipo category
        foreach($dtos as $category){

            // Asigno los questions
            foreach($questions as $question){
                if($question->category_id == $category->id){

                    // Asigno informacion de rate a la pregunta
                    $question->rate = new RateDto();
                    if (($mdlRate = Rate::find($question->rate_id))) {
                        $question->rate = RateDto::parse($mdlRate);
                    }

                    $category->questions[] =  $question;
                }
            }

            // Asigo informacion adicional
            if(!empty($infoextent)){
                foreach($infoextent as $ext){
                    if($ext->category_id == $category->id){
                        $category->advance =  $ext->advance;
                        $category->answers =  $ext->answers;
                        $category->average =  $ext->average;
                        $category->questionsCount =  $ext->questions;
                        $category->total =  $ext->total;
                        break;
                    }
                }
            }

            // Asigno subcategorias (recursivamente)
            if(!empty($category->items)){
                $category->items = $this->prepareCategories($category->items, $questions, $infoextent, true );
            }
        }

        return $dtos;
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
                    $program->questions[] =  $question;
                    $question->plan = new CustomerDiagnosticPreventionActionPlanDTO();
                    if (($mdlActionPlan = CustomerDiagnosticPreventionActionPlan::find($question->actionPlanId))) {
                        $question->plan = CustomerDiagnosticPreventionActionPlanDTO::parse($mdlActionPlan);
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
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            //Get data
            $model = CustomerDiagnosticPreventionDTO::fillAndSaveModel($info);

            $currentYear = Carbon::now()->year;
            $currentMonth = Carbon::now()->month;

            $this->service->fillMissingReportMonthly($info->diagnosticId, $this->user->id);
            $this->service->saveReportMonthly($info->diagnosticId, $currentYear, $currentMonth, $this->user->id);
            $this->service->updateReportMonthly($info->diagnosticId, $currentYear, $currentMonth, $this->user->id);

            // Parse to send on response
            //$result = CustomerDiagnosticPreventionDTO::parse($model);
            $result["data"] = 'testing';
            $this->response->setResult($result);
            //return $this->index($info->programId, $info->diagnostic_id);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function saveActionPlan() {

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

    public function saveComment() {

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

    public function get() {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if($id == "0"){
                throw new \Exception("invalid parameters", 403);
            }

            if(!($model = CustomerDiagnosticPrevention::find($id))){
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerDiagnosticPreventionDTO::parse($model);

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

    public function getQuestion() {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if($id == "0"){
                throw new \Exception("invalid parameters", 403);
            }

            if(!($model = CustomerDiagnosticPrevention::find($id))){
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
