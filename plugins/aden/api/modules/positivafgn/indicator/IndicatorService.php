<?php

namespace AdeN\Api\Modules\PositivaFgn\Indicator;

use AdeN\Api\Classes\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Wgroup\SystemParameter\SystemParameter;

class IndicatorService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getConsolidatedData()
    {
        DB::table("wg_positiva_fgn_consolidated_indicators")->delete();

        DB::statement("INSERT INTO wg_positiva_fgn_consolidated_indicators
            (period, regional_id, sectional_id, axis, `action`, fgn_activity_id, consultant_id,
            programmed, hour_programmed, executed, hour_executed, `call`, assistants, provides_compliance, provides_coverage,
            meta_compliance_consultant, meta_coverage_consultant, meta_compliance, meta_coverage, `group`,
            gestpos_activity_id, gestpos_task_id, total_programmed, total_executed, total_hour_programmed,
            total_hour_executed, total_call, total_assistants, config_id)
            select
                ind.period as period,
                ind.regional_id as regional_id,
                ind.sectional_id as sectional_id,
                wg_positiva_fgn_activity.axis,
                wg_positiva_fgn_activity.action,
                wg_positiva_fgn_activity.id as fgn_activity_id,
                consultant_id,
                ind.programmed,
                ind.hour_programmed,
                ind.executed,
                ind.hour_executed,
                ind.call,
                ind.assistants,
                indicator.coverage as provides_coverage,
                indicator.compliance as provides_compliance,
                ind.meta_compliance_consultant as meta_compliance_consultant,
                ind.meta_coverage_consultant as meta_coverage_consultant,
                ind.meta_compliance as meta_compliance,
                ind.meta_coverage as meta_coverage,
                `group`,
                gestpos_activity_id,
                gestpos_task_id,
                ind.total_programmed,
                ind.total_executed,
                ind.total_hour_programmed,
                ind.total_hour_executed,
                ind.total_call,
                ind.total_assistants, ind.config_id
            from
                `wg_positiva_fgn_activity`
                inner join (
                    SELECT
                        `value` COLLATE utf8_general_ci AS `value`
                    FROM
                        `system_parameters`
                    WHERE
                        `namespace` = 'wgroup'
                        AND `group` = 'positiva_fgn_activity_axis'
                ) axis on `wg_positiva_fgn_activity`.`axis` = `axis`.`value`
                inner join(
                    SELECT
                        MAX(IF(type='T001', 1, 0)) as coverage,
                        MAX(IF(type='T002', 1, 0)) as compliance,
                        fgn_activity_id
                    FROM
                        wg_positiva_fgn_activity_indicator
                    GROUP BY
                        fgn_activity_id
                ) indicator ON wg_positiva_fgn_activity.id = indicator.fgn_activity_id
                inner join (
                    select fgn_activity_id, period, regional_id, sectional_id, axis, consultant_id,
                        gestpos_activity_id, gestpos_task_id, config_id,
                        sum(programmed) as programmed, sum(hour_programmed) as hour_programmed,
                        sum(executed) as executed, sum(hour_executed) as hour_executed,
                        sum(`call`) as `call`, sum(assistants) as assistants,
                        sum(meta_compliance_consultant) as meta_compliance_consultant,
                        sum(meta_coverage_consultant) as meta_coverage_consultant,
                        sum(meta_compliance) as meta_compliance,
                        sum(meta_coverage) as meta_coverage,
                        sum(total_programmed) as total_programmed,
                        sum(total_executed) as total_executed,
                        sum(total_hour_programmed) as total_hour_programmed,
                        sum(total_hour_executed) as total_hour_executed,
                        sum(total_call) as total_call,
                        sum(total_assistants) AS total_assistants
                    from (
                         select
                             sr.fgn_activity_id, ir.period, sr.regional_id, sr.sectional_id, afgn.axis, scr.consultant_id,
                             gestpos_activity_id, gestpos_task_id, ac.id as config_id,
                             SUM(
                                 IF (config_subtask.id IS NOT NULL,
                                     IF(config_subtask.provides_compliance = 1, icom.programmed, 0),
                                     IF(ac.provides_compliance = 1, icom.programmed, 0)
                                 )
                             ) as programmed,
                             SUM(
                                 IF(config_subtask.id IS NOT NULL,
                                    IF(config_subtask.provides_compliance = 1, icom.hour_programmed, 0),
                                    IF(ac.provides_compliance = 1, icom.hour_programmed, 0)
                                 )
                             ) as hour_programmed,
                             SUM(
                                 IF(config_subtask.id IS NOT NULL,
                                    IF(config_subtask.provides_compliance = 1, icom.executed, 0),
                                    IF(ac.provides_compliance = 1, icom.executed, 0)
                                 )
                             ) as executed,
                             SUM(
                                 IF(config_subtask.id IS NOT NULL,
                                    IF(config_subtask.provides_compliance = 1, icom.hour_executed, 0),
                                    IF(ac.provides_compliance = 1, icom.hour_executed, 0)
                                 )
                             ) as hour_executed,
                             null as `call`, null assistants,
                             SUM(programmed) AS total_programmed,
                             SUM(executed) AS total_executed,
                             SUM(hour_programmed) AS total_hour_programmed,
                             SUM(hour_executed) AS total_hour_executed,
                             NULL AS total_call,
                             NULL AS total_assistants,
                             CASE
                                 WHEN RIGHT(ir.period, 2) = '01' THEN IF(sc.jan = 'block', 0, sc.jan)
                                 WHEN RIGHT(ir.period, 2) = '02' THEN IF(sc.feb = 'block', 0, sc.feb)
                                 WHEN RIGHT(ir.period, 2) = '03' THEN IF(sc.mar = 'block', 0, sc.mar)
                                 WHEN RIGHT(ir.period, 2) = '04' THEN IF(sc.apr = 'block', 0, sc.apr)
                                 WHEN RIGHT(ir.period, 2) = '05' THEN IF(sc.may = 'block', 0, sc.may)
                                 WHEN RIGHT(ir.period, 2) = '06' THEN IF(sc.jun = 'block', 0, sc.jun)
                                 WHEN RIGHT(ir.period, 2) = '07' THEN IF(sc.jul = 'block', 0, sc.jul)
                                 WHEN RIGHT(ir.period, 2) = '08' THEN IF(sc.aug = 'block', 0, sc.aug)
                                 WHEN RIGHT(ir.period, 2) = '09' THEN IF(sc.sep = 'block', 0, sc.sep)
                                 WHEN RIGHT(ir.period, 2) = '10' THEN IF(sc.oct = 'block', 0, sc.oct)
                                 WHEN RIGHT(ir.period, 2) = '11' THEN IF(sc.nov = 'block', 0, sc.nov)
                                 WHEN RIGHT(ir.period, 2) = '12' THEN IF(sc.dec = 'block', 0, sc.dec)
                             END AS meta_compliance_consultant,
                             0 as meta_coverage_consultant,
                             CASE
                                 WHEN RIGHT(ir.period, 2) = '01' THEN IF(ais.jan = 'block', 0, ais.jan)
                                 WHEN RIGHT(ir.period, 2) = '02' THEN IF(ais.feb = 'block', 0, ais.feb)
                                 WHEN RIGHT(ir.period, 2) = '03' THEN IF(ais.mar = 'block', 0, ais.mar)
                                 WHEN RIGHT(ir.period, 2) = '04' THEN IF(ais.apr = 'block', 0, ais.apr)
                                 WHEN RIGHT(ir.period, 2) = '05' THEN IF(ais.may = 'block', 0, ais.may)
                                 WHEN RIGHT(ir.period, 2) = '06' THEN IF(ais.jun = 'block', 0, ais.jun)
                                 WHEN RIGHT(ir.period, 2) = '07' THEN IF(ais.jul = 'block', 0, ais.jul)
                                 WHEN RIGHT(ir.period, 2) = '08' THEN IF(ais.aug = 'block', 0, ais.aug)
                                 WHEN RIGHT(ir.period, 2) = '09' THEN IF(ais.sep = 'block', 0, ais.sep)
                                 WHEN RIGHT(ir.period, 2) = '10' THEN IF(ais.oct = 'block', 0, ais.oct)
                                 WHEN RIGHT(ir.period, 2) = '11' THEN IF(ais.nov = 'block', 0, ais.nov)
                                 WHEN RIGHT(ir.period, 2) = '12' THEN IF(ais.dec = 'block', 0, ais.dec)
                            END AS meta_compliance,
                            0 as meta_coverage
                        from wg_positiva_fgn_activity afgn
                        join wg_positiva_fgn_activity_config ac on ac.fgn_activity_id = afgn.id
                        join wg_positiva_fgn_activity_indicator ai on ai.fgn_activity_id = afgn.id and ai.type = 'T002'
                        join wg_positiva_fgn_activity_indicator_sectional_relation sr on sr.fgn_activity_id = afgn.id
                        join wg_positiva_fgn_activity_indicator_sectional AS ais ON ais.sectional_relation_id = sr.id AND ais.activity_indicator_id = ai.id
                        join wg_positiva_fgn_activity_indicator_sectional_consultant_relation scr on scr.sectional_relation_id = sr.id AND scr.activity_config_id = ac.id
                        join wg_positiva_fgn_activity_indicator_sectional_consultant sc on sc.consultant_relation_id = scr.id and sc.activity_indicator_id = ai.id
                        join wg_positiva_fgn_management_indicator_relation ir on ir.sectional_consultant_relation_id = scr.id
                        join wg_positiva_fgn_management_indicator_compliance icom on icom.indicator_relation_id = ir.id
                        join wg_positiva_fgn_gestpos agest on agest.id = icom.activity_gestpos_id
                        left join wg_positiva_fgn_activity_config_subtask config_subtask on
                            config_subtask.gestpos_subtask_id = agest.id and
                            config_subtask.activity_config_id = ac.id
                        GROUP BY
                          ir.period, sr.fgn_activity_id, sr.regional_id, sr.sectional_id, afgn.axis, scr.consultant_id,
                          gestpos_activity_id, gestpos_task_id, ac.id

                        union

                         select
                             sr.fgn_activity_id, ir.period, sr.regional_id, sr.sectional_id, afgn.axis, scr.consultant_id,
                             gestpos_activity_id, gestpos_task_id, ac.id as config_id,
                             null as programmed,
                             null as hour_programmed,
                             null executed,
                             null hour_executed,
                             SUM(
                                 IF(config_subtask.id IS NOT NULL,
                                    IF(config_subtask.provides_coverage = 1, icov.`call`, 0),
                                    IF(ac.provides_coverage = 1, icov.`call`, 0)
                                 )
                             ) as `call`,
                             SUM(
                                 IF(config_subtask.id IS NOT NULL,
                                    IF(config_subtask.provides_coverage = 1, icov.assistants, 0),
                                    IF(ac.provides_coverage = 1, icov.assistants, 0)
                                 )
                             ) as assistants,
                             NULL AS total_programmed,
                             NULL AS total_executed,
                             NULL AS total_hour_programmed,
                             NULL AS total_hour_executed,
                             SUM(`call`) AS total_call,
                             SUM(assistants) AS total_assistants,
                             0 AS meta_compliance_consultant,
                             CASE
                                 WHEN RIGHT(ir.period, 2) = '01' THEN IF(sc.jan = 'block', 0, sc.jan)
                                 WHEN RIGHT(ir.period, 2) = '02' THEN IF(sc.feb = 'block', 0, sc.feb)
                                 WHEN RIGHT(ir.period, 2) = '03' THEN IF(sc.mar = 'block', 0, sc.mar)
                                 WHEN RIGHT(ir.period, 2) = '04' THEN IF(sc.apr = 'block', 0, sc.apr)
                                 WHEN RIGHT(ir.period, 2) = '05' THEN IF(sc.may = 'block', 0, sc.may)
                                 WHEN RIGHT(ir.period, 2) = '06' THEN IF(sc.jun = 'block', 0, sc.jun)
                                 WHEN RIGHT(ir.period, 2) = '07' THEN IF(sc.jul = 'block', 0, sc.jul)
                                 WHEN RIGHT(ir.period, 2) = '08' THEN IF(sc.aug = 'block', 0, sc.aug)
                                 WHEN RIGHT(ir.period, 2) = '09' THEN IF(sc.sep = 'block', 0, sc.sep)
                                 WHEN RIGHT(ir.period, 2) = '10' THEN IF(sc.oct = 'block', 0, sc.oct)
                                 WHEN RIGHT(ir.period, 2) = '11' THEN IF(sc.nov = 'block', 0, sc.nov)
                                 WHEN RIGHT(ir.period, 2) = '12' THEN IF(sc.dec = 'block', 0, sc.dec)
                             END AS meta_coverage_consultant,
                             0 AS meta_compliance,
                             CASE
                                 WHEN RIGHT(ir.period, 2) = '01' THEN IF(ais.jan = 'block', 0, ais.jan)
                                 WHEN RIGHT(ir.period, 2) = '02' THEN IF(ais.feb = 'block', 0, ais.feb)
                                 WHEN RIGHT(ir.period, 2) = '03' THEN IF(ais.mar = 'block', 0, ais.mar)
                                 WHEN RIGHT(ir.period, 2) = '04' THEN IF(ais.apr = 'block', 0, ais.apr)
                                 WHEN RIGHT(ir.period, 2) = '05' THEN IF(ais.may = 'block', 0, ais.may)
                                 WHEN RIGHT(ir.period, 2) = '06' THEN IF(ais.jun = 'block', 0, ais.jun)
                                 WHEN RIGHT(ir.period, 2) = '07' THEN IF(ais.jul = 'block', 0, ais.jul)
                                 WHEN RIGHT(ir.period, 2) = '08' THEN IF(ais.aug = 'block', 0, ais.aug)
                                 WHEN RIGHT(ir.period, 2) = '09' THEN IF(ais.sep = 'block', 0, ais.sep)
                                 WHEN RIGHT(ir.period, 2) = '10' THEN IF(ais.oct = 'block', 0, ais.oct)
                                 WHEN RIGHT(ir.period, 2) = '11' THEN IF(ais.nov = 'block', 0, ais.nov)
                                 WHEN RIGHT(ir.period, 2) = '12' THEN IF(ais.dec = 'block', 0, ais.dec)
                           END AS meta_coverage
                        from wg_positiva_fgn_activity afgn
                        join wg_positiva_fgn_activity_config ac on ac.fgn_activity_id = afgn.id
                        join wg_positiva_fgn_activity_indicator ai on ai.fgn_activity_id = afgn.id and ai.type = 'T001'
                        join wg_positiva_fgn_activity_indicator_sectional_relation sr on sr.fgn_activity_id = afgn.id
                        join wg_positiva_fgn_activity_indicator_sectional AS ais ON ais.sectional_relation_id = sr.id AND ais.activity_indicator_id = ai.id
                        join wg_positiva_fgn_activity_indicator_sectional_consultant_relation scr on scr.sectional_relation_id = sr.id AND scr.activity_config_id = ac.id
                        join wg_positiva_fgn_activity_indicator_sectional_consultant sc on sc.consultant_relation_id = scr.id and sc.activity_indicator_id = ai.id
                        join wg_positiva_fgn_management_indicator_relation ir on ir.sectional_consultant_relation_id = scr.id
                        join wg_positiva_fgn_management_indicator_coverage icov on icov.indicator_relation_id = ir.id
                        join wg_positiva_fgn_gestpos agest on agest.id = icov.activity_gestpos_id
                        left join wg_positiva_fgn_activity_config_subtask config_subtask on
                            config_subtask.gestpos_subtask_id = agest.id and
                            config_subtask.activity_config_id = ac.id
                        GROUP BY
                          sr.fgn_activity_id, ir.period, sr.regional_id, sr.sectional_id, afgn.axis, scr.consultant_id,
                          gestpos_activity_id, gestpos_task_id, ac.id
                    ) as allindicator
                    group by
                      period, regional_id, sectional_id, axis, fgn_activity_id, period, consultant_id, gestpos_activity_id, gestpos_task_id, config_id
                ) ind on ind.fgn_activity_id = `wg_positiva_fgn_activity`.`id`
            GROUP BY
                id, axis, action, period, regional_id, sectional_id, consultant_id, gestpos_activity_id, gestpos_task_id, config_id
        ");

    }

    /**
     * @todo Pendiente definir como quedaría el indicador
     *
     * @param $criteria
     * @return array
     */
    public function getGenreIndicatorChart($criteria)
    {
        $data = DB::table('wg_positiva_fgn_consolidated_indicators')
            ->join('wg_positiva_fgn_consultant', 'wg_positiva_fgn_consultant.id', '=', 'wg_positiva_fgn_consolidated_indicators.consultant_id')
            ->wherePeriod($criteria->period)
            ->whereSectionalId($criteria->sectional)
            ->whereAxis($criteria->axis)
            ->where('wg_positiva_fgn_consultant.user_id', $criteria->userId)
            ->select([
                DB::raw('SUM(programmed) as programmed'),
                DB::raw('SUM(executed) as executed'),

//                DB::raw("SUM('executed') / SUM(programmed) ")
            ])->first();

        // porcentaje (suma programada) / (sum ejecutada)

        $result[] = (object) ["label" => "Cumple", "value" => $data->programmed];
        $result[] = (object) ["label" => "No Comple", "value" => $data->executed];
        $total = $data->programmed + $data->executed;

        return [$this->chart->getChartPie($result), $total];
    }

    public function getDataChartBar($criteria)
    {

        $data = DB::table('wg_positiva_fgn_consolidated_indicators')
            ->join('wg_positiva_fgn_consultant', 'wg_positiva_fgn_consultant.id', '=', 'wg_positiva_fgn_consolidated_indicators.consultant_id')
            ->join('wg_positiva_fgn_sectional', 'wg_positiva_fgn_sectional.id', '=', 'wg_positiva_fgn_consolidated_indicators.sectional_id')
            ->wherePeriod($criteria->period)
            ->whereSectionalId($criteria->sectional)
            ->whereAxis($criteria->axis)
            ->where('wg_positiva_fgn_consultant.user_id', $criteria->userId)
            ->select(
                DB::raw("'Cumplimiento por Indicador' AS label"),
                DB::raw("ROUND(SUM(executed) / SUM(programmed) * 100, 0) AS compliance"),
                DB::raw("ROUND(SUM(assistants) / SUM(`call`) * 100, 0) AS coverage")
            )
            ->groupBy('period', 'axis')
            ->get();

        $config = [
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Cumplimiento', 'field' => 'compliance', 'color' => '#EEA236'],
                ['label' => 'Cobertura', 'field' => 'coverage', 'color' => '#5CB85C'],
            ],
        ];

        return $this->chart->getChartBar($data, $config);
    }

    public function getAllIndicatorsByActivity($criteria)
    {
        $results = DB::table('wg_positiva_fgn_consolidated_indicators')
            ->join('wg_positiva_fgn_consultant', 'wg_positiva_fgn_consultant.id', '=', 'wg_positiva_fgn_consolidated_indicators.consultant_id')
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_action', 'action')), function ($join) {
                $join->on('wg_positiva_fgn_consolidated_indicators.action', '=', 'action.value');
            })
            ->join('wg_positiva_fgn_activity', 'wg_positiva_fgn_activity.id', '=', 'wg_positiva_fgn_consolidated_indicators.fgn_activity_id')
            ->wherePeriod($criteria->period)
            ->whereSectionalId($criteria->sectional)
            ->where('wg_positiva_fgn_consolidated_indicators.axis', $criteria->axis)
            ->where('wg_positiva_fgn_consultant.user_id', $criteria->userId)
            ->select(
                'action.item as action',
                'wg_positiva_fgn_activity.name as activity',
                DB::raw("ROUND(SUM(executed) / SUM(programmed) * 100, 0) AS compliance"),
                DB::raw("ROUND(SUM(assistants) / SUM(`call`) * 100, 0) AS coverage"),
                'provides_compliance',
                'provides_coverage'
            )
            ->groupBy('wg_positiva_fgn_consolidated_indicators.action', 'wg_positiva_fgn_consolidated_indicators.fgn_activity_id')
            ->get();

        $data = [];

        // organizar las acciones (pestañas)
        foreach ($results as $row) {
            $data[$row->action]["action"] = $row->action;
            $data[$row->action]["activities"][] = $row;
        }

        // organizar las actividades
        foreach ($data as $key => $action) {
            $groupActivities = [];
            foreach ($action["activities"] as $activity) {
                $groupActivities[$activity->activity]["activity"] = $activity->activity;
                $groupActivities[$activity->activity]["indicators"][] = $activity;
            }
            $data[$key]["activities"] = $groupActivities;
        }

        // organizar los indicadores
        foreach ($data as $key1 => $actions) {
            foreach ($actions["activities"] as $key2 => $activity) {
                $groupIndicators = [];
                foreach ($activity["indicators"] as $indicator) {

                    if ($indicator->provides_compliance > 0) {
                        $typeIndicator = 'Cumplimiento';
                        $values = [
                            "percentage" => $indicator->compliance,
                        ];

                        $groupIndicators[$typeIndicator]['indicator'] = $typeIndicator;
                        $groupIndicators[$typeIndicator]['values'] = $values;
                    }

                    if ($indicator->provides_coverage > 0) {
                        $typeIndicator = 'Cobertura';
                        $values = [
                            "percentage" => $indicator->coverage,
                        ];

                        $groupIndicators[$typeIndicator]['indicator'] = $typeIndicator;
                        $groupIndicators[$typeIndicator]['values'] = $values;
                    }
                }

                $data[$key1]["activities"][$key2]["indicators"] = $groupIndicators;
            }
        }

        Log::debug('data', [$data]);

        return array_values($data);

    }

    /*
     *Actividades PTA
     */

    public function getActivitiesPTACompliance($criteria)
    {
        $regionals = $criteria->regionals ?? [];
        $sectionals = $criteria->sectionals ?? [];
        $periods = $criteria->periods ?? [];
        $groups = $criteria->groups ?? [];
        $axis = $criteria->axis ?? [];

        if (empty($periods)) {
            $goalCompliance = 'afgn.goal_compliance';
            $goalCoverage = 'afgn.goal_coverage';
        } else {
            $goalCompliance = $this->getColumnGoalBySectional($periods, "ais");
            $goalCoverage   = $goalCompliance;
        }

        $subqueryProvide = $this->getQueryActivityProvideIndicator();
        $subqueryExecuted = $this->getQueryExecutedActivitiesByPeriods($periods);

        $subquery = DB::table('wg_positiva_fgn_activity_indicator AS ai')
            ->join('wg_positiva_fgn_activity_indicator_sectional_relation as isr', 'isr.fgn_activity_id', '=', 'ai.fgn_activity_id')
            ->join('wg_positiva_fgn_activity_indicator_sectional as ais', function($join) {
                $join->on('ais.sectional_relation_id', 'isr.id');
                $join->on('ais.activity_indicator_id', 'ai.id');
            })
            ->join("wg_positiva_fgn_activity as afgn", "afgn.id", "=", "isr.fgn_activity_id")
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis', 'sp')), function ($join) {
                $join->on('afgn.axis', '=', 'sp.value');
            })
            ->join(DB::raw("({$subqueryProvide->toSql()}) as provide"), function($join) {
                $join->on('provide.fgn_activity_id', 'afgn.id');
            })
            ->leftjoin(DB::raw("({$subqueryExecuted->toSql()}) AS ex"), function($join) {
                $join->on('ex.fgn_activity_id', '=', 'isr.fgn_activity_id');
                $join->on('ex.regional_id',  '=', 'isr.regional_id');
                $join->on('ex.sectional_id', '=', 'isr.sectional_id');
                $join->on('ex.axis', '=', 'afgn.axis');
                $join->on('ex.group', '=', 'afgn.group');
            })
            ->when($regionals, function ($query) use ($regionals) {
                $query->whereIn('isr.regional_id', $regionals);
            })
            ->when($sectionals, function ($query) use ($sectionals) {
                $query->whereIn('isr.sectional_id', $sectionals);
            })
            ->when($groups, function ($query) use ($groups) {
                $query->whereIn('afgn.group', $groups);
            })
            ->when($axis, function ($query) use ($axis) {
                $query->whereIn('afgn.axis', $axis);
            })
            ->where(function($query) {
                $query->where("provide.provideCompliance", true);
                $query->orWhere("provide.provideCoverage", true);
            })
            ->groupBy('afgn.id')
            ->havingRaw("(SUM(IF(ai.type = 'T002', ($goalCompliance), 0)) > 0 OR SUM(IF(ai.type = 'T001', $goalCoverage, 0)) > 0)")
            ->select(
                'sp.value as id',
                'sp.item as axis',
                DB::raw("SUM(IF(ai.type = 'T002', $goalCompliance, 0)) as goalCompliance"),
                DB::raw("SUM(IF(ai.type = 'T002', ex.executed, 0)) as countActivities"),
                DB::raw("SUM(IF(ai.type = 'T001', $goalCoverage, 0)) as goalCoverage"),
                DB::raw("SUM(IF(ai.type = 'T001', ex.assistants, 0)) as countPopulation")
            );

        return DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('d.id')
            ->select(
                'd.id', 'd.axis',
                DB::raw("SUM(goalCompliance) as goalCompliance"),
                DB::raw("SUM(countActivities) as countActivities"),
                DB::raw("coalesce(round( (sum(countActivities) / sum(goalCompliance)) * 100, 2) , 0) as percentCompliance"),
                DB::raw("SUM(goalCoverage) as goalCoverage"),
                DB::raw("SUM(countPopulation) as countPopulation"),
                DB::raw("coalesce(round( (sum(countPopulation) / sum(goalCoverage)) *100, 2) , 0) as percentCoverage")
            );
    }


    public function getActivitiesPTAComplianceDetails($criteria, $axis)
    {
        $regionals = $criteria->regionals ?? [];
        $sectionals = $criteria->sectionals ?? [];
        $periods = $criteria->periods ?? [];
        $groups = $criteria->groups ?? [];

        if (empty($periods)) {
            $goalCompliance = 'afgn.goal_compliance';
            $goalCoverage = 'afgn.goal_coverage';
        } else {
            $goalCompliance = $this->getColumnGoalBySectional($periods, "ais");
            $goalCoverage   = $goalCompliance;
        }

        $subqueryProvide = $this->getQueryActivityProvideIndicator();
        $subqueryExecuted = $this->getQueryExecutedActivitiesByPeriods($periods);

        return DB::table('wg_positiva_fgn_activity_indicator AS ai')
            ->join('wg_positiva_fgn_activity_indicator_sectional_relation as isr', 'isr.fgn_activity_id', '=', 'ai.fgn_activity_id')
            ->join('wg_positiva_fgn_activity_indicator_sectional as ais', function($join) {
                $join->on('ais.sectional_relation_id', 'isr.id');
                $join->on('ais.activity_indicator_id', 'ai.id');
            })
            ->join("wg_positiva_fgn_activity as afgn", "afgn.id", "=", "isr.fgn_activity_id")
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis', 'sp')), function ($join) {
                $join->on('afgn.axis', '=', 'sp.value');
            })
            ->join(DB::raw("({$subqueryProvide->toSql()}) as provide"), function($join) {
                $join->on('provide.fgn_activity_id', 'afgn.id');
            })
            ->leftjoin(DB::raw("({$subqueryExecuted->toSql()}) AS ex"), function($join) {
                $join->on('ex.fgn_activity_id', '=', 'isr.fgn_activity_id');
                $join->on('ex.regional_id',  '=', 'isr.regional_id');
                $join->on('ex.sectional_id', '=', 'isr.sectional_id');
                $join->on('ex.axis', '=', 'afgn.axis');
                $join->on('ex.group', '=', 'afgn.group');
            })
            ->when($regionals, function ($query) use ($regionals) {
                $query->whereIn('isr.regional_id', $regionals);
            })
            ->when($sectionals, function ($query) use ($sectionals) {
                $query->whereIn('isr.sectional_id', $sectionals);
            })
            ->when($groups, function ($query) use ($groups) {
                $query->whereIn('afgn.group', $groups);
            })
            ->where('afgn.axis', $axis)
            ->where(function($query) {
                $query->where("provide.provideCompliance", true);
                $query->orWhere("provide.provideCoverage", true);
            })
            ->groupBy('afgn.id')
            ->havingRaw("(SUM(IF(ai.type = 'T002', ($goalCompliance), 0)) > 0 OR SUM(IF(ai.type = 'T001', $goalCoverage, 0)) > 0)")
            ->select(
                'sp.item as axis',
                'afgn.name as activity',
                DB::raw("SUM(IF(ai.type = 'T002', $goalCompliance, 0)) as goalCompliance"),
                DB::raw("SUM(IF(ai.type = 'T002', ex.executed, 0)) as countActivities"),
                DB::raw("coalesce(round((SUM(IF(ai.type = 'T002', ex.executed, 0)) / SUM(IF(ai.type = 'T002', $goalCompliance, 0)) * 100 ), 2), 0) as percentCompliance"),
                DB::raw("SUM(IF(ai.type = 'T001', $goalCoverage, 0)) as goalCoverage"),
                DB::raw("SUM(IF(ai.type = 'T001', ex.assistants, 0)) as population"),
                DB::raw("coalesce(round((SUM(if(ai.type = 'T001', ex.assistants, 0)) / SUM(IF(ai.type = 'T001', $goalCoverage, 0)) * 100 ), 2), 0) as percentCoverage")
            );
    }


    public function getActivitiesPTAComplianceAxis($criteria)
    {
        $regionals = $criteria->regionals ?? [];
        $sectionals = $criteria->sectionals ?? [];
        $periods = $criteria->periods ?? [];
        $groups = $criteria->groups ?? [];
        $axis = $criteria->axis ?? [];

        if (empty($periods)) {
            $goalCompliance = 'afgn.goal_compliance';
            $goalCoverage = 'afgn.goal_coverage';
        } else {
            $goalCompliance = $this->getColumnGoalBySectional($periods, "ais");
            $goalCoverage   = $goalCompliance;
        }

        $subqueryProvide = $this->getQueryActivityProvideIndicator();
        $subqueryExecuted = $this->getQueryExecutedActivitiesByPeriods($periods);

        return DB::table('wg_positiva_fgn_activity_indicator AS ai')
            ->join('wg_positiva_fgn_activity_indicator_sectional_relation as isr', 'isr.fgn_activity_id', '=', 'ai.fgn_activity_id')
            ->join('wg_positiva_fgn_activity_indicator_sectional as ais', function($join) {
                $join->on('ais.sectional_relation_id', 'isr.id');
                $join->on('ais.activity_indicator_id', 'ai.id');
            })
            ->join("wg_positiva_fgn_activity as afgn", "afgn.id", "=", "isr.fgn_activity_id")
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis', 'sp')), function ($join) {
                $join->on('afgn.axis', '=', 'sp.value');
            })
            ->join(DB::raw("({$subqueryProvide->toSql()}) as provide"), function($join) {
                $join->on('provide.fgn_activity_id', 'afgn.id');
            })
            ->leftjoin(DB::raw("({$subqueryExecuted->toSql()}) AS ex"), function($join) {
                $join->on('ex.fgn_activity_id', '=', 'isr.fgn_activity_id');
                $join->on('ex.regional_id',  '=', 'isr.regional_id');
                $join->on('ex.sectional_id', '=', 'isr.sectional_id');
                $join->on('ex.axis', '=', 'afgn.axis');
                $join->on('ex.group', '=', 'afgn.group');
            })
            ->when($regionals, function ($query) use ($regionals) {
                $query->whereIn('isr.regional_id', $regionals);
            })
            ->when($sectionals, function ($query) use ($sectionals) {
                $query->whereIn('isr.sectional_id', $sectionals);
            })
            ->when($groups, function ($query) use ($groups) {
                $query->whereIn('afgn.group', $groups);
            })
            ->when($axis, function ($query) use ($axis) {
                $query->whereIn('afgn.axis', $axis);
            })
            ->where(function($query) {
                $query->where("provide.provideCompliance", true);
                $query->orWhere("provide.provideCoverage", true);
            })
            ->groupBy('afgn.axis')
            ->havingRaw("(SUM(IF(ai.type = 'T002', ($goalCompliance), 0)) > 0 OR SUM(IF(ai.type = 'T001', $goalCoverage, 0)) > 0)")
            ->select(
                'sp.item as axis',
                DB::raw("coalesce(round((SUM(IF(ai.type = 'T002', ex.executed, 0)) / SUM(IF(ai.type = 'T002', $goalCompliance, 0)) * 100 ), 2), 0) as percentCompliance"),
                DB::raw("coalesce(round((SUM(if(ai.type = 'T001', ex.assistants, 0)) / SUM(IF(ai.type = 'T001', $goalCoverage, 0)) * 100 ), 2), 0) as percentCoverage")
            )
            ->get();
    }


    public function getActivitiesPTAComplianceExportExcel($criteria)
    {
        return $this->getActivitiesPTACompliance($criteria);
    }

    /*
     *Actividades fallidas
     */
    public function getActivitiesFailedCompliance($criteria)
    {
        $regionals = $criteria->regionals ?? [];
        $sectionals = $criteria->sectionals ?? [];
        $periods = $criteria->periods ?? [];
        $groups = $criteria->groups ?? [];
        $axis = $criteria->axis ?? [];

        return DB::table("wg_positiva_fgn_consolidated_indicators AS con")
            ->join("wg_positiva_fgn_regional AS reg", "reg.id", "=", "con.regional_id")
            ->join("wg_positiva_fgn_sectional as sec", "sec.id", "=", "con.sectional_id")
            ->join("wg_positiva_fgn_activity as afgn", "afgn.id", "=", "con.fgn_activity_id")
            ->join("wg_positiva_fgn_consultant as consu", "consu.id", "=", "con.consultant_id")
            ->join('wg_positiva_fgn_activity_indicator_sectional_relation as sr', function($join) {
                $join->on('sr.fgn_activity_id', '=', 'con.fgn_activity_id');
                $join->on('sr.regional_id', '=', 'con.regional_id');
                $join->on('sr.sectional_id', '=', 'con.sectional_id');
            })
            ->join('wg_positiva_fgn_activity_indicator_sectional_consultant_relation as scr', function($join) {
                $join->on('scr.sectional_relation_id', '=', 'sr.id');
                $join->on('scr.consultant_id', '=', 'consu.id');
            })
            ->join('wg_positiva_fgn_activity_config as ac', 'ac.id', '=', 'scr.activity_config_id')
            ->join('wg_positiva_fgn_gestpos as ages', 'ages.id', '=', 'ac.gestpos_activity_id')
            ->join('wg_positiva_fgn_management_indicator_relation as ir', 'ir.sectional_consultant_relation_id', '=', 'scr.id')

            ->join("wg_positiva_fgn_management_indicator_compliance as icom", 'icom.indicator_relation_id', '=', 'ir.id')
            ->join('wg_positiva_fgn_management_indicator_compliance_logs as icomlog', function($join) {
                $join->on('icomlog.indicator_compliance_id', '=', 'icom.id');
                $join->where('icomlog.activity_state', 'AS002');
            })
            ->join('wg_positiva_fgn_gestpos as task', 'task.id', '=', 'icom.activity_gestpos_id')

            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy', 'strategy')), function ($join) {
                $join->on('ac.strategy', '=', 'strategy.value');
            })

            ->when($regionals, function ($query) use ($regionals) {
                $query->whereIn('con.regional_id', $regionals);
            })
            ->when($sectionals, function ($query) use ($sectionals) {
                $query->whereIn('con.sectional_id', $sectionals);
            })
            ->when($periods, function ($query) use ($periods) {
                $query->whereIn(DB::raw("date_format(icomlog.date, '%Y%m')"), $periods);
            })
            ->when($groups, function ($query) use ($groups) {
                $query->whereIn('con.group', $groups);
            })
            ->when($axis, function ($query) use ($axis) {
                $query->whereIn('con.axis', $axis);
            })
            ->select(
                'afgn.name as activity',
                'strategy.item as strategy',
                'ages.name as activityGestpos',
                'task.name as task',
                DB::raw("DATE_FORMAT(icomlog.date, '%Y-%m-%d') AS date"),
                'consu.full_name as asesor',
                'reg.number as regional',
                'sec.name as sectional',
                'icomlog.observation'
            )
            ->groupBy('icomlog.id')
            ->orderBy('afgn.id', 'ages.id', 'task.id', 'icomlog.date');
    }

    public function getActivitiesFailedExportExcel($criteria)
    {
        return $this->getactivitiesfailedcompliance($criteria);
    }

    /*
     *Consolidado de indicadores
     */
    public function activitiesConsolidatedCompliance($criteria)
    {
        $regionals = $criteria->regionals ?? [];
        $sectionals = $criteria->sectionals ?? [];
        $periods = $criteria->periods ?? [];
        $groups = $criteria->groups ?? [];
        $axis = $criteria->axis ?? [];
        $typeIndicator = $criteria->typeIndicator ?? null;
        $goalAnnual = $criteria->goalAnnual ?? null;
        $provideCompliance = $criteria->provideCompliance ?? null;
        $provideCoverage = $criteria->provideCoverage ?? null;

        $typeIndicator = $typeIndicator == 'compliance' ? 'T002' : 'T001';

        if (empty($periods)) {
            $goalCompliance = 'afgn.goal_compliance';
            $goalCoverage = 'afgn.goal_coverage';
        } else {
            $goalCompliance = $this->getColumnGoalBySectional($periods, 'ais');
            $goalCoverage   = $goalCompliance;
        }

        $subqueryProvide = $this->getQueryActivityProvideIndicator();
        $subqueryExecuted = $this->getQueryExecutedActivitiesByPeriods($periods);

         return DB::table('wg_positiva_fgn_activity_indicator_sectional AS ais')
            ->join('wg_positiva_fgn_activity_indicator_sectional_relation as isr', 'isr.id', '=', 'sectional_relation_id')
            ->join("wg_positiva_fgn_activity as afgn", "afgn.id", "=", "isr.fgn_activity_id")
            ->join('wg_positiva_fgn_activity_indicator as ai', function ($join) use ($typeIndicator) {
                $join->on('ai.id', '=', 'ais.activity_indicator_id');
                $join->on('ai.fgn_activity_id', '=', 'afgn.id');
                $join->where('ai.type', $typeIndicator);
            })
            ->join(DB::raw("({$subqueryProvide->toSql()}) as provide"), function($join) {
                $join->on('provide.fgn_activity_id', 'afgn.id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis', 'sp')), function ($join) {
                $join->on('afgn.axis', '=', 'sp.value');
            })
            ->leftjoin(DB::raw("({$subqueryExecuted->toSql()}) AS ex"), function($join) {
                $join->on('ex.fgn_activity_id', '=', 'isr.fgn_activity_id');
                $join->on('ex.regional_id',  '=', 'isr.regional_id');
                $join->on('ex.sectional_id', '=', 'isr.sectional_id');
                $join->on('ex.axis', '=', 'afgn.axis');
                $join->on('ex.group', '=', 'afgn.group');
            })
            ->when($regionals, function ($query) use ($regionals) {
                $query->whereIn('isr.regional_id', $regionals);
            })
            ->when($sectionals, function ($query) use ($sectionals) {
                $query->whereIn('isr.sectional_id', $sectionals);
            })
            ->when($groups, function ($query) use ($groups) {
                $query->whereIn('afgn.group', $groups);
            })
            ->when($axis, function ($query) use ($axis) {
                $query->whereIn('afgn.axis', $axis);
            })
            ->when($goalAnnual, function ($query) use ($goalAnnual) {
                 $query->where('afgn.goal_annual', $goalAnnual);
             })
             ->when($provideCompliance || $typeIndicator == 'T002', function ($query) use ($goalCompliance) {
                 $query->where("provide.provideCompliance", true);
                 $query->havingRaw("SUM(IF(ai.type = 'T002', ($goalCompliance), 0)) > 0");
             })
             ->when($provideCoverage || $typeIndicator == 'T001', function ($query) use ($goalCoverage) {
                 $query->where("provide.provideCoverage", true);
                 $query->havingRaw("SUM(IF(ai.type = 'T001', ($goalCoverage), 0)) > 0");
             })
            ->groupBy('afgn.id')
            ->select('afgn.name as activity',
                'sp.item as axis',
                DB::raw("coalesce(round((sum(executed) / $goalCompliance * 100 ), 2), 0) as percentExecuted"),
                DB::raw('count(distinct afgn.id) as countActivities'),
                DB::raw('round(sum(ex.executed)) as executed'),
                DB::raw("round(sum($goalCompliance)) as meta_compliance"),
                DB::raw("round(sum($goalCoverage)) as meta_coverage"),
                DB::raw('sum(ex.`assistants`) as countPopulation'),
                DB::raw("coalesce(round((sum(ex.executed) / sum($goalCompliance) * 100 ), 2), 0) as percentCompliance"),
                DB::raw("coalesce(round((sum(ex.assistants) / sum($goalCoverage) * 100 ), 2), 0) as percentCoverage")
            );
    }

    public function getConsolidatedIndicatorsExportExcel($criteria)
    {
        return $this->activitiesConsolidatedCompliance($criteria);
    }

    private function getColumnGoalBySectional(array $periods, $alias = '') {
        $periodsQuery = "";
        foreach ($periods as $period) {
            $month = substr($period, 4, 2);
            switch ($month) {
                case "01": $periodsQuery .= " $alias.jan + "; break;
                case "02": $periodsQuery .= " $alias.feb + "; break;
                case "03": $periodsQuery .= " $alias.mar + "; break;
                case "04": $periodsQuery .= " $alias.apr + "; break;
                case "05": $periodsQuery .= " $alias.may + "; break;
                case "06": $periodsQuery .= " $alias.jun + "; break;
                case "07": $periodsQuery .= " $alias.jul + "; break;
                case "08": $periodsQuery .= " $alias.aug + "; break;
                case "09": $periodsQuery .= " $alias.sep + "; break;
                case "10": $periodsQuery .= " $alias.oct + "; break;
                case "11": $periodsQuery .= " $alias.nov + "; break;
                case "12": $periodsQuery .= " $alias.dec + "; break;
            }
        }

        return ' ( ' . substr($periodsQuery, 0, -2) . ' ) ';
    }

    /**
     * Obtiene el query de las actividades ejecutadas desde el consolidado.
     * @param array $periods
     * @return mixed
    */
    private function getQueryExecutedActivitiesByPeriods(array $periods){
        return DB::table("wg_positiva_fgn_consolidated_indicators AS con")
            ->when($periods, function ($join) use ($periods) {
                $periodsString = implode(',', $periods);
                $join->whereRaw("con.period in ($periodsString) ");
            })
            ->groupBy('con.fgn_activity_id', 'con.regional_id', 'con.sectional_id', 'con.axis', 'con.group')
            ->select(
                'con.fgn_activity_id', 'con.regional_id', 'con.sectional_id', 'con.axis', 'con.group',
                DB::raw('SUM(con.executed) AS executed'),
                DB::raw('SUM(con.`assistants`) AS assistants')
            );
    }


    private function getQueryActivityProvideIndicator() {
        return DB::table('wg_positiva_fgn_activity_config')
            ->groupBy('fgn_activity_id')
            ->select(
                'fgn_activity_id',
                DB::raw("sum(provides_compliance) > 0 AS provideCompliance"),
                DB::raw("sum(provides_coverage) > 0 AS provideCoverage")
            );
    }


}
