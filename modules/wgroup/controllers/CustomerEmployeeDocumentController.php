<?php

namespace Wgroup\Controllers;

use Carbon\Carbon;
use Controller as BaseController;
use Excel;
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
use Wgroup\CustomerEmployeeDocument\CustomerEmployeeDocument;
use Wgroup\CustomerEmployeeDocument\CustomerEmployeeDocumentDTO;
use Wgroup\CustomerEmployeeDocument\CustomerEmployeeDocumentService;
use Barryvdh\Snappy\Facades\SnappyPdf as SnappyPdf;
use Wgroup\Models\Customer;
use AdeN\Api\Helpers\CmsHelper;
use October\Rain\Exception\SystemException;
use October\Rain\Exception\ValidationException;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerEmployeeDocumentController extends BaseController
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
        $this->service = new CustomerEmployeeDocumentService();
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

        $customerEmployeeId = $this->request->get("customer_employee_id", "0");
        $customerId = $this->request->get("customer_id", "0");
        $hideCanceled = $this->request->get("hideCanceled", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $currentPage = $currentPage + 1;

            $hideCanceled = $hideCanceled == '1' ? true : false;

            // get all tracking by customer with pagination
            $data = $this->service->getAllBySearch(@$search['value'], $length, $currentPage, $orders, "", $customerEmployeeId, $customerId, $hideCanceled);

            // Counts
            $recordsTotal = $this->service->getCount("", $customerEmployeeId, $customerId, $hideCanceled);
            $recordsFiltered = $this->service->getCount(@$search['value'], $customerEmployeeId, $customerId, $hideCanceled);

            // extract info
            $result = CustomerEmployeeDocumentDTO::parse($data, "2");

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

    public function indexCritical()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $customerEmployeeId = $this->request->get("customer_employee_id", "0");
        $customerId = $this->request->get("customer_id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllByCriticalRequired(@$search['value'], $length, $currentPage, $orders, "", $customerEmployeeId, $customerId);

            // Counts
            $recordsTotal = $this->service->getAllByCriticalRequiredCount("", $customerEmployeeId, $customerId);
            $recordsFiltered = $this->service->getAllByCriticalRequiredCount(@$search['value'], $customerEmployeeId, $customerId);

            // extract info
            //$result = CustomerEmployeeDocumentDTO::parse($data, "2");

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
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

    public function filterExpiration()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $operation = $this->request->get("operation", "all");
        $customerEmployeeId = $this->request->get("customer_employee_id", "0");
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

            // get all tracking by customer with pagination
            $resultData = $this->service->getAllByExpiration(@$search['value'], $length, $currentPage, $year, $month, $customerEmployeeId, $customerId);

            // Counts
            $recordsTotal = $this->service->getAllByExpirationCount($year, $month, $customerEmployeeId, $customerId);

            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerEmployeeDocumentDTO::parse($resultData, "2");

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

    public function filterSearchExpiration()
    {

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

            // get all tracking by customer with pagination
            $resultData = $this->service->getAllBySearchExpiration(@$search['value'], $length, $currentPage, $customerId, $audit);

            // Counts
            $recordsTotal = $this->service->getAllBySearchExpirationCount(@$search['value'], $customerId, $audit);

            //$recordsFiltered = $this->service->getCount(@$search['value'], $customerId);

            // extract info
            $result = CustomerEmployeeDocumentDTO::parse($resultData, "2");

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

    public function exportSearchExpiration()
    {
        set_time_limit(0);

        $filter = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            if ($filter != "") {
                $json = base64_decode($filter);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data =  $this->service->getAllBySearchExpiration("", 0, 0, $customerId, $audit);

            $result = array_map(function ($row) {
                return array(
                    "ID" => $row->id,
                    "TIPO IDENTIFICACIÓN" => $row->documentType,
                    "NÚMERO IDENTIFICACIÓN" => $row->documentNumber,
                    "NOMBRE" => $row->fullName,
                    "TIPO DOCUMENTO" => $row->requirement,
                    "DESCRIPCIÓN" => $row->description,
                    "FECHA INICIO VIGENCIA" => $row->startDate ? Carbon::parse($row->startDate)->format('d/m/Y') : '',
                    "FECHA DE EXPIRACIÓN VIGENCIA" => $row->endDate ? Carbon::parse($row->endDate)->format('d/m/Y') : '',
                    "VERSION" => $row->version,
                    "REQUERIDO" => $row->isRequired,
                    "ESTADO" => $row->status,
                );
            }, $data);


            Excel::create('Consulta_Vencimiento_Dctos_Soporte', function ($excel) use ($result) {
                // Call them separately
                $excel->setDescription('Empleados');

                $excel->sheet('Empleados', function ($sheet) use ($result) {

                    $sheet->fromArray($result, null, 'A1', true, true);

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
                    $sheet->setFreeze('A2');
                    $sheet->setAutoFilter();

                    //Alto de la primer fila
                    $sheet->setHeight(1, 20);

                    //$sheet->fromArray($resultArray, null, 'A2', false, false);
                });

            })->export('xlsx');


        } catch (Exception $exc) {

            // var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc);

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function exportPdfSearchExpiration()
    {

        $data = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data =  $this->service->getAllBySearchExpiration("", 0, 0, $customerId, $audit);

            //var_dump($data);

            $model = Customer::find($customerId);

            $report = array(
                'contract' => $model->documentNumber,
                'date', Carbon::now('America/Bogota')->format('d/m/Y H:m'),
                'data' => $data
            );
            $report['themeUrl'] = CmsHelper::getThemeUrl();
            $report['themePath'] = CmsHelper::getThemePath();

            //var_dump($report);

            $pdf = SnappyPdf::loadView("aden.pdf::html.employee_document_expiration", $report)->setPaper('legal')->setOrientation('landscape')->setWarnings(false);
            return $pdf->download('Consulta_Vencimiento_Dctos_Soporte.pdf');

        } catch (Exception $exc) {
            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function required()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $customerEmployeeId = $this->request->get("customer_employee_id", "0");
        $customerId = $this->request->get("customer_id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllByRequired(@$search['value'], $length, $currentPage, $orders, "", $customerEmployeeId);

            // Counts
            $recordsTotal = $this->service->getAllByRequiredCount("", $customerEmployeeId);
            $recordsFiltered = $this->service->getAllByRequiredCount(@$search['value'], $customerEmployeeId);


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

    public function requiredValidate()
    {

        $itemsPerPage = Parameters::get('system::tables.rowsperpage', 15);

        $customerEmployeeId = $this->request->get("id", "0");
        $customerId = $this->request->get("id", "0");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());


        try {

            $currentPage = $currentPage + 1;

            // get all tracking by customer with pagination
            $data = $this->service->getAllByRequiredValidate(@$search['value'], $length, $currentPage, $orders, "", $customerEmployeeId, $customerId);

            // Counts
            $recordsTotal = $this->service->getCount("", $customerEmployeeId, $customerId);
            $recordsFiltered = $this->service->getCount(@$search['value'], $customerEmployeeId, $customerId);

            // extract info
            //$result = CustomerEmployeeDocumentDTO::parse($data, "2");

            // set count total ideas
            $this->response->setDraw($draw);
            $this->response->setData($data);
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

    public function export()
    {

        $customerEmployeeId = $this->request->get("id", "0");

        try {

            // decodify

            // get all tracking by customer with pagination
            $data = $this->service->getAllByRequiredExport("", 0, 0, null, "", $customerEmployeeId);


            Excel::create('Documentos_Soporte_Empleado', function ($excel) use ($data) {

                // Set the title
                $excel->setTitle('Our new awesome title');

                // Chain the setters
                $excel->setCreator('Maatwebsite')
                    ->setCompany('Maatwebsite');

                // Call them separately
                $excel->setDescription('A demonstration to change the file properties');

                $excel->sheet('Documentos', function ($sheet) use ($data) {

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

            $model = CustomerEmployeeDocumentDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerEmployeeDocumentDTO::parse($model);

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

    public function import()
    {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            //Log::info($json);

            // parse
            $info = json_decode($json);

            $result["id"] = CustomerEmployeeDocumentDTO::fillAndSaveModelImport($info);

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

    public function denied()
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

            $model = CustomerEmployeeDocumentDTO::denied($info);

            // Parse to send on response
            $result = CustomerEmployeeDocumentDTO::parse($model);

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

    public function approve()
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
            $model = CustomerEmployeeDocumentDTO::approve($info);

            // Parse to send on response
            $result = CustomerEmployeeDocumentDTO::parse($model);

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

    public function upload()
    {

        // Preapre parameters for query
        $document = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            //Log::info("agent document[" . $document . "]s::");

            $model = CustomerEmployeeDocument::find($document);

            //$uploadedFile = Input::file('file_data');

            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostback($file, $model);
            }

            $model = CustomerEmployeeDocument::find($document);

            $this->response->setResult($model);

            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(404);
            $this->response->setMessage($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function uploadBulk()
    {

        // Preapre parameters for query
        $document = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            $model = null;

            foreach (explode(',', $document) as $id) {
                $model = CustomerEmployeeDocument::find($id);

                //$uploadedFile = Input::file('file_data');

                if ($model != null) {
                    foreach ($allFiles as $file) {
                        // public/uploads
                        $this->checkUploadPostback($file, $model);
                    }
                }

                //$model = CustomerEmployeeDocument::find($document);
            }

            $this->response->setResult(true);

            //here code.
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(404);
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
            $validationRules[] = 'mimes:jpg,png,jpeg,bmp,gif,pdf,xls,xlsx,doc,docx,csv,msg';

            $validation = Validator::make(
                ['file_data' => $uploadedFile], ['file_data' => $validationRules]
            );

            if ($uploadedFile->getClientOriginalExtension() != 'msg') {
                if ($validation->fails()) {
                    throw new ValidationException($validation);
                }
            }

            if (!$uploadedFile->isValid())
                throw new SystemException('File is not valid');

            $fileRelation = $model->document();

            $file = new File();
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->save();

            $fileRelation->add($file);

            $result = [
                'file' => $uploadedFileName,
                'path' => $file->getPath()
            ];

        } catch (Exception $ex) {
            \Log::error($ex);
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName
            ];

            //Log::info($ex->getMessage() . $uploadedFileName);
            //$response = Response::make()->setContent($result);
            //$response->send();
            //die();
        }

        return $result;
    }

    public function get()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerEmployeeDocument::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerEmployeeDocumentDTO::parse($model);

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

    public function delete()
    {
        // Preapre parameters for query
        $customer = $this->request->get("id", "0");
        $userAdmn = Auth::getUser();
        try {

            $allFiles = Input::file();

            //Log::info("customer [" . $customer . "]s::");

            if (!($model = CustomerEmployeeDocument::find($customer))) {
                throw new Exception("Customer not found to delete.");
            }

            // Elimina el documento
            //$model->document()->delete();

            $model->status = 2;
            $model->updatedBy = $userAdmn ? $userAdmn->id : 0;
            $model->canceled_by = $userAdmn ? $userAdmn->id : 0;
            $model->canceled_at = Carbon::now('America/Bogota');
            $model->save();

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

    public function download()
    {
        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        $file = "";
        $headers = array();

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerEmployeeDocument::find($id))) {
                throw new \Exception("CustomerEmployee not found", 404);
            }

            //Get data
            $result = CustomerEmployeeDocumentDTO::parse($model);

            //$this->response->setResult($result);
            //$file = str_replace("/beta", "",public_path()). $result->document->path;
            //$file = $result->document->path;
            $file = $result->document->getDiskPath();

            $headers = array(
                'Content-Type:' . $result->document->content_type,
                'Content-Disposition:attachment; filename="' . $result->document->file_name . '"',
                'Content-Transfer-Encoding:binary',
                'Content-Length:' . $result->document->file_size,
            );

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
        //return Response::json($file, $this->response->getStatuscode());
        //return Response::download($file, $result->document->file_name, $headers);
        return $result->document->download();

    }

    public function stream()
    {
        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerEmployeeDocument::find($id))) {
                throw new \Exception("CustomerEmployee not found", 404);
            }

            //Get data
            $result = CustomerEmployeeDocumentDTO::parse($model);
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
        $headers = [
            'Content-Type' => $result->document->content_type,
            'Content-Description' => 'File Transfer',
            'Content-Disposition' => "inline; filename={$result->document->file_name}",
            'Content-Transfer-Encoding:binary',
            'Content-Length:' . $result->document->file_size,
            'filename' => $result->document->file_name
        ];
        return $result->document->download($headers);
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
