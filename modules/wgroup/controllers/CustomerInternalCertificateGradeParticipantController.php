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
use Wgroup\CustomerInternalCertificateGradeParticipant\CustomerInternalCertificateGradeParticipant;
use Wgroup\CustomerInternalCertificateGradeParticipant\CustomerInternalCertificateGradeParticipantDTO;
use Wgroup\CustomerInternalCertificateGradeParticipant\CustomerInternalCertificateGradeParticipantService;
use Wgroup\Classes\ApiResponse;
use Excel;
use PDF;
use Illuminate\Support\Facades\Input;
use System\Models\File;
use Validator;
use AdeN\Api\Helpers\CmsHelper;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerInternalCertificateGradeParticipantController extends BaseController {

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
        $this->service = new CustomerInternalCertificateGradeParticipantService();
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
        $certificateGradeId = $this->request->get("certificate_grade_id", "0");
        $data = $this->request->get("data", "");

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

            //Log::info($data);

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $currentPage = $currentPage + 1;

            if ($operation == "audit") {
                $data = $this->service->getAllByFilter(@$search['value'], $length, $currentPage, $audit);
                // Counts
                $recordsTotal = $this->service->getAllByFilterCount(@$search['value'], $length, $currentPage, $audit);
                $recordsFiltered = $recordsTotal;

                $result = CustomerInternalCertificateGradeParticipantDTO::parse($data, "2");
            } else {
                $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, "", $certificateGradeId);

                // Counts
                $recordsTotal = $this->service->getCount("", $certificateGradeId);
                $recordsFiltered = $this->service->getCount(@$search['value'], $certificateGradeId);
                $result = CustomerInternalCertificateGradeParticipantDTO::parse($data);
            }
            // get all tracking by customer with pagination

            // extract info
            //$result = CustomerInternalCertificateGradeParticipantDTO::parse($data);

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

    public function filterIndex(){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
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

            $user = Auth::getUser();

            if($user){

                if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser" || $user->wg_type == "externalCustomer")
                {
                    // get all tracking by customer with pagination
                    $resultData = $this->service->getAllByFilterCustomer(@$search['value'], $length, $currentPage, $audit, $user->company);

                    // Counts
                    $recordsTotal = $this->service->getAllByFilterCountCustomer(@$search['value'], $length, $currentPage, $audit, $user->company);
                } else {
                    // get all tracking by customer with pagination
                    $resultData = $this->service->getAllByFilter(@$search['value'], $length, $currentPage, $audit);

                    // Counts
                    $recordsTotal = $this->service->getAllByFilterCount(@$search['value'], $length, $currentPage, $audit);
                }

            }


            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerInternalCertificateGradeParticipantDTO::parse($resultData, "2");

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal(count($recordsTotal));
            $this->response->setRecordsFiltered(count($recordsTotal));
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

    public function filterExpiration(){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", 0);
        $month = $this->request->get("month", 0);

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $currentPage = $currentPage + 1;
            $user = Auth::getUser();
            if ($user) {
                if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser" || $user->wg_type == "externalCustomer")
                {
                    // get all tracking by customer with pagination
                    $resultData = $this->service->getAllByExpiration(@$search['value'], $length, $currentPage, $year, $month, $user->company);

                    // Counts
                    $recordsTotal = $this->service->getAllByExpirationCount(@$search['value'], $length, $currentPage, $year, $month, $user->company);
                } else {
                    // get all tracking by customer with pagination
                    $resultData = $this->service->getAllByExpiration(@$search['value'], $length, $currentPage, $year, $month);

                    // Counts
                    $recordsTotal = $this->service->getAllByExpirationCount(@$search['value'], $length, $currentPage, $year, $month);
                }
            }



            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerInternalCertificateGradeParticipantDTO::parse($resultData, "2");

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal(count($recordsTotal));
            $this->response->setRecordsFiltered(count($recordsTotal));
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

            $model = CustomerInternalCertificateGradeParticipantDTO::fillAndSaveModel($info);

            $result = CustomerInternalCertificateGradeParticipantDTO::parse($model);

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

            if (!($model = CustomerInternalCertificateGradeParticipant::find($id))) {
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

    public function validate()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            $model = CustomerInternalCertificateGradeParticipant::where('validateCodeCertificate',$id)->first();

            if ($model == null) {
                throw new \Exception("invalid parameters", 404);
            }

            $result = CustomerInternalCertificateGradeParticipantDTO::parse($model);

            $this->response->setResult($result);
            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());
            $this->response->setResult(0);
            // error on server
            $this->response->setStatuscode(404);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function download()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            $model = CustomerInternalCertificateGradeParticipant::where('identificationNumber',$id)->where('hasCertificate','1')->orderBy('certificateCreatedAt', 'desc')->first();

            if ($model == null) {
                throw new \Exception("invalid parameters", 404);
            }

            $result = CustomerInternalCertificateGradeParticipantDTO::parse($model);

            $this->response->setResult($result);
            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());
            $this->response->setResult(0);
            // error on server
            $this->response->setStatuscode(404);
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

            $model = CustomerInternalCertificateGradeParticipantDTO::find($id);

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

    public function downloadCertificate()
    {

        $id = $this->request->get("id", "");

        try {

            if (!($participant = CustomerInternalCertificateGradeParticipant::find($id))) {
                throw new Exception("CertificateGrade not found to delete.");
            }

            $file = "certificate_" . $participant->id . "_.pdf";

            $pathToFile = CmsHelper::getStorageDirectory('internal/certificate') . '/' . $file;

            return Response::download($pathToFile, $file, array(
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>  'attachment; filename="'.$file.'"'
            ));

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

    }

    public function streamCertificate()
    {

        $id = $this->request->get("id", "");

        try {

            if (!($participant = CustomerInternalCertificateGradeParticipant::find($id))) {
                throw new Exception("CertificateGrade not found to delete.");
            }

            $file = "certificate_" . $participant->id . "_.pdf";

            $pathToFile = str_replace("certificado", "bolivar", public_path()) . "/uploads/public/certificate/$file";


            return Response::make(file_get_contents($pathToFile), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' =>  'attachment; filename="'.$file.'"'
            ]);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

    }

    public function upload()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            //Log::info("Agent [" . $id . "]s::");

            $model = CustomerInternalCertificateGradeParticipant::find($id);

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostback($file, $model);
            }

            $model = CustomerInternalCertificateGradeParticipant::find($id);

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

            //$response = Response::make()->setContent($result);
            //$response->send();
            //die();
        }

        return $result;
    }

    public function get() {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if($id == "0"){
                throw new \Exception("invalid parameters", 403);
            }

            if(!($model = CustomerInternalCertificateGradeParticipant::find($id))){
                throw new \Exception("Customer not found");
            }

            //Get data
            $result = CustomerInternalCertificateGradeParticipantDTO::parse($model);

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
