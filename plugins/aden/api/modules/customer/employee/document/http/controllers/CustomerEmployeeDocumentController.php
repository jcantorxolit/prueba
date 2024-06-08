<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 25/11/2017
 * Time: 6:14 PM
 */

namespace AdeN\Api\Modules\Customer\Employee\Document\Http\Controllers;

use DB;
use Excel;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Exception;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\HttpHelper;
use Wgroup\Traits\UserSecurity;

use AdeN\Api\Modules\Customer\Employee\Document\CustomerEmployeeDocumentRepository;
use System\Models\Parameters;

class CustomerEmployeeDocumentController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new CustomerEmployeeDocumentRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            DB::listen(function ($query) {
                //Log::error($query);
                //var_dump($query);
            });


            $mandatoryFilters = [
                array("field" => 'customerEmployeeId', "operator" => 'eq'),
                array("field" => 'statusCode', "operator" => 'neq'),
                array("field" => 'year', "operator" => 'eq'),
                array("field" => 'month', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'requirement', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'version', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isRequired', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isVerified', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observation', "operator" => 'like', "value" => $criteria->search)
            ];


            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->all($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            \Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexFilter()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerEmployeeId', "operator" => 'eq'),
                array("field" => 'statusCode', "operator" => 'neq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'requirement', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'version', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isRequired', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isVerified', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observation', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allFilter($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
            $this->response->setExtra($result["uids"]);
        } catch (Exception $ex) {
            \Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexExpiration()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {
            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'statusCode', "operator" => 'in'),
                array("field" => 'year', "operator" => 'eq'),
                array("field" => 'month', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'requirement', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'version', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isRequired', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isVerified', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allExpiration($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexRequired()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            DB::listen(function ($query) {
                //Log::error($query);
                //var_dump($query);
            });


            $mandatoryFilters = [
                array("field" => 'customerEmployeeId', "operator" => 'notIn'),
                array("field" => 'customerId', "operator" => 'raw'),
                array("field" => 'isRequired', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'requirement', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'version', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isRequired', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isVerified', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'observation', "operator" => 'like', "value" => $criteria->search)
            ];


            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allRequired($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexRequiredCritical()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            DB::listen(function ($query) {
                //Log::error($query);
                //var_dump($query);
            });

            $mandatoryFilters = [
                array("field" => 'customerEmployeeId', "operator" => 'notIn'),
                array("field" => 'criticalActivityCustomerEmployeeId', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
            ];


            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allRequiredCritital($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexExport()
    {
        $request = Request::instance();

        $content = $request->getContent();

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'requirementCode', "operator" => 'eq'),
                array("field" => 'requirementOrigin', "operator" => 'eq'),
            ];

            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);

            $defaultFilters = [
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'requirement', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'description', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'startDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'endDate', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'status', "operator" => 'like', "value" => $criteria->search),
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);

            $result = $this->repository->allExport($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);
            $this->response->setExtra($result["uids"]);
        } catch (Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function indexExpirationExport()
    {
        set_time_limit(0);

        $content = $this->request->get("data", "");
        $customerId = $this->request->get("id", "");

        try {

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true));

            $criteria = CriteriaHelper::addMandatoryFilter($criteria, [
                array("field" => 'customerId', "operator" => 'eq', "value" => $customerId)
            ]);

            $this->repository->exportExpirationExcel($criteria);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function store()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function destroy()
    {
        $id = $this->request->get("id", "");

        try {
            $this->repository->delete($id);
            $this->response->setResult(1);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function show()
    {
        $content = $this->request->get("data", "");

        try {
            $criteria = HttpHelper::parse($content, true);
            $result = $this->repository->parseModelWithDocumentRelations($criteria);
            $this->response->setResult($result);
        } catch (Exception $ex) {
            \Log::error($ex);
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function upload()
    {
        $id = $this->request->get("id", "0");
        try {

            $allFiles = Input::file();

            $model = $this->repository->find($id);

            foreach ($allFiles as $file) {
                $this->repository->checkUploadPostBack($file, $model);
            }
            $model = $this->repository->find($id);
            $this->response->setResult($model);
        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function download()
    {
        $id = $this->request->get("id", "0");
        try {

            $model = $this->repository->find($id);

            $file = $model->document->getDiskPath();

            $headers = $this->repository->getDownloadHeaders($model->document);

            //return Response::download($file, $model->document->file_name, $headers);
            return $model->document->download();

        } catch (Exception $ex) {
            $this->response->setStatuscode(404);
            $this->response->setMessage($ex->getMessage());
            return Response::json($this->response, $this->response->getStatuscode());
        }
    }

    public function export()
    {
        set_time_limit(0);

        $content = $this->request->get("data", "");

        try {

            $mandatoryFilters = [
                array("field" => 'customerId', "operator" => 'eq'),
                array("field" => 'customerEmployeeId', "operator" => 'eq'),
            ];

            $data = HttpHelper::parse($content, true);

            $criteria = CriteriaHelper::parse($data, $mandatoryFilters);
            $criteria->filter = !empty($data->audit) ? $data->audit : null;            
            $result = $this->repository->export($criteria);

            return Response::json($result, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function exportByType()
    {
        set_time_limit(0);

        $content = $this->request->get("data", "");

        try {

            $mandatoryFilters = [
                array("field" => 'filename', "operator" => 'eq'),
                array("field" => 'id', "operator" => 'inRaw'),
            ];

            $criteria = CriteriaHelper::parse(HttpHelper::parse($content, true), $mandatoryFilters);

            $data = $this->repository->exportByType($criteria);

            return Response::json($data, 200);

        } catch (Exception $ex) {
            Log::error($ex);
            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
    }

    public function getWorkShifts() {
        return Parameters::whereNamespace("wgroup")->whereGroup("work_shifts")->select("item as NOMBRE")->get()->toArray();
    }

    public function downloadTemplate()
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/Plantilla_Creacion_De_TrabajadoresNew.xlsx";

        $workShifts = $this->getWorkShifts();

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($workShifts) {
            $sheet = $file->setActiveSheetIndex(1);

            $sheet->fromArray($workShifts, null, 'Y2', false);

            // configurar las columnas de la hoja de datos como rangos
            $file->addNamedRange(new \PHPExcel_NamedRange('identificationType', $sheet, 'M2:M4'));
            $file->addNamedRange(new \PHPExcel_NamedRange('gender', $sheet, 'O2:O3'));
            $file->addNamedRange(new \PHPExcel_NamedRange('contractType', $sheet, 'H2:H12'));
            $file->addNamedRange(new \PHPExcel_NamedRange('employeeProfession', $sheet, 'F2:F12'));
            $file->addNamedRange(new \PHPExcel_NamedRange('listEps', $sheet, 'J2:J44'));
            $file->addNamedRange(new \PHPExcel_NamedRange('listAfp', $sheet, 'A2:A19'));
            $file->addNamedRange(new \PHPExcel_NamedRange('listArl', $sheet, 'D2:D10'));
            $file->addNamedRange(new \PHPExcel_NamedRange('countries', $sheet, 'Q2:Q2'));
            $file->addNamedRange(new \PHPExcel_NamedRange('states', $sheet, 'S2:S33'));
            $file->addNamedRange(new \PHPExcel_NamedRange('municipio', $sheet, 'U2:U1113'));
            $file->addNamedRange(new \PHPExcel_NamedRange('listRh', $sheet, 'W2:W9'));
            $file->addNamedRange(new \PHPExcel_NamedRange('riskLevel', $sheet, 'X2:X6'));
            $file->addNamedRange(new \PHPExcel_NamedRange('workShifts', $sheet, 'Y2:Y' . (count($workShifts) + 1)));

            $sheet = $file->setActiveSheetIndex(0);
            // asignar los rangos en la hoja principal. Formula es el nombre del rango.
            $cells = [
                'A2' => ['range' => 'A2:A5000', 'formula' => 'identificationType'],
                'F2' => ['range' => 'F2:F5000', 'formula' => 'gender'],
                'I2' => ['range' => 'I2:I5000', 'formula' => 'contractType'],
                'J2' => ['range' => 'J2:J5000', 'formula' => 'employeeProfession'],
                'O2' => ['range' => 'O2:O5000', 'formula' => 'listEps'],
                'P2' => ['range' => 'P2:P5000', 'formula' => 'listAfp'],
                'Q2' => ['range' => 'Q2:Q5000', 'formula' => 'listArl'],
                'R2' => ['range' => 'R2:R5000', 'formula' => 'countries'],
                'S2' => ['range' => 'S2:S5000', 'formula' => 'states'],
                'T2' => ['range' => 'T2:T5000', 'formula' => 'municipio'],
                'U2' => ['range' => 'U2:U5000', 'formula' => 'listRh'],
                'V2' => ['range' => 'V2:V5000', 'formula' => 'riskLevel'],
                'AB2' => ['range' => 'AB2:AB5000', 'formula' => 'workShifts'],
            ];


            ExportHelper::configSheetValidation($cells, $sheet);
        })->download('xlsx');
    }
}
