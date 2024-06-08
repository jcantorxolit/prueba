<?php


namespace AdeN\Api\Modules\PositivaFgn\Fgn\Activity;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\ActivityConfigModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig\SubtaskModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ActivityConfigSectionalModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\ConfigConsultantModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\Activity\IndicatorModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ActivityConfigSectionalRelationModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ActivityConfigSectionalRepository;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\ConfigConsultantRelationModel;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use stdClass;
use Wgroup\SystemParameter\SystemParameter;

class ActivityRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ActivityModel());
    }


    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_activity.id",
            "axis" => "positiva_fgn_activity_axis.item AS axis",
            "action" => "positiva_fgn_activity_action.item AS action",
            "code" => "wg_positiva_fgn_activity.code",
            "name" => "wg_positiva_fgn_activity.name",
            "strategy" => "strategy.strategy",
            "goalCoverage" => "wg_positiva_fgn_activity.goal_coverage AS goalCoverage",
            "goalCompliance" => "wg_positiva_fgn_activity.goal_compliance AS goalCompliance",
            "configId" => "config_id as configId"
        ]);

        $strategy = DB::table("wg_positiva_fgn_activity_strategy")
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                $join->on('wg_positiva_fgn_activity_strategy.strategy', '=', 'positiva_fgn_consultant_strategy.value');
            })
            ->select(
                DB::raw("GROUP_CONCAT(' ',positiva_fgn_consultant_strategy.item) as strategy"),
                "fgn_activity_id"
            )
            ->groupBy("fgn_activity_id");

        $this->parseCriteria($criteria);
        $query = $this->query();
        $query->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_axis')), function ($join) {
            $join->on('wg_positiva_fgn_activity.axis', '=', 'positiva_fgn_activity_axis.value');
        })
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_action')), function ($join) {
                $join->on('wg_positiva_fgn_activity.action', '=', 'positiva_fgn_activity_action.value');
            })
            ->leftjoin(DB::raw("({$strategy->toSql()}) as strategy"), function ($join) {
                $join->on("wg_positiva_fgn_activity.id", "=", "strategy.fgn_activity_id");
            })
            ->mergeBindings($strategy);

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

        $entityModel->id = $entity->id;
        $entityModel->axis = $entity->axis->value;
        $entityModel->configId = $entity->configId;
        $entityModel->action = $entity->action->value;
        $entityModel->code = $entity->code;
        $entityModel->name = $entity->name;
        $entityModel->type = $entity->type ? $entity->type->value : null;
        $entityModel->group = $entity->group ? $entity->group->value : null;
        $entityModel->goal_annual = $entity->goalAnnual;

        if ($isNewRecord) {
            $entityModel->createdAt = Carbon::now();
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        }

        $entity = $this->saveDetails($entity, $entityModel);
        if($entity->id) {
            ActivityConfigSectionalRepository::heredeIndicator($entity->id);
        }
        $entity->id = $entityModel->id;
        return $entity;
    }

    private function saveDetails($entity, $entityModel)
    {
        if ($entity->details->strategy) {
            foreach ($entity->details->strategy as $key => $strategy) {
                $StrategyModel = StrategyModel::findOrNew($strategy->id);
                $StrategyModel->id = $strategy->id;
                $StrategyModel->fgnActivityId = $entityModel->id;
                $StrategyModel->strategy = $strategy->strategy->value;
                $StrategyModel->save();
                $strategy->id = $StrategyModel->id;
                $entity->details->strategy[$key] = $strategy;
            }
        }

        if ($entity->details->indicator) {
            foreach ($entity->details->indicator as $key => $indicator) {
                $IndicatorModel = IndicatorModel::findOrNew($indicator->id);
                $IndicatorModel->id = $indicator->id;
                $IndicatorModel->fgnActivityId = $entityModel->id;
                $IndicatorModel->type = $indicator->type->value;
                $IndicatorModel->periodicity = $indicator->periodicity->value;
                $IndicatorModel->formulation = $indicator->formulation;
                $IndicatorModel->save();
                $indicator->id = $IndicatorModel->id;
                $entity->details->indicator[$key] = $indicator;
            }
        }

        return $entity;
    }

    public function parseModelWithRelations(ActivityModel $model, $clear = false)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $clear ? 0 : $model->id;
            $entity->configId = $model->configId;
            $entity->fgnActivityId = $model->id;
            $entity->axis = $model->getAxis();
            $entity->action = $model->getAction();
            $entity->code = $model->code;
            $entity->name = $model->name;
            $entity->type = $model->getType();
            $entity->goalAnnual = $model->goalAnnual == 1;
            $entity->group = $model->getGroup();
            $entity->hasCoverage = false;
            $entity->hasCompliance = false;
            $entity->goalCoverage = $model->goalCoverage;
            $entity->goalCompliance = $model->goalCompliance;
            $entity->assignmentCoverage = $model->assignmentCoverage;
            $entity->assignmentCompliance = $model->assignmentCompliance;

            $entity->details = [];
            $entity->details["strategy"] = $model->strategy->each(function ($value) {
                $value->strategy = $value->getStrategy();
            });
            $entity->details["indicator"] = $model->indicator->each(function ($value) use ($entity, $clear) {
                $value->type = $value->getType();
                $value->activityIndicatorId = $value->id;
                $value->periodicity = $value->getPeriodicity();
                if ($value->type->value == "T001") {
                    $entity->hasCoverage = true;
                    if (!$clear) {
                        $value->goals = $this->allGoals("T001", $entity->fgnActivityId);
                    } else {
                        $value->id = 0;
                        $value->goal = 0;
                    }
                } elseif ($value->type->value == "T002") {
                    $entity->hasCompliance = true;
                    if (!$clear) {
                        $value->goals = $this->allGoals("T002", $entity->fgnActivityId);
                    } else {
                        $value->id = 0;
                        $value->goal = 0;
                    }
                }
            });

            return $entity;
        } else {
            return null;
        }
    }


    function allGoals($type, $id)
    {
        $months = new stdClass;
        $months->jan = 0;
        $months->feb = 0;
        $months->mar = 0;
        $months->apr = 0;
        $months->may = 0;
        $months->jun = 0;
        $months->jul = 0;
        $months->aug = 0;
        $months->sep = 0;
        $months->oct = 0;
        $months->nov = 0;
        $months->dec = 0;
        $months->goal = 0;

        IndicatorModel::whereFgnActivityId($id)
            ->join("wg_positiva_fgn_activity_indicator_sectional as is", function ($join) {
                $join->on("wg_positiva_fgn_activity_indicator.id", "=", "is.activity_indicator_id");
            })
            ->whereType($type)
            ->get()
            ->each(function ($value) use ($months) {
                $months->jan += (int)$value->jan;
                $months->feb += (int)$value->feb;
                $months->mar += (int)$value->mar;
                $months->apr += (int)$value->apr;
                $months->may += (int)$value->may;
                $months->jun += (int)$value->jun;
                $months->jul += (int)$value->jul;
                $months->aug += (int)$value->aug;
                $months->sep += (int)$value->sep;
                $months->oct += (int)$value->oct;
                $months->nov += (int)$value->nov;
                $months->dec += (int)$value->dec;
            });

        $months->goal += $months->jan + $months->feb + $months->mar + $months->apr + $months->may +
            $months->jun + $months->jul + $months->aug + $months->sep +
            $months->oct + $months->nov + $months->dec;

        return $months;
    }

    public function delete($id, $detail)
    {
        switch ($detail) {
            case "indicator":
                $hasSectional = ActivityConfigSectionalModel::whereActivityIndicatorId($id)->first();
                if ($hasSectional) {
                    throw new Exception("No se puede eliminar este indicador porque ya tiene seccionales asociadas.", 1);
                }
                $entityModel = IndicatorModel::find($id);
                break;
            case "strategy":
                $entityModel = StrategyModel::find($id);
                break;
            case "activity":
                $entityModel = ActivityModel::find($id);
                StrategyModel::whereFgnActivityId($id)->delete();
                IndicatorModel::whereFgnActivityId($id)->delete();

                ActivityConfigModel::whereFgnActivityId($id)
                    ->get()
                    ->each(function ($value) {
                        SubtaskModel::whereActivityConfigId($value->id)->delete();
                        $value->delete();
                    });

                ActivityConfigSectionalRelationModel::whereFgnActivityId($id)
                    ->get()
                    ->each(function ($value) {
                        ActivityConfigSectionalModel::whereSectionalRelationId($value->id)->delete();
                        ConfigConsultantRelationModel::whereSectionalRelationId($value->id)
                            ->get()
                            ->each(function ($value) {
                                ConfigConsultantModel::whereConsultantRelationId($value->id)->delete();
                                $value->delete();
                            });
                        $value->delete();
                    });

                // TODO
                // FALTA BORRAR CONSOLIDADOS
                // DB::table("wg_positiva_fgn_management_indicator_compliance")
                //     ->where("fgn_activity_id", $id)
                //     ->delete();
                // DB::table("wg_positiva_fgn_management_indicator_coverage")
                //     ->where("fgn_activity_id", $id)
                //     ->delete();
                // DB::table("wg_positiva_fgn_consolidated_indicators")
                //     ->where("fgn_activity_id", $id)
                //     ->delete();

                break;
        }

        $entityModel->delete();
    }
}
