<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\Activity\Http\Controllers;

use AdeN\Api\Helpers\ExportHelper;
use DB;
use Excel;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Request;
use Response;
use Session;
use Validator;

use Wgroup\Traits\UserSecurity;
use System\Models\Parameters;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\HttpHelper;

use AdeN\Api\Modules\PositivaFgn\Fgn\Activity\ActivityRepository;
use AdeN\Api\Modules\PositivaFgn\Fgn\Config\ConfigModel;

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

            $mandatoryFilters = [
                array("field" => 'configId', "operator" => 'eq'),
            ];
            $criteria = CriteriaHelper::parse($content, $mandatoryFilters);
            $defaultFilters = [
                array("field" => 'axis', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'action', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'code', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'name', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'strategy', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goalCoverage', "operator" => 'like', "value" => $criteria->search),
                array("field" => 'goalCompliance', "operator" => 'like', "value" => $criteria->search)
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

    public function showClear()
    {
        $id = $this->request->get("id", "");

        try {
            $result = $this->repository->parseModelWithRelations($this->repository->find($id), true);
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
        $detail = $this->request->get("detail", "");

        try {
            $result = $this->repository->delete($id, $detail);
            $this->response->setResult($result);
        } catch (\Exception $ex) {
            $this->response->setResult(0);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
        }
        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function downloadTemplate()
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/Plantilla_Actividades_FGN.xlsx";

        $axis = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_axis")->select("item as NOMBRE")->get()->toArray();
        $action = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_action")->select("item as NOMBRE")->get()->toArray();
        $type = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_activity_type")->select("item as NOMBRE")->get()->toArray();
        $strategy = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_consultant_strategy")->select("item as NOMBRE")->get()->toArray();
        $periodicity = Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_periodicity")->select("item as NOMBRE")->get()->toArray();
        $periods = ConfigModel::whereIsActive(1)->select("period as NOMBRE")->get()->toArray();

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($axis, $action, $type, $strategy, $periodicity, $periods) {

            $sheet = $file->setActiveSheetIndex(1);
            $sheet->fromArray($axis, null, 'A2', false);
            $sheet->fromArray($action, null, 'B2', false);
            $sheet->fromArray($type, null, 'C2', false);
            $sheet->fromArray($strategy, null, 'D2', false);
            $sheet->fromArray($periodicity, null, 'F2', false);
            $sheet->fromArray($periods, null, 'G2', false);

            // configurar las columnas de la hoja de datos como rangos
            $file->addNamedRange(new \PHPExcel_NamedRange('axis', $sheet, 'A2:A' . (count($axis) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('action', $sheet, 'B2:B' . (count($action) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('type', $sheet, 'C2:C' . (count($type) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('strategy', $sheet, 'D2:D' . (count($strategy) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('periodicity', $sheet, 'F2:F' . (count($periodicity) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('periods', $sheet, 'G2:G' . (count($periods) + 1) ));
            $file->addNamedRange(new \PHPExcel_NamedRange('indicatorsType', $sheet, 'E2:E3'));


            $sheet = $file->setActiveSheetIndex(0);

            // asignar los rangos en la hoja principal. Formula es el nombre del rango.
            $cells = [
                'A2' => ['range' => 'A2:A5000', 'formula' => 'periods'],
                'B2' => ['range' => 'B2:B5000', 'formula' => 'axis'],
                'C2' => ['range' => 'C2:C5000', 'formula' => 'action'],
                'F2' => ['range' => 'F2:F5000', 'formula' => 'type'],
                'G2' => ['range' => 'G2:G5000', 'formula' => 'strategy'],
                'H2' => ['range' => 'H2:H5000', 'formula' => 'strategy'],
                'I2' => ['range' => 'I2:I5000', 'formula' => 'strategy'],
                'J2' => ['range' => 'J2:J5000', 'formula' => 'strategy'],
                'K2' => ['range' => 'K2:K5000', 'formula' => 'indicatorsType'],
                'L2' => ['range' => 'L2:L5000', 'formula' => 'periodicity']
            ];

            ExportHelper::configSheetValidation($cells, $sheet);

        })->download('xlsx');
    }

}
