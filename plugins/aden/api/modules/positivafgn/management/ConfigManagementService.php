<?php

namespace AdeN\Api\Modules\PositivaFgn\Management;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Modules\PositivaFgn\Consultant\ConsultantModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\ConfigConsultantModel;
use AdeN\Api\Modules\PositivaFgn\Models\IndicatorSectionalConsultantRelationModel;
use Carbon\Carbon;
use RainLab\User\Facades\Auth;
use DB;
use Wgroup\SystemParameter\SystemParameter;

class ConfigManagementService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function activitiesProgrammed($period)
    {
        $q1 = DB::table("wg_positiva_fgn_management_indicator_coverage as i")
            ->join("wg_positiva_fgn_management_indicator_relation as ar", "i.indicator_relation_id", "=", "ar.id")
            ->whereRaw("period = " . (isset($period->value) ? $period->value : $period))
            ->whereRaw("`call` > 0")
            ->select("ar.sectional_consultant_relation_id");

        $q2 = DB::table("wg_positiva_fgn_management_indicator_compliance as i")
            ->join("wg_positiva_fgn_management_indicator_relation as ar", "i.indicator_relation_id", "=", "ar.id")
            ->whereRaw("period = " . (isset($period->value) ? $period->value : $period))
            ->whereRaw("programmed > 0")
            ->select("ar.sectional_consultant_relation_id");

        return $q1->union($q2);
    }

    public function activitiesExecuted($period)
    {
        $q1 = DB::table("wg_positiva_fgn_management_indicator_coverage as i")
            ->join("wg_positiva_fgn_management_indicator_relation as ar", "i.indicator_relation_id", "=", "ar.id")
            ->whereRaw("period = " . (isset($period->value) ? $period->value : $period))
            ->whereRaw("`assistants` > 0")
            ->select("ar.sectional_consultant_relation_id");

        $q2 = DB::table("wg_positiva_fgn_management_indicator_compliance as i")
            ->join("wg_positiva_fgn_management_indicator_relation as ar", "i.indicator_relation_id", "=", "ar.id")
            ->whereRaw("period = " . (isset($period->value) ? $period->value : $period))
            ->whereRaw("executed > 0")
            ->select("ar.sectional_consultant_relation_id");

        return $q1->union($q2);
    }

    public function getCoverage($consultantRelationId, $period)
    {
        $columnMonth = $this->getMonthColumn($period);
        $result = ConfigConsultantModel::whereConsultantRelationId($consultantRelationId)
            ->join('wg_positiva_fgn_activity_indicator as ai', 'ai.id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant.activity_indicator_id')
            ->where('ai.type', 'T001')
            ->select(DB::raw("{$columnMonth} as goal"))
            ->first();

        return $result ? $result->goal : 0;
    }

    public function getCompliance($consultantRelationId, $period)
    {
        $columnMonth = $this->getMonthColumn($period);
        $result = ConfigConsultantModel::whereConsultantRelationId($consultantRelationId)
            ->join('wg_positiva_fgn_activity_indicator as ai', 'ai.id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant.activity_indicator_id')
            ->where('ai.type', 'T002')
            ->select(DB::raw("{$columnMonth} as goal"))
            ->first();

        return $result ? $result->goal : 0;
    }

    public function findModel($period, $consultantRelationId)
    {
        return DB::table('wg_positiva_fgn_activity_indicator_sectional_consultant_relation as aiscr')
            ->join('wg_positiva_fgn_activity_indicator_sectional_relation as aisr', 'aisr.id', '=', 'aiscr.sectional_relation_id')
            ->join('wg_positiva_fgn_activity as afgn', 'afgn.id', '=', 'aisr.fgn_activity_id')
            ->join("wg_positiva_fgn_activity_config as ac", 'ac.id', '=', 'aiscr.activity_config_id')
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis')), function ($join) {
                $join->on('afgn.axis', '=', 'positiva_fgn_activity_axis.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_action')), function ($join) {
                $join->on('afgn.action', '=', 'positiva_fgn_activity_action.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                $join->on('ac.strategy', '=', 'positiva_fgn_consultant_strategy.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_modality')), function ($join) {
                $join->on('ac.modality', '=', 'positiva_fgn_activity_modality.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_execution_type')), function ($join) {
                $join->on('ac.execution_type', '=', 'positiva_fgn_activity_execution_type.value');
            })
            ->join('wg_positiva_fgn_regional as r', 'r.id', '=', 'aisr.regional_id')
            ->join('wg_positiva_fgn_sectional as sec', 'sec.id', '=', 'aisr.sectional_id')
            ->join('wg_positiva_fgn_config as fgn_con', 'fgn_con.id', '=', 'afgn.config_id')
            ->join("wg_positiva_fgn_gestpos as agestpos", "agestpos.id", '=', "ac.gestpos_activity_id")
            ->join("wg_positiva_fgn_gestpos AS task", "ac.gestpos_task_id", "=", "task.id")
            ->leftjoin('wg_positiva_fgn_management_indicator_relation as ir', function($join) use ($period) {
                $join->on('ir.sectional_consultant_relation_id', '=', 'aiscr.id');
                $join->where('ir.period', $period);
            })
            ->where('aiscr.id', $consultantRelationId)
            ->select(
                "r.number AS regional",
                "sec.name AS sectional",
                "positiva_fgn_activity_axis.item AS axis",
                "positiva_fgn_activity_action.item AS action",
                "afgn.code AS fgncode",
                "afgn.name AS activity",
                DB::raw("{$period} AS period"),
                "afgn.goal_coverage AS goal",
                "positiva_fgn_consultant_strategy.item AS strategy",
                "positiva_fgn_activity_modality.item AS modality",
                "positiva_fgn_activity_execution_type.item AS executiontype",
                "agestpos.code AS gestposcode",
                "agestpos.name AS activitygestpos",
                "task.name AS task",
                "ac.provides_coverage AS providescoverage",
                "ac.provides_compliance AS providescompliance",
                "ac.id AS activityconfigid",
                "ac.fgn_activity_id AS fgnactivityid",
                "r.id AS regionalid",
                "sec.id AS sectionalid",
                "positiva_fgn_consultant_strategy.value AS strategyvalue",
                "positiva_fgn_activity_modality.value AS modalityvalue",
                "agestpos.id AS activityid",
                "task.id AS taskid",
                "afgn.axis AS axisvalue",
                "aiscr.consultant_id AS consultantid",
                "ir.advice_type",
                "ir.observation",
                "ir.satisfaction_indicator_45",
                "ir.satisfaction_indicator_123",
                "ir.id as indicatorRelationId",
                "aiscr.id as sectionalConsultantRelationId"
            )
            ->first();
    }

    public function getComplianceIndicator($consultantRelationId, $period)
    {
        $task = (new IndicatorSectionalConsultantRelationModel)
            ->join('wg_positiva_fgn_activity_indicator_sectional_consultant as sc', 'sc.consultant_relation_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id')
            ->join('wg_positiva_fgn_activity_indicator as ai', function ($join) {
                $join->on('ai.id', '=', 'sc.activity_indicator_id');
                $join->where('ai.type', 'T002');
            })
            ->join('wg_positiva_fgn_activity_config as ac', 'ac.id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.activity_config_id')
            ->join('wg_positiva_fgn_gestpos as task', function ($join) {
                $join->on('task.id', '=', 'ac.gestpos_task_id');
            })
            ->leftjoin('wg_positiva_fgn_management_indicator_relation as ir', function($join) use ($period) {
                $join->on('ir.sectional_consultant_relation_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id');
                $join->where('ir.period', $period);
            })
            ->leftjoin('wg_positiva_fgn_management_indicator_compliance as ic', function ($join) {
                $join->on('ic.indicator_relation_id', '=', 'ir.id');
                $join->on('ic.activity_gestpos_id', 'task.id');
            })
            ->where("wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id", $consultantRelationId)
            ->select(
                "ic.id",
                'task.id as taskid',
                'task.name',
                'task.code',
                "programmed",
                "executed",
                "hour_programmed",
                "hour_executed",
                DB::raw("IF(provides_compliance=1,'SI','NO') AS providesCompliance"),
                DB::raw("'PRINCIPAL' AS type")
            )
            ->first();

        $subtask = (new IndicatorSectionalConsultantRelationModel)
            ->join('wg_positiva_fgn_activity_indicator_sectional_consultant as sc', 'sc.consultant_relation_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id')
            ->join('wg_positiva_fgn_activity_indicator as ai', function ($join) {
                $join->on('ai.id', '=', 'sc.activity_indicator_id');
                $join->where('ai.type', 'T002');
            })
            ->join('wg_positiva_fgn_activity_config_subtask as subtasks', 'subtasks.activity_config_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.activity_config_id')
            ->join('wg_positiva_fgn_gestpos as subtask', function ($join) {
                $join->on('subtask.id', '=', 'subtasks.gestpos_subtask_id');
            })
            ->leftjoin('wg_positiva_fgn_management_indicator_relation as ir', function($join) use ($period) {
                $join->on('ir.sectional_consultant_relation_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id');
                $join->where('ir.period', $period);
            })
            ->leftjoin('wg_positiva_fgn_management_indicator_compliance as ic', function ($join) {
                $join->on('ic.indicator_relation_id', '=', 'ir.id');
                $join->on('ic.activity_gestpos_id', 'subtask.id');
            })
            ->where("wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id", $consultantRelationId)
            ->select(
                "ic.id",
                'subtask.id as taskid',
                'subtask.name',
                'subtask.code',
                "programmed",
                "executed",
                "hour_programmed",
                "hour_executed",
                DB::raw("IF(provides_compliance=1,'SI','NO') AS providesCompliance"),
                DB::raw("'SUBTAREA' AS type")
            )
            ->get()
            ->toArray();

        array_push($subtask, $task);

/*




        $opt2 = DB::table('wg_positiva_fgn_activity_indicator_sectional_consultant_relation as scr')
            ->join('wg_positiva_fgn_activity_indicator_sectional_consultant as sc', 'sc.consultant_relation_id', '=', 'scr.id')
            ->join('wg_positiva_fgn_activity_indicator as ai', function($join) {
                $join->on('ai.id', '=', 'sc.activity_indicator_id');
                $join->where('ai.type', 'T002');
            })
            ->join('wg_positiva_fgn_activity_config as ac', 'ac.fgn_activity_id', '=', 'ai.fgn_activity_id')
            ->leftjoin('wg_positiva_fgn_activity_config_subtask as subtasks', 'subtasks.activity_config_id', '=', 'ac.id')
            ->join('wg_positiva_fgn_gestpos as agest', function($join) {
                $join->on('agest.id', '=', 'ac.gestpos_task_id');
                $join->orOn('agest.id', '=', 'subtasks.gestpos_subtask_id');
            })
            ->leftjoin('wg_positiva_fgn_management_indicator_relation as ir', 'ir.sectional_consultant_relation_id', '=', 'scr.id')
            ->leftjoin('wg_positiva_fgn_management_indicator_compliance as ic', function($join) {
                $join->on('ic.indicator_relation_id', '=', 'ir.id');
                $join->on('ic.activity_gestpos_id', 'agest.id');
            })
            ->where('scr.id', $consultantRelationId)
            ->select("ic.id", 'agest.id as taskid', 'agest.name', 'agest.code',
                'ic.programmed', 'ic.executed', 'ic.hour_programmed as hourProgrammed', 'ic.hour_executed as hourExecuted',
                DB::raw("
                    IF(
                        IF(agest.type = 'main', ac.provides_compliance, subtasks.provides_compliance) = 1,
                        'SI', 'NO'
                    ) AS providesCompliance"),
                DB::raw("IF(agest.type = 'main', 'PRINCIPAL', 'SUBTAREA') AS type")
            )
            ->get();

//        dd($opt2, $subtask);*/

        return $task ? $subtask : [];
    }

    public function getCoverageIndicator($consultantRelationId, $period)
    {
        $task = (new IndicatorSectionalConsultantRelationModel)
            ->join('wg_positiva_fgn_activity_indicator_sectional_consultant as sc', 'sc.consultant_relation_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id')
            ->join('wg_positiva_fgn_activity_indicator as ai', function ($join) {
                $join->on('ai.id', '=', 'sc.activity_indicator_id');
                $join->where('ai.type', 'T001');
            })
            ->join('wg_positiva_fgn_activity_config as ac', 'ac.id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.activity_config_id')
            ->join('wg_positiva_fgn_gestpos as task', function ($join) {
                $join->on('task.id', '=', 'ac.gestpos_task_id');
            })
            ->leftjoin('wg_positiva_fgn_management_indicator_relation as ir', function($join) use ($period) {
                $join->on('ir.sectional_consultant_relation_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id');
                $join->where('ir.period', $period);
            })
            ->leftjoin('wg_positiva_fgn_management_indicator_coverage as ic', function ($join) {
                $join->on('ic.indicator_relation_id', '=', 'ir.id');
                $join->on('ic.activity_gestpos_id', 'task.id');
            })
            ->where("wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id", $consultantRelationId)
            ->select(
                "ic.id",
                'task.id as taskid',
                'task.name',
                'task.code',
                DB::raw("COALESCE(`call`, 0) as `call`"),
                DB::raw("COALESCE(assistants, 0) as assistants"),
                DB::raw("IF(provides_coverage=1,'SI','NO') AS providesCoverage"),
                DB::raw("'PRINCIPAL' AS type")
            )
            ->first();

        $subtask = (new IndicatorSectionalConsultantRelationModel)
            ->join('wg_positiva_fgn_activity_indicator_sectional_consultant as sc', 'sc.consultant_relation_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id')
            ->join('wg_positiva_fgn_activity_indicator as ai', function ($join) {
                $join->on('ai.id', '=', 'sc.activity_indicator_id');
                $join->where('ai.type', 'T001');
            })
            ->join('wg_positiva_fgn_activity_config_subtask as subtasks', 'subtasks.activity_config_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.activity_config_id')
            ->join('wg_positiva_fgn_gestpos as subtask', function ($join) {
                $join->on('subtask.id', '=', 'subtasks.gestpos_subtask_id');
            })
            ->leftjoin('wg_positiva_fgn_management_indicator_relation as ir', function($join) use ($period) {
                $join->on('ir.sectional_consultant_relation_id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id');
                $join->where('ir.period', $period);
            })
            ->leftjoin('wg_positiva_fgn_management_indicator_coverage as ic', function ($join) {
                $join->on('ic.indicator_relation_id', '=', 'ir.id');
                $join->on('ic.activity_gestpos_id', 'subtask.id');
            })
            ->where("wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id", $consultantRelationId)
            ->select(
                "ic.id",
                'subtask.id as taskid',
                'subtask.name',
                'subtask.code',
                DB::raw("COALESCE(`call`, 0) as `call`"),
                DB::raw("COALESCE(assistants, 0) as assistants"),
                DB::raw("IF(provides_coverage=1,'SI','NO') AS providesCoverage"),
                DB::raw("'SUBTAREA' AS type")
            )
            ->get()
            ->toArray();

        array_push($subtask, $task);
        return $task ? $subtask : [];
    }

    public function getPoblation($indicatorId, $date)
    {
        $items = SystemParameter::getRelationTable('positiva_fgn_management_indicator_coverage', "SP");
        $allSum = DB::table("wg_positiva_fgn_management_indicator_coverage_poblation")
            ->whereRaw("indicator_coverage_id = {$indicatorId}")
            ->whereRaw("type = 'execution'")
            ->select(
                "poblation",
                DB::raw("SUM( IF(activity_state = 'AS001' OR activity_state = 'AS002', COALESCE(value, 0), 0) ) as allAssistant")
            )
            ->groupBy("poblation");

        $ic = DB::table(DB::raw("{$items}"))
            ->leftJoin("wg_positiva_fgn_management_indicator_coverage_poblation AS p", function ($join) use ($indicatorId) {
                $join->on("SP.value", "=", "p.poblation");
                $join->where("p.indicator_coverage_id", "=", $indicatorId);
                $join->where("p.type", "=", "execution");
            })
            ->leftJoin(DB::raw("({$allSum->toSql()}) as allAssistant"), function ($join) {
                $join->on("p.poblation", "=", "allAssistant.poblation");
            })
            ->leftJoin("wg_positiva_fgn_management_indicator_coverage_poblation AS programing", function ($join) use ($indicatorId) {
                $join->on("SP.value", "=", "programing.poblation");
                $join->where("programing.indicator_coverage_id", "=", $indicatorId);
                $join->where("programing.type", "=", "programming");
            })
            ->where('p.date', $date)
            ->select(
                "p.id",
                "SP.item",
                "SP.value",
                DB::raw("COALESCE(programing.value, 0) as `call`"),
                "allAssistant.allAssistant",
                DB::raw("COALESCE(p.value, 0) as assistants"),
                DB::raw("p.date as date"),
                "p.activity_state as activityState"
            );

        return $ic->groupBy("p.poblation")->get();
    }

    public function getPoblationBase($indicatorId, $action)
    {
        $items = SystemParameter::getRelationTable('positiva_fgn_management_indicator_coverage', "SP");
        $query = DB::table(DB::raw("{$items}"))
            ->leftJoin("wg_positiva_fgn_management_indicator_coverage_poblation AS P", function ($join) use ($indicatorId, $action) {
                $join->on("SP.value", "=", "P.poblation");
                if ($indicatorId) {
                    $join->where("P.indicator_coverage_id", "=", $indicatorId);
                } else {
                    $join->whereNull("P.id");
                }
                $join->where("P.type", "=", $action);
            });

        $query->select(
            "P.id",
            "SP.item",
            "SP.value as value"
        );

        if ($action == "execution") {
            $query->leftJoin("wg_positiva_fgn_management_indicator_coverage_poblation AS programing", function ($join) use ($indicatorId) {
                $join->on("SP.value", "=", "programing.poblation");
                $join->where("programing.indicator_coverage_id", "=", $indicatorId);
                $join->where("programing.type", "=", "programming");
            });
            $query->addSelect(DB::raw("COALESCE(programing.value, 0) as `call`"));
            $query->addSelect(DB::raw("SUM( IF (P.activity_state = 'AS001' OR P.activity_state = 'AS002', COALESCE(P.value, 0), 0) ) as allAssistant"));
        } else {
            $query->addSelect(DB::raw("COALESCE(P.value, 0) as `call`"));
            $query->addSelect(DB::raw("null as asistants"));
        }

        return $query->groupBy("SP.value")->get();
    }

    public function insertLog($old, $new, $id)
    {
        $old = SystemParameter::whereGroup("positiva_fgn_gestpos_advice_type")->whereValue($old)->first();
        $new = SystemParameter::whereGroup("positiva_fgn_gestpos_advice_type")->whereValue($new)->first();

        $description = empty($old) ? "Tipo inicial" : "Se modificó [{$old->item}] por [{$new->item}].";

        DB::table("wg_positiva_fgn_management_indicator_log")->insert([
            "management_indicador_id" => $id,
            "advice_type" => $new->value,
            "description" => $description,
            "created_by" => Auth::getUser()->id,
            "created_at" => Carbon::now(),
        ]);
    }

    /**
     * @todo Review consult of stats
     *
     * @param $result
     * @param $criteria
     * @return mixed
     */
    public function axisStats($result, $criteria)
    {

        $managementCompliance = DB::table("wg_positiva_fgn_management_indicator_compliance as mic")
            ->join("wg_positiva_fgn_management_indicator_relation as mir", "mic.indicator_relation_id", "=", "mir.id")
            ->join("wg_positiva_fgn_activity_indicator_sectional_consultant_relation as aiscr", "mir.sectional_consultant_relation_id", "=", "aiscr.id")
            ->join("wg_positiva_fgn_activity_indicator_sectional_relation as aisr", "aiscr.sectional_relation_id", "=", "aisr.id")
            ->join(DB::raw("wg_positiva_fgn_activity_config as task"), function ($join) {
                $join->on("aiscr.activity_config_id", "=", "task.id");
                $join->on("mic.activity_gestpos_id", "=", "task.gestpos_task_id");
            })
            ->join("wg_positiva_fgn_activity as a", "task.fgn_activity_id", "=", "a.id")
            ->leftJoin(DB::raw("wg_positiva_fgn_activity_config_subtask as subtask"), function ($join) {
                $join->on("task.id", "=", "subtask.activity_config_id");
                $join->on("mic.activity_gestpos_id", "=", "subtask.gestpos_subtask_id");
            })
            ->select(
                DB::raw(
                    "SUM(hour_programmed) as hour_programmed"
                ),
                DB::raw(
                    "SUM(programmed) as programmed"
                ),
                DB::raw(
                    "SUM(hour_executed) as hour_executed"
                ),
                DB::raw(
                    "SUM(executed) as executed"
                )
            )
            ->where("period", $criteria->period)
            ->where("config_id", $criteria->config)
            // ->where("sectional_id", $criteria->sectionalId)
            ->where("consultant_id", $criteria->consultantId)
            ->groupBy("period")
            // ->groupBy("sectional_id", "period")
            ->first();

        $managementCoverage = DB::table("wg_positiva_fgn_management_indicator_coverage as mic")
            ->join("wg_positiva_fgn_management_indicator_relation as mir", "mic.indicator_relation_id", "=", "mir.id")
            ->join("wg_positiva_fgn_activity_indicator_sectional_consultant_relation as aiscr", "mir.sectional_consultant_relation_id", "=", "aiscr.id")
            ->join("wg_positiva_fgn_activity_indicator_sectional_relation as aisr", "aiscr.sectional_relation_id", "=", "aisr.id")
            ->join(DB::raw("wg_positiva_fgn_activity_config as task"), function ($join) {
                $join->on("aiscr.activity_config_id", "=", "task.id");
                $join->on("mic.activity_gestpos_id", "=", "task.gestpos_task_id");
            })
            ->join("wg_positiva_fgn_activity as a", "task.fgn_activity_id", "=", "a.id")
            ->leftJoin(DB::raw("wg_positiva_fgn_activity_config_subtask as subtask"), function ($join) {
                $join->on("task.id", "=", "subtask.activity_config_id");
                $join->on("mic.activity_gestpos_id", "=", "subtask.gestpos_subtask_id");
            })
            ->select(
                DB::raw("SUM(`call`) as `call`"),
                DB::raw("SUM(assistants) as assistants")
            )
            ->where("period", $criteria->period)
            ->where("config_id", $criteria->config)
            // ->where("sectional_id", $criteria->sectionalId)
            ->where("consultant_id", $criteria->consultantId)
            ->groupBy("period")
            // ->groupBy("sectional_id", "period")
            ->first();

        $consultantHours = ConsultantModel::where("wg_positiva_fgn_consultant.id", $criteria->consultantId)
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_workday')), function ($join) {
                $join->on('wg_positiva_fgn_consultant.working_day', '=', 'positiva_fgn_consultant_workday.value');
            })
            ->select("positiva_fgn_consultant_workday.code")
            ->first();

        $columnMonth = $this->getMonthColumn($criteria->period);
        $sqlActivities = DB::table('wg_positiva_fgn_activity_indicator_sectional_consultant_relation as aiscr')
            ->join('wg_positiva_fgn_activity_indicator_sectional_consultant as aisc', 'aisc.consultant_relation_id', '=', 'aiscr.id')
            ->join('wg_positiva_fgn_activity_indicator_sectional_relation as aisr', 'aisr.id', '=', 'aiscr.sectional_relation_id')
            ->join("wg_positiva_fgn_activity as afgn",  "aisr.fgn_activity_id", "=", "afgn.id")
            ->join("wg_positiva_fgn_activity_indicator as ai",  "aisc.activity_indicator_id", "=", "ai.id")
            ->where("consultant_id", $criteria->consultantId)
            ->whereRaw("{$columnMonth} > 0")
            ->selectRaw("SUM({$columnMonth}) as goal")
            // ->where("sectional_id", $criteria->sectionalId)
            ->where("config_id", $criteria->config);

        $pendingActivitiesCom = (clone $sqlActivities)->where("ai.type", "T002")->value("goal");
        $pendingActivitiesCov = (clone $sqlActivities)->where("ai.type", "T001")->value("goal");

        if ($criteria->action == "programming") {
            $currentHours = $managementCompliance ? (int)$managementCompliance->hour_programmed : 0;
            $currentActivitiesCom = $managementCompliance ? (int)$managementCompliance->programmed : 0;
            $currentActivitiesCov = $managementCoverage ? (int)$managementCoverage->call : 0;
        } else {
            $currentHours = $managementCompliance ? (int)$managementCompliance->hour_executed : 0;
            $currentActivitiesCom = $managementCompliance ? (int)$managementCompliance->executed : 0;
            $currentActivitiesCov = $managementCoverage ? (int)$managementCoverage->assistants : 0;
        }

        $pendingHours = $consultantHours ? $consultantHours->code : 0;
        $hourPercentage = $pendingHours > 0 ? number_format(($currentHours / $pendingHours) * 100) : 0;
        $activityPercentageCom = $pendingActivitiesCom > 0 ? number_format(($currentActivitiesCom / $pendingActivitiesCom) * 100) : 0;
        $activityPercentageCov = $pendingActivitiesCov > 0 ? number_format(($currentActivitiesCov / $pendingActivitiesCov) * 100) : 0;

        $result['axisStats'] = [
            "currentHours" => $currentHours,
            "pendingHours" => $pendingHours,
            "hourPercentage" => $hourPercentage,
            "currentActivitiesCom" => $currentActivitiesCom,
            "pendingActivitiesCom" => $pendingActivitiesCom,
            "activityPercentageCom" => $activityPercentageCom,
            "currentActivitiesCov" => $currentActivitiesCov,
            "pendingActivitiesCov" => $pendingActivitiesCov,
            "activityPercentageCov" => $activityPercentageCov
        ];

        return $result;
    }

    public function getMonthColumn($period)
    {
        $month = substr((string) $period, -2);
        switch ($month) {
            case "01":
                return 'jan';
                break;
            case "02":
                return 'feb';
                break;
            case "03":
                return 'mar';
                break;
            case "04":
                return 'apr';
                break;
            case "05":
                return 'may';
                break;
            case "06":
                return 'jun';
                break;
            case "07":
                return 'jul';
                break;
            case "08":
                return 'aug';
                break;
            case "09":
                return 'sep';
                break;
            case "10":
                return 'oct';
                break;
            case "11":
                return 'nov';
                break;
            case "12":
                return 'dec';
                break;
        }

        return null;
    }
}
