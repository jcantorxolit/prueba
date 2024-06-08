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

use Wgroup\CustomerInternalCertificateGradeAgent\CustomerInternalCertificateGradeAgent;
use Wgroup\CustomerInternalCertificateGradeAgent\CustomerInternalCertificateGradeAgentDTO;
use Wgroup\CustomerInternalCertificateGradeAgent\CustomerInternalCertificateGradeAgentService;
use Wgroup\CustomerInternalCertificateGradeAgent\CertificateGradeCalendarService;
use Wgroup\CustomerInternalCertificateGradeAgent\CertificateGradeCalendar;
use Wgroup\CustomerInternalCertificateGradeAgent\CertificateGradeCalendarDTO;
use Wgroup\Classes\ApiResponse;
use Excel;
use PDF;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerInternalCertificateGradeAgentController extends BaseController {

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
        $this->service = new CustomerInternalCertificateGradeAgentService();
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


    public function index(){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $id = $this->request->get("id", "0");

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
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders);

            // Counts
            $recordsTotal = $this->service->getCount();
            $recordsFiltered = $this->service->getCount(@$search['value']);

            // extract info
            $result = CustomerInternalCertificateGradeAgentDTO::parse($data);

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

    public function save() {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            $model = CustomerInternalCertificateGradeAgentDTO::fillAndSaveModel($info);

            $result = CustomerInternalCertificateGradeAgentDTO::parse($model);

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

            if (!($model = CustomerInternalCertificateGradeAgent::find($id))) {
                throw new Exception("Program not found to delete.");
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

    public function generateCertificate()
    {
        $id = $this->request->get("id", "0");

        try {

            if (!($model = CustomerInternalCertificateGradeAgent::find($id))) {
                throw new Exception("CustomerInternalCertificateGradeAgent not found to delete.");
            }

            foreach ($model->getParticipants() as $participant) {
                if ($participant->isApproved == 1 && $participant->hasCertificate == 0) {
                    //$participant->hasCertificate = 1;
                    $participant->validateCodeCertificate = $this->generateRandomString(6);
                    $participant->generatedBy = $this->getUser()->id;
                    $participant->certificateCreatedAt = Carbon::now("America/Bogota");
                    $participant->save();

                    $data = array();

                    $file = "certificate_".$participant->id."_.pdf";

                    $path = public_path()."/uploads/public/certificate/$file";

                    //Log::info(public_path());
                    //Log::info(base_path());
                    //Log::info(storage_path());

                    $pdf = PDF::loadHTML('<h1>Test</h1>', $data)->setPaper('a4')->setOrientation('landscape')->setWarnings(false);
                    $pdf->save($path);

                }
            }

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

    public function dashboard() {

        // Preapre parameters for query
        $pollId = $this->request->get("id", "");

        try {

            $data = $this->service->getDashboardPie($pollId);

            //Log::info("Busco");

            $colors = array("#46BFBD", "#e0d653", "#F7464A", "#46BFBD");
            $hcolors = array("#5AD3D1", "#FF5A5E", "#FBF25A", "5AD3D1");

            $resultArray = json_decode(json_encode($data), true);

            ////Log::info(var_dump($resultArray));
/*
            for ($i = 0; $i <= count($resultArray); $i++) {
                $resultArray[$i]["color"] = $colors[0];
                $resultArray[$i]["highlight"] = $hcolors[0];
            }
*/

            foreach ($resultArray as $resultado)
            {
                $resultado["color"] = "#46BFBD";
                $resultado["highlight"] = "#46BFBD";
            }

            $result["pie"] = $resultArray;
            $result["totalAvg"] = "";

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

        $id = $this->request->get("id", "");

        try {


            $data = array();

            $model = CustomerInternalCertificateGradeAgentDTO::find($id);

            $fileName = $model != null ? $model->name : "Programas para certificados";

            Excel::create($fileName, function($excel) use($data) {

                // Set the title
                $excel->setTitle('Programas');

                // Chain the setters
                $excel->setCreator('waygroup')
                    ->setCompany('waygroup');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Resultados', function($sheet) use($data) {

                    //$resultArray = json_decode(json_encode($data), true);

                    //$sheet->fromArray($resultArray, null, 'A1', true, true);
                    $sheet->fromArray($data, null, 'A1', true, true);

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

    public function get() {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if($id == "0"){
                throw new \Exception("invalid parameters", 403);
            }

            if(!($model = CustomerInternalCertificateGradeAgent::find($id))){
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerInternalCertificateGradeAgentDTO::parse($model);

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

    function generateRandomString($length = 10) {
        //$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
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
