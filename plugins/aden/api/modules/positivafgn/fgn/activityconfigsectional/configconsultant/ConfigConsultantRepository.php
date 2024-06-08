<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\PositivaFgn\Consultant\ConsultantModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ActivityConfigSectionalModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\ActivityConfigModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\ActivityConfigRepository;
use AdeN\Api\Modules\PositivaFgn\Fgn\Activity\IndicatorModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ActivityConfigSectionalRelationModel;
use DB;
use Exception;
use Wgroup\SystemParameter\SystemParameter;

class ConfigConsultantRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ConfigConsultantRelationModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id",
            "strategy" => "strategy.item as strategy",
            "activity" => "activity.name as activity",
            "task" => "task.name as task",
            "consultant" => "wg_positiva_fgn_consultant.full_name as consultant",
            "consultantType" => DB::raw("IF(wg_positiva_fgn_activity_indicator_sectional_consultant_relation.is_occasional = 1, 'Ocasional', 'Base') as consultantType"),
            "providesCoverage" => DB::raw("IF(ac.provides_coverage = 1, 'SI', 'NO') as providesCoverage"),
            "goalCoverage" => "coverage.goal AS goalCoverage",
            "providesCompliance" => DB::raw("IF(ac.provides_compliance = 1, 'SI', 'NO') as providesCompliance"),
            "goalCompliance" => "compliance.goal AS goalCompliance",
            "sectionalRelationId" => "wg_positiva_fgn_activity_indicator_sectional_consultant_relation.sectional_relation_id as sectionalRelationId"
        ]);

        $indicator = DB::table("wg_positiva_fgn_activity_indicator_sectional_consultant as aisc")
            ->join("wg_positiva_fgn_activity_indicator AS ai", "aisc.activity_indicator_id", "=", "ai.id")
            ->select("aisc.goal", "ai.type", "aisc.consultant_relation_id");

        $this->parseCriteria($criteria);
        $query = $this->query()
            ->join('wg_positiva_fgn_activity_indicator_sectional_relation as sr', 'sr.id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant_relation.sectional_relation_id')
            ->join("wg_positiva_fgn_activity_config as ac", "wg_positiva_fgn_activity_indicator_sectional_consultant_relation.activity_config_id", "=", "ac.id")
            ->join("wg_positiva_fgn_gestpos as activity", "ac.gestpos_activity_id", "=", "activity.id")
            ->join("wg_positiva_fgn_gestpos AS task", "ac.gestpos_task_id", "=", "task.id")
            ->join("wg_positiva_fgn_consultant", "wg_positiva_fgn_consultant.id", "=", "wg_positiva_fgn_activity_indicator_sectional_consultant_relation.consultant_id")
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy', 'strategy')), function ($join) {
                $join->on('ac.strategy', '=', 'strategy.value');
            })
            ->leftjoin(DB::raw("({$indicator->toSql()}) as coverage"), function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id", "=", "coverage.consultant_relation_id");
                $join->where("coverage.type", "=", "T001");
            })
            ->leftjoin(DB::raw("({$indicator->toSql()}) as compliance"), function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator_sectional_consultant_relation.id", "=", "compliance.consultant_relation_id");
                $join->where("compliance.type", "=", "T002");
            });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $activityModelCompliance = null;
        $activityModelCoverage = null;
        $valideConsultant = ConfigConsultantRelationModel::whereSectionalRelationId($entity->sectionalRelationId)
            ->whereActivityConfigId($entity->activityConfigId)
            ->whereConsultantId($entity->consultant->value)
            ->where('id', '<>', $entity->id)
            ->first();

        if ($valideConsultant) {
            throw new \Exception('No es posible adicionar la información, ya existe una asignación de metas para este asesor con estas características.');
        }

        DB::beginTransaction();

        $consultantRelationModel = ConfigConsultantRelationModel::findOrNew($entity->id);
        $consultantRelationModel->sectionalRelationId = $entity->sectionalRelationId;
        $consultantRelationModel->activityConfigId = $entity->activityConfigId;
        $consultantRelationModel->consultantId = $entity->consultant->value;
        $consultantRelationModel->isOccasional = $entity->isOccasional;
        $consultantRelationModel->save();

        if ($entity->details->indicator) {
            foreach ($entity->details->indicator as $indicator) {
                $IndicatorModel = ConfigConsultantModel::findOrNew($indicator->id);
                $IndicatorModel->id = $indicator->id;
                $IndicatorModel->consultantRelationId = $consultantRelationModel->id;
                $IndicatorModel->activityIndicatorId = $indicator->activityIndicatorId;
                $IndicatorModel->jan = !empty($indicator->jan) && $indicator->jan != "block" ? $indicator->jan : 0;
                $IndicatorModel->feb = !empty($indicator->feb) && $indicator->feb != "block" ? $indicator->feb : 0;
                $IndicatorModel->mar = !empty($indicator->mar) && $indicator->mar != "block" ? $indicator->mar : 0;
                $IndicatorModel->apr = !empty($indicator->apr) && $indicator->apr != "block" ? $indicator->apr : 0;
                $IndicatorModel->may = !empty($indicator->may) && $indicator->may != "block" ? $indicator->may : 0;
                $IndicatorModel->jun = !empty($indicator->jun) && $indicator->jun != "block" ? $indicator->jun : 0;
                $IndicatorModel->jul = !empty($indicator->jul) && $indicator->jul != "block" ? $indicator->jul : 0;
                $IndicatorModel->aug = !empty($indicator->aug) && $indicator->aug != "block" ? $indicator->aug : 0;
                $IndicatorModel->sep = !empty($indicator->sep) && $indicator->sep != "block" ? $indicator->sep : 0;
                $IndicatorModel->oct = !empty($indicator->oct) && $indicator->oct != "block" ? $indicator->oct : 0;
                $IndicatorModel->nov = !empty($indicator->nov) && $indicator->nov != "block" ? $indicator->nov : 0;
                $IndicatorModel->dec = !empty($indicator->dec) && $indicator->dec != "block" ? $indicator->dec : 0;
                $IndicatorModel->goal = $indicator->goal;
                $IndicatorModel->save();

                $allIndicator = (new ConfigConsultantModel)
                    ->join('wg_positiva_fgn_activity_indicator_sectional_consultant_relation as iscr', 'iscr.id', '=', 'wg_positiva_fgn_activity_indicator_sectional_consultant.consultant_relation_id')
                    ->join("wg_positiva_fgn_activity_indicator_sectional as ais", function ($join) {
                        $join->on("wg_positiva_fgn_activity_indicator_sectional_consultant.activity_indicator_id", "=", "ais.activity_indicator_id");
                        $join->on('ais.sectional_relation_id', '=', 'iscr.sectional_relation_id');
                    })
                    ->join("wg_positiva_fgn_activity_indicator as ai", "ais.activity_indicator_id", "=", "ai.id")
                    ->where("iscr.sectional_relation_id", $entity->sectionalRelationId)
                    ->selectRaw("SUM(wg_positiva_fgn_activity_indicator_sectional_consultant.goal) as goal");

                $sectionalIndicator = ActivityConfigSectionalModel::whereSectionalRelationId($entity->sectionalRelationId)
                    ->join("wg_positiva_fgn_activity_indicator as ai", "wg_positiva_fgn_activity_indicator_sectional.activity_indicator_id", "=", "ai.id")
                    ->where("activity_indicator_id", $indicator->activityIndicatorId)
                    ->select("wg_positiva_fgn_activity_indicator_sectional.id", "type", "goal")
                    ->first();

                if ($sectionalIndicator && $sectionalIndicator->type == "T001") {
                    $allIndicator = $allIndicator->whereType("T001")->get();
                    $allIndicator = $allIndicator ? $allIndicator->first()->goal : 0;
                    $sectionalIndicator->assignment = (int) $sectionalIndicator->goal - (int) $allIndicator;
                    // if ($sectionalIndicator->assignment < 0) {
                    //     DB::rollBack();
                    //     throw new \Exception('No es posible adicionar la información, ya no queda asignación disponible para cobertura.');
                    // }
                    $activityModelCoverage = $sectionalIndicator;
                } elseif ($sectionalIndicator && $sectionalIndicator->type == "T002") {
                    $allIndicator = $allIndicator->whereType("T002")->get();
                    $allIndicator = $allIndicator ? $allIndicator->first()->goal : 0;
                    $sectionalIndicator->assignment = (int) $sectionalIndicator->goal - (int) $allIndicator;
                    // if ($sectionalIndicator->assignment < 0) {
                    //     DB::rollBack();
                    //     throw new \Exception('No es posible adicionar la información, ya no queda asignación disponible para cumplimiento. ' . $allIndicator);
                    // }
                    $activityModelCompliance = $sectionalIndicator;
                }

                if($sectionalIndicator) {
                    $assignedSectional = ActivityConfigSectionalModel::find($sectionalIndicator->id);
                    $assignedSectional->assignment = $sectionalIndicator->assignment;
                    $assignedSectional->save();
                }
            }
        }

        DB::commit();

        return [
            "assignmentCompliance" => $activityModelCompliance ? $activityModelCompliance->assignment : 0,
            "assignmentCoverage" => $activityModelCoverage ? $activityModelCoverage->assignment : 0,
        ];
    }

    public function parseModelWithRelations($model)
    {
        $activityConfigModel = (new ActivityConfigModel)
            ->whereActivityConfigId($model->activityConfigId)
            ->join("wg_positiva_fgn_activity_indicator_sectional_consultant_relation as aiscr", function ($join) {
                $join->on("wg_positiva_fgn_activity_config.id", "=", "aiscr.activity_config_id");
            })
            ->first();

        $entity = new \stdClass();
        $entity->id = $model->id;
        $entity->strategy = $activityConfigModel->getStrategy()["strategy"];
        $entity->activity = $activityConfigModel->getGestposActivity();
        $entity->modality = $activityConfigModel->getModality();
        $entity->executionType = $activityConfigModel->getExecutionType()->item;
        $entity->gestposTask = $activityConfigModel->getTask();
        $entity->isOccasional = $model->isOccasional == 1;
        $entity->consultant = $model->getConsultant();
        $entity->activityConfigId = $model->activityConfigId;

        $entity->details["indicator"] = ConfigConsultantModel::whereConsultantRelationId($model->id)
            ->get()
            ->each(function ($entity) use ($model) {
                $configSectional = ActivityConfigSectionalModel::whereSectionalRelationId($model->sectionalRelationId)
                    ->whereActivityIndicatorId($entity->activityIndicatorId)
                    ->first();

                $entity->jan = (integer) $entity->jan;
                $entity->feb = (integer) $entity->feb;
                $entity->mar = (integer) $entity->mar;
                $entity->apr = (integer) $entity->apr;
                $entity->may = (integer) $entity->may;
                $entity->jun = (integer) $entity->jun;
                $entity->jul = (integer) $entity->jul;
                $entity->aug = (integer) $entity->aug;
                $entity->sep = (integer) $entity->sep;
                $entity->oct = (integer) $entity->oct;
                $entity->nov = (integer) $entity->nov;
                $entity->dec = (integer) $entity->dec;
                
                $indicatorsConfig = $configSectional->getIndicatorsConfig();
                $entity->type = $indicatorsConfig->type;
                $entity->periodicity = $indicatorsConfig->periodicity;
            });

        return $entity;
    }

    public function delete($id)
    {
        $activityModelCoverage = null;
        $activityModelCompliance = null;
        $model = ConfigConsultantRelationModel::find($id);
        ConfigConsultantModel::whereConsultantRelationId($id)->delete();
        ActivityConfigSectionalModel::whereSectionalRelationId($model->sectionalRelationId)
        ->get()
        ->each(function($value) use(&$activityModelCompliance, &$activityModelCoverage) {
            $assignedGoal = (new ConfigConsultantModel)
            ->join("wg_positiva_fgn_activity_indicator_sectional_consultant_relation as aiscr", function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator_sectional_consultant.consultant_relation_id", "=", "aiscr.id");
            })
            ->join("wg_positiva_fgn_activity_indicator as ai", function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator_sectional_consultant.activity_indicator_id", "=", "ai.id");
            })
            ->whereSectionalRelationId($value->sectionalRelationId)
            ->where("activity_indicator_id", $value->activityIndicatorId)
            ->select(
                DB::raw("SUM(goal) as goal"), "type"
            )
            ->first();

            $value->assignment = (int)$value->goal - (int)$assignedGoal->goal;
            $value->save();

            if($assignedGoal->type == "T001"){
                $activityModelCoverage = $value;
            } elseif($assignedGoal->type == "T002"){
                $activityModelCompliance = $value;
            }

        });

        $model->delete();
        return [
            "assignmentCompliance" => $activityModelCompliance ? $activityModelCompliance->assignment : 0,
            "assignmentCoverage" => $activityModelCoverage ? $activityModelCoverage->assignment : 0,
        ];
    }

    public function config($config)
    {
        $result = [];
        foreach ($config as $criteria) {
            $sectionalRelationId = ActivityConfigSectionalRelationModel::find($criteria->value);
            switch ($criteria->name) {
                case "strategy_list":
                    $result["strategyList"] = ActivityConfigModel::whereFgnActivityId($sectionalRelationId->fgnActivityId)
                        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                            $join->on('wg_positiva_fgn_activity_config.strategy', '=', 'positiva_fgn_consultant_strategy.value');
                        })
                        ->select(
                            "positiva_fgn_consultant_strategy.item",
                            "positiva_fgn_consultant_strategy.value"
                        )
                        ->groupBy("positiva_fgn_consultant_strategy.value")
                        ->get();
                    break;
                case "activity_list":
                    $result["activityList"] = ActivityConfigModel::whereFgnActivityId($sectionalRelationId->fgnActivityId)
                        ->whereStrategy($criteria->strategy)
                        ->join("wg_positiva_fgn_gestpos", function ($join) {
                            $join->on("wg_positiva_fgn_activity_config.gestpos_activity_id", "=", "wg_positiva_fgn_gestpos.id");
                        })
                        ->select("wg_positiva_fgn_gestpos.name AS item", "wg_positiva_fgn_gestpos.id AS value")
                        ->groupBy("strategy", "wg_positiva_fgn_gestpos.id")
                        ->get();
                    break;
                case "task_list":
                    $task = ActivityConfigModel::whereFgnActivityId($sectionalRelationId->fgnActivityId)
                        ->whereGestposActivityId($criteria->activity)
                        ->join("wg_positiva_fgn_gestpos", function ($join) {
                            $join->on("wg_positiva_fgn_activity_config.gestpos_task_id", "=", "wg_positiva_fgn_gestpos.id");
                        })
                        ->select("wg_positiva_fgn_gestpos.name AS item", "wg_positiva_fgn_gestpos.id AS value")
                        ->groupBy("gestpos_task_id")
                        ->get();

                    $result["taskList"] = $task;
                    break;
                case "modality_list":
                    $configActivity = ActivityConfigModel::whereFgnActivityId($sectionalRelationId->fgnActivityId)
                        ->whereStrategy($criteria->strategy)
                        ->whereGestposActivityId($criteria->activity)
                        ->whereGestposTaskId($criteria->task)
                        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_modality')), function ($join) {
                            $join->on('wg_positiva_fgn_activity_config.modality', '=', 'positiva_fgn_activity_modality.value');
                        })
                        ->select("item", "value")
                        ->groupBy("modality")
                        ->get();

                    $result["modalityList"] = $configActivity;
                    break;
                case "activity_config":
                    $configActivity = ActivityConfigModel::whereFgnActivityId($sectionalRelationId->fgnActivityId)
                        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_execution_type')), function ($join) {
                            $join->on('wg_positiva_fgn_activity_config.execution_type', '=', 'positiva_fgn_activity_execution_type.value');
                        })
                        ->whereStrategy($criteria->strategy)
                        ->whereGestposActivityId($criteria->activity)
                        ->whereGestposTaskId($criteria->task)
                        ->whereModality($criteria->modality)
                        ->select("positiva_fgn_activity_execution_type.item AS executiontype", "wg_positiva_fgn_activity_config.id")
                        ->first();

                    $result["executionType"] = $configActivity ? $configActivity->executiontype : null;
                    $result["activityConfigId"] = $configActivity ? $configActivity->id : null;
                    break;
                case "consultant_list":
                    $result["consultantList"] = ConsultantModel::join("wg_positiva_fgn_consultant_sectional", function ($join) {
                        $join->on("wg_positiva_fgn_consultant.id", "=", "wg_positiva_fgn_consultant_sectional.consultant_id");
                    })
                        ->whereRegionalId($sectionalRelationId->regionalId)
                        ->whereSectionalId($sectionalRelationId->sectionalId)
                        ->where("wg_positiva_fgn_consultant_sectional.is_active", 1)
                        ->select("full_name AS item", "wg_positiva_fgn_consultant.id AS value")
                        ->get();
                    break;
            }
        }

        return $result;
    }
}
