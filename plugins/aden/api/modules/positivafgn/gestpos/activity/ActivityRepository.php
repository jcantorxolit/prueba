<?php

namespace AdeN\Api\Modules\PositivaFgn\GestPos\Activity;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Modules\PositivaFgn\GestPos\Activity\AssociatedTask\AssociatedTaskModel;
use AdeN\Api\Modules\PositivaFgn\GestPos\Task\TaskModel;
use Carbon\Carbon;
use DB;
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
            "id" => "wg_positiva_fgn_gestpos.id",
            "name" => "wg_positiva_fgn_gestpos.name",
            "code" => "wg_positiva_fgn_gestpos.code",
            "strategy" => "strategys.strategy",
            "isActive" => DB::raw("IF(wg_positiva_fgn_gestpos.is_active=1,'Activo','Inactivo') AS isActive"),
        ]);

        $strategy = DB::table("wg_positiva_fgn_gestpos_strategy")
            ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                $join->on('wg_positiva_fgn_gestpos_strategy.strategy', '=', 'positiva_fgn_consultant_strategy.value');
            })
            ->select(
                DB::raw("GROUP_CONCAT(' ',positiva_fgn_consultant_strategy.item) as strategy"),
                "gestpos_id"
            )
            ->groupBy("gestpos_id");

        $this->parseCriteria($criteria);
        $query = $this->query()
            ->leftjoin(DB::raw("({$strategy->toSql()}) as strategys"), function ($join) {
                $join->on("wg_positiva_fgn_gestpos.id", "=", "strategys.gestpos_id");
            })
            ->mergeBindings($strategy)
            ->where("type", "activity");

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
        $entityModel->type = "activity";
        $entityModel->name = $entity->name;
        $entityModel->isActive = $entity->isActive == 1;
        $entityModel->isAutomatic = $entity->isAutomatic;
        $entityModel->sector = $entity->sector ? $entity->sector->value : null;
        $entityModel->program = $entity->program ? $entity->program->value : null;
        $entityModel->plan = $entity->plan ? $entity->plan->value : null;
        $entityModel->actionLine = $entity->actionLine ? $entity->actionLine->value : null;
        $entityModel->consecutive = $entity->consecutive;
        $entityModel->code = $entity->sector->item . $entity->program->item . $entity->plan->item . $entity->actionLine->item . $entity->consecutive;
        $entityModel->activityType = $entity->activityType ? $entity->activityType->value : null;

        if ($isNewRecord) {
            $entityModel->createdAt = Carbon::now();
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        }

        if ($entity->enableAutomatic && $entity->isAutomatic) {
            $task = TaskModel::whereName($entity->name)
                    ->whereType("main")
                    ->first();

            if(!$task){
                $task = new TaskModel;
                $task->name = $entity->name;
                $task->type = "main";
                $task->number = TaskModel::max("number") + 1;
                $task->save();
            }

            $addTask = AssociatedTaskModel::whereGestposId($entityModel->id)
                    ->whereTaskId($task->id)
                    ->first();

            if(!$addTask){
                $addTask = new AssociatedTaskModel;
                $addTask->gestposId = $entityModel->id;
                $addTask->taskId = $task->id;
                $addTask->save();
            }
            $entity->enableAutomatic = false;
        }

        $entity = $this->saveStrategy($entity, $entityModel);
        $entity->id = $entityModel->id;
        return $entity;
    }

    private function saveStrategy($entity, $entityModel)
    {
        if ($entity->strategy) {
            foreach ($entity->strategy as $key => $strategy) {
                $StrategyModel = StrategyModel::findOrNew($strategy->id);
                $StrategyModel->id = $strategy->id;
                $StrategyModel->gestposId = $entityModel->id;
                $StrategyModel->strategy = $strategy->strategy->value;
                $StrategyModel->isActive = $strategy->isActive;
                $StrategyModel->save();
                $strategy->id = $StrategyModel->id;
                $entity->strategy[$key] = $strategy;
            }
        }

        return $entity;
    }

    public function delete($id)
    {
        $entityModel = StrategyModel::find($id);
        $entityModel->delete();
    }

    public function parseModelWithRelations(ActivityModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->type = $model->type;
            $entity->name = $model->name;
            $entity->isActive = $model->isActive == 1;
            $entity->isAutomatic = $model->is_automatic;
            $entity->enableAutomatic = $model->hasTask();
            $entity->sector = $model->getSector();
            $entity->program = $model->getProgram();
            $entity->plan = $model->getPlan();
            $entity->actionLine = $model->getActionLine();
            $entity->consecutive = $model->consecutive;
            $entity->activityType = $model->getActivityType();
            $entity->strategy = $model->strategys->each(function ($value) {
                $value->strategy = $value->getStrategy();
                $value->isActive = $value->isActive == 1;
            });

            return $entity;
        } else {
            return null;
        }
    }

    public function config($config)
    {
        $result = [];
        foreach ($config as $criteria) {
            switch ($criteria->name) {
            }
        }

        return $result;

    }

}
