<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Jobcondition;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeModel;
use AdeN\Api\Modules\Customer\JobConditions\Indicator\IndicatorService;use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionWorkplaceModel;
use DB;
use Maatwebsite\Excel\Facades\Excel;
use Wgroup\SystemParameter\SystemParameter;

class JobConditionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new JobConditionModel());
        $this->service = new JobConditionService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Tipo Identificación", "name" => "documentType"],
            ["alias" => "Número Identificación", "name" => "documentNumber"],
            ["alias" => "Nombre", "name" => "name"],
            ["alias" => "Cargo", "name" => "occupation"],
            ["alias" => "Fecha", "name" => "date"],
            ["alias" => "Estado", "name" => "state"],
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "customerId" => "ce.customer_id as customerId",
            "documentType" => "documentType.item as documentType",
            "documentNumber" => "documentNumber",
            "name" => DB::raw('CONCAT(e.firstName, " ", e.lastName) as name'),
            "date" => DB::raw("DATE_FORMAT(eval.registration_date, '%Y-%m-%d') as date"),
            "state" => DB::raw("CASE WHEN eval.state IS NULL THEN 'INICIAL' ELSE eval.state END AS state"),
            "id" => "wg_customer_job_condition.id as id",
        ]);

        $this->parseCriteria($criteria);

        $subQueryEvaluations = DB::table('wg_customer_job_condition_self_evaluation')
            ->groupBy('job_condition_id')
            ->select('job_condition_id',
                DB::raw("CASE WHEN id IS NULL THEN 'INICIAL'
                              WHEN count(if(state = 1, 1, null)) THEN 'EN PROCESO'
                              ELSE 'COMPLETADA'
                          END AS state"),
                DB::raw('max(registration_date) AS registration_date')
            );

        $query = $this->query()
            ->join("wg_customer_employee as ce", 'wg_customer_job_condition.customer_employee_id', '=', 'ce.id')
            ->join("wg_employee as e", 'e.id', '=', 'ce.employee_id')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type', 'documentType')), function ($join) {
                $join->on('e.documentType', '=', 'documentType.value');
            })
            ->leftjoin(DB::raw("({$subQueryEvaluations->toSql()}) as eval"), 'eval.job_condition_id', '=', 'wg_customer_job_condition.id')
            ->groupBy('wg_customer_job_condition.id');

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
        }

        $customerEmployee = CustomerEmployeeModel::whereCustomerId($entity->customerId)
            ->whereEmployeeId($entity->employee->id)
            ->select('id')
            ->first();

        $customerEmployeeBoss = CustomerEmployeeModel::whereCustomerId($entity->customerId)
            ->whereEmployeeId($entity->boss->id)
            ->select('id')
            ->first();

        $exists = $this->model
            ->where('customer_employee_id', $customerEmployee->id)
            ->where('id', '<>', $entity->id)
            ->exists();

        if ($exists) {
            throw new \Exception('El empleado indicado ya se encuentra registrado.');
        }

        $entityModel->customer_id = $entity->customerId;
        $entityModel->customer_employee_id = $customerEmployee->id;
        $entityModel->immediate_boss_id = $customerEmployeeBoss->id;
        $entityModel->save();

        $entity->id = $entityModel->id;
        return $entity;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->employee = $model->getEmployee();
            $entity->boss = $model->getBoss();
            $entity->customerId = $model->customer_id;
            return $entity;

        } else {
            return null;
        }
    }

    // Export template excel
    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/plantilla_condiciones_puestos_de_trabajo.xlsx";
        $job = array_map(function ($row) {
            return [
                'NOMBRE' => $row->name,
            ];
        }, $this->service->getJobList($customerId));

        $identificationType = $this->service->getIdentificationType();
        $jobWorkModel = $this->service->getWorkModel();
        $jobWorkLocation = $this->service->getWorkLocation();

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($identificationType, $jobWorkModel, $jobWorkLocation, $job) {

            $sheet = $file->setActiveSheetIndex(1);
            $sheet->fromArray($identificationType, null, 'A2', false);
            $sheet->fromArray($jobWorkModel, null, 'B2', false);
            $sheet->fromArray($jobWorkLocation, null, 'C2', false);
            $sheet->fromArray($job, null, 'D2', false);

            // configurar las columnas de la hoja de datos como rangos
            $file->addNamedRange(new \PHPExcel_NamedRange('identificationType', $sheet, 'A2:A' . (count($identificationType) + 1)));
            $file->addNamedRange(new \PHPExcel_NamedRange('jobWorkModel', $sheet, 'B2:B' . (count($jobWorkModel) + 1)));
            $file->addNamedRange(new \PHPExcel_NamedRange('jobWorkLocation', $sheet, 'C2:C' . (count($jobWorkLocation) + 1)));
            $file->addNamedRange(new \PHPExcel_NamedRange('job', $sheet, 'D2:D' . (count($job) + 1)));

            $sheet = $file->setActiveSheetIndex(0);

            // asignar los rangos en la hoja principal. Formula es el nombre del rango.
            $cells = [
                'A2' => ['range' => 'A2:A5000', 'formula' => 'identificationType'],
                'D2' => ['range' => 'D2:D5000', 'formula' => 'jobWorkModel'],
                'E2' => ['range' => 'E2:E5000', 'formula' => 'jobWorkLocation'],
                'F2' => ['range' => 'F2:F5000', 'formula' => 'job'],
            ];

            ExportHelper::configSheetValidation($cells, $sheet);

        })->download('xlsx');
    }

    public function config($config)
    {
        $result = [];

        foreach ($config as $criteria) {

            switch ($criteria->name) {
                case "occupations":
                    $result['occupations'] = $this->service->getJobList($criteria->customerId);
                    break;

                case "occupationEmployee":
                    $jobConditionId = $criteria->jobConditionId;
                    $result["occupationEmployee"] = $this->service->getOccupationByEmployee($jobConditionId);
                    break;

                case "job_condition_by_current_user":
                    $authUser = $this->getAuthUser();
                    $result['jobConditionByCurrentUser'] = $this->service->getJobConditionByCurrentUser($authUser->id);
                    break;

                case "classifications":
                    $evaluationId = $criteria->evaluationId;
                    $result['classifications'] = $this->service->getClassifications($evaluationId);
                    break;

                case "workplaces":
                    $result['workplaces'] = JobConditionWorkplaceModel::all();
                    break;

                case "periods":
                    $jobConditionId = $criteria->jobConditionId;
                    $result['periods'] = $this->service->getPeriods($jobConditionId);
                    break;

                // General indicators
                case "customer_job_conditions_intervention_list":
                    $result["jobConditionsInterventionGetStast"] = IndicatorService::getTotalRiskLevel($criteria->criteria);
                    break;

                case "general_indicators_get_years":
                    $customerId = $criteria->customerId;
                    $result['yearsGeneralIndicators'] = IndicatorService::getYearToGeneralIndicator($customerId);
                    break;

                case "general_indicators_get_locations":
                    $customerId = $criteria->customerId;
                    $year = $criteria->year;
                    $result['locationsGeneralIndicators'] = IndicatorService::getLocationToGeneralIndicator($customerId, $year);
                    break;

                default:
                    break;
            }
        }

        return $result;
    }

}
