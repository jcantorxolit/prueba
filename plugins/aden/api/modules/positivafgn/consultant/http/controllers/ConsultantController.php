<?php

namespace AdeN\Api\Modules\PositivaFgn\Consultant\Http\Controllers;

use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\DisabilityDiagnostic\Http\Controllers\DisabilityDiagnosticController;
use DB;
use Excel;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use System\Models\Parameters;
use Validator;

use Wgroup\Traits\UserSecurity;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;

use AdeN\Api\Modules\PositivaFgn\Consultant\ConsultantRepository;

class ConsultantController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new ConsultantRepository();
        $this->request = app('Input');

        $this->run();
    }

    public function index()
    {
        $request = Request::instance();
        $content = $request->getContent();
        try {

            $criteria = CriteriaHelper::parse($content, []);
            $defaultFilters = [
                array("field" => 'fullName', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentType', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'documentNumber', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'job', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'regional', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'sectional', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'strategy', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isActive', "operator" => 'like', "value" => $criteria->search)
            ];

            $criteria = CriteriaHelper::addFilters($criteria, $defaultFilters);
            $result = $this->repository->all($criteria);

            $this->response->setData($result["data"]);
            $this->response->setRecordsTotal($result["recordsTotal"]);
            $this->response->setRecordsFiltered($result["recordsFiltered"]);
            $this->response->setDraw($result["draw"]);

        } catch (Exception $ex) {
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function store()
    {
        $content = $this->request->get("data", "");;

        try {
            $entity = HttpHelper::parse($content, true);
            $this->repository->canSave($entity);
            $result = $this->repository->insertOrUpdate($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function destroyDetail()
    {
        $id = $this->request->get("id", "");
        $detail = $this->request->get("detail", "");

        try {
            $this->repository->delete($id, $detail);
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
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->parseModelWithRelations($this->repository->find($id));
            $this->response->setResult($result);
        } catch (Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function downloadTemplate()
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/Plantilla_Asesores_FGN.xlsx";

        $type = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_consultant_type")->select("item as NOMBRE")->get()->toArray();
        $identificationType = Parameters::whereNamespace("wgroup")->whereGroup("employee_document_type")->select("item as NOMBRE")->get()->toArray();
        $gender = Parameters::whereNamespace("wgroup")->whereGroup("gender")->select("item as NOMBRE")->get()->toArray();
        $grade = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_consultant_grade")->select("item as NOMBRE")->get()->toArray();
        $accountingAccount = Parameters::whereNamespace("wgroup")->whereGroup("accounting_account")->select("item as NOMBRE")->get()->toArray();
        $workingDay = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_consultant_workday")->select("item as NOMBRE")->get()->toArray();
        $eps = Parameters::whereNamespace("wgroup")->whereGroup("eps")->select("item as NOMBRE")->get()->toArray();
        $afp = Parameters::whereNamespace("wgroup")->whereGroup("afp")->select("item as NOMBRE")->get()->toArray();
        $ccf = Parameters::whereNamespace("wgroup")->whereGroup("ccf")->select("item as NOMBRE")->get()->toArray();
        $accountType = Parameters::whereNamespace("wgroup")->whereGroup("account_type")->select("item as NOMBRE")->get()->toArray();
        $strategy = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_consultant_strategy")->select("item as NOMBRE")->get()->toArray();

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($type, $identificationType, $gender, $grade, $accountingAccount,
                $workingDay, $eps, $afp, $ccf, $accountType, $strategy) {

            $sheet = $file->setActiveSheetIndex(1);
            $sheet->fromArray($type, null, 'A2', false);
            $sheet->fromArray($identificationType, null, 'B2', false);
            $sheet->fromArray($gender, null, 'C2', false);
            $sheet->fromArray($grade, null, 'D2', false);
            $sheet->fromArray($accountingAccount, null, 'E2', false);
            $sheet->fromArray($workingDay, null, 'G2', false);
            $sheet->fromArray($eps, null, 'H2', false);
            $sheet->fromArray($afp, null, 'I2', false);
            $sheet->fromArray($ccf, null, 'J2', false);
            $sheet->fromArray($accountType, null, 'K2', false);
            $sheet->fromArray($strategy, null, 'L2', false);

            // configurar las columnas de la hoja de datos como rangos
            $file->addNamedRange(new \PHPExcel_NamedRange('type', $sheet, 'A2:A' . (count($type) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('identificationType', $sheet, 'B2:B' . (count($identificationType) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('gender', $sheet, 'C2:C' . (count($gender) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('grade', $sheet, 'D2:D' . (count($grade) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('accountingAccount', $sheet, 'E2:E' . (count($accountingAccount) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('state', $sheet, 'F2:F3'));
            $file->addNamedRange(new \PHPExcel_NamedRange('workingDay', $sheet, 'G2:G' . (count($workingDay) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('eps', $sheet, 'H2:H' . (count($eps) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('afp', $sheet, 'I2:I' . (count($afp) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('ccf', $sheet, 'J2:J' . (count($ccf) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('accountType', $sheet, 'K2:K' . (count($accountType) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('strategy', $sheet, 'L2:L' . (count($strategy) + 1) ));


            $sheet = $file->setActiveSheetIndex(0);

            // asignar los rangos en la hoja principal. Formula es el nombre del rango.
            $cells = [
                'A2' => ['range' => 'A2:A5000', 'formula' => 'type'],
                'B2' => ['range' => 'B2:B5000', 'formula' => 'identificationType'],
                'E2' => ['range' => 'E2:E5000', 'formula' => 'gender'],
                'H2' => ['range' => 'H2:H5000', 'formula' => 'grade'],
                'I2' => ['range' => 'I2:I5000', 'formula' => 'accountingAccount'],
                'Q2' => ['range' => 'Q2:Q5000', 'formula' => 'state'],
                'S2' => ['range' => 'S2:S5000', 'formula' => 'workingDay'],
                'T2' => ['range' => 'T2:T5000', 'formula' => 'eps'],
                'U2' => ['range' => 'U2:U5000', 'formula' => 'afp'],
                'V2' => ['range' => 'V2:V5000', 'formula' => 'ccf'],
                'AB2' => ['range' => 'AB2:AB5000', 'formula' => 'accountType'],
                'AE2' => ['range' => 'AE2:AE5000', 'formula' => 'strategy'],
                'AF2' => ['range' => 'AE2:AE5000', 'formula' => 'strategy'],
                'AG2' => ['range' => 'AE2:AE5000', 'formula' => 'strategy'],
                'AH2' => ['range' => 'AE2:AE5000', 'formula' => 'strategy']
            ];

            ExportHelper::configSheetValidation($cells, $sheet);

        })->download('xlsx');
    }

}
