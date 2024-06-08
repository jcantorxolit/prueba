<?php

namespace AdeN\Api\Modules\PositivaFgn\Indicator;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\ActivityConfigModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\SubtaskModel;
use Carbon\Carbon;
use DB;
use Wgroup\SystemParameter\SystemParameter;

class IndicatorRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new IndicatorModel());
        $this->service = new IndicatorService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_indicators_config.id",
            "title" => "wg_positiva_fgn_indicators_config.title",
            "description" => "wg_positiva_fgn_indicators_config.description",
            "isActive" => "wg_positiva_fgn_indicators_config.is_active AS isActive",
            "code" => "wg_positiva_fgn_indicators_config.code",
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();
        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function consolidate()
    {
        $this->service->getConsolidatedData();
    }

    public function filterAssignment($criteria)
    {
        $this->setColumns([
            "id" => DB::raw("CONCAT(configsectional.fgn_activity_id, ',',
                            configsectional.regional_id, ',',
                            configsectional.sectional_id) AS id"),
            "axis" => "positiva_fgn_activity_axis.item AS axis",
            "action" => "positiva_fgn_activity_action.item AS action",
            "activityCode" => "activity.code as activityCode",
            "activity" => "wg_positiva_fgn_activity.name AS activity",
            "strategy" => "positiva_fgn_consultant_strategy.item AS strategy",
            "activityGestpos" => "activity.name AS activityGestpos",
            "task" => "task.name AS task",
            "goalCoverage" => "wg_positiva_fgn_activity.goal_coverage AS goalCoverage",
            "assignedCoverage" => DB::raw("wg_positiva_fgn_activity.assignment_coverage AS assignedCoverage"),
            "pendingCoverage" => DB::raw("(wg_positiva_fgn_activity.goal_coverage - wg_positiva_fgn_activity.assignment_coverage) AS pendingCoverage"),
            "goalCompliance" => "wg_positiva_fgn_activity.goal_compliance AS goalCompliance",
            "assignedCompliance" => DB::raw("wg_positiva_fgn_activity.assignment_compliance AS assignedCompliance"),
            "pendingCompliance" => DB::raw("(wg_positiva_fgn_activity.goal_compliance - wg_positiva_fgn_activity.assignment_compliance) AS pendingCompliance"),
            "regionalVal" => "configsectional.regional_id AS regionalVal",
            "sectionalVal" => "configsectional.sectional_id AS sectionalVal",
            "strategyVal" => "positiva_fgn_consultant_strategy.value AS strategyVal",
        ]);

        $configSectional = DB::table("wg_positiva_fgn_activity_indicator_sectional")
            ->groupBy("regional_id", "sectional_id", "fgn_activity_id");

        $this->parseCriteria($criteria);
        $query = DB::table("wg_positiva_fgn_activity");
        $query->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis')), function ($join) {
            $join->on('wg_positiva_fgn_activity.axis', '=', 'positiva_fgn_activity_axis.value');
        })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_action')), function ($join) {
                $join->on('wg_positiva_fgn_activity.action', '=', 'positiva_fgn_activity_action.value');
            })
            ->join("wg_positiva_fgn_activity_config", function ($join) {
                $join->on("wg_positiva_fgn_activity.id", "=", "wg_positiva_fgn_activity_config.fgn_activity_id");
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                $join->on('wg_positiva_fgn_activity_config.strategy', '=', 'positiva_fgn_consultant_strategy.value');
            })
            ->join(DB::raw("wg_positiva_fgn_gestpos as activity"), function ($join) {
                $join->on("wg_positiva_fgn_activity_config.activity_id", "=", "activity.id");
            })
            ->join(DB::raw("wg_positiva_fgn_gestpos AS task"), function ($join) {
                $join->on("wg_positiva_fgn_activity_config.gestpos_task_id", "=", "task.id");
            })
            ->join(DB::raw("({$configSectional->toSql()}) AS configsectional"), function ($join) {
                $join->on("wg_positiva_fgn_activity.id", "=", "configsectional.fgn_activity_id");
            });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function activitiesProgramming($criteria)
    {
        $this->setColumns([
            "id" => "configsectionalconsultant.id",
            "action" => "positiva_fgn_activity_action.item AS action",
            "activityCode" => "activity.code as activityCode",
            "activity" => "activity.name as activity",
            "goalCoverage" => "wg_positiva_fgn_activity.goal_coverage AS goalCoverage",
            "goalCompliance" => "wg_positiva_fgn_activity.goal_compliance AS goalCompliance",
            "assignmentCompliance" => "wg_positiva_fgn_activity.assignment_compliance AS assignmentCompliance",
            "assignmentCoverage" => "wg_positiva_fgn_activity.assignment_coverage AS assignmentCoverage",
            "strategy" => "positiva_fgn_consultant_strategy.item AS strategy",
            "activityGestpos" => "activity.name AS activityGestpos",
            "task" => "task.name as task",
            "percentageCoverage" => DB::raw("ROUND((wg_positiva_fgn_activity.goal_coverage / wg_positiva_fgn_activity.assignment_coverage) * 100, 2) as percentageCoverage"),
            "percentageCompliance" => DB::raw("ROUND((wg_positiva_fgn_activity.goal_compliance / wg_positiva_fgn_activity.assignment_compliance) * 100, 2) as percentageCompliance"),
            "sectionalVal" => "configsectional.sectional_id AS sectionalVal",
            "periodVal" => "wg_positiva_fgn_activity.config_id AS periodVal",
            "axisVal" => "wg_positiva_fgn_activity.axis AS axisVal",
            "consultantVal" => "configsectionalconsultant.consultant_id AS consultantVal",
        ]);

        $configSectional = DB::table("wg_positiva_fgn_activity_indicator_sectional")
            ->groupBy("regional_id", "sectional_id", "fgn_activity_id");

        $configSectionalConsultant = DB::table("wg_positiva_fgn_activity_indicator_sectional_consultant")
            ->groupBy("regional_id", "sectional_id", "fgn_activity_id", "activity_id", "gestpos_task_id");

        $this->parseCriteria($criteria);
        $query = DB::table("wg_positiva_fgn_activity");

        $query
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis')), function ($join) {
                $join->on('wg_positiva_fgn_activity.axis', '=', 'positiva_fgn_activity_axis.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_action')), function ($join) {
                $join->on('wg_positiva_fgn_activity.action', '=', 'positiva_fgn_activity_action.value');
            })
            ->join('wg_positiva_fgn_activity_config', 'wg_positiva_fgn_activity_config.fgn_activity_id', '=', 'wg_positiva_fgn_activity.id')
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                $join->on('wg_positiva_fgn_activity_config.strategy', '=', 'positiva_fgn_consultant_strategy.value');
            })
            ->join(DB::raw("wg_positiva_fgn_gestpos as activity"), function ($join) {
                $join->on("wg_positiva_fgn_activity_config.activity_id", "=", "activity.id");
            })
            ->join(DB::raw("wg_positiva_fgn_gestpos AS task"), function ($join) {
                $join->on("wg_positiva_fgn_activity_config.gestpos_task_id", "=", "task.id");
            })
            ->join(DB::raw("({$configSectional->toSql()}) AS configsectional"), function ($join) {
                $join->on("wg_positiva_fgn_activity.id", "=", "configsectional.fgn_activity_id");
            })
            ->join(DB::raw("({$configSectionalConsultant->toSql()}) AS configsectionalconsultant"), function ($join) {
                $join->on("wg_positiva_fgn_activity.id", "=", "configsectionalconsultant.fgn_activity_id");
                $join->on("activity.id", "=", "configsectionalconsultant.activity_id");
                $join->on("task.id", "=", "configsectionalconsultant.gestpos_task_id");
                $join->on("configsectional.regional_id", "=", "configsectionalconsultant.regional_id");
                $join->on("configsectional.sectional_id", "=", "configsectionalconsultant.sectional_id");
            });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function getComplianceIndicator($model)
    {

        $task =
        ActivityConfigModel::where("wg_positiva_fgn_activity_config.id", $model->activityconfigid)
            ->join("wg_positiva_fgn_gestpos", "gestpos_task_id", "=", "wg_positiva_fgn_gestpos.id")
            ->leftJoin("wg_positiva_fgn_management_indicator_compliance", function ($join) use ($model) {
                $join->on("wg_positiva_fgn_gestpos.id", "=", "wg_positiva_fgn_management_indicator_compliance.gestpos_task_id");
                $join->where("wg_positiva_fgn_management_indicator_compliance.fgn_activity_id", "=", $model->fgnactivityid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.regional_id", "=", $model->regionalid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.sectional_id", "=", $model->sectionalid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.consultant_id", "=", $model->consultantid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.activity_id", "=", $model->activityid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.period_id", "=", $model->periodid);
            })
            ->select(
                "wg_positiva_fgn_gestpos.id as taskid",
                "name", "code",
                "programmed", "executed", "hour_programmed", "hour_executed",
                DB::raw("IF(provides_coverage=1,'SI','NO') AS providesCoverage"),
                DB::raw("IF(provides_compliance=1,'SI','NO') AS providesCompliance"),
                DB::raw("IF(type='main','PRINCIPAL','SUBTAREA') AS type")
            )
            ->first();

        $subTask =
        SubtaskModel::where('activity_config_id', $model->activityconfigid)
            ->join("wg_positiva_fgn_gestpos", "gestpos_subtask_id", "=", "wg_positiva_fgn_gestpos.id")
            ->leftJoin("wg_positiva_fgn_management_indicator_compliance", function ($join) use ($model) {
                $join->on("wg_positiva_fgn_gestpos.id", "=", "wg_positiva_fgn_management_indicator_compliance.gestpos_task_id");
                $join->where("wg_positiva_fgn_management_indicator_compliance.fgn_activity_id", "=", $model->fgnactivityid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.regional_id", "=", $model->regionalid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.sectional_id", "=", $model->sectionalid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.consultant_id", "=", $model->consultantid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.activity_id", "=", $model->activityid);
                $join->where("wg_positiva_fgn_management_indicator_compliance.period_id", "=", $model->periodid);
            })
            ->select(
                "wg_positiva_fgn_gestpos.id as taskid", "name", "code",
                "programmed", "executed", "hour_programmed", "hour_executed",
                DB::raw("IF(provides_compliance=1,'SI','NO') AS providesCompliance"),
                DB::raw("IF(type='main','PRINCIPAL','SUBTAREA') AS type")
            )
            ->get()
            ->toArray();

        array_push($subTask, $task);

        return $subTask;

    }

    public function getCoverageIndicator($model)
    {

        $items = SystemParameter::getRelationTable('positiva_fgn_management_indicator_coverage', "SP");
        $ic = DB::table(DB::raw("{$items}"))
            ->leftJoin("wg_positiva_fgn_management_indicator_coverage", function ($join) use ($model) {
                $join->on("SP.value", "=", "wg_positiva_fgn_management_indicator_coverage.poblation");
                $join->where("wg_positiva_fgn_management_indicator_coverage.gestpos_task_id", "=", $model->taskid);
                $join->where("wg_positiva_fgn_management_indicator_coverage.fgn_activity_id", "=", $model->fgnactivityid);
                $join->where("wg_positiva_fgn_management_indicator_coverage.regional_id", "=", $model->regionalid);
                $join->where("wg_positiva_fgn_management_indicator_coverage.sectional_id", "=", $model->sectionalid);
                $join->where("wg_positiva_fgn_management_indicator_coverage.consultant_id", "=", $model->consultantid);
                $join->where("wg_positiva_fgn_management_indicator_coverage.activity_id", "=", $model->activityid);
                $join->where("wg_positiva_fgn_management_indicator_coverage.period_id", "=", $model->periodid);
            })
            ->select(
                "SP.item", "SP.value",
                "call", "assistants"
            )
            ->get();

        return $ic;
    }

    public static function getGenreIndicatorChart($criteria)
    {
        $reposity = new self;
        return $reposity->service->getGenreIndicatorChart($criteria);
    }

    public static function getDataChartBar($criteria)
    {
        $reposity = new self;
        return $reposity->service->getDataChartBar($criteria);
    }

    public function getAllIndicatorsByActivity($criteria)
    {
        $reposity = new self;
        return $reposity->service->getAllIndicatorsByActivity($criteria);
    }

    public function getActivitiesPTACompliance($criteria, $filters)
    {
        $query = $this->service->getActivitiesPTACompliance($filters);
        return $this->get($query, $criteria);
    }

    public function getActivitiesPTAComplianceDetails($criteria, $filters, $axis)
    {
        $this->parseCriteria($criteria);
        $query = $this->service->getActivitiesPTAComplianceDetails($filters, $axis);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function getActivitiesPTAComplianceAxis($criteria)
    {
        return $this->service->getActivitiesPTAComplianceAxis($criteria);
    }

    public function getActivitiesPTAComplianceExportExcel($criteria)
    {
        $header = [
            "EJE" => "axis",
            "META CUMPLIMIENTO" => "goalCompliance",
            "N° ACTIVIDADES" => "countActivities",
            "PORCENTAJE CUMPLIMIENTO" => "percentCompliance",
            "META COBERTURA" => "goalCoverage",
            "N° POBLACIÓN" => "countPopulation",
            "PORCENTAJE COBERTURA" => "percentCoverage",
        ];

        $query = $this->service->getActivitiesPTAComplianceExportExcel($criteria->filters);
        $data = ExportHelper::headings($query->get(), $header);

        $filename = 'Cumplimiento_Actividades_PTA_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Actividades', $data);
    }

    /*
     *Indicador Actividades Fallidas
     */
    public function getActivitiesFailedCompliance($criteria, $filters)
    {
        $query = $this->service->getActivitiesFailedCompliance($filters);
        return $this->get($query, $criteria);
    }

    public function getActivitiesFailedComplianceExportExcel($criteria)
    {
        $header = [
            "ACTIVIDAD FGN" => "activity",
            "ESTRATEGIA" => "strategy",
            "ACTIVIDAD GESTPOS" => "activityGestpos",
            "TAREA" => "task",
            "ASESOR" => "asesor",
            "REGIONAL" => "regional",
            "SECCIONAL" => "sectional",
            "FECHA" => "date",
            "OBSERVACION" => "observation",
        ];

        $query = $this->service->getActivitiesFailedExportExcel($criteria->filters);

        $data = ExportHelper::headings($query->get()->toArray(), $header);

        $filename = 'Actividades fallidas' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Actividades', $data);
    }

    /*
     *Indicador Consolidado de Actividades
     */

    public function activitiesConsolidatedCompliance($criteria, $filters)
    {
        $this->parseCriteria($criteria);
        $query = $this->service->activitiesConsolidatedCompliance($filters);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function getActivitiesConsolidatedComplianceExcel($criteria)
    {
        $typeIndicator = $criteria->filters->typeIndicator ?? 'compliance';

        $header = [
            "ACTIVIDAD" => "activity",
            "% ACTIVIDADES EJECUTADAS" => "percentExecuted",
            "N° DE ACTIVIDADES EJECUTADAS" => "countPopulation",
            "META CUMPLIMIENTO" => "meta_compliance",
            "% DE CUMPLIMIENTO" => "percentCompliance",
            "META COBERTURA" => "meta_coverage",
            "% COBERTURA" => "percentCoverage",
            "N° DE PARTICIPANTES" => "countPopulation",
        ];

        $headerCompliance = [
            "ACTIVIDAD" => "activity",
            "EJE" => "axis",
            "META" => "meta_compliance",
            "EJECUCIÓN" => "executed",
            "INDICADOR CUMPLIMIENTO" => "percentCompliance",
        ];

        $headerCoverage = [
            "ACTIVIDAD" => "activity",
            "EJE" => "axis",
            "META" => "meta_coverage",
            "N° PARTICIPANTES" => "countPopulation",
            "INDICADOR COBERTURA" => "percentCoverage",
        ];

        $query = $this->service->getConsolidatedIndicatorsExportExcel($criteria->filters);
        if ($typeIndicator == 'compliance') {
            $data = ExportHelper::headings($query->get()->toArray(), $headerCompliance);
            $filename = 'INDICADOR_CUMPLIMIENTO_POR_EJE' . Carbon::now()->timestamp;
        } else if ($typeIndicator == 'coverage') {
            $data = ExportHelper::headings($query->get()->toArray(), $headerCoverage);
            $filename = 'INDICADOR_COBERTURA_POR_EJE' . Carbon::now()->timestamp;
        } else {
            $data = ExportHelper::headings($query->get()->toArray(), $header);
            $filename = 'CONSOLIDADO_DE_INDICADORES' . Carbon::now()->timestamp;
        }

        ExportHelper::excel($filename, 'Actividades', $data);
    }

}
