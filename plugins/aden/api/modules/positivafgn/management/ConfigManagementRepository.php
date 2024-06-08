<?php

namespace AdeN\Api\Modules\PositivaFgn\Management;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\DisabilityDiagnostic\Http\Controllers\DisabilityDiagnosticController;
use AdeN\Api\Modules\PositivaFgn\Consultant\ConsultantModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\ConfigConsultantRelationModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\Activity\ActivityModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\Config\ConfigModel;
use AdeN\Api\Modules\PositivaFgn\Management\Relations\ComplianceLogModel;
use AdeN\Api\Modules\PositivaFgn\Management\Relations\ComplianceModel;
use AdeN\Api\Modules\PositivaFgn\Management\Relations\CoverageModel;
use AdeN\Api\Modules\PositivaFgn\Management\Relations\HeaderModel;
use AdeN\Api\Modules\PositivaFgn\Management\Relations\IndicatorRelationModel;
use AdeN\Api\Modules\PositivaFgn\Management\Relations\PoblationModel;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Str;
use stdClass;
use Wgroup\SystemParameter\SystemParameter;

class ConfigManagementRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ConfigConsultantRelationModel());
        $this->service = new ConfigManagementService();
    }

    public function filterAssignment($criteria)
    {
        $this->setColumns([
            "id" => "aisr.id",
            "axis" => "positiva_fgn_activity_axis.item AS axis",
            "action" => "positiva_fgn_activity_action.item AS action",
            "activityCodeFgn" => "activityfgn.code AS activityCodeFgn",
            "activity" => "activityfgn.name AS activity",
            "strategy" => "positiva_fgn_consultant_strategy.item AS strategy",
            "activityCode" => "activitygestpos.code as activityCode",
            "activityGestpos" => "activitygestpos.name AS activityGestpos",
            "task" => "task.name AS task",
            "regional" => "wg_positiva_fgn_regional.number AS regional",
            "sectional" => "wg_positiva_fgn_sectional.name AS sectional",
            "goalCoverage" => "coverage.goal AS goalCoverage",
            "pendingCoverage" => "coverage.assignment AS pendingCoverage",
            "assignedCoverage" => DB::raw("(coverage.goal - coverage.assignment) AS assignedCoverage"),
            "goalCompliance" => "compliance.goal AS goalCompliance",
            "pendingCompliance" => "compliance.assignment AS pendingCompliance",
            "assignedCompliance" => DB::raw("(compliance.goal - compliance.assignment) AS assignedCompliance"),
            "regionalVal" => "aisr.regional_id AS regionalVal",
            "sectionalVal" => "aisr.sectional_id AS sectionalVal",
            "strategyVal" => "positiva_fgn_consultant_strategy.value AS strategyVal",
            "configId" => "activityfgn.config_id AS configId",
            "activityId" => "activityfgn.id AS activityId",
        ]);

        $indicator = DB::table("wg_positiva_fgn_activity_indicator_sectional as ais")
            ->join("wg_positiva_fgn_activity_indicator AS ai", "ais.activity_indicator_id", "=", "ai.id")
            ->select("ais.goal", "ais.assignment", "ai.type", "ais.sectional_relation_id");

        $this->parseCriteria($criteria);
        $query = $this->query()
            ->join("wg_positiva_fgn_activity_indicator_sectional_relation as aisr", function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator_sectional_consultant_relation.sectional_relation_id", "=", "aisr.id");
            })
            ->join("wg_positiva_fgn_activity_config as ac", function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator_sectional_consultant_relation.activity_config_id", "=", "ac.id");
            })
            ->join("wg_positiva_fgn_activity as activityfgn", function ($join) {
                $join->on("aisr.fgn_activity_id", "=", "activityfgn.id");
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis')), function ($join) {
                $join->on('activityfgn.axis', '=', 'positiva_fgn_activity_axis.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_action')), function ($join) {
                $join->on('activityfgn.action', '=', 'positiva_fgn_activity_action.value');
            })
            ->join('wg_positiva_fgn_regional', 'aisr.regional_id', '=', 'wg_positiva_fgn_regional.id')
            ->join('wg_positiva_fgn_sectional', 'aisr.sectional_id', '=', 'wg_positiva_fgn_sectional.id')
            ->join("wg_positiva_fgn_gestpos as activitygestpos", function ($join) {
                $join->on("ac.gestpos_activity_id", "=", "activitygestpos.id");
            })
            ->join("wg_positiva_fgn_gestpos AS task", function ($join) {
                $join->on("ac.gestpos_task_id", "=", "task.id");
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                $join->on('ac.strategy', '=', 'positiva_fgn_consultant_strategy.value');
            })
            ->leftjoin(DB::raw("({$indicator->toSql()}) as coverage"), function ($join) {
                $join->on("aisr.id", "=", "coverage.sectional_relation_id");
                $join->where("coverage.type", "=", "T001");
            })
            ->leftjoin(DB::raw("({$indicator->toSql()}) as compliance"), function ($join) {
                $join->on("aisr.id", "=", "compliance.sectional_relation_id");
                $join->where("compliance.type", "=", "T002");
            });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function activitiesProgrammingExecution($criteria, $sectional, $period, $config, $axis, $consultant, $action)
    {
        if (!$period || !$sectional) {
            return [];
        }

        $columnMonth = $this->service->getMonthColumn($period->value);
        $this->setColumns([
            "id" => "id",
            "action" => "action",
            "activity" => "activity",
            "strategy" => "strategy",
            "modality" => "modality",
            "activityCode" => "activityCode",
            "activityGestpos" => "activityGestpos",
            "task" => "task",
            "goalCompliance" => "goalCompliance",
            "programCompliance" => "programCompliance",
            "programPercentCompliance" => "programPercentCompliance",
            "goalCoverage" => "goalCoverage",
            "programCoverage" => "programCoverage",
            "programPercentCoverage" => "programPercentCoverage",
            "executedCompliance" => "executedCompliance",
            "executedPercentCompliance" => "executedPercentCompliance",
            "executedCoverage" => "executedCoverage",
            "executedPercentCoverage" => "executedPercentCoverage",
        ]);

        $this->parseCriteria($criteria);

        $indicator = DB::table("wg_positiva_fgn_activity_indicator_sectional_consultant as aisc")
            ->join("wg_positiva_fgn_activity_indicator AS ai", "aisc.activity_indicator_id", "=", "ai.id")
            ->select("consultant_relation_id", DB::raw("aisc.{$columnMonth} as goal"), "ai.type")
            ->whereRaw("aisc.{$columnMonth} > 0");

        $complianceIndicatorValues = DB::table('wg_positiva_fgn_management_indicator_compliance as icom')
            ->join('wg_positiva_fgn_management_indicator_relation as ir', 'ir.id', '=', 'icom.indicator_relation_id')
            ->join('wg_positiva_fgn_activity_indicator_sectional_consultant_relation as aiscr', 'aiscr.id', '=', 'ir.sectional_consultant_relation_id')
            ->join('wg_positiva_fgn_activity_config as task', 'task.id', '=', 'aiscr.activity_config_id')
            ->leftJoin("wg_positiva_fgn_activity_config_subtask as subtask", function ($join) {
                $join->on("task.id", "=", "subtask.activity_config_id");
                $join->on("icom.activity_gestpos_id", "=", "subtask.gestpos_subtask_id");
            })
            ->whereRaw("ir.period = {$period->value}")
            ->select(
                'ir.sectional_consultant_relation_id',
                DB::raw("SUM(programmed) as programmed"),
                DB::raw("SUM(executed) as executed")
            )
            ->groupBy("ir.id");


        $coverageIndicatorValues = DB::table('wg_positiva_fgn_management_indicator_coverage as icov')
            ->join('wg_positiva_fgn_management_indicator_relation as ir', 'ir.id', '=', 'icov.indicator_relation_id')
            ->join('wg_positiva_fgn_activity_indicator_sectional_consultant_relation as aiscr', 'aiscr.id', '=', 'ir.sectional_consultant_relation_id')
            ->join('wg_positiva_fgn_activity_config as task', 'task.id', '=', 'aiscr.activity_config_id')
            ->leftJoin("wg_positiva_fgn_activity_config_subtask as subtask", function ($join) {
                $join->on("task.id", "=", "subtask.activity_config_id");
                $join->on("icov.activity_gestpos_id", "=", "subtask.gestpos_subtask_id");
            })
            ->whereRaw("ir.period = {$period->value}")
            ->select(
                'ir.sectional_consultant_relation_id',
                DB::raw("SUM(`call`) as `call`"),
                DB::raw("SUM(assistants) as assistants")
            )
            ->groupBy("ir.id");

        $relationProgrammed = $this->service->activitiesProgrammed($period);
        $filterConsultant = DB::table('wg_positiva_fgn_activity_indicator_sectional_consultant')
            ->whereRaw("{$columnMonth} > 0")
            ->select("consultant_relation_id")
            ->groupBy("consultant_relation_id");

        $query = DB::table("wg_positiva_fgn_activity_indicator_sectional_consultant_relation as scr")
            ->join(DB::raw("( {$filterConsultant->toSql()} ) as filterconsultant"), function ($join) {
                $join->on('filterconsultant.consultant_relation_id', '=', 'scr.id');
            })
            ->join('wg_positiva_fgn_activity_indicator_sectional_relation as sr', 'sr.id', '=', 'scr.sectional_relation_id')
            ->join('wg_positiva_fgn_activity_config as ac', 'scr.activity_config_id', '=', 'ac.id')
            ->join('wg_positiva_fgn_activity as afgn', 'ac.fgn_activity_id', '=', 'afgn.id')
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_action')), function ($join) {
                $join->on('afgn.action', '=', 'positiva_fgn_activity_action.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                $join->on('ac.strategy', '=', 'positiva_fgn_consultant_strategy.value');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_modality')), function ($join) {
                $join->on('ac.modality', '=', 'positiva_fgn_activity_modality.value');
            })
            ->join("wg_positiva_fgn_gestpos as activity", "ac.gestpos_activity_id", "=", "activity.id")
            ->join("wg_positiva_fgn_gestpos AS task", "ac.gestpos_task_id", "=", "task.id");

        if ($action->value == "execution") {
            $query->join(DB::raw("({$relationProgrammed->toSql()}) as programmed"), function ($join) {
                $join->on("scr.id", "=", "programmed.sectional_consultant_relation_id");
            });
        }

        $query
            ->leftjoin(DB::raw("( {$indicator->toSql()} ) as goals "), "goals.consultant_relation_id", "=", "scr.id")
            ->leftjoin(DB::raw(" ({$complianceIndicatorValues->toSql()}) as complianceValue "), 'complianceValue.sectional_consultant_relation_id', '=', 'scr.id')
            ->leftjoin(DB::raw(" ({$coverageIndicatorValues->toSql()}) as coverageValue "), 'coverageValue.sectional_consultant_relation_id', '=', 'scr.id')
            ->where("consultant_id", $consultant->value)
            ->where("sectional_id", $sectional->value)
            ->where("axis", $axis->value)
            ->where("config_id", $config->value)
            ->groupBy('scr.id')
            ->select(
                "scr.id AS id",
                "positiva_fgn_activity_action.item AS action",
                "afgn.name as activity",
                "positiva_fgn_consultant_strategy.item AS strategy",
                "positiva_fgn_activity_modality.item AS modality",
                "afgn.code as activityCode",
                "activity.name AS activityGestpos",
                "task.name as task",
                DB::raw("SUM(if( `goals`.`type` = 'T002', goals.goal, 0)) AS goalCompliance"),
                "complianceValue.programmed AS programCompliance",
                DB::raw("ROUND((complianceValue.programmed / SUM(if( `goals`.`type` = 'T002', goals.goal, null)) ) * 100, 0) as programPercentCompliance"),
                DB::raw("SUM(if( `goals`.`type` = 'T001', goals.goal, 0)) AS goalCoverage"),
                "coverageValue.call AS programCoverage",
                DB::raw("ROUND((coverageValue.`call` / SUM(if( `goals`.`type` = 'T001', goals.goal, 0)) ) * 100, 0) as programPercentCoverage"),
                "complianceValue.executed AS executedCompliance",
                DB::raw("ROUND((complianceValue.executed / SUM(if( `goals`.`type` = 'T002', goals.goal, null)) ) * 100, 0) as executedPercentCompliance"),
                "coverageValue.assistants AS executedCoverage",
                DB::raw("ROUND((coverageValue.assistants / SUM(if( `goals`.`type` = 'T001', goals.goal, 0)) ) * 100, 0) as executedPercentCoverage")
            );

        $query = DB::table(DB::raw("({$query->toSql()}) as d"))
            ->mergeBindings($query);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $indicatorRelation = IndicatorRelationModel::findOrNew($entity->indicatorRelationId);
        $indicatorRelation->sectional_consultant_relation_id = $entity->sectionalConsultantRelationId;
        $indicatorRelation->period = $entity->period;
        $indicatorRelation->advice_type = $entity->adviceType;
        $indicatorRelation->observation = $entity->observation ?? null;
        $indicatorRelation->satisfaction_indicator_45 = $entity->satisfactionIndicator45 ?? null;
        $indicatorRelation->satisfaction_indicator_123 = $entity->satisfactionIndicator123 ?? null;

        $oldAdviceType = $indicatorRelation->getOriginal('advice_type');
        $indicatorRelation->save();

        // Compliance
        if (!empty($entity->indicators->compliance)) {
            foreach ($entity->indicators->compliance as $compliance) {
                $model = ComplianceModel::findOrNew($compliance->id);
                $model->programmed = isset($compliance->programmed) ? $compliance->programmed : $model->programmed;
                $model->executed =   isset($compliance->executed)   ? $compliance->executed : $model->executed;
                $model->hour_programmed = isset($compliance->hourProgrammed) ? $compliance->hourProgrammed : $model->hour_programmed;
                $model->hour_executed =   isset($compliance->hourExecuted)   ? $compliance->hourExecuted : $model->hour_executed;
                $model->activity_gestpos_id = $compliance->taskid;
                $model->indicator_relation_id = $indicatorRelation->id;
                $model->save();
            }
        }

        // Coverage
        if (!empty($entity->indicators->coverage)) {
            foreach ($entity->indicators->coverage as $coverage) {
                $model = CoverageModel::findOrNew($coverage->id);
                $model->call = isset($coverage->call) ? $coverage->call : $model->call;
                $model->assistants = isset($coverage->assistants) ? $coverage->assistants : $model->assistants;
                $model->activity_gestpos_id = $coverage->taskid;
                $model->indicator_relation_id = $indicatorRelation->id;
                $model->save();

                if (!empty($coverage->poblation) && !empty($entity->managementType) && $entity->managementType == "programming") {
                    foreach ($coverage->poblation as $poblation) {
                        $poblationModel = PoblationModel::findOrNew($poblation->id);
                        $poblationModel->value = $poblation->call;
                        $poblationModel->type = $entity->managementType;
                        $poblationModel->poblation = $poblation->value;
                        $poblationModel->indicator_coverage_id = $model->id;
                        $poblationModel->save();
                    }
                }
            }
        }

        // save log of changes to advice type
        if ($indicatorRelation->advice_type != $oldAdviceType) {
            $this->service->insertLog($oldAdviceType, $indicatorRelation->advice_type, $indicatorRelation->id);
        }

        return $this->parseModelWithRelations($entity->sectionalConsultantRelationId, $entity->period);
    }

    public function parseModelWithRelations($consultantRelationId, $period)
    {
        $model = $this->service->findModel($period, $consultantRelationId);
        $entity = new \stdClass();

        if ($model) {
            $entity->indicatorRelationId = $model->indicatorRelationId;
            $entity->regional = $model->regional;
            $entity->sectional = $model->sectional;
            $entity->axis = $model->axis;
            $entity->action = $model->action;
            $entity->fgnCode = $model->fgncode;
            $entity->activity = $model->activity;
            $entity->period = $model->period;
            // $entity->goal = $model->goal;
            $entity->strategy = $model->strategy;
            $entity->modality = $model->modality;
            $entity->executionType = $model->executiontype;
            $entity->gestposCode = $model->gestposcode;
            $entity->activityGestpos = $model->activitygestpos;
            $entity->task = $model->task;
            $entity->providesCoverage = (int) $model->providescoverage;
            $entity->providesCompliance = (int) $model->providescompliance;
            $entity->activityConfigId = $model->activityconfigid;
            $entity->fgnActivityId = $model->fgnactivityid;
            $entity->regionalId = $model->regionalid;
            $entity->sectionalId = $model->sectionalid;
            $entity->strategyValue = $model->strategyvalue;
            $entity->modalityValue = $model->modalityvalue;
            $entity->activityId = $model->activityid;
            $entity->taskId = $model->taskid;
            $entity->axisValue = $model->axisvalue;
            $entity->consultantId = $model->consultantid;
            $entity->sectionalConsultantRelationId = $model->sectionalConsultantRelationId;
            $entity->adviceType = $model->advice_type;
            $entity->oldAdviceType = $model->advice_type;
            $entity->observation = $model->observation;
            $entity->satisfactionIndicator45 = $model->satisfaction_indicator_45;
            $entity->satisfactionIndicator123 = $model->satisfaction_indicator_123;

            $entity->goalCoverage = $this->service->getCoverage($consultantRelationId, $period);
            $entity->goalCompliance = $this->service->getCompliance($consultantRelationId, $period);

            $detailCoverage = $this->service->getCoverageIndicator($consultantRelationId, $model->period);
            $detailCompliance = $this->service->getComplianceIndicator($consultantRelationId, $model->period);

            $entity->indicators = [
                "coverage" => $detailCoverage,
                "compliance" => $detailCompliance,
            ];
        }

        return $entity;
    }

    public function config($config)
    {
        $result = [];

        foreach ($config as $criteria) {

            switch ($criteria->name) {
                case "axis_list":
                    $result['axisList'] = ActivityModel::whereConsultantId($criteria->value)
                        ->whereConfigId($criteria->config)
                        ->whereSectionalId($criteria->sectionalId)
                        ->select(
                            'axis.item',
                            'wg_positiva_fgn_activity.axis'
                        )
                        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis', 'axis')), function ($join) {
                            $join->on('wg_positiva_fgn_activity.axis', '=', 'axis.value');
                        })
                        ->join('wg_positiva_fgn_activity_indicator_sectional_relation as sr', 'sr.fgn_activity_id', '=', 'wg_positiva_fgn_activity.id')
                        ->join('wg_positiva_fgn_activity_indicator_sectional_consultant_relation as scr', 'scr.sectional_relation_id', '=', 'sr.id')
                        ->groupBy("axis.value")
                        ->get();
                    break;

                case "axis_stats":
                    $result = $this->service->axisStats($result, $criteria);
                    break;

                case "axisByUserIdConsultantList":
                    $result['axisByUserIdConsultantList'] =
                        ActivityModel::whereUserId($criteria->value)
                        ->select(
                            'axis.item as item',
                            'wg_positiva_fgn_activity.axis as value'
                        )
                        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis', 'axis')), function ($join) {
                            $join->on('wg_positiva_fgn_activity.axis', '=', 'axis.value');
                        })
                        ->join(
                            "wg_positiva_fgn_activity_indicator_sectional_consultant",
                            "wg_positiva_fgn_activity.id",
                            "=",
                            "wg_positiva_fgn_activity_indicator_sectional_consultant.fgn_activity_id"
                        )
                        ->join("wg_positiva_fgn_consultant", 'wg_positiva_fgn_consultant.id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant.consultant_id')
                        ->groupBy("axis.value")
                        ->get();
                    break;

                case "positiva_fgn_period":
                    $activePeriod = ConfigModel::whereIsActive(1)
                        ->first();
                    $monthList = [];
                    for ($i = 1; $i <= 12; $i++) {
                        $month = $i < 10 ? "0{$i}" : $i;
                        array_push($monthList, ["item" => $activePeriod->period . $month, "value" => $activePeriod->period . $month, "config" => $activePeriod->id]);
                    }
                    $result['positivaFgnPeriod'] = $monthList;
                    break;

                case "positiva_fgn_period_all":
                    $result['positivaFgnPeriod'] = ConfigModel::whereIsActive(1)
                        ->select("period")
                        ->distinct()
                        ->orderBy("period")
                        ->get();
                    break;

                case "info_basic":
                    $result['infoBasic'] = ConsultantModel::whereUserId($criteria->value)
                        ->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                            $join->on('employee_document_type.value', '=', 'wg_positiva_fgn_consultant.document_type');
                        })
                        ->join(DB::raw(SystemParameter::getRelationTable('gender')), function ($join) {
                            $join->on('gender.value', '=', 'wg_positiva_fgn_consultant.gender');
                        })
                        ->select(
                            'wg_positiva_fgn_consultant.id',
                            'wg_positiva_fgn_consultant.document_number',
                            'wg_positiva_fgn_consultant.full_name',
                            'employee_document_type.item as documentType',
                            'gender.item as gender'
                        )
                        ->first();
                    break;

                case "all_consultant_list":
                    $regionalId = $criteria->regionalId;

                    $result["allConsultantList"] = ConsultantModel::join("wg_positiva_fgn_consultant_sectional", function ($join) {
                            $join->on("wg_positiva_fgn_consultant.id", "=", "wg_positiva_fgn_consultant_sectional.consultant_id");
                        })
                        ->when($regionalId, function($query) use ($regionalId) {
                            $query->whereIn('regional_id', $regionalId);
                        })
                        ->where("wg_positiva_fgn_consultant_sectional.is_active", 1)
                        ->select("full_name AS item", "wg_positiva_fgn_consultant.id AS value")
                        ->groupBy('wg_positiva_fgn_consultant.id')
                        ->orderBy('full_name')
                        ->get();
                    break;
            }
        }

        return $result;
    }


    public function getComplianceLogs($criteria)
    {
        $this->setColumns([
            "id" => "l.id",
            "indicatorId" => "l.indicator_compliance_id as indicatorId",
            "date" => DB::raw("DATE_FORMAT(l.date, '%d/%m/%Y') as date"),
            "activityState" => "param.item as activityState",
            "activityStateValue" => "l.activity_state as activityStateValue",
            "programmed" => "i.programmed",
            "executed" => "l.executed",
            "hour_programmed" => "i.hour_programmed",
            "hour_executed" => "l.hour_executed",
            "satisfactionIndicator45" => "l.satisfaction_indicator_45 as satisfactionIndicator45",
            "satisfactionIndicator123" => "l.satisfaction_indicator_123 as satisfactionIndicator123",
            "observation" => "l.observation"
        ]);

        $this->parseCriteria($criteria);

        $query = DB::table("wg_positiva_fgn_management_indicator_compliance_logs as l")
            ->join("wg_positiva_fgn_management_indicator_compliance as i", "i.id", "=", "l.indicator_compliance_id")
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_gestpos_activity_states', 'param')), function ($join) {
                $join->on('l.activity_state', '=', 'param.value');
            })
            ->orderBy('l.date');

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function parseModelWithRelationsComplianceLogs(ComplianceLogModel $model)
    {
        $model->activityState = $model->getActivityState();
        $model->date = Carbon::parse($model->date)->format("d/m/Y");
        return $model;
    }


    public function insertOrUpdateComplianceLogs($entity)
    {
        try {
            $authUser = $this->getAuthUser();
            $userId = $authUser ? $authUser->id : 1;

            $model = ComplianceLogModel::findOrNew($entity->id);
            $model->indicatorComplianceId = $entity->indicatorId;
            $model->management_type = 'execution';
            $model->date = Carbon::createFromFormat("d/m/Y", $entity->date)->timezone('America/Bogota');
            $model->activityState = $entity->activityState->value;
            $model->executed = $entity->executed;
            $model->hourExecuted = $entity->hourExecuted;
            $model->observation = $entity->observation;
            $model->satisfaction_indicator_45 = $entity->satisfactionIndicator45;
            $model->satisfaction_indicator_123 = $entity->satisfactionIndicator123;
            $model->updatedBy = $userId;

            if (empty($model->id)) {
                $model->createdBy = $userId;
            }

            $model->save();

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function deleteComplianceLogs($id)
    {
        $model = ComplianceLogModel::find($id);
        if (empty($model)) {
            throw new \Exception("Record not found to delete.");
        }

        $model->delete();
    }


    public function getPopulationAll($criteria)
    {
        $this->setColumns([
            "id" => "p.id",
            "indicatorId" => "p.indicator_coverage_id as indicatorId",
            "date" => "p.date",
            "activityState" => "param.item as activityState"
        ]);

        $query = DB::table("wg_positiva_fgn_management_indicator_coverage_poblation as p")
            ->join("wg_positiva_fgn_management_indicator_coverage as i", "i.id", "=", "p.indicator_coverage_id")
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('positiva_fgn_gestpos_activity_states', 'param')), function ($join) {
                $join->on('p.activity_state', '=', 'param.value');
            })
            ->where('type', "execution")
            ->groupBy('p.date')
            ->orderBy('p.created_at');

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function parseModelWithRelationsPoblation($indicatorId, $date)
    {
        $rows = $this->service->getPoblation($indicatorId, $date);
        $data = new \stdClass();
        if ($rows->count() > 0) {
            $data->date = Carbon::parse($rows[0]->date)->format("d/m/Y");
            $data->activityState = PoblationModel::getActivityState($rows[0]->activityState);
            $data->coverages = $rows;
        }

        return $data;
    }

    public function getPoblationBase($indicatorId, $action)
    {
        return $this->service->getPoblationBase($indicatorId, $action);
    }

    public function populationDelete($indicatorId, $date)
    {
        PoblationModel::where('indicator_coverage_id', $indicatorId)
            ->where("date", $date)
            ->delete();
    }


    public function insertOrUpdatePopulation($entities)
    {
        $date = Carbon::createFromFormat("d/m/Y", $entities->date)->toDateString();
        $data = PoblationModel::where('indicator_coverage_id', $entities->indicatorId)
            ->whereRaw("date = DATE('{$date}')")
            ->whereType('execution')
            ->get()
            ->count();

        $activityState = $entities->activityState->value ?? null;

        if ($data == 0) {
            foreach ($entities->coverages as $entity) {
                $model = new PoblationModel();
                $model->indicator_coverage_id = $entities->indicatorId;
                $model->poblation = $entity->value;
                $model->value = (int) $entity->assistants;
                $model->date = $date;
                $model->activityState = $activityState;
                $model->type = "execution";
                $model->save();
            }
        } else {
            foreach ($entities->coverages as $entity) {
                $model = PoblationModel::where('indicator_coverage_id', $entities->indicatorId)
                    ->whereRaw("date = DATE('{$date}')")
                    ->where('poblation', $entity->value)
                    ->first();

                if (empty($model)) {
                    $model = new PoblationModel();
                    $model->indicator_coverage_id = $entities->indicatorId;
                    $model->poblation = $entity->value;
                }

                $model->value = (int) $entity->assistants;
                $model->date = $date;
                $model->activityState = $activityState;
                $model->type = "execution";
                $model->save();
            }
        }

        return $this->service->getPoblationBase($entities->indicatorId, "execution");
    }


    public function getTotalsComplianceLogs($indicatorId)
    {
        return ComplianceLogModel::where('indicator_compliance_id', $indicatorId)
            ->select(
                DB::raw("SUM(executed) as totalExecuted"),
                DB::raw("SUM(hour_executed) as totalHourExecuted")
            )
            ->whereIn('activity_state', ['AS001', 'AS002' ])
            ->first();
    }


    public function getPoblationTotals($indicatorId)
    {
        $programming =  PoblationModel::where('indicator_coverage_id', $indicatorId)
            ->select(
                DB::raw("SUM(value) as value")
            )
            ->whereType("programming")
            ->first();

        $execution =  PoblationModel::where('indicator_coverage_id', $indicatorId)
            ->select(
                DB::raw("SUM(value) as value")
            )
            ->whereType("execution")
            ->whereIn('activity_state', ['AS001', 'AS002' ])
            ->first();

        $entity = new \stdClass;
        $entity->totalCalls = $programming ? $programming->value : 0;
        $entity->totalAssistants = $execution ? $execution->value : 0;
        return $entity;

    }
}
