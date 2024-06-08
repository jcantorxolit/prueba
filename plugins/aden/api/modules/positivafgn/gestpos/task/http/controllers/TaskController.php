<?php

namespace AdeN\Api\Modules\PositivaFgn\GestPos\Task\Http\Controllers;

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

use AdeN\Api\Modules\PositivaFgn\GestPos\Task\TaskRepository;

class TaskController extends BaseController
{
    use UserSecurity;

    private $repository;

    public function __construct()
    {
        parent::__construct();

        $this->repository = new TaskRepository();
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
                array("field" => 'number', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'code', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'type', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'mainTask', "operator" => 'like', "value" => $criteria->search),
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
            $result = $this->repository->delete($id);
            $this->response->setResult($result);
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
        $file = "templates/$instance/Plantilla_Tareas_Gestpos_FGN.xlsx";

        $sector = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_sector")->select("item as NOMBRE")->get()->toArray();
        $program = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_program")->select("item as NOMBRE")->get()->toArray();
        $plan = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_plan")->select("item as NOMBRE")->get()->toArray();
        $actionLine = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_action_line")->select("item as NOMBRE")->get()->toArray();

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($sector, $program, $plan, $actionLine) {

            $sheet = $file->setActiveSheetIndex(1);
            $sheet->fromArray($sector, null, 'B2', false);
            $sheet->fromArray($program, null, 'C2', false);
            $sheet->fromArray($plan, null, 'D2', false);
            $sheet->fromArray($actionLine, null, 'E2', false);

            // configurar las columnas de la hoja de datos como rangos
            $file->addNamedRange(new \PHPExcel_NamedRange('type', $sheet, 'A2:A4'));
            $file->addNamedRange(new \PHPExcel_NamedRange('sector', $sheet, 'B2:B' . (count($sector) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('program', $sheet, 'C2:C' . (count($program) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('plan', $sheet, 'D2:D' . (count($plan) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('actionLine', $sheet, 'E2:E' . (count($actionLine) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('associateCode', $sheet, 'F2:F3'));

            $sheet = $file->setActiveSheetIndex(0);

            // asignar los rangos en la hoja principal. Formula es el nombre del rango.
            $cells = [
                'A2' => ['range' => 'A2:A5000', 'formula' => 'type'],
                'C2' => ['range' => 'C2:C5000', 'formula' => 'associateCode'],
                'D2' => ['range' => 'D2:D5000', 'formula' => 'sector'],
                'E2' => ['range' => 'E2:E5000', 'formula' => 'program'],
                'F2' => ['range' => 'F2:F5000', 'formula' => 'plan'],
                'G2' => ['range' => 'G2:G5000', 'formula' => 'actionLine'],
            ];

            ExportHelper::configSheetValidation($cells, $sheet);

        })->download('xlsx');
    }

}
