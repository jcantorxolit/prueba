<?php

namespace AdeN\Api\Modules\Customer\VrEmployee;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Classes\SnappyPdfOptions;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Config\SignatureCertificateVr\SignatureCertificateVrModel;
use AdeN\Api\Modules\Customer\VrEmployee\Experience\ExperienceModel;
use AdeN\Api\Modules\Customer\VrEmployee\Experience\ExperienceRepository;
use AdeN\Api\Modules\Customer\VrEmployee\Experience\ExperienceService;
use AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\ExperienceAnswerModel;
use AdeN\Api\Modules\Customer\VrSignatureCertificate\SignatureCertificateVrRepository;
use DB;
use Exception;
use Log;
use Illuminate\Support\Facades\Input;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Maatwebsite\Excel\Facades\Excel;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use System\Models\File;
use System\Models\Parameters;
use Wgroup\SystemParameter\SystemParameter;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\CustomerEmployeeDocument\CustomerEmployeeDocumentDTO;
use Wgroup\Employee\EmployeeDTO;

class CustomerVrEmployeeRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerVrEmployeeModel());
        $this->service = new CustomerVrEmployeeService();
    }

    public static function getCustomFilters()
    {
        $aDefault = [
            ["alias" => "Fecha", "name" => "registrationDate"],
            ["alias" => "Tipo Identificación", "name" => "documentType"],
            ["alias" => "Num. Identificación", "name" => "documentNumber"],
            ["alias" => "Nombre", "name" => "fullName"],
            ["alias" => "Fecha Realización", "name" => "dateRealization"],
            ["alias" => "% Completado", "name" => "average"],
            ["alias" => "Estado", "name" => "isActive"]
        ];

        return $aDefault;
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_vr_employee.id",
            "registrationDate" => DB::raw("DATE_FORMAT(DATE_SUB(wg_customer_vr_employee.created_at, INTERVAL 5 HOUR), '%Y-%m-%d') AS registrationDate"),
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber AS documentNumber",
            "fullName" => "wg_employee.fullName AS fullName",
            "dateRealization" => "wg_customer_vr_employee_answer_experience.registration_date AS dateRealization",
            "average" => "wg_customer_vr_employee.average",
            "qtyExperience" => DB::raw("COUNT(wg_customer_vr_employee_experience.customer_vr_employee_experience_id) AS qtyExperience"),
            "isActive" => DB::raw("IF(wg_customer_vr_employee.is_active=1,'En Progreso',IF(wg_customer_vr_employee.is_active=0,'Anulado','Finalizado')) as isActive"),
            "hasConfig" => DB::raw("IF(wg_customer_vr_employee_experience.customer_vr_employee_experience_id IS NOT NULL, 1 , 0) as hasConfig"),
            "customerId" => "wg_customer_vr_employee.customer_id"
        ]);

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $newCriteria = new stdClass;
        $newCriteria->customerId = $customerId ? $customerId->value : null;

        $qExperience = ExperienceRepository::getEmployeeExperienceQuery($newCriteria);

        $this->parseCriteria($criteria);

        $query = $this->query()
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_vr_employee.customer_employee_id');
            })->join("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('employee_document_type.value', '=', 'wg_customer_vr_employee.document_type');
            })->leftjoin(DB::raw("({$qExperience->toSql()}) AS wg_customer_vr_employee_experience"), function ($join) {
                $join->on('wg_customer_vr_employee_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee.id');
            })->mergeBindings($qExperience)->leftJoin("wg_customer_vr_employee_answer_experience", function ($join) {
                $join->on('wg_customer_vr_employee.id', '=', 'wg_customer_vr_employee_answer_experience.customer_vr_employee_id');
            })
            ->groupBy("wg_customer_vr_employee.id");

        $this->applyCriteria($query, $criteria);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, CustomerVrEmployeeModel::class);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allExperienceDetail($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_vr_employee.id",
            "customerVrEmployeeId" => "wg_customer_vr_employee_experience.customer_vr_employee_id",
            "experience" => "wg_customer_vr_employee_experience.experience",
            "experienceValue" => "wg_customer_vr_employee_experience.experienceValue",
            "percent" => "wg_customer_vr_employee_experience.percent",
            "color_percent" => "wg_customer_vr_employee_experience.color_percent",
            "customerId" => "wg_customer_vr_employee.customer_id"
        ]);

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $newCriteria = new stdClass;
        $newCriteria->customerId = $customerId ? $customerId->value : null;

        $qExperience = ExperienceRepository::getEmployeeExperienceQuery($newCriteria);

        $this->parseCriteria($criteria);

        $query = $this->query();
        $query->join(DB::raw("({$qExperience->toSql()}) AS wg_customer_vr_employee_experience"), function ($join) {
            $join->on('wg_customer_vr_employee_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee.id');
        })->mergeBindings($qExperience);

        $this->applyCriteria($query, $criteria);

        $result = $this->get($query, $criteria);

        return $result;
    }

    public function allToGenerate($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_vr_employee.id",
            "registrationDate" => DB::raw("DATE_FORMAT(DATE_SUB(wg_customer_vr_employee.created_at, INTERVAL 5 HOUR), '%Y-%m-%d') AS registrationDate"),
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_employee.documentNumber AS documentNumber",
            "fullName" => "wg_employee.fullName AS fullName",    
            "average" => "wg_customer_vr_employee.average",
            "isActive" => DB::raw("IF(wg_customer_vr_employee.is_active=1,'En Progreso',IF(wg_customer_vr_employee.is_active=0,'Anulado','Finalizado')) as isActive"),            
            "customerId" => "wg_customer_vr_employee.customer_id"
        ]);

        $this->parseCriteria(null);

        $query = $this->query()
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_vr_employee.customer_employee_id');
            })->join("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('employee_document_type.value', '=', 'wg_customer_vr_employee.document_type');
            })
            ->groupBy("wg_customer_vr_employee.id");

        $this->applyCriteria($query, $criteria);

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModel($data);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allStaging($criteria)
    {
        $this->setColumns([
            "index" => "wg_customer_vr_employee_staging.index",
            "registrationDate" => "wg_customer_vr_employee_staging.registration_date",
            "documentNumber" => "wg_customer_vr_employee_staging.document_number",
            "documentType" => "wg_customer_vr_employee_staging.document_type",
            "experience" => "wg_customer_vr_employee_staging.experience",
            "experienceScene" => "wg_customer_vr_employee_staging.experience_scene",
            "indicator" => "wg_customer_vr_employee_staging.indicator",
            "value" => "wg_customer_vr_employee_staging.value",
            "justification" => "wg_customer_vr_employee_staging.justification",
            "observationType" => "wg_customer_vr_employee_staging.observation_type",
            "observationValue" => "wg_customer_vr_employee_staging.observation_value",
            "errorValidation" => "wg_customer_vr_employee_staging.error_validation",
            "isValid" => "wg_customer_vr_employee_staging.is_valid",
            "customerId" => "wg_customer_vr_employee_staging.customer_id",
            "sessionId" => "wg_customer_vr_employee_staging.session_id"
        ]);


        $this->parseCriteria($criteria);

        $query = $this->query(DB::table("wg_customer_vr_employee_staging"));

        $this->applyCriteria($query, $criteria);

        $result = $this->get($query, $criteria);

        return $result;
    }


    public function canInsert($entity)
    {
        return true;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;
        $authUser = $this->getAuthUser();
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->id = $entity->id;
        $entityModel->customerId = $entity->customerId;
        $entityModel->customerEmployeeId = $entity->employee->id;
        $entityModel->documentType = $entity->employee->documentType->value;
        $entityModel->isActive = $entity->isActive;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->createdAt = Carbon::now();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
        }

        if (!empty($entity->removeLogo)) {
            //EmployeeDTO::removeLogo($entity->employee->entity->id);
            $entity->employee->logo = null;
        }

        $entityModel->save();
        $entity->id = $entityModel->id;
        return $entity;
    }

    public function cancel($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->isActive = 0;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();
    }

    public function parseModelWithRelations(CustomerVrEmployeeModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {

            $employeeRepository = new CustomerEmployeeDTO();
            $employee = (object)$employeeRepository->find($model->customerEmployeeId, 2);
            $experienceAnswer = ExperienceAnswerModel::whereCustomerVrEmployeeId($model->id)->first();
            $hasExperienceConfig = ExperienceModel::whereCustomerVrEmployeeId($model->id)->first();

            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->hasConfig = $hasExperienceConfig !== null;
            $entity->employee = [
                "id" => $employee ? $employee->id : null,
                "customerId" => $model->customerId,
                "documentType" => $employee ? $employee->entity->documentType :  $model->getDocumentType(),
                "documentNumber" => $employee ? $employee->entity->documentNumber :  $model->documentNumber,
                "firstName" => $employee->entity->firstName,
                "lastName" => $employee->entity->lastName,
                "gender" => $employee->entity->gender,
                "logo" => $employee->entity->logo,
                "entity" => ["id" => $employee->entity->id]
            ];
            $entity->isActive = (int)$model->isActive;
            $entity->registrationDate = $experienceAnswer ? Carbon::parse($experienceAnswer->registrationDate) : null;

            return $entity;
        } else {
            return null;
        }
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'Historico_VR_Employee_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Detalle', $data);
    }

    public function exportExcelIndicators($criteria)
    {
        $data = $this->service->exportExcelIndicators($criteria);
        $filename = 'VR_Employee_Indicadores' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Detalle', $data);
    }

    public function exportCertificate($criteria, $zipFilename = null)
    {
        $start = Carbon::now();

        $data = $this->service->getExportDataCertificate($criteria);

        if ($data) {
            $excelFilename = 'GUIA_CERTIFICADOS_EMPLEADOS_VR_' . Carbon::now()->timestamp;
            ExportHelper::excelStorage($excelFilename, 'GUIA', $data['excel']);


            $zipFilename = $zipFilename ? $zipFilename : 'CERTIFICADOS_EMPLEADOS_VR_' . Carbon::now()->timestamp . '.zip';
            $zipFullPath = CmsHelper::getStorageDirectory('zip/exports') . '/' . $zipFilename;

            if (!CmsHelper::makeDir(CmsHelper::getStorageDirectory('zip/exports'))) {
                throw new \Exception("Can create folder", 403);
            }

            $zipData = array_merge($data['zip'], [[
                'fullPath' => CmsHelper::getStorageDirectory('excel/exports') . '/' . $excelFilename . ".xlsx",
                "filename" => $excelFilename . ".xlsx"
            ]]);

            ExportHelper::zipFileSystemStream($zipFullPath, $zipData);

            $end = Carbon::now();

            return [
                'message' => 'ok',
                'elapseTime' => $end->diffInSeconds($start),
                'endTime' => $end->timestamp,
                'filename' => $zipFilename,
                'path' => CmsHelper::getPublicDirectory('zip/exports/'),
                'uids' => $data['uids']
            ];
        } else {
            return [
                'message' => 'error',
            ];
        }
    }

    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/PlantillaVREmployee.xlsx";
        $columns = $this->service->makeExperienceList($customerId);

        $experience = [];
        $scene = [];
        $question = [];
        if (count($columns)) {
            $experience = array_map(function ($row) {
                return [
                    'NOMBRE' => $row
                ];
            }, $columns["experience"]);

            $scene = array_map(function ($row) {
                return [
                    'NOMBRE' => $row
                ];
            }, $columns["scene"]);

            $question = array_map(function ($row) {
                return [
                    'NOMBRE' => $row
                ];
            }, $columns["question"]);
        }

        $obsTypes = array_map(function ($row) {
            return [
                'NOMBRE' => $row->item
            ];
        }, SystemParameter::whereNamespace("wgroup")->whereGroup("experience_scene_observation_type")
            ->select("item")
            ->get()
            ->all());

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($experience, $scene, $question, $obsTypes) {
            $sheet = $file->setActiveSheetIndex(1);
            $sheet->fromArray($experience, null, 'A2', false);
            $sheet->fromArray($scene, null, 'B2', false);
            $sheet->fromArray($question, null, 'C2', false);
            $sheet->fromArray($obsTypes, null, 'D2', false);
            $sheet = $file->setActiveSheetIndex(0);
        })->download('xlsx');
    }

    public function downloadTemplateEmployee()
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/Plantilla_VR_Employee_Head.xlsx";
        Excel::load(CmsHelper::getAppPath($file))->download('xlsx');
    }

    public static function setFinish($id)
    {
        $reposity = new self;
        $reposity->query()->where('id', $id)->update(['is_active' => 2]);
    }

    public static function generateCertificate($id)
    {
        $reposity = new self;
        $model = $reposity->find($id);
        $parseModel = $reposity->parseModelWithRelations($model);

        $experiences = ExperienceService::getExperiencesWithAnswers($id)
            ->pluck('experience');

        $answerExperience = ExperienceAnswerModel::where('customer_vr_employee_id', $id)->first();

        $settings = SignatureCertificateVrModel::first();
        $customerSettings = (new SignatureCertificateVrRepository)->parseModelWithRelations($parseModel->customerId);

        $logo = $settings->logo ? $settings->logo->getTemporaryUrl() : null;
        $signature = $settings->signature ? $settings->signature->getTemporaryUrl() : null;
        $fullName = $settings->fullName ?? '';
        $job = $settings->job;

        if ($customerSettings && $customerSettings->isActive) {
            $logo = $customerSettings->logo ? $customerSettings->logo : null;
            $signature = $customerSettings->signature ? $customerSettings->signature : null;
            $fullName = $customerSettings->fullName ?? '';
            $job = $customerSettings->job;
        }

        $data = [
            "captionHeader" => "REALIDAD VIRTUAL",
            "participant" => $parseModel->employee["firstName"] . " " . $parseModel->employee["lastName"],
            "documentType" => $parseModel->employee["documentType"]->value,
            "identification" => $parseModel->employee["documentNumber"],
            "code" => $reposity->generateRandomString(),
            "themeUrl" => CmsHelper::getThemeUrl(),
            "themePath" => CmsHelper::getThemePath(),
            "date" => Carbon::parse($answerExperience->registration_date)->format('d/m/Y'),
            "experiences" => $experiences,
            "instance" => CmsHelper::getInstance(),
            "fullName" => $fullName,
            "job" => $job,
            "logo" => $logo,
            "signature" => $signature
        ];

        $filename = 'CERT_VR_' . Carbon::now()->timestamp . '.pdf';
        $pathFile = "/tmp/{$filename}";
        $pdfOptions = (new SnappyPdfOptions('letter', 'landscape'))
            ->setJavascriptDelay(2500)
            ->setEnableJavascript(true)
            ->setEnableSmartShrinking(true)
            ->setNoStopSlowScripts(true);

        ExportHelper::store("aden.pdf::html.certificate_vr_employee", $data, $pathFile, $pdfOptions);
        $file = new UploadedFile($pathFile, $filename, "application/pdf", 3000, null, true);

        $documentType = Parameters::whereNamespace("wgroup")->whereGroup("wg_employee_attachment")->whereValue("CERVR")->first()->getAttributes();
        if ($documentType) {
            $documentType["origin"] = "system";
        }

        $employeeDocumentModel = new \stdClass();
        $employeeDocumentModel->id = 0;
        $employeeDocumentModel->customerEmployeeId = $parseModel->employee["id"];
        $employeeDocumentModel->requirement = (object)$documentType;
        $employeeDocumentModel->status = Parameters::whereNamespace("wgroup")->whereGroup("customer_document_status")->first();
        $employeeDocumentModel->version = 1;
        $employeeDocumentModel->description = "CERTIFICADO GENERADO POR MÓDULO REALIADAD VIRTUAL";
        $employeeDocumentModel->startDate = null;
        $employeeDocumentModel->endDate = null;

        $employeeDocumentModel = CustomerEmployeeDocumentDTO::fillAndSaveModel($employeeDocumentModel);

        $reposity->checkUploadPostBack($file, $model);
        $reposity->checkUploadPostBack($file, $employeeDocumentModel);
    }

    function generateRandomString($length = 10)
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function setAverage($id, $avg)
    {
        $reposity = new self;
        $reposity->query()->where('id', $id)->update(['average' => $avg]);
    }

    public function consolidate($customerId)
    {
        return $this->service->consolidate($customerId);
    }

    public function generateReportPdf($data)
    {
        $pdfData = $this->service->getReportPdfData($data);

        $pdfData['general_objective'] = $this->getParagraph("pdf_report_vr_indicator_general_objective");
        $pdfData['methodologies'] = $this->getParagraph("pdf_report_vr_indicator_methodology");
        $pdfData['metrics'] = $this->getParagraph("pdf_report_vr_indicator_metric");

        $filename = 'INFORME_DE_PRESTACIÓN_DE_SERVICIOS_DE_PROMOCIÓN_Y_PREVENCIÓN_' . Carbon::now()->timestamp . '.pdf';

        $header = \View::make('aden.pdf::html.customer_vr_employee_report_header', $pdfData);
        $footer = \View::make('aden.pdf::html.customer_vr_employee_report_footer', $pdfData);

        $pdfOptions = (new SnappyPdfOptions('Legal'))
            ->setJavascriptDelay(2500)
            ->setEnableJavascript(true)
            ->setEnableSmartShrinking(true)
            ->setNoStopSlowScripts(true)
            ->setMarginBottom(10)
            ->setMarginTop(37)
            ->setMarginLeft(0)
            ->setMarginRight(0)
            //->setOption('header-center', '-[page]-')
            //->setOption('header-right', "$project - EP. $episode")
            // ->setOption('footer-left', 'Para uso exclusivo de CRYSTAL DUBS')
            ->setOption('header-spacing', 5)
            ->setOption('footer-spacing', 2)
            ->setOption('debug-javascript', true)
            ->setOption('header-html', $header)
            ->setOption('footer-html', $footer);
        return ExportHelper::pdf("aden.pdf::html.customer_vr_employee_report", $pdfData, $filename, $pdfOptions);
    }

    private function getParagraph($type)
    {
        return SystemParameter::where("group", $type)
            ->where("namespace", "WGROUP")
            ->offset(0)->limit(3)
            ->get()->map(function($item) {
            return $item->value;
        });
    }
}
