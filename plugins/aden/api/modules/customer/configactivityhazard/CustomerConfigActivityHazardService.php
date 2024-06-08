<?php

namespace AdeN\Api\Modules\Customer\ConfigActivityHazard;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\ExportHelper;
use DB;
use Wgroup\SystemParameter\SystemParameter;

class CustomerConfigActivityHazardService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getExportData($criteria)
    {
        $query = DB::table('wg_customer_config_workplace');

        $query
            ->join("wg_customer_config_macro_process", function ($join) {
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->join("wg_customer_config_process", function ($join) {
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            })
            ->join(DB::raw(CustomerConfigActivityHazardModel::getRelationTable('wg_customer_config_job_activity_hazard')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_job_activity_hazard.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_job_activity_hazard.process_id', '=', 'wg_customer_config_process.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.classification', '=', 'wg_config_job_activity_hazard_classification.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_description", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.description', '=', 'wg_config_job_activity_hazard_description.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_type", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.type', '=', 'wg_config_job_activity_hazard_type.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_effect", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.health_effect', '=', 'wg_config_job_activity_hazard_effect.id');
            })
            ->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nd', 'ND')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.measure_nd', '=', 'measure_nd.id');
            })
            ->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_ne', 'NE')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.measure_ne', '=', 'measure_ne.id');
            })
            ->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nc', 'NC')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.measure_nc', '=', 'measure_nc.id');
            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.createdBy', '=', 'users.id');
            })
            ->select(
                "wg_customer_config_job_activity_hazard.id",
                "wg_customer_config_workplace.name as workPlace",
                "wg_customer_config_macro_process.name as macroProcess",
                "wg_customer_config_process.name as process",
                "wg_customer_config_job_activity_hazard.job",

                "wg_customer_config_job_activity_hazard.activity",
                DB::raw("CASE WHEN wg_customer_config_job_activity_hazard.isRoutine = 1 THEN 'SI' WHEN wg_customer_config_job_activity_hazard.isRoutine IS NULL THEN '' ELSE 'NO' END as isRoutine"),
                "wg_config_job_activity_hazard_classification.name as classification",
                "wg_config_job_activity_hazard_type.name as type",
                "wg_config_job_activity_hazard_description.name as description",
                "wg_config_job_activity_hazard_effect.name as effect",
                "wg_customer_config_job_activity_hazard.time_exposure AS timeExposure",
                "wg_customer_config_job_activity_hazard.control_method_source_text AS controlMethodSourceText",
                "wg_customer_config_job_activity_hazard.control_method_medium_text AS controlMethodMediumText",
                "wg_customer_config_job_activity_hazard.control_method_person_text AS controlMethodPersonText",
                "wg_customer_config_job_activity_hazard.control_method_administrative_text AS controlMethodAdministrativeText",

                "measure_nd.value AS measureND",
                "measure_ne.value AS measureNE",
                "measure_nc.value AS measureNC",

                DB::raw("(measure_nd.value * measure_ne.value) as levelP"),
                DB::raw("CASE
                WHEN (measure_nd.value * measure_ne.value) > 20 THEN 'Muy Alto'
                WHEN (measure_nd.value * measure_ne.value) >= 10 AND (measure_nd.value * measure_ne.value) <= 20 THEN 'Alto'
                WHEN (measure_nd.value * measure_ne.value) >= 6 AND (measure_nd.value * measure_ne.value) <= 8 THEN 'Medio'
                WHEN (measure_nd.value * measure_ne.value) >= 1 AND (measure_nd.value * measure_ne.value) <= 4 THEN'Bajo'
                END as levelIP"),

                DB::raw("((measure_nd.value * measure_ne.value) * measure_nc.value) as levelR"),
                DB::raw("CASE
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'I'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'II'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'III'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'IV'
                END as levelIR"),

                DB::raw("CASE
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
                END as riskValue"),

                DB::raw("CASE
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable'
                END as riskText"),

                "wg_customer_config_job_activity_hazard.exposed",
                "wg_customer_config_job_activity_hazard.contractors",
                "wg_customer_config_job_activity_hazard.visitors",
                "wg_customer_config_job_activity_hazard.status",
                "wg_customer_config_job_activity_hazard.reason",

                "wg_customer_config_workplace.customer_id AS customerId",
                "wg_customer_config_job_activity_hazard.activityId",
                "wg_customer_config_workplace.id AS workPlaceId",
                "wg_customer_config_job_activity_hazard.jobActivityId",
                "wg_customer_config_job_activity_hazard.hasHazards"
            );

        $query = $this->prepareQuery($query->toSql());

        $this->applyWhere($query, $criteria);

        $result = $query->get();

        $workplaceTitle = $_ENV['instance'] == 'isa' ? 'GRUPO OCUPACIONAL O INSTALACIÓN' : 'CENTRO DE TRABAJO';
        $macroprocessTitle = $_ENV['instance'] == 'isa' ? 'SUBESTACIÓN' : 'MACROPROCESO';
        $processTitle = $_ENV['instance'] == 'isa' ? 'UBICACIÓN, SITIO O ÁREA' : 'PROCESO';
        $activityTitle = $_ENV['instance'] == 'isa' ? 'LABOR/TAREA' : 'ACTIVIDAD';
        $controlMethodSourceTitle = $_ENV['instance'] == 'isa' ? 'M. Control Fuente' : 'M. Control Fuente';
        $controlMethodMediumTitle = $_ENV['instance'] == 'isa' ? 'M. Control Medio' : 'M. Control Medio';
        $controlMethodPersonTitle = $_ENV['instance'] == 'isa' ? 'M. Control Persona' : 'M. Control Persona';

        $heading = [
            $workplaceTitle => "workPlace",
            $macroprocessTitle => "macroProcess",
            $processTitle => "process",
            "CARGO" => "job",
            $activityTitle => "activity",
            "RUTINARIA" => "isRoutine",
            "CLASIFICACIÓN" => "classification",
            "TIPO PELIGRO" => "type",
            "DESCRIPCIÓN PELIGRO" => "description",
            "EFECTOS A LA SALUD" => "effect",
            "T. EXPUESTO" => "timeExposure",
            $controlMethodSourceTitle => "controlMethodSourceText",
            $controlMethodMediumTitle => "controlMethodMediumText",
            $controlMethodPersonTitle => "controlMethodPersonText",
            "N. DEFICIENCIA" => "measureND",
            "N. EXPOSICIÓN" => "measureNE",
            "N. CONSECUENCIA" => "measureNC",
            "N. PROBABILIDAD" => "levelP",
            "INTERP N. PROBABILIDAD" => "levelIP",
            "NIVEL RIESGO" => "levelR",
            "INTERP RIESGO" => "levelIR",
            "VALORACIÓN RIESGO" => "riskValue",
            "TRABAJADORES VINCULADOS O EN MISIÓN" => "exposed",
            "TRABAJADORES CONTRATISTAS" => "contractors",
            "VISITANTES" => "visitors",
            "VERIFICADO" => "status",
            "MOTIVO" => "reason",
            //"MEDIDA DE INTERVENCIÓN" => "intervention_type",
            //"DESCRIPCIÓN MEDIDA DE INTERVENCIÓN" => "intervention_description",
            //"SEGUIMIENTO Y MEDICIÓN" => "intervention_tracking",
            //"OBSERVACIÓN" => "intervention_observation",
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportPriorizationData($criteria)
    {
        $query = DB::table('wg_customer_config_workplace');

        $query
            ->join("wg_customer_config_macro_process", function ($join) {
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->join("wg_customer_config_process", function ($join) {
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            })
            ->join(DB::raw(CustomerConfigActivityHazardModel::getRelationTable('wg_customer_config_job_activity_hazard', true)), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_job_activity_hazard.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_job_activity_hazard.process_id', '=', 'wg_customer_config_process.id');
                //$join->where('wg_customer_config_job_activity_hazard.id', '>', 0);

            })
            ->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.classification', '=', 'wg_config_job_activity_hazard_classification.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_description", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.description', '=', 'wg_config_job_activity_hazard_description.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_type", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.type', '=', 'wg_config_job_activity_hazard_type.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_effect", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.health_effect', '=', 'wg_config_job_activity_hazard_effect.id');
            })
            ->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nd', 'ND')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.measure_nd', '=', 'measure_nd.id');
            })
            ->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_ne', 'NE')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.measure_ne', '=', 'measure_ne.id');
            })
            ->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nc', 'NC')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.measure_nc', '=', 'measure_nc.id');
            })
            ->leftjoin("wg_customer_config_job_activity_hazard_intervention", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard_intervention.job_activity_hazard_id', '=', 'wg_customer_config_job_activity_hazard.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('config_type_measure')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard_intervention.type', '=', 'config_type_measure.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('hazard_tracking')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard_intervention.tracking', '=', 'hazard_tracking.value');
            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.createdBy', '=', 'users.id');
            })
            ->select(
                "wg_customer_config_job_activity_hazard.id",
                "wg_customer_config_workplace.name as workPlace",
                "wg_customer_config_macro_process.name as macroProcess",
                "wg_customer_config_process.name as process",
                "wg_customer_config_job_activity_hazard.job",

                "wg_customer_config_job_activity_hazard.activity",
                DB::raw("CASE WHEN wg_customer_config_job_activity_hazard.isRoutine = 1 THEN 'SI' WHEN wg_customer_config_job_activity_hazard.isRoutine IS NULL THEN '' ELSE 'NO' END as isRoutine"),
                "wg_config_job_activity_hazard_classification.name as classification",
                "wg_config_job_activity_hazard_type.name as type",
                "wg_config_job_activity_hazard_description.name as description",
                "wg_config_job_activity_hazard_effect.name as effect",
                "wg_customer_config_job_activity_hazard.time_exposure AS timeExposure",
                "wg_customer_config_job_activity_hazard.control_method_source_text AS controlMethodSourceText",
                "wg_customer_config_job_activity_hazard.control_method_medium_text AS controlMethodMediumText",
                "wg_customer_config_job_activity_hazard.control_method_person_text AS controlMethodPersonText",
                "wg_customer_config_job_activity_hazard.control_method_administrative_text AS controlMethodAdministrativeText",

                "measure_nd.value AS measureND",
                "measure_ne.value AS measureNE",
                "measure_nc.value AS measureNC",

                DB::raw("(measure_nd.value * measure_ne.value) as levelP"),
                DB::raw("CASE
                WHEN (measure_nd.value * measure_ne.value) > 20 THEN 'Muy Alto'
                WHEN (measure_nd.value * measure_ne.value) >= 10 AND (measure_nd.value * measure_ne.value) <= 20 THEN 'Alto'
                WHEN (measure_nd.value * measure_ne.value) >= 6 AND (measure_nd.value * measure_ne.value) <= 8 THEN 'Medio'
                WHEN (measure_nd.value * measure_ne.value) >= 1 AND (measure_nd.value * measure_ne.value) <= 4 THEN'Bajo'
                END as levelIP"),

                DB::raw("((measure_nd.value * measure_ne.value) * measure_nc.value) as levelR"),
                DB::raw("CASE
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'I'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'II'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'III'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'IV'
                END as levelIR"),

                DB::raw("CASE
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
                END as riskValue"),

                DB::raw("CASE
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable'
                END as riskText"),

                "wg_customer_config_job_activity_hazard.exposed",
                "wg_customer_config_job_activity_hazard.contractors",
                "wg_customer_config_job_activity_hazard.visitors",
                "wg_customer_config_job_activity_hazard.status",
                "wg_customer_config_job_activity_hazard.reason",

                "config_type_measure.item as interventionType",
                "wg_customer_config_job_activity_hazard_intervention.description as interventionDescription",
                "hazard_tracking.item as interventionTracking",
                "wg_customer_config_job_activity_hazard_intervention.observation as interventionObservation",

                "wg_customer_config_workplace.customer_id AS customerId",
                "wg_customer_config_job_activity_hazard.activityId",
                "wg_customer_config_job_activity_hazard.id AS activityHazardId",
                "wg_customer_config_workplace.id AS workPlaceId"
            );

        $query = $this->prepareQuery($query->toSql());

        $this->applyWhere($query, $criteria);

        $result = $query->get();

        $workplaceTitle = $_ENV['instance'] == 'isa' ? 'GRUPO OCUPACIONAL O INSTALACIÓN' : 'CENTRO DE TRABAJO';
        $macroprocessTitle = $_ENV['instance'] == 'isa' ? 'SUBESTACIÓN' : 'MACROPROCESO';
        $processTitle = $_ENV['instance'] == 'isa' ? 'UBICACIÓN, SITIO O ÁREA' : 'PROCESO';
        $activityTitle = $_ENV['instance'] == 'isa' ? 'LABOR/TAREA' : 'ACTIVIDAD';
        $controlMethodSourceTitle = $_ENV['instance'] == 'isa' ? 'M. Control Fuente' : 'M. Control Fuente';
        $controlMethodMediumTitle = $_ENV['instance'] == 'isa' ? 'M. Control Medio' : 'M. Control Medio';
        $controlMethodPersonTitle = $_ENV['instance'] == 'isa' ? 'M. Control Persona' : 'M. Control Persona';

        $heading = [
            $workplaceTitle => "workPlace",
            $macroprocessTitle => "macroProcess",
            $processTitle => "process",
            "CARGO" => "job",
            $activityTitle => "activity",
            "RUTINARIA" => "isRoutine",
            "CLASIFICACIÓN" => "classification",
            "TIPO PELIGRO" => "type",
            "DESCRIPCIÓN PELIGRO" => "description",
            "EFECTOS A LA SALUD" => "effect",
            "T. EXPUESTO" => "timeExposure",
            $controlMethodSourceTitle => "controlMethodSourceText",
            $controlMethodMediumTitle => "controlMethodMediumText",
            $controlMethodPersonTitle => "controlMethodPersonText",
            "N. DEFICIENCIA" => "measureND",
            "N. EXPOSICIÓN" => "measureNE",
            "N. CONSECUENCIA" => "measureNC",
            "N. PROBABILIDAD" => "levelP",
            "INTERP N. PROBABILIDAD" => "levelIP",
            "NIVEL RIESGO" => "levelR",
            "INTERP RIESGO" => "levelIR",
            "VALORACIÓN RIESGO" => "riskValue",
            "TRABAJADORES VINCULADOS O EN MISIÓN" => "exposed",
            "TRABAJADORES CONTRATISTAS" => "contractors",
            "VISITANTES" => "visitors",
            "VERIFICADO" => "status",
            "MOTIVO" => "reason",
            "MEDIDA DE INTERVENCIÓN" => "interventionType",
            "DESCRIPCIÓN MEDIDA DE INTERVENCIÓN" => "interventionDescription",
            "SEGUIMIENTO Y MEDICIÓN" => "interventionTracking",
            "OBSERVACIÓN" => "interventionObservation",
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportHistoricalData($criteria)
    {
        $query = DB::table('wg_customer_config_workplace');

        $query
            ->join("wg_customer_config_macro_process", function ($join) {
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->join("wg_customer_config_process", function ($join) {
                $join->on('wg_customer_config_process.workplace_id', '=', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', '=', 'wg_customer_config_macro_process.id');
            })
            ->join(DB::raw(CustomerConfigActivityHazardModel::getRelationTable('wg_customer_config_job_activity_hazard')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.workplace_id', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_job_activity_hazard.macro_process_id', '=', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_job_activity_hazard.process_id', '=', 'wg_customer_config_process.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.classification', '=', 'wg_config_job_activity_hazard_classification.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_description", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.description', '=', 'wg_config_job_activity_hazard_description.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_type", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.type', '=', 'wg_config_job_activity_hazard_type.id');
            })
            ->leftjoin("wg_config_job_activity_hazard_effect", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.health_effect', '=', 'wg_config_job_activity_hazard_effect.id');
            })
            ->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nd', 'ND')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.measure_nd', '=', 'measure_nd.id');
            })
            ->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_ne', 'NE')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.measure_ne', '=', 'measure_ne.id');
            })
            ->leftjoin(DB::raw(CustomerConfigActivityHazardModel::getConfigGeneralRelation('measure_nc', 'NC')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard.measure_nc', '=', 'measure_nc.id');
            })
            ->leftjoin("wg_customer_config_job_activity_hazard_tracking", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard_tracking.job_activity_hazard_id', '=', 'wg_customer_config_job_activity_hazard.id');
            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard_tracking.created_by', '=', 'users.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_config_activity_hazard_reason')), function ($join) {
                $join->on('customer_config_activity_hazard_reason.value', '=', 'wg_customer_config_job_activity_hazard_tracking.reason');
            })
            ->select(
                "wg_customer_config_job_activity_hazard.id",
                "wg_customer_config_workplace.name as workPlace",
                "wg_customer_config_macro_process.name as macroProcess",
                "wg_customer_config_process.name as process",
                "wg_customer_config_job_activity_hazard.job",

                "wg_customer_config_job_activity_hazard.activity",
                DB::raw("CASE WHEN wg_customer_config_job_activity_hazard.isRoutine = 1 THEN 'SI' WHEN wg_customer_config_job_activity_hazard.isRoutine IS NULL THEN '' ELSE 'NO' END as isRoutine"),
                "wg_config_job_activity_hazard_classification.name as classification",
                "wg_config_job_activity_hazard_type.name as type",
                "wg_config_job_activity_hazard_description.name as description",
                "wg_config_job_activity_hazard_effect.name as effect",
                "wg_customer_config_job_activity_hazard.time_exposure AS timeExposure",
                "wg_customer_config_job_activity_hazard.control_method_source_text AS controlMethodSourceText",
                "wg_customer_config_job_activity_hazard.control_method_medium_text AS controlMethodMediumText",
                "wg_customer_config_job_activity_hazard.control_method_person_text AS controlMethodPersonText",
                "wg_customer_config_job_activity_hazard.control_method_administrative_text AS controlMethodAdministrativeText",

                "measure_nd.value AS measureND",
                "measure_ne.value AS measureNE",
                "measure_nc.value AS measureNC",

                DB::raw("(measure_nd.value * measure_ne.value) as levelP"),
                DB::raw("CASE
                WHEN (measure_nd.value * measure_ne.value) > 20 THEN 'Muy Alto'
                WHEN (measure_nd.value * measure_ne.value) >= 10 AND (measure_nd.value * measure_ne.value) <= 20 THEN 'Alto'
                WHEN (measure_nd.value * measure_ne.value) >= 6 AND (measure_nd.value * measure_ne.value) <= 8 THEN 'Medio'
                WHEN (measure_nd.value * measure_ne.value) >= 1 AND (measure_nd.value * measure_ne.value) <= 4 THEN'Bajo'
                END as levelIP"),

                DB::raw("((measure_nd.value * measure_ne.value) * measure_nc.value) as levelR"),
                DB::raw("CASE
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'I'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'II'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'III'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'IV'
                END as levelIR"),

                DB::raw("CASE
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
                END as riskValue"),

                DB::raw("CASE
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
                WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable'
                END as riskText"),

                "wg_customer_config_job_activity_hazard.exposed",
                "wg_customer_config_job_activity_hazard.contractors",
                "wg_customer_config_job_activity_hazard.visitors",
                "wg_customer_config_job_activity_hazard.status",
                "wg_customer_config_job_activity_hazard.reason",

                "wg_customer_config_job_activity_hazard_tracking.type AS historicalType",
                "wg_customer_config_job_activity_hazard_tracking.source AS historicalSource",
                "users.name AS historicalCreator",
                "wg_customer_config_job_activity_hazard_tracking.created_at AS historicalCreatedAt",

                "wg_customer_config_workplace.customer_id AS customerId",
                "wg_customer_config_job_activity_hazard.activityId",
                "wg_customer_config_workplace.id AS workPlaceId",
                "wg_customer_config_job_activity_hazard.jobActivityId",
                "wg_customer_config_job_activity_hazard.hasHazards",

                "customer_config_activity_hazard_reason.item as historicalReason",
                "wg_customer_config_job_activity_hazard_tracking.reason_observation AS historicalReasonObservation"
            );

        $query = $this->prepareQuery($query->toSql());

        $this->applyWhere($query, $criteria);

        $result = $query->get();

        $workplaceTitle = $_ENV['instance'] == 'isa' ? 'GRUPO OCUPACIONAL O INSTALACIÓN' : 'CENTRO DE TRABAJO';
        $macroprocessTitle = $_ENV['instance'] == 'isa' ? 'SUBESTACIÓN' : 'MACROPROCESO';
        $processTitle = $_ENV['instance'] == 'isa' ? 'UBICACIÓN, SITIO O ÁREA' : 'PROCESO';
        $activityTitle = $_ENV['instance'] == 'isa' ? 'LABOR/TAREA' : 'ACTIVIDAD';
        $controlMethodSourceTitle = $_ENV['instance'] == 'isa' ? 'M. Control Fuente' : 'M. Control Fuente';
        $controlMethodMediumTitle = $_ENV['instance'] == 'isa' ? 'M. Control Medio' : 'M. Control Medio';
        $controlMethodPersonTitle = $_ENV['instance'] == 'isa' ? 'M. Control Persona' : 'M. Control Persona';

        $heading = [
            "USUARIO" => "historicalCreator",
            "ORIGEN" => "historicalSource",
            "TIPO ACCIÓN" => "historicalType",
            "FECHA ACCIÓN" => "historicalCreatedAt",
            "MOTIVO DE ACTUALIZACIÓN" => "historicalReason",
            "OBSERVACIÓN DEL MOTIVO" => "historicalReasonObservation",
            $workplaceTitle => "workPlace",
            $macroprocessTitle => "macroProcess",
            $processTitle => "process",
            "CARGO" => "job",
            $activityTitle => "activity",
            "RUTINARIA" => "isRoutine",
            "CLASIFICACIÓN" => "classification",
            "TIPO PELIGRO" => "type",
            "DESCRIPCIÓN PELIGRO" => "description",
            "EFECTOS A LA SALUD" => "effect",
            "T. EXPUESTO" => "timeExposure",
            $controlMethodSourceTitle => "controlMethodSourceText",
            $controlMethodMediumTitle => "controlMethodMediumText",
            $controlMethodPersonTitle => "controlMethodPersonText",
            "N. DEFICIENCIA" => "measureND",
            "N. EXPOSICIÓN" => "measureNE",
            "N. CONSECUENCIA" => "measureNC",
            "N. PROBABILIDAD" => "levelP",
            "INTERP N. PROBABILIDAD" => "levelIP",
            "NIVEL RIESGO" => "levelR",
            "INTERP RIESGO" => "levelIR",
            "VALORACIÓN RIESGO" => "riskValue",
            "TRABAJADORES VINCULADOS O EN MISIÓN" => "exposed",
            "TRABAJADORES CONTRATISTAS" => "contractors",
            "VISITANTES" => "visitors",
            "VERIFICADO" => "status",
            "MOTIVO" => "reason",
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportCharacterizatioData($criteria)
    {
        $qSub = $this->prepareCharacterizationSubQuery();

        $query = DB::table(DB::raw("({$qSub->toSql()}) as characterization"))
            ->mergeBindings($qSub)
            ->select(
                'characterization.customerId',
                "characterization.classification",
                "characterization.type",
                DB::raw("COUNT(*) AS total"),
                DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable o Aceptable con control especifico' THEN 1 ELSE 0 END) AS noAcceptableControl"),
                DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable' THEN 1 ELSE 0 END) AS noAcceptable"),
                DB::raw("SUM(CASE WHEN riskValue = 'Mejorable' THEN 1 ELSE 0 END) AS improvable"),
                DB::raw("SUM(CASE WHEN riskValue = 'Aceptable' THEN 1 ELSE 0 END) AS acceptable")
            )                   
            ->groupBy('characterization.customerId', 'characterization.classificationId', 'characterization.typeId')
            ->orderBy('characterization.classificationId', 'ASC')
            ->orderBy('characterization.typeId', 'ASC');

        $query = $this->prepareQuery($query->toSql());

        $this->applyWhere($query, $criteria);

        $result = $query->get();

        $heading = [
            "CLASIFICACIÓN" => "classification",
            "TIPO DE PELIGRO" => "type",
            "TOTAL" => "total",
            "ACEPTABLE" => "acceptable",
            "MEJORABLE" => "improvable",
            "NO ACEPTABLE O ACEPTABLE CON CONTROL ESPECIFICO" => "noAcceptableControl",
            "NO ACEPTABLE" => "noAcceptable"
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getWorkplaceList($criteria)
    {
        return DB::table('wg_customer_config_workplace')
            ->join("wg_customer_config_macro_process", function ($join) {
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
                "wg_customer_config_workplace.id",
                "wg_customer_config_workplace.name"
            )->whereNotNull('wg_customer_config_job_activity_hazard.id')
            ->where('wg_customer_config_workplace.customer_id', $criteria->customerId)
            ->groupBy('wg_customer_config_workplace.customer_id', 'wg_customer_config_workplace.id')
            ->get();
    }

    public function getProcessList($criteria)
    {
        $query = DB::table('wg_customer_config_workplace')
            ->join("wg_customer_config_macro_process", function ($join) {
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
                "wg_customer_config_process.id",
                "wg_customer_config_process.name"
            )->whereNotNull('wg_customer_config_job_activity_hazard.id')
            ->where('wg_customer_config_workplace.customer_id', $criteria->customerId)
            ->groupBy('wg_customer_config_workplace.customer_id', 'wg_customer_config_process.id');

        
        $query->where("wg_customer_config_workplace.id", $criteria->workplace ? $criteria->workplace->id : 0);

        return $query->get();
    }

    public function getClassificationList($criteria)
    {
        $query = DB::table('wg_customer_config_workplace')
            ->join("wg_customer_config_macro_process", function ($join) {
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
                "wg_config_job_activity_hazard_classification.id",
                "wg_config_job_activity_hazard_classification.name"
            )->whereNotNull('wg_customer_config_job_activity_hazard.id')
            ->where('wg_customer_config_workplace.customer_id', $criteria->customerId)
            ->groupBy('wg_customer_config_workplace.customer_id', 'wg_config_job_activity_hazard_classification.id');

        if ($criteria->workplace) {
            $query->where("wg_customer_config_workplace.id", $criteria->workplace->id);
        }

        return $query->get();
    }

    public function getChartBarClassification($criteria)
    {
        $qSub = $this->prepareCharacterizationSubQuery();

        $query = DB::table(DB::raw("({$qSub->toSql()}) as characterization"))
            ->mergeBindings($qSub)
            ->select(
                DB::raw("CASE WHEN characterization.classification IS NULL THEN 'Sin Definir' ELSE characterization.classification END AS label"),
                DB::raw("COUNT(*) AS value")
            )
            ->where('characterization.customerId', $criteria->customerId)
            ->groupBy('characterization.customerId', 'characterization.classificationId')
            ->orderBy('characterization.classificationId', 'ASC');

        if ($criteria->workplace) {
            $query->where("characterization.workplaceId", $criteria->workplace->id);
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Clasificación', 'field' => 'value']
            ]
        );
        return $this->chart->getChartBar($query->get()->toArray(), $config);
    }

    public function getChartPieAcceptability($criteria)
    {
        $qSub = $this->prepareCharacterizationSubQuery();

        $query = DB::table(DB::raw("({$qSub->toSql()}) as characterization"))
            ->mergeBindings($qSub)
            ->select(
                "characterization.riskValue AS label",
                DB::raw("COUNT(*) AS value"),
                DB::raw("CASE WHEN riskValue = 'No Aceptable o Aceptable con control especifico' THEN '#eea236' 
                        WHEN riskValue = 'No Aceptable' THEN '#d43f3a'
                        WHEN riskValue = 'Mejorable' THEN '#46b8da'
                        WHEN riskValue = 'Aceptable' THEN '#5cb85c' END AS color")                            
            )
            ->where('characterization.customerId', $criteria->customerId)
            ->groupBy('characterization.customerId', 'characterization.riskValue')
            ->orderBy('characterization.riskValue', 'ASC');

        if ($criteria->workplace) {
            $query->where("characterization.workplaceId", $criteria->workplace->id);
        }

        $data = $query->get()->toArray();

        $total = array_reduce($data, function ($value, $item) {
            $value += floatval($item->value);
            return $value;
        }, 0);

        $data = array_map(function ($item) use ($total) {
            $item->value = round((floatval($item->value) / $total) * 100, 2);
            $item->label .= ": ({$item->value} %)";
            return $item;
        }, $data);

        return $this->chart->getChartPie($data);
    }

    public function getChartBarAcceptabilityClassification($criteria)
    {
        $qSub = $this->prepareCharacterizationSubQuery();

        $query = DB::table(DB::raw("({$qSub->toSql()}) as characterization"))
            ->mergeBindings($qSub)
            ->select(
                "characterization.classification AS label",
                DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable o Aceptable con control especifico' THEN 1 ELSE 0 END) AS noAcceptableControl"),
                DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable' THEN 1 ELSE 0 END) AS noAcceptable"),
                DB::raw("SUM(CASE WHEN riskValue = 'Mejorable' THEN 1 ELSE 0 END) AS improvable"),
                DB::raw("SUM(CASE WHEN riskValue = 'Aceptable' THEN 1 ELSE 0 END) AS acceptable")
            )
            ->where('characterization.customerId', $criteria->customerId)
            ->groupBy('characterization.customerId', 'characterization.classificationId')
            ->orderBy('characterization.classificationId', 'ASC');

        if ($criteria->workplace) {
            $query->where("characterization.workplaceId", $criteria->workplace->id);
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Aceptable', 'field' => 'acceptable', 'color' => '#5cb85c'],
                ['label' => 'Mejorable', 'field' => 'improvable', 'color' => '#46b8da'],
                ['label' => 'No Aceptable o Aceptable con control especifico', 'field' => 'noAcceptableControl', 'color' => '#eea236'],
                ['label' => 'No Aceptable', 'field' => 'noAcceptable', 'color' => '#d43f3a'],
            ]
        );

        return $this->chart->getChartBar($query->get()->toArray(), $config);
    }

    public function getChartBarAcceptabilityType($criteria)
    {
        $qSub = $this->prepareCharacterizationSubQuery();

        $query = DB::table(DB::raw("({$qSub->toSql()}) as characterization"))
            ->mergeBindings($qSub)
            ->select(
                "characterization.type AS label",
                DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable o Aceptable con control especifico' THEN 1 ELSE 0 END) AS noAcceptableControl"),
                DB::raw("SUM(CASE WHEN riskValue = 'No Aceptable' THEN 1 ELSE 0 END) AS noAcceptable"),
                DB::raw("SUM(CASE WHEN riskValue = 'Mejorable' THEN 1 ELSE 0 END) AS improvable"),
                DB::raw("SUM(CASE WHEN riskValue = 'Aceptable' THEN 1 ELSE 0 END) AS acceptable")
            )
            ->where('characterization.customerId', $criteria->customerId)
            ->groupBy('characterization.customerId', 'characterization.typeId')
            ->orderBy('characterization.typeId', 'ASC');

        if ($criteria->workplace) {
            $query->where("characterization.workplaceId", $criteria->workplace->id);
        }

        $query->where("characterization.processId", $criteria->process ? $criteria->process->id : 0);
        $query->where("characterization.classificationId", $criteria->classification ? $criteria->classification->id : 0);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Aceptable', 'field' => 'acceptable', 'color' => '#5cb85c'],
                ['label' => 'Mejorable', 'field' => 'improvable', 'color' => '#46b8da'],
                ['label' => 'No Aceptable o Aceptable con control especifico', 'field' => 'noAcceptableControl', 'color' => '#eea236'],
                ['label' => 'No Aceptable', 'field' => 'noAcceptable', 'color' => '#d43f3a'],
            ]
        );

        return $this->chart->getChartBar($query->get()->toArray(), $config);
    }

    public function getChartIntervention($criteria)
    {
        $qSub = $this->prepareCharacterizationSubQuery();

        $query = DB::table(DB::raw("({$qSub->toSql()}) as characterization"))
            ->mergeBindings($qSub)
            ->join("wg_customer_config_job_activity_hazard_intervention", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard_intervention.job_activity_hazard_id', '=', 'characterization.id');
            })->leftjoin(DB::raw(SystemParameter::getRelationTable('config_type_measure')), function ($join) {
                $join->on('wg_customer_config_job_activity_hazard_intervention.type', '=', 'config_type_measure.value');
            })
            ->select(
                "config_type_measure.item AS label",
                DB::raw("COUNT(*) AS value")
            )
            ->where('characterization.customerId', $criteria->customerId)
            ->groupBy('characterization.customerId', 'wg_customer_config_job_activity_hazard_intervention.type')
            ->orderBy('config_type_measure.item', 'ASC');

        if ($criteria->workplace) {
            $query->where("characterization.workplaceId", $criteria->workplace->id);
        }

        $query->where("characterization.processId", $criteria->process ? $criteria->process->id : 0);
        $query->where("characterization.classificationId", $criteria->classification ? $criteria->classification->id : 0);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Medidas de Intervención', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($query->get()->toArray(), $config);
    }

    public function getChartPieImprovementPlan($criteria)
    {
        $qSub = $this->prepareCharacterizationSubQuery();

        $query = DB::table(DB::raw("({$qSub->toSql()}) as characterization"))
            ->mergeBindings($qSub)
            ->join("wg_customer_config_job_activity_hazard_intervention", function ($join) {
                $join->on('wg_customer_config_job_activity_hazard_intervention.job_activity_hazard_id', '=', 'characterization.id');
            })
            ->join("wg_customer_improvement_plan", function ($join) {
                $join->on('wg_customer_improvement_plan.entityId', '=', 'wg_customer_config_job_activity_hazard_intervention.id');
                $join->where('wg_customer_improvement_plan.entityName', '=', 'MT');
            })
            ->select(
                DB::raw("CASE WHEN wg_customer_improvement_plan.status = 'AB' THEN 'Abierto' ELSE 'Completado' END AS label"),
                DB::raw("COUNT(*) AS value")
            )
            ->where('characterization.customerId', $criteria->customerId)
            ->whereIn('wg_customer_improvement_plan.status', ['AB', 'CO'])
            ->groupBy('characterization.customerId', 'wg_customer_improvement_plan.status')
            ->orderBy('wg_customer_improvement_plan.status', 'ASC');

        $data = $query->get()->toArray();

        $total = array_reduce($data, function ($value, $item) {
            $value += floatval($item->value);
            return $value;
        }, 0);

        $data = array_map(function ($item) use ($total) {
            $item->label = "({$item->value}) {$item->label}";
            $item->value = (floatval($item->value) / $total) * 100;
            return $item;
        }, $data);

        return $this->chart->getChartPie($data);
    }

    private function prepareCharacterizationSubQuery()
    {
        return DB::table('wg_customer_config_workplace')
            ->join("wg_customer_config_macro_process", function ($join) {
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
                "wg_customer_config_job_activity_hazard.id",
                "wg_customer_config_workplace.customer_id AS customerId",
                "wg_customer_config_workplace.id AS workplaceId",
                "wg_config_job_activity_hazard_classification.id as classificationId",
                "wg_config_job_activity_hazard_classification.name as classification",
                "wg_config_job_activity_hazard_type.id as typeId",
                "wg_config_job_activity_hazard_type.name as type",
                "wg_customer_config_process.id AS processId",
                DB::raw("CASE
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
            WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 10 AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
            END as riskValue")
            )->whereNotNull('wg_customer_config_job_activity_hazard.id');
    }


    public function getChartBarAcceptability($criteria)
    {
        $qSub = $this->prepareCharacterizationSubQuery();

        $query = DB::table(DB::raw("({$qSub->toSql()}) as characterization"))
            ->mergeBindings($qSub)
            ->select(
                "characterization.riskValue AS label",
                DB::raw("COUNT(*) AS value"),
                DB::raw("CASE WHEN riskValue = 'No Aceptable o Aceptable con control especifico' THEN '#eea236' 
                        WHEN riskValue = 'No Aceptable' THEN '#d43f3a'
                        WHEN riskValue = 'Mejorable' THEN '#46b8da'
                        WHEN riskValue = 'Aceptable' THEN '#5cb85c' END AS color")
            )
            ->where('characterization.customerId', $criteria->customerId)
            ->groupBy('characterization.customerId', 'characterization.riskValue')
            ->orderBy('characterization.riskValue', 'ASC');

        if ($criteria->workplace) {
            $query->where("characterization.workplaceId", $criteria->workplace->id);
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Medidas de Intervención', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($query->get(), $config);
    }


    public function getAmountRecords($criteria)
    {
        $workplaceId = $criteria->workplace->id ?? null;

        $subquery = DB::table('wg_customer_config_job')
            ->join('wg_customer_config_job_data', 'wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id')
            ->join('wg_customer_config_job_activity', 'wg_customer_config_job_activity.job_id', '=', 'wg_customer_config_job.id')
            ->leftJoin('wg_customer_config_activity_process', 'wg_customer_config_activity_process.id', '=', 'wg_customer_config_job_activity.activity_id')
            ->leftJoin('wg_customer_config_activity', 'wg_customer_config_activity.id', '=', 'wg_customer_config_activity_process.activity_id')
            ->leftJoin('wg_customer_config_job_activity_hazard_relation', 'wg_customer_config_job_activity_hazard_relation.customer_config_job_activity_id', '=', 'wg_customer_config_job_activity.id')
            ->leftJoin('wg_customer_config_job_activity_hazard', 'wg_customer_config_job_activity_hazard.id', '=', 'wg_customer_config_job_activity_hazard_relation.customer_config_job_activity_hazard_id')
            ->select(
                'wg_customer_config_job_activity_hazard.id',
                'wg_customer_config_job.workplace_id',
                'wg_customer_config_job.macro_process_id',
                'wg_customer_config_job.process_id'
            );

        $query = DB::table('wg_customer_config_workplace')
            ->join('wg_customer_config_macro_process as wg_customer_config_macro_process', 'wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_workplace.id')
            ->join('wg_customer_config_process', function ($join) {
                $join->on('wg_customer_config_process.workplace_id', 'wg_customer_config_macro_process.workplace_id');
                $join->on('wg_customer_config_process.macro_process_id', 'wg_customer_config_macro_process.id');
            })
            ->join(DB::raw("({$subquery->toSql()}) as wg_customer_config_job_activity_hazard"), function($join) {
                $join->on('wg_customer_config_job_activity_hazard.workplace_id', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_config_job_activity_hazard.macro_process_id', 'wg_customer_config_macro_process.id');
                $join->on('wg_customer_config_job_activity_hazard.process_id', 'wg_customer_config_process.id');
            })
            ->whereNotNull('wg_customer_config_job_activity_hazard.id')
            ->where('wg_customer_config_workplace.customer_id', $criteria->customerId)
            ->when($workplaceId, function ($query) use ($workplaceId) {
                $query->where('wg_customer_config_workplace.id', $workplaceId);
            })
            ->count();

        return $query;
    }
}
