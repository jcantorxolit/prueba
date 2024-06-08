<?php


namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\PositivaFgn\Fgn\Activity\ActivityModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\Activity\IndicatorModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\ConfigConsultantModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\ConfigConsultantRelationModel;
use DB;
use Exception;
use Log;

class ActivityConfigSectionalRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ActivityConfigSectionalRelationModel());
    }


    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_activity_indicator_sectional_relation.id",
            "regional" => "wg_positiva_fgn_regional.number AS regional",
            "sectional" => "wg_positiva_fgn_sectional.name AS sectional",
            "goalCoverage" => "coverage.goal AS goalCoverage",
            "goalCompliance" => "compliance.goal AS goalCompliance",
            "fgnActivityId" => "wg_positiva_fgn_activity_indicator_sectional_relation.fgn_activity_id AS fgnActivityId"
        ]);

        $indicator = DB::table("wg_positiva_fgn_activity_indicator_sectional AS is")
            ->join("wg_positiva_fgn_activity_indicator AS ai", "is.activity_indicator_id", "=", "ai.id")
            ->select("goal", "type", "sectional_relation_id");

        $this->parseCriteria($criteria);
        $query = $this->query();
        $query->join('wg_positiva_fgn_sectional', function ($join) {
            $join->on('wg_positiva_fgn_activity_indicator_sectional_relation.sectional_id', '=', 'wg_positiva_fgn_sectional.id');
        })
            ->join('wg_positiva_fgn_regional', function ($join) {
                $join->on('wg_positiva_fgn_activity_indicator_sectional_relation.regional_id', '=', 'wg_positiva_fgn_regional.id');
            })
            ->leftjoin(DB::raw("({$indicator->toSql()}) as coverage"), function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator_sectional_relation.id", "=", "coverage.sectional_relation_id");
                $join->where("coverage.type", "=", "T001");
            })
            ->leftjoin(DB::raw("({$indicator->toSql()}) as compliance"), function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator_sectional_relation.id", "=", "compliance.sectional_relation_id");
                $join->where("compliance.type", "=", "T002");
            });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $valideSectional = ActivityConfigSectionalRelationModel::whereSectionalId($entity->sectional->value)
            ->whereRegionalId($entity->regional->value)
            ->whereFgnActivityId($entity->fgnActivityId)
            ->first();

        if ($valideSectional && $entity->id == 0) {
            throw new \Exception('No es posible adicionar la información, ya existe una asignación de metas para esta seccional.');
        }

        $IndicatorRelationModel = ActivityConfigSectionalRelationModel::findOrNew($entity->id);
        $IndicatorRelationModel->fgnActivityId = $entity->fgnActivityId;
        $IndicatorRelationModel->sectionalId = $entity->sectional->value;
        $IndicatorRelationModel->regionalId = $entity->regional->value;
        $IndicatorRelationModel->save();

        $assignedGoal =
            ConfigConsultantModel::join("wg_positiva_fgn_activity_indicator_sectional_consultant_relation as aiscr", function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator_sectional_consultant.consultant_relation_id", "=", "aiscr.id");
            })
            ->whereSectionalRelationId($IndicatorRelationModel->id);

        if ($entity->details->indicator) {
            foreach ($entity->details->indicator as $indicator) {
                $IndicatorModel = ActivityConfigSectionalModel::findOrNew($indicator->id);
                $IndicatorModel->sectionalRelationId = $IndicatorRelationModel->id;
                $IndicatorModel->activityIndicatorId = $indicator->activityIndicatorId;
                $IndicatorModel->jan = !empty($indicator->jan) ? $indicator->jan : 0;
                $IndicatorModel->feb = !empty($indicator->feb) ? $indicator->feb : 0;
                $IndicatorModel->mar = !empty($indicator->mar) ? $indicator->mar : 0;
                $IndicatorModel->apr = !empty($indicator->apr) ? $indicator->apr : 0;
                $IndicatorModel->may = !empty($indicator->may) ? $indicator->may : 0;
                $IndicatorModel->jun = !empty($indicator->jun) ? $indicator->jun : 0;
                $IndicatorModel->jul = !empty($indicator->jul) ? $indicator->jul : 0;
                $IndicatorModel->aug = !empty($indicator->aug) ? $indicator->aug : 0;
                $IndicatorModel->sep = !empty($indicator->sep) ? $indicator->sep : 0;
                $IndicatorModel->oct = !empty($indicator->oct) ? $indicator->oct : 0;
                $IndicatorModel->nov = !empty($indicator->nov) ? $indicator->nov : 0;
                $IndicatorModel->dec = !empty($indicator->dec) ? $indicator->dec : 0;
                $IndicatorModel->goal = $indicator->goal;
                if ($indicator->id == 0) {
                    $IndicatorModel->assignment = $indicator->goal;
                } else {
                    $IndicatorModel->assignment = (int)$indicator->goal - (int)(clone $assignedGoal)
                        // ->where("sectional_relation_id", $IndicatorRelationModel->id)
                        ->where("activity_indicator_id", $indicator->activityIndicatorId)
                        ->sum("goal");
                }
                $IndicatorModel->save();
            }
        }

        $activityModel = $this->recalculated($entity->fgnActivityId);
        return $activityModel;
    }


    public function parseModelWithRelations($id, $clear = false)
    {
        $model = ActivityConfigSectionalRelationModel::find($id);
        $indicatorsConfig = $model->getIndicatorsConfig();
        $entity = new \stdClass;
        $entity->id = $model->id;
        $entity->regional = $model->getRegional();
        $entity->sectional = $model->getSectional();
        $entity->details = [];
        $entity->details["indicator"] = ActivityConfigSectionalModel::whereSectionalRelationId($model->id)
            ->get()
            ->each(function ($model) use ($entity, $clear, $indicatorsConfig) {
                $model->type = $indicatorsConfig->where("id", $model->activityIndicatorId)->first()->type;
                $model->periodicity = $indicatorsConfig->where("id", $model->activityIndicatorId)->first()->periodicity;
                if ($model->type->value == "T001") {
                    $entity->goalCoverage = $model->goal;
                    $entity->assignmentCoverage = $model->assignment;
                } elseif ($model->type->value == "T002") {
                    $entity->goalCompliance = $model->goal;
                    $entity->assignmentCompliance = $model->assignment;
                }

                $model->id = $clear ? 0 : $model->id;
                $model->jan = $clear ? ($model->jan > 0 ? null : "block") : (int)$model->jan;
                $model->feb = $clear ? ($model->feb > 0 ? null : "block") : (int)$model->feb;
                $model->mar = $clear ? ($model->mar > 0 ? null : "block") : (int)$model->mar;
                $model->apr = $clear ? ($model->apr > 0 ? null : "block") : (int)$model->apr;
                $model->may = $clear ? ($model->may > 0 ? null : "block") : (int)$model->may;
                $model->jun = $clear ? ($model->jun > 0 ? null : "block") : (int)$model->jun;
                $model->jul = $clear ? ($model->jul > 0 ? null : "block") : (int)$model->jul;
                $model->aug = $clear ? ($model->aug > 0 ? null : "block") : (int)$model->aug;
                $model->sep = $clear ? ($model->sep > 0 ? null : "block") : (int)$model->sep;
                $model->oct = $clear ? ($model->oct > 0 ? null : "block") : (int)$model->oct;
                $model->nov = $clear ? ($model->nov > 0 ? null : "block") : (int)$model->nov;
                $model->dec = $clear ? ($model->dec > 0 ? null : "block") : (int)$model->dec;
                $model->goal = $clear ? 0 : $model->goal;
            });

        return $entity;
    }

    public function delete($id)
    {
        ActivityConfigSectionalRelationModel::find($id)->delete();
        ActivityConfigSectionalModel::whereSectionalRelationId($id)->delete();
        ConfigConsultantRelationModel::whereSectionalRelationId($id)
            ->get()
            ->each(function ($value) {
                ConfigConsultantModel::whereConsultantRelationId($value->id)->delete();
                $value->delete();
            });
    }

    //Function for recalculated values meta compliance and coverage
    public function recalculated($activityId = null)
    {
        if ($activityId) {
            $activityModel = ActivityModel::find($activityId);
            return $this->calculeGoals($activityModel);
        } else {
            foreach (ActivityModel::all() as $activityModel) {
                $this->calculeGoals($activityModel);
            }
        }

        return null;
    }

    private function calculeGoals($activityModel)
    {
        IndicatorModel::whereFgnActivityId($activityModel->id)
            ->join("wg_positiva_fgn_activity_indicator_sectional as is", function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator.id", "=", "is.activity_indicator_id");
            })
            ->select(
                "type",
                DB::raw("SUM(goal) AS goal")
            )
            ->groupBy("type")
            ->get()
            ->each(function ($row) use ($activityModel) {
                if ($row->type == "T001") {
                    $activityModel->goalCoverage = (int)$row->goal;
                } elseif ($row->type == "T002") {
                    $activityModel->goalCompliance = (int)$row->goal;
                }
                $activityModel->save();
            });


        return $activityModel;
    }

    public static function heredeIndicator($activityId) {
        $repo = new self;
        $repo->heredeIndicators($activityId);
    }

    //Function to fix sectionals withow they indicator existing in activity
    public function heredeIndicators($activityId = null)
    {
        if($activityId) {
            $sectionalRelations = ActivityConfigSectionalRelationModel::whereFgnActivityId($activityId)->get();
        } else {
            $sectionalRelations = ActivityConfigSectionalRelationModel::all();
        }

        $activities = $activityId ? [ActivityModel::find($activityId)] : ActivityModel::all();
        foreach ($activities as $activityModel) {
            $indicatorsActivity = $activityModel->indicator->pluck("id")->unique();
            $relations = $activityId ? $sectionalRelations : $sectionalRelations->where("fgn_activity_id", $activityModel->id);
            foreach ($relations as $relation) {
                $indicatorsSectional = $relation->sectional_indicator->pluck("activity_indicator_id")->unique();
                if($indicatorsActivity->count() != $indicatorsSectional->count()) {
                    foreach ($indicatorsActivity as $currentIndicator) {
                        if(!$indicatorsSectional->contains($currentIndicator)){
                            $IndicatorModel = new ActivityConfigSectionalModel();
                            $IndicatorModel->sectionalRelationId = $relation->id;
                            $IndicatorModel->activityIndicatorId = $currentIndicator;
                            $IndicatorModel->jan = 0;
                            $IndicatorModel->feb = 0;
                            $IndicatorModel->mar = 0;
                            $IndicatorModel->apr = 0;
                            $IndicatorModel->may = 0;
                            $IndicatorModel->jun = 0;
                            $IndicatorModel->jul = 0;
                            $IndicatorModel->aug = 0;
                            $IndicatorModel->sep = 0;
                            $IndicatorModel->oct = 0;
                            $IndicatorModel->nov = 0;
                            $IndicatorModel->dec = 0;
                            $IndicatorModel->goal = 0;
                            $IndicatorModel->save();

                            $consultantRelations = ConfigConsultantRelationModel::whereSectionalRelationId($relation->id)->get();
                            foreach ($consultantRelations as $consultant) {
                                $IndicatorModel = new ConfigConsultantModel();
                                $IndicatorModel->consultantRelationId = $consultant->id;
                                $IndicatorModel->activityIndicatorId = $currentIndicator;
                                $IndicatorModel->jan = 0;
                                $IndicatorModel->feb = 0;
                                $IndicatorModel->mar = 0;
                                $IndicatorModel->apr = 0;
                                $IndicatorModel->may = 0;
                                $IndicatorModel->jun = 0;
                                $IndicatorModel->jul = 0;
                                $IndicatorModel->aug = 0;
                                $IndicatorModel->sep = 0;
                                $IndicatorModel->oct = 0;
                                $IndicatorModel->nov = 0;
                                $IndicatorModel->dec = 0;
                                $IndicatorModel->goal = 0;
                                $IndicatorModel->save();
                            }


                        }
                    }
                }
            }
                
        }
        return null;
    }
}
