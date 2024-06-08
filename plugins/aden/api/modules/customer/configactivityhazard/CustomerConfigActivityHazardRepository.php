<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigActivityHazard;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;

use Wgroup\SystemParameter\SystemParameter;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use AdeN\Api\Helpers\ExportHelper;

class CustomerConfigActivityHazardRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerConfigActivityHazardModel());

        $this->service = new CustomerConfigActivityHazardService();
    }

    public static function getCustomFilters()
    {
        $workplaceTitle = $_ENV['instance'] == 'isa' ? 'Grupo Ocupacional O Instalación' : 'Centro De Trabajo';
        $macroprocessTitle = $_ENV['instance'] == 'isa' ? 'Subestación' : 'Macroproceso';
        $processTitle = $_ENV['instance'] == 'isa' ? 'Ubicación, Sitio O Área' : 'Proceso';
        $activityTitle = $_ENV['instance'] == 'isa' ? 'Labor/Tarea' : 'Actividad';
        $controlMethodSourceTitle = $_ENV['instance'] == 'isa' ? 'M. Control Fuente' : 'M. Control Fuente';
        $controlMethodMediumTitle = $_ENV['instance'] == 'isa' ? 'M. Control Medio' : 'M. Control Medio';
        $controlMethodPersonTitle = $_ENV['instance'] == 'isa' ? 'M. Control Persona' : 'M. Control Persona';

        return [
            ["alias" => $workplaceTitle, "name" => "workPlace"],
            ["alias" => $macroprocessTitle, "name" => "macroProcess"],
            ["alias" => $processTitle, "name" => "process"],
            ["alias" => "Cargo", "name" => "job"],
            ["alias" => $activityTitle, "name" => "activity"],
            ["alias" => "Rutinario", "name" => "isRoutine"],
            ["alias" => "Clasificación", "name" => "classification"],
            ["alias" => "Tipo Peligro", "name" => "type"],
            ["alias" => "Descripcion", "name" => "description"],
            ["alias" => "Posibles efectos salud", "name" => "effect"],
            ["alias" => "Tiempo exposicion", "name" => "timeExposure"],
            ["alias" => $controlMethodSourceTitle, "name" => "controlMethodSourceText"],
            ["alias" => $controlMethodMediumTitle, "name" => "controlMethodMediumText"],
            ["alias" => $controlMethodPersonTitle, "name" => "controlMethodPersonText"],
            ["alias" => "M. Control Administrativo", "name" => "controlMethodAdministrativeText"],
            ["alias" => "Nivel deficiencia ND", "name" => "measureND"],
            ["alias" => "Nivel exposicion NE", "name" => "measureNE"],
            ["alias" => "Nivel consecuencia", "name" => "measureNC"],
            ["alias" => "Nivel probabilidad", "name" => "levelP"],
            ["alias" => "Interpretación nivel probabilidad", "name" => "levelIP"],
            ["alias" => "Nivel riesgo", "name" => "levelR"],
            ["alias" => "Interpretación nivel riesgo", "name" => "levelIR"],
            ["alias" => "Valoración Riesgo", "name" => "riskValue"]
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity_hazard.id",
            "workPlace" => "wg_customer_config_workplace.name as workPlace",
            "macroProcess" => "wg_customer_config_macro_process.name as macroProcess",
            "process" => "wg_customer_config_process.name as process",
            "job" => "wg_customer_config_job_activity_hazard.job",

            "activity" => "wg_customer_config_job_activity_hazard.activity",
            "isRoutine" => DB::raw("CASE WHEN wg_customer_config_job_activity_hazard.isRoutine = 1 THEN 'SI' WHEN wg_customer_config_job_activity_hazard.isRoutine IS NULL THEN '' ELSE 'NO' END as isRoutine"),
            "classification" => "wg_config_job_activity_hazard_classification.name as classification",
            "type" => "wg_config_job_activity_hazard_type.name as type",
            "description" => "wg_config_job_activity_hazard_description.name as description",
            "effect" => "wg_config_job_activity_hazard_effect.name as effect",
            "timeExposure" => "wg_customer_config_job_activity_hazard.time_exposure AS timeExposure",
            "controlMethodSourceText" => "wg_customer_config_job_activity_hazard.control_method_source_text AS controlMethodSourceText",
            "controlMethodMediumText" => "wg_customer_config_job_activity_hazard.control_method_medium_text AS controlMethodMediumText",
            "controlMethodPersonText" => "wg_customer_config_job_activity_hazard.control_method_person_text AS controlMethodPersonText",
            "controlMethodAdministrativeText" => "wg_customer_config_job_activity_hazard.control_method_administrative_text AS controlMethodAdministrativeText",

            "measureND" => "measure_nd.value AS measureND",
            "measureNE" => "measure_ne.value AS measureNE",
            "measureNC" => "measure_nc.value AS measureNC",

            "levelP" => DB::raw("(measure_nd.value * measure_ne.value) as levelP"),
            "levelIP" => DB::raw("CASE
            WHEN (measure_nd.value * measure_ne.value) > 20 THEN 'Muy Alto'
            WHEN (measure_nd.value * measure_ne.value) >= 10 AND (measure_nd.value * measure_ne.value) <= 20 THEN 'Alto'
            WHEN (measure_nd.value * measure_ne.value) >= 6 AND (measure_nd.value * measure_ne.value) <= 8 THEN 'Medio'
            WHEN (measure_nd.value * measure_ne.value) >= 1 AND (measure_nd.value * measure_ne.value) <= 4 THEN'Bajo'
            END as levelIP"),

            "levelR" => DB::raw("((measure_nd.value * measure_ne.value) * measure_nc.value) as levelR"),
            "levelIR" => DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'I'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'II'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'III'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'IV'
            END as levelIR"),

            "riskValue" => DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
            END as riskValue"),

            "riskText" => DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable'
            END as riskText"),

            "exposed" => "wg_customer_config_job_activity_hazard.exposed",
            "contractors" => "wg_customer_config_job_activity_hazard.contractors",
            "visitors" => "wg_customer_config_job_activity_hazard.visitors",
            "status" => "wg_customer_config_job_activity_hazard.status",
            "reason" => "wg_customer_config_job_activity_hazard.reason",

            "customerId" => "wg_customer_config_workplace.customer_id",
            "activityId" => "wg_customer_config_job_activity_hazard.activityId",
            "workPlaceId" => "wg_customer_config_workplace.id AS workPlaceId",
            "jobActivityId" => "wg_customer_config_job_activity_hazard.jobActivityId",
            "hasHazards" => "wg_customer_config_job_activity_hazard.hasHazards"
        ]);

        $authUser = $this->getAuthUser();

        //var_dump($criteria);

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id', 'DESC');
        }

        $query = $this->query(DB::table('wg_customer_config_workplace'));

        $query->join("wg_customer_config_macro_process", function ($join) {
            $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
        })->join("wg_customer_config_process", function ($join) {
            $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
            $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
        })->join(DB::raw(CustomerConfigActivityHazardModel::getRelationTable('wg_customer_config_job_activity_hazard')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.workplace_id', '=', 'wg_customer_config_workplace.id');
            $join->on('wg_customer_config_job_activity_hazard.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            $join->on('wg_customer_config_job_activity_hazard.process_id', '=', 'wg_customer_config_process.id');
        })->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.classification', '=', 'wg_config_job_activity_hazard_classification.id');
        })->leftjoin("wg_config_job_activity_hazard_description", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.description', '=', 'wg_config_job_activity_hazard_description.id');
        })->leftjoin("wg_config_job_activity_hazard_type", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.type', '=', 'wg_config_job_activity_hazard_type.id');
        })->leftjoin("wg_config_job_activity_hazard_effect", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.health_effect', '=', 'wg_config_job_activity_hazard_effect.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nd', 'ND')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nd', '=', 'measure_nd.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_ne', 'NE')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_ne', '=', 'measure_ne.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nc', 'NC')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nc', '=', 'measure_nc.id');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.createdBy', '=', 'users.id');
        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                            Log::error($ex);
                        }
                    }
                });
            }
        }

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModel($data);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allIntervention($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity_hazard_intervention.id",
            "type" => "config_type_measure.item as type",
            "description" => "wg_customer_config_job_activity_hazard_intervention.description",
            "tracking" => "hazard_tracking.item as tracking",
            "observation" => "wg_customer_config_job_activity_hazard_intervention.observation",

            "jobActivityHazardId" => "wg_customer_config_job_activity_hazard_intervention.job_activity_hazard_id AS jobActivityHazardId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query(DB::table('wg_customer_config_job_activity_hazard_intervention'));

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('config_type_measure')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_intervention.type', '=', 'config_type_measure.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('hazard_tracking')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_intervention.tracking', '=', 'hazard_tracking.value');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_intervention.createdBy', '=', 'users.id');
        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModel($data);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allPriorization($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity_hazard_intervention.id",
            "workPlace" => "wg_customer_config_workplace.name as workPlace",
            "macroProcess" => "wg_customer_config_macro_process.name as macroProcess",
            "process" => "wg_customer_config_process.name as process",
            "job" => "wg_customer_config_job_activity_hazard.job",

            "activity" => "wg_customer_config_job_activity_hazard.activity",
            "isRoutine" => DB::raw("CASE WHEN wg_customer_config_job_activity_hazard.isRoutine = 1 THEN 'SI' WHEN wg_customer_config_job_activity_hazard.isRoutine IS NULL THEN '' ELSE 'NO' END as isRoutine"),
            "classification" => "wg_config_job_activity_hazard_classification.name as classification",
            "type" => "wg_config_job_activity_hazard_type.name as type",
            "description" => "wg_config_job_activity_hazard_description.name as description",
            "effect" => "wg_config_job_activity_hazard_effect.name as effect",
            "timeExposure" => "wg_customer_config_job_activity_hazard.time_exposure AS timeExposure",
            "controlMethodSourceText" => "wg_customer_config_job_activity_hazard.control_method_source_text AS controlMethodSourceText",
            "controlMethodMediumText" => "wg_customer_config_job_activity_hazard.control_method_medium_text AS controlMethodMediumText",
            "controlMethodPersonText" => "wg_customer_config_job_activity_hazard.control_method_person_text AS controlMethodPersonText",
            "controlMethodAdministrativeText" => "wg_customer_config_job_activity_hazard.control_method_administrative_text AS controlMethodAdministrativeText",

            "measureND" => "measure_nd.value AS measureND",
            "measureNE" => "measure_ne.value AS measureNE",
            "measureNC" => "measure_nc.value AS measureNC",

            "levelP" => DB::raw("(measure_nd.value * measure_ne.value) as levelP"),
            "levelIP" => DB::raw("CASE
            WHEN (measure_nd.value * measure_ne.value) > 20 THEN 'Muy Alto'
            WHEN (measure_nd.value * measure_ne.value) >= 10 AND (measure_nd.value * measure_ne.value) <= 20 THEN 'Alto'
            WHEN (measure_nd.value * measure_ne.value) >= 6 AND (measure_nd.value * measure_ne.value) <= 8 THEN 'Medio'
            WHEN (measure_nd.value * measure_ne.value) >= 1 AND (measure_nd.value * measure_ne.value) <= 4 THEN'Bajo'
            END as levelIP"),

            "levelR" => DB::raw("((measure_nd.value * measure_ne.value) * measure_nc.value) as levelR"),
            "levelIR" => DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'I'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'II'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'III'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'IV'
            END as levelIR"),

            "riskValue" => DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
            END as riskValue"),

            "riskText" => DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable'
            END as riskText"),

            "interventionType" => "config_type_measure.item as interventionType",
            "interventionDescription" => "wg_customer_config_job_activity_hazard_intervention.description as interventionDescription",
            "interventionTracking" => "hazard_tracking.item as interventionTracking",
            "interventionObservation" => "wg_customer_config_job_activity_hazard_intervention.observation as interventionObservation",

            "exposed" => "wg_customer_config_job_activity_hazard.exposed",
            "contractors" => "wg_customer_config_job_activity_hazard.contractors",
            "visitors" => "wg_customer_config_job_activity_hazard.visitors",
            "status" => "wg_customer_config_job_activity_hazard.status",
            "reason" => "wg_customer_config_job_activity_hazard.reason",

            "customerId" => "wg_customer_config_workplace.customer_id",
            "activityId" => "wg_customer_config_job_activity_hazard.activityId",
            "activityHazardId" => "wg_customer_config_job_activity_hazard.id AS activityHazardId",
            "workPlaceId" => "wg_customer_config_workplace.id AS workPlaceId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('activityHazardId');
        }

        $query = $this->query(DB::table('wg_customer_config_workplace'));

        $query->join("wg_customer_config_macro_process", function ($join) {
            $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
        })->join("wg_customer_config_process", function ($join) {
            $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
            $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
        })->join(DB::raw(CustomerConfigActivityHazardModel::getRelationTable('wg_customer_config_job_activity_hazard')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.workplace_id', '=', 'wg_customer_config_workplace.id');
            $join->on('wg_customer_config_job_activity_hazard.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            $join->on('wg_customer_config_job_activity_hazard.process_id', '=', 'wg_customer_config_process.id');
            $join->where('wg_customer_config_job_activity_hazard.id', '>', 0);
        })->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.classification', '=', 'wg_config_job_activity_hazard_classification.id');
        })->leftjoin("wg_config_job_activity_hazard_description", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.description', '=', 'wg_config_job_activity_hazard_description.id');
        })->leftjoin("wg_config_job_activity_hazard_type", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.type', '=', 'wg_config_job_activity_hazard_type.id');
        })->leftjoin("wg_config_job_activity_hazard_effect", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.health_effect', '=', 'wg_config_job_activity_hazard_effect.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nd', 'ND')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nd', '=', 'measure_nd.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_ne', 'NE')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_ne', '=', 'measure_ne.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nc', 'NC')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nc', '=', 'measure_nc.id');
        })->leftjoin("wg_customer_config_job_activity_hazard_intervention", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_intervention.job_activity_hazard_id', '=', 'wg_customer_config_job_activity_hazard.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('config_type_measure')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_intervention.type', '=', 'config_type_measure.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('hazard_tracking')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_intervention.tracking', '=', 'hazard_tracking.value');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.createdBy', '=', 'users.id');
        });

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModel($data);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function allHistorical($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_job_activity_hazard_tracking.id",
            "type" => "wg_customer_config_job_activity_hazard_tracking.type",
            "source" => "wg_customer_config_job_activity_hazard_tracking.source",
            "createdBy" => "users.name",
            "createdAt" => "wg_customer_config_job_activity_hazard_tracking.created_at as createdAt",

            "jobActivityHazardId" => "wg_customer_config_job_activity_hazard_tracking.job_activity_hazard_id AS jobActivityHazardId",
            "description" => "wg_customer_config_job_activity_hazard_tracking.description",
            "oldValue" => "wg_customer_config_job_activity_hazard_tracking.old_value AS oldValue",
            "newValue" => "wg_customer_config_job_activity_hazard_tracking.new_value AS newValue",
            "reason" => "customer_config_activity_hazard_reason.item as reason",
            "reasonObservation" => "wg_customer_config_job_activity_hazard_tracking.reason_observation AS reasonObservation",
        ]);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('createdAt', 'DESC');
        }

        $this->parseCriteria($criteria);

        $query = $this->query(DB::table('wg_customer_config_job_activity_hazard_tracking'));

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_config_activity_hazard_reason')), function ($join) {
            $join->on('customer_config_activity_hazard_reason.value', '=', 'wg_customer_config_job_activity_hazard_tracking.reason');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_tracking.created_by', '=', 'users.id');
        });

        $this->applyCriteria($query, $criteria);

        $result = $this->get($query, $criteria);

        $result["data"] = array_map(function ($item) {
            $item->oldValue = json_decode($item->oldValue);
            $item->newValue = json_decode($item->newValue);
            return $item;
        }, $result["data"]);

        return $result;
    }

    public function allHistoricalReason($criteria)
    {
        $this->setColumns([
            "createdAt" => "wg_customer_config_job_activity_hazard_tracking.created_at as createdAt",
            "createdBy" => "users.name",
            "reason" => "customer_config_activity_hazard_reason.item as reason",
            "reasonObservation" => "wg_customer_config_job_activity_hazard_tracking.reason_observation AS reasonObservation",

            "jobActivityHazardId" => "wg_customer_config_job_activity_hazard_tracking.job_activity_hazard_id AS jobActivityHazardId",
        ]);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('createdAt', 'DESC');
        }

        $this->parseCriteria($criteria);

        $query = $this->query(DB::table('wg_customer_config_job_activity_hazard_tracking'));

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_config_activity_hazard_reason')), function ($join) {
            $join->on('customer_config_activity_hazard_reason.value', '=', 'wg_customer_config_job_activity_hazard_tracking.reason');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard_tracking.created_by', '=', 'users.id');
        })
            ->whereNotNull('wg_customer_config_job_activity_hazard_tracking.reason');

        $this->applyCriteria($query, $criteria);

        $result = $this->get($query, $criteria);

        $result["data"] = array_map(function ($item) {
            $item->createdAt = $item->createdAt ? $item->createdAt->format('d/m/Y H:i') : null;
            return $item;
        }, $result["data"]);

        return $result;
    }

    public function allCharacterization($criteria)
    {
        $this->setColumns([
            "id" => "characterization.classificationId AS id",
            "classification" => "characterization.classification",
            "total" => DB::raw("COUNT(*) AS total"),
            "noAcceptableControl" => DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable o Aceptable con control especifico' THEN 1 ELSE 0 END) AS noAcceptableControl"),
            "noAcceptable" => DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable' THEN 1 ELSE 0 END) AS noAcceptable"),
            "improvable" => DB::raw("SUM(CASE WHEN riskValue = 'Mejorable' THEN 1 ELSE 0 END) AS improvable"),
            "acceptable" => DB::raw("SUM(CASE WHEN riskValue = 'Aceptable' THEN 1 ELSE 0 END) AS acceptable"),
            "customerId" => "characterization.customerId",
            "workplaceId" => "characterization.workplaceId",
        ]);

        $this->parseCriteria($criteria);

        $qSub = DB::table('wg_customer_config_workplace');

        $qSub->join("wg_customer_config_macro_process", function ($join) {
            $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
        })->join("wg_customer_config_process", function ($join) {
            $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
            $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
        })->join(DB::raw(CustomerConfigActivityHazardModel::getRelationTable('wg_customer_config_job_activity_hazard')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.workplace_id', '=', 'wg_customer_config_workplace.id');
            $join->on('wg_customer_config_job_activity_hazard.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            $join->on('wg_customer_config_job_activity_hazard.process_id', '=', 'wg_customer_config_process.id');
        })->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.classification', '=', 'wg_config_job_activity_hazard_classification.id');
        })->leftjoin("wg_config_job_activity_hazard_type", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.type', '=', 'wg_config_job_activity_hazard_type.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nd', 'ND')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nd', '=', 'measure_nd.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_ne', 'NE')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_ne', '=', 'measure_ne.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nc', 'NC')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nc', '=', 'measure_nc.id');
        })->select(
            "wg_customer_config_workplace.customer_id AS customerId",
            "wg_customer_config_workplace.id AS workplaceId",
            "wg_config_job_activity_hazard_classification.id as classificationId",
            "wg_config_job_activity_hazard_classification.name as classification",
            "wg_config_job_activity_hazard_type.id as type_id",
            "wg_config_job_activity_hazard_type.name as type",
            DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
            END as riskValue")
        )->whereNotNull('wg_customer_config_job_activity_hazard.id');

        $query = $this->query(DB::table(DB::raw("({$qSub->toSql()}) as characterization")))
            ->mergeBindings($qSub)
            ->groupBy('characterization.customerId', 'characterization.classificationId')
            ->orderBy('characterization.classificationId', 'ASC');

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allCharacterizationDetail($criteria)
    {
        $this->setColumns([
            "id" => "characterization.typeId AS id",
            "type" => "characterization.type",
            "total" => DB::raw("COUNT(*) AS total"),
            "noAcceptableControl" => DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable o Aceptable con control especifico' THEN 1 ELSE 0 END) AS noAcceptableControl"),
            "noAcceptable" => DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable' THEN 1 ELSE 0 END) AS noAcceptable"),
            "improvable" => DB::raw("SUM(CASE WHEN riskValue = 'Mejorable' THEN 1 ELSE 0 END) AS improvable"),
            "acceptable" => DB::raw("SUM(CASE WHEN riskValue = 'Aceptable' THEN 1 ELSE 0 END) AS acceptable"),
            "customerId" => "characterization.customerId",
            "workplaceId" => "characterization.workplaceId",
            "classificationId" => "characterization.classificationId",
        ]);

        $this->parseCriteria($criteria);

        $qSub = DB::table('wg_customer_config_workplace');

        $qSub->join("wg_customer_config_macro_process", function ($join) {
            $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
        })->join("wg_customer_config_process", function ($join) {
            $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
            $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
        })->join(DB::raw(CustomerConfigActivityHazardModel::getRelationTable('wg_customer_config_job_activity_hazard')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.workplace_id', '=', 'wg_customer_config_workplace.id');
            $join->on('wg_customer_config_job_activity_hazard.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            $join->on('wg_customer_config_job_activity_hazard.process_id', '=', 'wg_customer_config_process.id');
        })->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.classification', '=', 'wg_config_job_activity_hazard_classification.id');
        })->leftjoin("wg_config_job_activity_hazard_type", function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.type', '=', 'wg_config_job_activity_hazard_type.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nd', 'ND')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nd', '=', 'measure_nd.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_ne', 'NE')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_ne', '=', 'measure_ne.id');
        })->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nc', 'NC')), function ($join) {
            $join->on('wg_customer_config_job_activity_hazard.measure_nc', '=', 'measure_nc.id');
        })->select(
            "wg_customer_config_workplace.customer_id AS customerId",
            "wg_customer_config_workplace.id AS workplaceId",
            "wg_config_job_activity_hazard_classification.id as classificationId",
            "wg_config_job_activity_hazard_classification.name as classification",
            "wg_config_job_activity_hazard_type.id as typeId",
            "wg_config_job_activity_hazard_type.name as type",
            DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
            END as riskValue")
        )->whereNotNull('wg_customer_config_job_activity_hazard.id');

        $query = $this->query(DB::table(DB::raw("({$qSub->toSql()}) as characterization")))
            ->mergeBindings($qSub)
            ->groupBy('characterization.typeId')
            ->orderBy('characterization.typeId', 'ASC');

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->job = $model->getJobData();
            $entity->jobId = $model->jobId;

            return $entity;
        } else {
            return null;
        }
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'Resumen_Matriz_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Matriz', $data);
    }

    public function exportPriorizationExcel($criteria)
    {
        $data = $this->service->getExportPriorizationData($criteria);
        $filename = 'Resumen_Matriz_Priorización_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Priorización', $data);
    }

    public function exportHistoricalExcel($criteria)
    {
        $data = $this->service->getExportHistoricalData($criteria);
        $filename = 'Resumen_Matriz_Historial_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Historial', $data);
    }

    public function exportCharacterizationExcel($criteria)
    {
        $data = $this->service->getExportCharacterizatioData($criteria);
        $filename = 'Resumen_Matriz_Caracterización_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Caracterización', $data);
    }

    public function getWorkplaceList($criteria)
    {
        return $this->service->getWorkplaceList($criteria);
    }

    public function getProcessList($criteria)
    {
        return $this->service->getProcessList($criteria);
    }

    public function getClassificationList($criteria)
    {
        return $this->service->getClassificationList($criteria);
    }

    public function getChartBarClassification($criteria)
    {
        return $this->service->getChartBarClassification($criteria);
    }

    public function getChartPieAcceptability($criteria)
    {
        return $this->service->getChartPieAcceptability($criteria);
    }

    public function getChartBarAcceptabilityClassification($criteria)
    {
        return $this->service->getChartBarAcceptabilityClassification($criteria);
    }

    public function getChartBarAcceptabilityType($criteria)
    {
        return $this->service->getChartBarAcceptabilityType($criteria);
    }

    public function getChartIntervention($criteria)
    {
        return $this->service->getChartIntervention($criteria);
    }

    public function getChartPieImprovementPlan($criteria)
    {
        return $this->service->getChartPieImprovementPlan($criteria);
    }

    public function getChartBarAcceptability($criteria)
    {
        return $this->service->getChartBarAcceptability($criteria);
    }

    public function getAmountRecords($criteria)
    {
        return $this->service->getAmountRecords($criteria);
    }

}
