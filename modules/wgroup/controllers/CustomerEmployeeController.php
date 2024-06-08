<?php

namespace Wgroup\Controllers;

use AdeN\Api\Helpers\CmsHelper;
use Barryvdh\Snappy\Facades\SnappyPdf as SnappyPdf;
use Carbon\Carbon;
use Controller as BaseController;
use DB;
use Excel;
use Exception;
use Input;
use Log;
use RainLab\Translate\Classes\Translator;
use RainLab\User\Facades\Auth;
use Response;
use Session;
use System\Models\File;
use System\Models\Parameters;
use Validator;
use Wgroup\Classes\ApiResponse;
use Wgroup\Classes\ServiceApi;
use Wgroup\CustomerEmployee\CustomerEmployee;
use Wgroup\CustomerEmployee\CustomerEmployeeDeleted;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeService;
use Wgroup\CustomerEmployeeAudit\CustomerEmployeeAudit;
use Wgroup\CustomerEmployeeValidity\CustomerEmployeeValidityDTO;
use Wgroup\EmployeeStaging\EmployeeStaging;
use Wgroup\Employee\Employee;
use Wgroup\Employee\EmployeeDTO;
use Wgroup\Models\Customer;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package FINDideas\api
 * @author Andres Mejia
 */
class CustomerEmployeeController extends BaseController
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
        $this->service = new CustomerEmployeeService();
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
        $data = $this->request->get("data", "");

        $length = $this->request->get("length", $itemsPerPage);
        $start = $this->request->get("start", 0);
        $draw = $this->request->get("draw", "1");
        $search = $this->request->get("search", array());
        $currentPage = $start / $length;
        $orders = $this->request->get("order", array());

        try {
            $user = $this->user();

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                if ($user->company != $customerId) {

                    $customer = Customer::find($user->company);

                    if ($customer != null) {
                        if ($customer->classification != "Contratante") {
                            //TODO BB
                            //$customerId = -1;
                        }
                    }
                }
            }

            $currentPage = $currentPage + 1;

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            // get all tracking by customer with pagination
            $data = $this->service->getAllBy(@$search['value'], $length, $currentPage, $orders, $customerId, $audit);

            // Counts
            $recordsTotal = $this->service->getAllCountBy(@$search['value'], $length, $currentPage, $customerId, null);
            $recordsFiltered = $this->service->getAllCountBy(@$search['value'], $length, $currentPage, $customerId, $audit);

            // extract info
            $result = $data;

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

    public function indexActive()
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
            $user = $this->user();

            if ($user->wg_type == "customerAdmin" || $user->wg_type == "customerUser") {
                if ($user->company != $customerId) {

                    $customer = Customer::find($user->company);

                    if ($customer != null) {
                        if ($customer->classification != "Contratante") {
                            //TODO BB
                            //$customerId = -1;
                        }
                    }
                }
            }

            $currentPage = $currentPage + 1;

            if ($data != "") {
                $json = base64_decode($data);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            // get all tracking by customer with pagination
            $data = $this->service->getAllByActive(@$search['value'], $length, $currentPage, $orders, $customerId, $audit);

            // Counts
            $recordsTotal = $this->service->getAllCountByActive(@$search['value'], $length, $currentPage, $customerId, null);
            $recordsFiltered = $this->service->getAllCountByActive(@$search['value'], $length, $currentPage, $customerId, $audit);

            // extract info
            $result = $data;

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

    public function export()
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

            $data = $this->service->getAllExportBy("", 0, 0, $customerId, $audit);

            Excel::create('Reporte_Empleados', function ($excel) use ($data, $customerId) {
                // Call them separately
                $excel->setDescription('Empleados');

                $excel->sheet('Empleados', function ($sheet) use ($data, $customerId) {

                    $model = Customer::find($customerId);

                    $sheet->row(1, array(
                        'Nro Contrato', $model->documentNumber,
                        '', '',
                        '', '',
                        '', '',
                        'Fecha', Carbon::now('America/Bogota')->format('d/m/Y H:m'),
                    ));

                    $resultArray = json_decode(json_encode($data), true);

                    $sheet->fromArray($resultArray, null, 'A3', true, true);
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

    public function exportTemplate()
    {

        //ini_set('memory_limit','2048M');
        set_time_limit(240);

        $filter = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            if ($filter != "") {
                $json = base64_decode($filter);
                $audit = json_decode($json);
            } else {
                $audit = null;
            }

            $data = $this->service->getAllExportByTemplate($customerId, $audit);

            $result = array_map(function ($row) {
                return array(
                    "ID" => $row->id,
                    "TIPO DOCUMENTO" => $row->documentType,
                    "NUMERO DOCUMENTO" => $row->documentNumber,
                    "LUGAR EXPEDICION" => $row->expeditionPlace,
                    "FECHA EXPEDICION" => $row->expeditionDate ? Carbon::parse($row->expeditionDate)->format('d/m/Y') : '',
                    "FECHA NACIMIENTO" => $row->birthdate ? Carbon::parse($row->birthdate)->format('d/m/Y') : '',
                    "GENERO" => $row->gender,
                    "NOMBRE" => $row->firstName,
                    "APELLIDOS" => $row->lastName,
                    "TIPO CONTRATO" => $row->contractType,
                    "PROFESION" => $row->profession,
                    "OCUPACION" => $row->occupation,
                    "CARGO" => $row->job,
                    "CENTRO TRABAJO" => $row->workPlace,
                    "SALARIO" => $row->salary,
                    "EPS" => $row->eps,
                    "AFP" => $row->afp,
                    "ARL" => $row->arl,
                    "PAIS" => $row->country,
                    "DEPARTAMENTO" => $row->state,
                    "CIUDAD" => $row->city,
                    "CENTRO DE COSTOS" => $row->neighborhood,
                    "OBSERVACION" => $row->observation,
                    "DIRECCION" => $row->address,
                    "TELEFONO" => $row->tel,
                    "CELULAR" => $row->cel,
                    "EMAIL" => $row->mail,
                    "ACTIVO" => $row->isActive,
                );
            }, $data);

            Excel::create('Plantilla_Importacion_Empleados', function ($excel) use ($result) {
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
                            'bold' => true,
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

            var_dump($exc->getMessage());
            // Log the full exception
            Log::error($exc->getTraceAsString());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
        }
    }

    public function exportPdf()
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

            $data = $this->service->getAllExportPDF("", 0, 0, $customerId, $audit);

            //var_dump($data);

            $model = Customer::find($customerId);

            $report = array(
                'contract' => $model->documentNumber,
                'date', Carbon::now('America/Bogota')->format('d/m/Y H:m'),
                'data' => $data,
            );
            $report['themeUrl'] = CmsHelper::getThemeUrl();
            $report['themePath'] = CmsHelper::getThemePath();
            //var_dump($report);

            $pdf = SnappyPdf::loadView("aden.pdf::html.employee", $report)
                ->setPaper('legal')
                ->setOrientation('landscape')
                ->setWarnings(false);
            return $pdf->download('Reporte_Empleados.pdf');
        } catch (Exception $exc) {
            var_dump($exc->getMessage());
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

            $model = CustomerEmployeeDTO::fillAndSaveModel($info);

            // Parse to send on response
            $result = CustomerEmployeeDTO::parse($model);

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

    public function quickSave()
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

            $model = CustomerEmployeeDTO::fillAndQuickSaveModel($info);

            // Parse to send on response
            $result = CustomerEmployeeDTO::parse($model);

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

    public function saveDemographic()
    {

        // Preapre parameters for query
        $text = $this->request->get("data", "");

        try {

            // decodify
            $json = base64_decode($text);

            // parse
            $info = json_decode($json);

            // Parse to model

            $model = EmployeeDTO::fillAndSaveDemographicModel($info);

            // Parse to send on response
            $result = EmployeeDTO::parse($model);

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

    public function delete()
    {
        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {
            //Log::info("customer [" . $traking . "]s::");
            if (!($model = CustomerEmployee::find($id))) {
                throw new Exception("Customer not found to delete.");
            }

            $messages = [];

            //----------------------------------------------------------------------
            $absenteeismDisability = DB::table("wg_customer_absenteeism_disability")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($absenteeismDisability)
                $messages[] = "Ausentismos";

            //----------------------------------------------------------------------
            $workMedicine = DB::table("wg_customer_work_medicine")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($workMedicine)
                $messages[] = "Medicina del trabajo";

            //----------------------------------------------------------------------
            $jobCondition = DB::table("wg_customer_job_condition")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($jobCondition)
                $messages[] = "Condiciones puestos de trabajo";

            //----------------------------------------------------------------------
            $diagnosticSource = DB::table("wg_customer_health_damage_diagnostic_source")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($diagnosticSource)
                $messages[] = "Medicina laboral - Origen del diagnóstico";

            //----------------------------------------------------------------------
            $damageQs = DB::table("wg_customer_health_damage_qs")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($damageQs)
                $messages[] = "Medicina laboral - Calificación de origen";

            //----------------------------------------------------------------------
            $damageQl = DB::table("wg_customer_health_damage_ql")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($damageQl)
                $messages[] = "Medicina laboral - Calificación de pérdida";

            //----------------------------------------------------------------------
            $damageRestriction = DB::table("wg_customer_health_damage_restriction")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($damageRestriction)
                $messages[] = "Medicina laboral - Recomendaciones o restricciones";

            //----------------------------------------------------------------------
            $administrativeProcess = DB::table("wg_customer_health_damage_administrative_process")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($administrativeProcess)
                $messages[] = "Medicina laboral - Proceso administrativo";

            //----------------------------------------------------------------------
            $reportAl = DB::table("wg_customer_occupational_report_al")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($reportAl)
                $messages[] = "Reporte AT";

            //----------------------------------------------------------------------
            $investigationAl = DB::table("wg_customer_occupational_investigation_al")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($investigationAl)
                $messages[] = "Investigación AT";

            //----------------------------------------------------------------------
            $reportIncident = DB::table("wg_customer_occupational_report_incident")
                ->where("customer_employee_id", $id)
                ->count() > 0;

            if ($reportIncident)
                $messages[] = "Reporte de incidentes.";

            if (count($messages) == 0) {

                $employeeDeleted = new CustomerEmployeeDeleted();
                $employeeDeleted->deleted_id = $model->id;
                $employeeDeleted->customer_id = $model->customer_id;
                $employeeDeleted->employee_id = $model->employee_id;
                $employeeDeleted->contractType = $model->contractType;
                $employeeDeleted->occupation = $model->occupation;
                $employeeDeleted->job = $model->job;
                $employeeDeleted->workPlace = $model->workPlace;
                $employeeDeleted->salary = $model->salary;
                $employeeDeleted->type = $model->type;
                $employeeDeleted->isActive = $model->isActive;
                $employeeDeleted->isAuthorized = $model->isAuthorized;
                $employeeDeleted->primary_email = $model->primary_email;
                $employeeDeleted->primary_cellphone = $model->primary_cellphone;
                $employeeDeleted->location_id = $model->location_id;
                $employeeDeleted->department_id = $model->department_id;
                $employeeDeleted->area_id = $model->area_id;
                $employeeDeleted->turn_id = $model->turn_id;
                $employeeDeleted->work_shift = $model->work_shift;
                $employeeDeleted->createdBy = $model->createdBy;
                $employeeDeleted->updatedBy = $model->updatedBy;
                $employeeDeleted->created_at = $model->created_at;
                $employeeDeleted->updated_at = $model->updated_at;
                $employeeDeleted->save();

                $model->delete();
            }

            $this->response->setResult([
                "isSuccess" => count($messages) == 0,
                "messages" => $messages,
            ]);
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

    public function inactive()
    {
        // Preapre parameters for query
        $traking = $this->request->get("id", "0");

        try {

            //Log::info("customer [" . $traking . "]s::");

            if (!($model = CustomerEmployee::find($traking))) {
                throw new Exception("Customer not found to delete.");
            }

            if ($model != null) {

                $userAdmn = Auth::getUser();
                if ($model->isActive != false) {
                    $description = "inactivación";
                    $action = "Inactivado";

                    $customerEmployeeAudit = new CustomerEmployeeAudit();
                    $customerEmployeeAudit->customer_employee_id = $model->id;
                    $customerEmployeeAudit->model_name = "Empleados";
                    $customerEmployeeAudit->model_id = $model->id;
                    $customerEmployeeAudit->user_type = $userAdmn->wg_type;
                    $customerEmployeeAudit->user_id = $userAdmn->id;
                    $customerEmployeeAudit->action = "{$action} Manual";
                    $customerEmployeeAudit->observation = "Se realizó la {$description} del empleado";
                    $customerEmployeeAudit->date = Carbon::now('America/Bogota');
                    $customerEmployeeAudit->save();
                }
            }

            $model->isActive = false;
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

    public function validityDelete()
    {
        $id = $this->request->get("id", "0");

        try {

            $result = CustomerEmployeeValidityDTO::delete($id);

            $this->response->setResult($result);
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

    public function get()
    {

        // Preapre parameters for query
        $id = $this->request->get("id", "0");

        try {

            if ($id == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            if (!($model = CustomerEmployee::find($id))) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerEmployeeDTO::parse($model, "2");

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

    public function getThree()
    {

        // Preapre parameters for query
        $documentNumber = $this->request->get("documentNumber", "0");
        $customerId = $this->request->get("customerId", "0");

        try {

            if ($customerId == "0") {
                throw new \Exception("invalid parameters", 403);
            }

            $model = CustomerEmployee::where("customer_id", $customerId)
                ->whereHas("employee", function ($query) use ($documentNumber) {
                    $query->where("documentNumber", $documentNumber);
                })
                ->first();
            if (!$model) {
                throw new \Exception("Customer not found", 404);
            }

            //Get data
            $result = CustomerEmployeeDTO::parse($model, "2");

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

            $model = Employee::find($id);

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkUploadPostback($file, $model);
            }

            $model = Employee::find($id);

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

        $uploadedFileName = null;

        try {
            //  $uploadedFile = Input::file('file');

            if ($uploadedFile) {
                $uploadedFileName = $uploadedFile->getClientOriginalName();
            }

            $validationRules = ['max:' . File::getMaxFilesize()];
            $validationRules[] = 'mimes:jpg,png,jpeg,bmp,gif';

            $validation = Validator::make(
                ['file_data' => $uploadedFile],
                ['file_data' => $validationRules]
            );

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new SystemException('File is not valid');
            }

            $fileRelation = $model->logo();

            $file = new File();
            $file->data = $uploadedFile;
            $file->is_public = true;
            $file->save();

            $fileRelation->add($file);

            $result = [
                'file' => $uploadedFileName,
                'path' => $file->getPath(),
            ];
        } catch (Exception $ex) {
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName,
            ];
        }

        return $result;
    }

    public function import()
    {
        $customerId = $this->request->get("id", "0");

        try {

            $allFiles = Input::file();

            //$uploadedFile = Input::file('file_data');
            foreach ($allFiles as $file) {
                // public/uploads
                $this->checkImportPostback($file, $customerId);
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

    protected function checkImportPostback($uploadedFile, $customerId)
    {
        ini_set('memory_limit', '128M');
        set_time_limit(240);

        $uploadedFileName = null;
        $result = array();
        try {
            //  $uploadedFile = Input::file('file');

            if ($uploadedFile) {
                $uploadedFileName = $uploadedFile->getClientOriginalName();
            }

            $validationRules = ['max:' . File::getMaxFilesize()];
            $validationRules[] = 'mimes:xls,xlsx';

            $validation = Validator::make(
                ['file_data' => $uploadedFile],
                ['file_data' => $validationRules]
            );

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            if (!$uploadedFile->isValid()) {
                throw new SystemException('File is not valid');
            }


            DB::connection()->disableQueryLog();

            EmployeeStaging::truncate();

            //Excel::filter('chunk')->selectSheetsByIndex(0)->load($file->getDiskPath())->chunk(500, function($results) use ($customerId) {
            //  ini_set('max_execution_time', 0);
            //Excel::load($file->getDiskPath(), function ($file) use ($customerId) {

            //$results = $file->all();

            $data = array();

            $now = Carbon::now('America/Bogota')->toDateTimeString();

            $hasCustomerEmployeeId = false;

            //var_dump($results);
            /*foreach ($results as $sheet) {

            if (isset($sheet) && isset($sheet->tipo_documento) && $sheet->tipo_documento != '') {

            $hasCustomerEmployeeId = isset($sheet->id);

            $data[] = $this->getParsedExcelDataToArray($customerId, $sheet);

            } else {
            foreach ($sheet as $row) {
            if (isset($row->tipo_documento) && $row->tipo_documento != '') {

            $hasCustomerEmployeeId = isset($row->id);

            $data[] = $this->getParsedExcelDataToArray($customerId, $row);
            }

            }
            }

            }

            EmployeeStaging::insert($data);*/

            /*if (count($data) > 0) {
            EmployeeStaging::truncate();
            EmployeeStaging::insert($data);
            if (!$hasCustomerEmployeeId) {
            DB::statement('CALL TL_Employee()');
            } else {
            DB::statement('CALL TL_Employee_Template()');
            }
            }*/
            // });

            Excel::load($uploadedFile, function ($file) use ($customerId) {

                $results = $file->all();

                $data = array();

                $now = Carbon::now('America/Bogota')->toDateTimeString();

                $hasCustomerEmployeeId = false;

                //var_dump($results);
                foreach ($results as $sheet) {

                    if (isset($sheet) && isset($sheet->tipo_documento) && $sheet->tipo_documento != '') {

                        $hasCustomerEmployeeId = isset($sheet->id);

                        $data[] = $this->getParsedExcelDataToArray($customerId, $sheet);
                    } else {
                        foreach ($sheet as $row) {
                            if (isset($row->tipo_documento) && $row->tipo_documento != '') {

                                $hasCustomerEmployeeId = isset($row->id);

                                $data[] = $this->getParsedExcelDataToArray($customerId, $row);
                            }
                        }
                    }
                }

                if (count($data) > 0) {
                    EmployeeStaging::truncate();
                    EmployeeStaging::insert($data);
                    if (!$hasCustomerEmployeeId) {
                        DB::statement('CALL TL_Employee()');
                    } else {
                        DB::statement('CALL TL_Employee_Template()');
                    }
                }
            });
        } catch (Exception $ex) {

            var_dump($ex->getMessage());
            $message = $uploadedFileName ? 'Error uploading file "%s". %s' : 'Error uploading file. %s';

            $result = [
                'error' => sprintf($message, $uploadedFileName, $ex->getMessage()),
                'file' => $uploadedFileName,
            ];

            //Log::info('Message text.' . sprintf($message, $uploadedFileName, $ex->getMessage()));
        }

        return $result;
    }

    private function getParsedExcelDataToArray($customerId, $data)
    {
        $now = Carbon::now('America/Bogota')->toDateTimeString();

        if (isset($data->centro_de_costos)) {
            $neighborhood = $data->centro_de_costos;
        } else if (isset($data->barrio)) {
            $neighborhood = $data->barrio;
        } else {
            $neighborhood = null;
        }

        return [
            'id' => "", 'customer_id' => $customerId, 'customer_employee_id' => isset($data->id) ? $data->id : null, 'documentType' => $data->tipo_documento, 'documentNumber' => $data->numero_documento, 'expeditionPlace' => $data->lugar_expedicion, 'expeditionDate' => $data->fecha_expedicion, 'birthdate' => $data->fecha_nacimiento, 'gender' => $data->genero, 'firstName' => $data->nombre, 'lastName' => $data->apellidos, 'fullName' => $data->nombre . ' ' . $data->apellidos, 'contractType' => $data->tipo_contrato, 'profession' => $data->profesion, 'occupation' => $data->ocupacion, 'job' => $data->cargo, 'workPlace' => $data->centro_trabajo, 'salary' => $data->salario, 'eps' => $data->eps, 'afp' => $data->afp, 'arl' => $data->arl, 'country_id' => $data->pais, 'state_id' => $data->departamento, 'city_id' => $data->ciudad, 'neighborhood' => $neighborhood, 'observation' => $data->observacion, 'isActive' => isset($data->activo) ? strtoupper($data->activo) == 'SI' ? 1 : 0 : 1, 'address' => $data->direccion, 'telephone' => $data->telefono, 'mobil' => $data->celular, 'email' => $data->email, 'createdBy' => 1, 'created_at' => $now,
        ];
    }

    /**
     *  PRIVATED METHODS
     */

    /**
     * Returns the logged in user, if available
     */
    private function user()
    {
        if (!Auth::check()) {
            return null;
        }

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
        if (!Session::has(self::SESSION_LOCALE)) {
            return null;
        }

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

            if ($newfile) {
                while (!feof($file)) {
                    fwrite($newfile, fread($file, 1024 * 8), 1024 * 8);
                }
            }
        }

        if ($file) {
            fclose($file);
        }
        if ($newfile) {
            fclose($newfile);
        }
    }

    public function debug($message, $param = null)
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
