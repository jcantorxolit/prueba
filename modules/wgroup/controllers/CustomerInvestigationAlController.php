<?php

namespace Wgroup\Controllers;

use Carbon\Carbon;
use Controller as BaseController;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Models\File;
use System\Models\Parameters;
use Validator;
use Wgroup\Classes\ApiResponse;
use Wgroup\CustomerInvestigationAl\CustomerInvestigationAl;
use Wgroup\CustomerInvestigationAl\CustomerInvestigationAlDTO;
use Wgroup\CustomerInvestigationAl\CustomerInvestigationAlService;
use Wgroup\Models\Customer;
use Wgroup\SystemParameter\SystemParameter;
use PDF;
use Excel;
use Barryvdh\Snappy\Facades\SnappyPdf as SnappyPdf;
use Wgroup\Traits\UserSecurity;
use AdeN\Api\Helpers\CmsHelper;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerInvestigationAlController extends BaseController
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

        //set service
        $this->service = new CustomerInvestigationAlService();
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

        $this->run();

    }


    public function index()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());

        try {

            $currentPage = $currentPage + 1;

            $exclude = 0;

            if ($this->isUserAdmin()) {
                $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders);
                $recordCount = $this->service->getCount();
                $recordCountFilter = $this->service->getCount(@$search['value']);
            } else if ($this->isUserAgentExternal()) {
                $data = $this->service->getAllByAgentExternal(@$search['value'], $length, $currentPage, $orders, '', $this->isUserRelatedAgent());
                $recordCount = $this->service->getAgentExternalCount("", $this->isUserRelatedAgent());
                $recordCountFilter = $this->service->getAgentExternalCount(@$search['value'], $this->isUserRelatedAgent());
            } else if ($this->isUserAgentEmployee()) {
                $data = $this->service->getAllByAgentEmployee(@$search['value'], $length, $currentPage, $orders, '', $this->isUserRelatedAgent());
                $recordCount = $this->service->getAgentEmployeeCount("", $this->isUserRelatedAgent());
                $recordCountFilter = $this->service->getAgentEmployeeCount(@$search['value'], $this->isUserRelatedAgent());
            } else {
                $data = [];
                $recordCount = 0;
                $recordCountFilter = 0;
            }
            // get all ideas with pagination


            // extract info
            $result = CustomerInvestigationAlDTO::parse($data);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($result);
            $this->response->setRecordsTotal($recordCount);
            $this->response->setRecordsFiltered($recordCountFilter);
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

    public function tracing()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
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
            // get all ideas with pagination

            $data = $this->service->getAllFilter(@$search['value'], $length, $currentPage, $orders, $audit);

            // Counts
            $recordsTotal = $this->service->getAllFilterCount(null);
            $recordsFiltered = $this->service->getAllFilterCount($audit);

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
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

    public function tracingExport()
    {
        $data = $this->request->get("data", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $result = $this->service->getAllTracingExport($audit);


            Excel::create('SEGUIMIENTO_A_CUMPLIMIENTO_INVESTIGATION_BOLIVAR_AT', function ($excel) use ($result, $audit) {

                // Set the title
                $excel->setCreator('sylogic')
                    ->setCompany('waygroup');

                $excel->sheet('SEGUIMIENTO A CUMPLIMIENTO', function ($sheet) use ($result, $audit) {


                    $resultArray = json_decode(json_encode($result), true);

                    $sheet->fromArray($resultArray, null, 'A1', true, true);

                    // Set row background
                    $sheet->row(1, function ($row) {

                        // call cell manipulation methods
                        $row->setBackground('#958057');
                        $row->setFontColor('#FFFFFF');
                        $row->setAlignment('center');
                        $row->setValignment('center');
                        $row->setFont(array(
                            'family' => 'Calibri',
                            'size' => '13',
                            'bold' => true
                        ));

                    });

                    //Filtro y Bloqueo de la primer fila
                    //$sheet->freezeFirstRow(); // $sheet->freezeFirstRowAndColumn();
                    $sheet->setFreeze('C2');
                    $sheet->setAutoFilter();

                    //Alto de la primer fila
                    $sheet->setHeight(1, 20);
                });
            })->export('xlsx');

        } catch (Exception $exc) {


            // Log the full exception
            var_dump($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
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

            $model = CustomerInvestigationAlDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerInvestigationAlDTO::parse($model);

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

    public function saveCustomer()
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

            $model = CustomerInvestigationAlDTO::fillAndSaveCustomerModel($info);

            // Parse to send on response
            $result = CustomerInvestigationAlDTO::parse($model);

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

    public function saveEmployee()
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

            $model = CustomerInvestigationAlDTO::fillAndSaveEmployeeModel($info);

            // Parse to send on response
            $result = CustomerInvestigationAlDTO::parse($model);

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

    public function saveAccident()
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

            $model = CustomerInvestigationAlDTO::fillAndSaveAccidentModel($info);

            // Parse to send on response
            $result = CustomerInvestigationAlDTO::parse($model);

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

    public function saveSummary()
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

            $model = CustomerInvestigationAlDTO::fillAndSaveSummaryModel($info);

            // Parse to send on response
            $result = CustomerInvestigationAlDTO::parse($model);

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

    public function saveEvent()
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

            $model = CustomerInvestigationAlDTO::fillAndSaveEventModel($info);

            // Parse to send on response
            $result = CustomerInvestigationAlDTO::parse($model);

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

    public function saveAnalysis()
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

            $model = CustomerInvestigationAlDTO::fillAndSaveAnalysisModel($info);

            // Parse to send on response
            $result = CustomerInvestigationAlDTO::parse($model);

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

    public function saveCause()
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

            $model = CustomerInvestigationAlDTO::fillAndSaveCauseModel($info);

            // Parse to send on response
            $result = CustomerInvestigationAlDTO::parseCause($model);

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

            // Parse to model

            $model = CustomerInvestigationAlDTO::fillAndSaveActivateEntity($info);

            // Parse to send on response
            //$result = CustomerInvestigationAlDTO::parse($model);

            $this->response->setResult($model);

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

            // Parse to model

            $model = CustomerInvestigationAlDTO::fillAndUpdateModel($info);

            // Parse to send on response
            $result = CustomerInvestigationAlDTO::parse($model);

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

            if (!($model = CustomerInvestigationAl::find($id))) {
                throw new \Exception("CustomerInvestigationAl not found", 404);
            }

            //Get data
            $result = CustomerInvestigationAlDTO::parse($model);

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

    public function getCause()
    {
        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerInvestigationAl::find($id))) {
                throw new \Exception("CustomerInvestigationAl not found", 404);
            }

            //Get data
            $result = CustomerInvestigationAlDTO::parseCause($model);

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
        $id = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            //Log::info("CustomerInvestigationAl [" . $id . "]s::");

            $model = CustomerInvestigationAl::find($id);

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostback($file, $model);
            }

            $model = CustomerInvestigationAl::find($id);

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

    public function uploadSignature()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            //Log::info("CustomerInvestigationAl [" . $id . "]s::");

            $model = CustomerInvestigationAl::find($id);

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostbackSignature($file, $model);
            }

            $model = CustomerInvestigationAl::find($id);

            $this->response->setResult(\AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->signature));
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

    protected function checkUploadPostbackSignature($uploadedFile, $model)
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

            $fileRelation = $model->signature();

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

    public function delete()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            //Log::info("CustomerInvestigationAl [" . $id . "]s::");

            if (!($model = CustomerInvestigationAl::find($id))) {
                throw new Exception("CustomerInvestigationAl not found to delete.");
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


    public function reviewInjury()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $data = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {
            if ($data != "") {
                $json = base64_decode($data);

                //Log::info($json);

                $audit = json_decode($json);
            } else {
                $audit = null;
            }
            // get all tracking by customer with pagination
            $data = $this->service->getAllReviewInjury($audit->customerId, $audit->year);

            // Counts
            $recordsTotal = 0;
            $recordsFiltered = 0;

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
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

    public function reviewPivot()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $data = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {
            if ($data != "") {
                $json = base64_decode($data);

                //Log::info($json);

                $audit = json_decode($json);
            } else {
                $audit = null;
            }
            // get all tracking by customer with pagination
            $data = $this->service->getAllReviewPivot();

            $result = [
                "d" => ["results" => $data]
            ];

            return Response::json($result, $this->response->getStatuscode());

        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }


    }

    public function reviewInjuryExport()
    {
        $data = $this->request->get("data", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $result = $this->service->getAllReviewInjuryExport($audit->customerId, $audit->year);


            Excel::create('Resumen_Lesiones_INVESTIGATION_BOLIVAR_AT', function ($excel) use ($result, $audit) {

                // Set the title
                $excel->setCreator('sylogic')
                    ->setCompany('waygroup');

                $excel->sheet('INVESTIGATION_BOLIVAR_AT', function ($sheet) use ($result, $audit) {

                    $customerTitle = 'Todas';
                    $yearTitle = $audit->year != 0 ? $audit->year : 'Todos';

                    if ($audit->customerId != '' && $audit->customerId != 0) {
                        $model = Customer::find($audit->customerId);
                        if ($model != null) {
                            $customerTitle = strtoupper($model->businessName);
                        }
                    }

                    $sheet->row(1, array(
                        'Empresa', $customerTitle
                    ));

                    $sheet->row(2, array(
                        'Año', $yearTitle
                    ));

                    $sheet->row(3, array(
                        'Fecha', Carbon::now('America/Bogota')->format('d/m/Y H:m'),
                    ));

                    $resultArray = json_decode(json_encode($result), true);

                    $sheet->fromArray($resultArray, null, 'A5', true, true);
                });
            })->export('xlsx');

        } catch (Exception $exc) {


            // Log the full exception
            var_dump($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function getReviewFilters()
    {
        try {
            $data['years'] = $this->service->getInvestigationYearFilter();
            $data['customers'] = $this->service->getInvestigationCustomerFilter();

            $result["data"] = $data;

            $this->response->setData($data);
        } catch (Exception $exc) {
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function getReviewCharts()
    {
        $customerId = $this->request->get("customer_id", "0");
        $year = $this->request->get("year", "0");

        try {
            $resultBarEconomyActivity = $this->service->getDashboardBarEconomyActivity($customerId, $year);
            $resultBarLink = $this->service->getDashboardBarLink($customerId, $year);
            $resultBarGender = $this->service->getDashboardBarGender($customerId, $year);
            $resultBarAccidentState = $this->service->getDashboardBarAccidentState($customerId, $year);
            $resultBarAccidentCity = $this->service->getDashboardBarAccidentCity($customerId, $year);
            $resultBarRegularWork = $this->service->getDashboardBarRegularWork($customerId, $year);
            $resultBarWorkTime = $this->service->getDashboardBarWorkTime($customerId, $year);
            $resultBarWeekDay = $this->service->getDashboardBarWeekDay($customerId, $year);
            $resultBarAccidentType = $this->service->getDashboardBarAccidentType($customerId, $year);
            $resultBarPlace = $this->service->getDashboardBarPlace($customerId, $year);
            $resultBarInjuryType = $this->service->getDashboardBarInjuryType($customerId, $year);
            $resultBarBody = $this->service->getDashboardBarBody($customerId, $year);
            $resultBarAgent = $this->service->getDashboardBarAgent($customerId, $year);
            $resultBarMechanism = $this->service->getDashboardBarMechanism($customerId, $year);
            $resultBarInsecureAct = $this->service->getDashboardBarInsecureAct($customerId, $year);
            $resultBarInsecureCondition = $this->service->getDashboardBarInsecureCondition($customerId, $year);
            $resultBarWorkFactor = $this->service->getDashboardBarWorkFactor($customerId, $year);
            $resultBarPersonalFactor = $this->service->getDashboardBarPersonalFactor($customerId, $year);

            $lineChartEconomyActivity = $this->getBarChart($resultBarEconomyActivity);
            $lineChartLink = $this->getBarChart($resultBarLink);
            $lineChartGender = $this->getBarChart($resultBarGender);
            $lineChartAccidentState = $this->getBarChart($resultBarAccidentState);
            $lineChartAccidentCity = $this->getBarChart($resultBarAccidentCity);
            $lineChartRegularWork = $this->getBarChart($resultBarRegularWork);
            $lineChartWorkTime = $this->getBarChart($resultBarWorkTime);
            $lineChartWeekDay = $this->getBarChart($resultBarWeekDay);
            $lineChartAccidentType = $this->getBarChart($resultBarAccidentType);
            $lineChartPlace = $this->getBarChart($resultBarPlace);
            $lineChartInjuryType = $this->getBarChart($resultBarInjuryType);
            $lineChartBody = $this->getBarChart($resultBarBody);
            $lineChartAgent = $this->getBarChart($resultBarAgent);
            $lineChartMechanism = $this->getBarChart($resultBarMechanism);
            $lineChartInsecureAct = $this->getBarChart($resultBarInsecureAct);
            $lineChartInsecureCondition = $this->getBarChart($resultBarInsecureCondition);
            $lineChartWorkFactor = $this->getBarChart($resultBarWorkFactor);
            $lineChartPersonalFactor = $this->getBarChart($resultBarPersonalFactor);

            $result = array();

            $result["dataBarEconomyActivity"] = $lineChartEconomyActivity;
            $result["dataBarLink"] = $lineChartLink;
            $result["dataBarGender"] = $lineChartGender;
            $result["dataBarAccidentState"] = $lineChartAccidentState;
            $result["dataBarAccidentCity"] = $lineChartAccidentCity;
            $result["dataBarRegularWork"] = $lineChartRegularWork;
            $result["dataBarWorkTIme"] = $lineChartWorkTime;
            $result["dataBarWeekDay"] = $lineChartWeekDay;
            $result["dataBarAccidentType"] = $lineChartAccidentType;
            $result["dataBarPlace"] = $lineChartPlace;
            $result["dataBarInjuryType"] = $lineChartInjuryType;
            $result["dataBarBody"] = $lineChartBody;
            $result["dataBarAgent"] = $lineChartAgent;
            $result["dataBarMechanism"] = $lineChartMechanism;
            $result["dataBarInsecureAct"] = $lineChartInsecureAct;
            $result["dataBarInsecureCondition"] = $lineChartInsecureCondition;
            $result["dataBarWorkFactor"] = $lineChartWorkFactor;
            $result["dataBarPersonalFactor"] = $lineChartPersonalFactor;

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

    private function getBarChart($data)
    {
        $colors[] = "#5cb85c";
        $colors[] = "#e0d653";
        $colors[] = "#F7464A";
        $colors[] = "#46BFBD";
        $colors[] = "#46BEBE";
        $colors[] = "#5cb855";

        $colors2[] = "#5cb855";
        $colors2[] = "#F7464A";
        $colors2[] = "#46BFBD";
        $colors2[] = "#5cb85c";
        $colors2[] = "#e0d653";
        $colors2[] = "#46BEBE";

        if (!empty($data)) {

            $label = array("Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Ocbtubre", "Noviembre", "Diciembre");

            $dataSet = array();

            $index = 0;
            foreach ($data as $line) {
                if ($index == count($colors)) {
                    $index = 0;
                }
                $dataSet[] = array(
                    "label" => $line->abbreviation,
                    "fillColor" => $this->hex2rgba($colors[$index], 0.5),
                    "strokeColor" => $this->hex2rgba($colors[$index], 1),
                    "pointColor" => $this->hex2rgba($colors[$index], 1),
                    "pointStrokeColor" => '#fff',
                    "pointHighlightFill" => '#fff',
                    "pointHighlightStroke" => $this->hex2rgba($line->color, 1),
                    "data" => array($line->ENE, $line->FEB, $line->MAR, $line->ABR, $line->MAY, $line->JUN, $line->JUL, $line->AGO, $line->SEP, $line->OCT, $line->NOV, $line->DIC)
                );
                $index++;
            }

            $barLineChart = array();

            $barLineChart["labels"] = $label;
            $barLineChart["datasets"] = $dataSet;
        } else {
            $barLineChart = array();
        }

        return $barLineChart;
    }

    private function hex2rgba($color, $opacity = false)
    {

        $default = 'rgb(0,0,0)';

        //Return default if no color provided
        if (empty($color))
            return $default;

        //Sanitize $color if "#" is provided
        if ($color[0] == '#') {
            $color = substr($color, 1);
        }

        //Check if color has 6 or 3 characters and get values
        if (strlen($color) == 6) {
            $hex = array($color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5]);
        } elseif (strlen($color) == 3) {
            $hex = array($color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2]);
        } else {
            return $default;
        }

        //Convert hexadec to rgb
        $rgb = array_map('hexdec', $hex);

        //Check if opacity is set(rgba or rgb)
        if ($opacity) {
            if (abs($opacity) > 1)
                $opacity = 1.0;
            $output = 'rgba(' . implode(",", $rgb) . ',' . $opacity . ')';
        } else {
            $output = 'rgb(' . implode(",", $rgb) . ')';
        }

        //Return rgb(a) color string
        return $output;
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

    public function download()
    {

        $id = $this->request->get("id", "");

        try {

            $reportOne = $this->service->getInvestigationReportDataPartOne($id);
            $reportTwo = $this->service->getInvestigationReportDataPartTwo($id);
            $reportDocument = $this->service->getInvestigationReportDocuments($id);
            $reportFactor = $this->service->getInvestigationReportFactors($id);
            $reportMeasure = $this->service->getInvestigationReportMeasures($id);


            $reportOne = (array)($reportOne[0]);
            $reportTwo = (array)($reportTwo[0]);
            $reportTwo["documents"] = $reportDocument;
            $reportTwo["measures"] = $reportMeasure;
            $reportTwo["causes"] = $reportFactor;

            $reportTwo["acts"] = $this->service->getInvestigationReportCause('CIAI', $id);
            $reportTwo["conditions"] = $this->service->getInvestigationReportCause('CICI', $id);;
            $reportTwo["works"] = $this->service->getInvestigationReportCause('CBFT', $id);;
            $reportTwo["personals"] = $this->service->getInvestigationReportCause('CBFP', $id);;


            $data = array_merge($reportOne, $reportTwo);

            $fileName = "Reporte_Investigation_AT_" . $reportOne['id'] . ".pdf";

            //$pdf = SnappyPdf::loadView("aden.pdf::html.investigational", $data)->setPaper('legal')->setOrientation('portrait')->setWarnings(false);
            $pdf = SnappyPdf::loadView("aden.pdf::html.investigational", $data)->setPaper('A4')
                ->setOption('margin-top', '2.5cm')
                ->setOption('margin-bottom', '2.4cm')
                //->setOption('margin-bottom', 10)
                ->setOption('margin-left', 15)
                ->setOption('margin-right', 15)
                ->setOrientation('portrait')
                ->setWarnings(false);

            return $pdf->download($fileName);

        } catch (Exception $exc) {
            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

    }

    public function downloadLetter()
    {

        $id = $this->request->get("id", "");

        try {

            $reportOne = $this->service->getInvestigationReportDataPartOne($id);
            $reportTwo = $this->service->getInvestigationReportDataPartTwo($id);
            $reportDocument = $this->service->getInvestigationReportDocuments($id);
            $reportFactor = $this->service->getInvestigationReportFactors($id);
            $reportMeasure = $this->service->getInvestigationReportMeasures($id);


            $reportOne = (array)($reportOne[0]);
            $reportTwo = (array)($reportTwo[0]);
            $reportTwo["documents"] = $reportDocument;
            $reportTwo["measures"] = $reportMeasure;
            $reportTwo["causes"] = $reportFactor;

            $reportTwo["acts"] = $this->service->getInvestigationReportCause('CIAI', $id);
            $reportTwo["conditions"] = $this->service->getInvestigationReportCause('CICI', $id);;
            $reportTwo["works"] = $this->service->getInvestigationReportCause('CBFT', $id);;
            $reportTwo["personals"] = $this->service->getInvestigationReportCause('CBFP', $id);;


            $data = array_merge($reportOne, $reportTwo);
            $data['themeUrl'] = CmsHelper::getThemeUrl();
            $data['themePath'] = CmsHelper::getThemePath();

            $fileName = "Carta_Consecutivo_" . $reportOne['id'] . ".pdf";

            //$pdf = SnappyPdf::loadView("aden.pdf::html.investigational", $data)->setPaper('legal')->setOrientation('portrait')->setWarnings(false);

            $pdf = SnappyPdf::loadView("aden.pdf::html.letter", $data)->setPaper('A4')
                ->setOption('margin-top', '5cm')
                ->setOption('margin-bottom', '5cm')
                ->setOption('margin-left', 15)
                ->setOption('margin-right', 15)
                ->setOrientation('portrait')->setWarnings(false);
            return $pdf->download($fileName);

        } catch (Exception $exc) {
            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }

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
