<?php

namespace AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Http\Controllers;

use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\ExportHelper;
use Exception;
use Log;
use Request;
use Response;
use Excel;

use System\Models\Parameters;
use Wgroup\Traits\UserSecurity;
use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;

use AdeN\Api\Modules\PositivaFgn\GestPos\Activity\ActivityRepository;

class ActivityController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new ActivityRepository();
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
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'code', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'strategy', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'isActive', "operator" => 'like', "value" => $criteria->search),
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

    public function config()
    {
        $content = $this->request->get("data", "");

        try {
            $entity = HttpHelper::parse($content, true);
            $result = $this->repository->config($entity);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }


    public function downloadTemplate()
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/Plantilla_Actividades GESTPOST.xlsx";

        $sector = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_sector")->select("item as NOMBRE")->get()->toArray();
        $program = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_program")->select("item as NOMBRE")->get()->toArray();
        $plan = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_plan")->select("item as NOMBRE")->get()->toArray();
        $actionLine = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_action_line")->select("item as NOMBRE")->get()->toArray();
        $typeActivity = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_activity_type")->select("item as NOMBRE")->get()->toArray();
        $strategy = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_consultant_strategy")->select("item as NOMBRE")->get()->toArray();

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($sector, $program, $plan, $actionLine, $typeActivity, $strategy) {

            $sheet = $file->setActiveSheetIndex(1);
            $sheet->fromArray($sector, null, 'A2', false);
            $sheet->fromArray($program, null, 'B2', false);
            $sheet->fromArray($plan, null, 'C2', false);
            $sheet->fromArray($actionLine, null, 'D2', false);
            $sheet->fromArray($typeActivity, null, 'E2', false);
            $sheet->fromArray($strategy, null, 'F2', false);

            // configurar las columnas de la hoja de datos como rangos
            $file->addNamedRange(new \PHPExcel_NamedRange('sector', $sheet, 'A2:A' . (count($sector) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('program', $sheet, 'B2:B' . (count($program) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('plan', $sheet, 'C2:C' . (count($plan) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('actionLine', $sheet, 'D2:D' . (count($actionLine) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('typeActivity', $sheet, 'E2:E' . (count($typeActivity) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('strategy', $sheet, 'F2:F' . (count($strategy) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('state', $sheet, 'G2:G3'));
            $file->addNamedRange(new \PHPExcel_NamedRange('automaticTask', $sheet, 'H2:H3'));


            $sheet = $file->setActiveSheetIndex(0);

            // asignar los rangos en la hoja principal. Formula es el nombre del rango.
            $cells = [
                'B2' => ['range' => 'B2:B5000', 'formula' => 'state'],
                'C2' => ['range' => 'C2:C5000', 'formula' => 'automaticTask'],
                'D2' => ['range' => 'D2:D5000', 'formula' => 'sector'],
                'E2' => ['range' => 'E2:E5000', 'formula' => 'program'],
                'F2' => ['range' => 'F2:F5000', 'formula' => 'plan'],
                'G2' => ['range' => 'G2:G5000', 'formula' => 'actionLine'],
                'I2' => ['range' => 'I2:I5000', 'formula' => 'typeActivity'],
                'J2' => ['range' => 'J2:J5000', 'formula' => 'strategy'],
                'K2' => ['range' => 'K2:K5000', 'formula' => 'strategy'],
                'L2' => ['range' => 'L2:L5000', 'formula' => 'strategy'],
                'M2' => ['range' => 'M2:M5000', 'formula' => 'strategy']
            ];

            ExportHelper::configSheetValidation($cells, $sheet);
        })->download('xlsx');
    }

}
