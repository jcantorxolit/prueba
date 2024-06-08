<?php

namespace Wgroup\Controllers;

use Carbon\Carbon;
use Controller as BaseController;
use DB;
use Excel;
use Exception;
use Input;
use Log;
use October\Rain\Support\Facades\Flash;
use October\Rain\Support\ValidationException;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Classes\SystemException;
use System\Models\File;
use System\Models\Parameters;
use Validator;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\ServiceApi;
use Wgroup\Models\Customer;
use Wgroup\NephosIntegration\NephosIntegration;
use Wgroup\SystemParameter\SystemParameter;
use Wgroup\SystemParameter\SystemParameterDTO;
use Wgroup\SystemParameter\SystemParameterService;
use Redirect;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class SystemParameterController extends BaseController {

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
        $this->service = new SystemParameterService();
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

        $group = $this->request->get("group", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {
            //Si es un usuario de un cliente
            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            $data = $this->service->getAll(@$search['value'], $length, $currentPage, $orders, $group);

            // Counts
            $recordsTotal = $this->service->getAllRecordsCount("");
            $recordsFiltered = $this->service->getAllRecordsCount(@$search['value'], $group);

            // extract info
            $result = SystemParameterDTO::parse($data);

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

    public function indexRelation(){

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $group = $this->request->get("group", "");
        $parent = $this->request->get("parent", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {
            //Si es un usuario de un cliente
            $currentPage = $currentPage + 1;


            // get all tracking by customer with pagination
            $data = $this->service->getAllRelation(@$search['value'], $length, $currentPage, $orders, $group, $parent);

            $result = SystemParameterDTO::parse($data);

            // Counts
            $recordsTotal = $this->service->getAllRelationCount("");
            $recordsFiltered = $this->service->getAllRelationCount(@$search['value'], $group, $parent);

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

    public function getGroupParameter(){
        try {
            // get all tracking by customer with pagination
            $data = $this->service->getGroupParameter();

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
            if (!SystemParameterDTO::canInsert($info)) {
                throw new Exception("No es posible guardar el registro, ya existe el resultado. Por favor verifique.");
            }

            $model = SystemParameterDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = SystemParameterDTO::parse($model);

            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());
            Log::error($exc->getLine());
            Log::error($exc->getFile());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function agree() {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        $redirectUrl = "logout";

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            $user = Auth::getUser();

            if ($user != null) {
                if ($info->type != null && $info->type == 'agree') {
                    $user->wg_term_condition = 1;
                    $user->wg_term_condition_date = Carbon::now('America/Bogota');
                    $user->save();
                    $redirectUrl = $this->getRedirectUrl($user);
                }
            }

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());
            Log::error($exc->getLine());
            Log::error($exc->getFile());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

        $this->response->setResult($redirectUrl);

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function getRedirectUrl($user)
    {
        $redirectUrl = "app/clientes/list";

        if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser")
        {
            $redirectUrl = "app/clientes/view/". $user->company;

            $nephos = NephosIntegration::where('adminUser', $user->email)->first();

            if ($nephos == null) {
                $customer = Customer::find($user->company);

                if ($customer != null) {
                    if ($customer->classification == "Contratante") {
                        $redirectUrl = "app/clientes/list";
                    } else if ($customer->hasEconomicGroup == 1) {
                        $redirectUrl = "app/clientes/list";
                    }
                }
            } else {
                if ($nephos->customer_id == null || $nephos->customer_id = '') {
                    $redirectUrl = "app/enrollment/create";
                } else {
                    $customer = Customer::find($user->company);

                    if ($customer != null) {
                        if ($customer->is_remove == 1) {
                            Flash::success("Lo sentimos la instancia ha sido removida");
                            $redirectUrl = "logout";
                        } else if ($customer->is_disable == 1) {
                            Flash::success("Lo sentimos la instancia ha sido deshabilitada");
                            $redirectUrl = "logout";
                        } else {
                            if ($customer->classification == "Contratante") {
                                $redirectUrl = "app/clientes/list";
                            } else if ($customer->hasEconomicGroup == 1) {
                                $redirectUrl = "app/clientes/list";
                            }
                        }
                    }
                }
            }
        } else if ($user->wg_type == "externalCustomer")
        {
            $redirectUrl = "logout";
        }

        return $redirectUrl;
    }

    public function delete()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if (!($model = SystemParameter::find($id))) {
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

    public function upload()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            //Log::info("Agent [" . $id . "]s::");

            $model = SystemParameter::find($id);

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostBack($file, $model);
            }

            $model = SystemParameter::find($id);

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

    public function get() {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = SystemParameter::find($id))) {
                throw new \Exception("Disability Diagnostic not found", 404);
            }

            //Get data
            $result = SystemParameterDTO::parse($model);

            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
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

    public function terms() {

        try {

            $model = SystemParameter::whereGroup("wg_term_condition")->first();

            //Get data
            $result = SystemParameterDTO::parse($model);

            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
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

    public function privacyPolicy() {

        try {

            $model = SystemParameter::whereGroup("wg_privacy_policy")->first();

            //Get data
            $result = SystemParameterDTO::parse($model);

            $this->response->setResult($result);

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
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

    public function import()
    {
        try {

            $allFiles = Input::file();

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkImportPostback($file);
            }

            $this->response->setResult($allFiles);
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


    protected function checkImportPostback($uploadedFile)
    {
        ini_set('memory_limit','128M');
        set_time_limit(240);

        $uploadedFileName = null;
        $result = array();
        try {
            //  $uploadedFile = Input::file('file');

            if ($uploadedFile)
                $uploadedFileName = $uploadedFile->getClientOriginalName();

            $validationRules = ['max:' . File::getMaxFilesize()];
            $validationRules[] = 'mimes:xls,xlsx';

            $validation = Validator::make(
                ['file_data' => $uploadedFile], ['file_data' => $validationRules]
            );

            if ($validation->fails())
                throw new ValidationException($validation);

            if (!$uploadedFile->isValid())
                throw new SystemException('File is not valid');

            Excel::load($uploadedFile, function ($file)
            {

                $results = $file->all();

                $data = array();

                $now = Carbon::now('America/Bogota')->toDateTimeString();

                foreach ($results as $row) {

                    $data[] = array(
                        'id' => ""
                        , 'code' => $row->codigo
                        , 'description' => $row->diagnostico
                        , 'isActive' => 1
                        , 'createdBy' => 1
                        , 'created_at' => $now
                    );
                }

                if (count($data) > 0) {
                    SystemParameterStaging::truncate();
                    SystemParameterStaging::insert($data);
                    DB::statement('CALL TL_SystemParameter()');
                }
            });


        } catch (Exception $ex) {

            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName
            ];


            //Log::info('Message text.'.sprintf($message, $uploadedFileName, $ex->getMessage()));
        }

        return $result;
    }

    protected function checkUploadPostBack($uploadedFile, $model)
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
