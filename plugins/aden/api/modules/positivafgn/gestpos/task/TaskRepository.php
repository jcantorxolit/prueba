<?php

namespace AdeN\Api\Modules\PositivaFgn\GestPos\Task;

use AdeN\Api\Classes\BaseRepository;
use Carbon\Carbon;
use DB;
use Exception;

class TaskRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new TaskModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_gestpos.id",
            "number" => "wg_positiva_fgn_gestpos.number",
            "code" => "wg_positiva_fgn_gestpos.code",
            "name" => "wg_positiva_fgn_gestpos.name",
            "type" => DB::raw("IF(wg_positiva_fgn_gestpos.type='main','Principal',IF(wg_positiva_fgn_gestpos.type='subtask','Subtarea','Dependiente')) AS type"),
            "mainTask" => "wg_positiva_fgn_gestpos.main_task",
            "isActive" => DB::raw("IF(wg_positiva_fgn_gestpos.is_active=1,'Activo','Inactivo') AS isActive"),
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query()->whereRaw("type IN(?,?,?)", ["main", "subtask", "dependenTask"]);
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
        $entityModel->type = $entity->type;
        $entityModel->name = $entity->name;
        $entityModel->isActive = $entity->isActive == 1;
        $entityModel->sector = $entity->sector ? $entity->sector->value : null;
        $entityModel->program = $entity->program ? $entity->program->value : null;
        $entityModel->plan = $entity->plan ? $entity->plan->value : null;
        $entityModel->actionLine = $entity->actionLine ? $entity->actionLine->value : null;
        $entityModel->consecutive = $entity->consecutive;

        if ($entity->addCode) {
            $entityModel->code = $entity->sector->item . $entity->program->item . $entity->plan->item . $entity->actionLine->item . $entity->consecutive;
        } else {
            $entityModel->code = null;
        }

        if (!$entityModel->number) {
            $entityModel->number = TaskModel::max("number") + 1;
        }

        if ($entity->type == "dependenTask") {
            if ($entity->subTask) {
                $entityModel->name = $entity->subTask->code ? $entity->subTask->code . " - " . $entity->subTask->item : $entity->subTask->item;
                $entityModel->number = $entity->subTask->value;
            } else {
                $subTask = clone ($entityModel);
                $subTask->type = "subtask";

                $findSubTask = TaskModel::where("type", $subTask->type)
                    ->where("name", $subTask->name)
                    ->where("code", $subTask->code)
                    ->first();

                if (!$findSubTask) {
                    $subTask->save();
                }

                $entityModel->code = null;
                $entityModel->sector = null;
                $entityModel->program = null;
                $entityModel->plan = null;
                $entityModel->actionLine = null;
                $entityModel->consecutive = null;

                $entityModel->name = $subTask->code ? $subTask->code . " - " . $subTask->name : $subTask->name;

            }
            $entityModel->mainTask = $entity->mainTask ? $entity->mainTask->value : null;
        }

        if ($entity->type == "subtask") {
            TaskModel::where("type", "dependenTask")
                    ->where("number", $entityModel->number)
                    ->get()
                    ->map(function($task) use($entityModel) {
                        $task->name = $entityModel->code . " - " . $entityModel->name;
                        $task->save();
                    });
        }

        $valideUnique = TaskModel::where("type", $entity->type)
            ->where("name", $entityModel->name)
            ->where("main_task", $entityModel->mainTask)
            ->where("number", $entityModel->number)
            ->first();

        if ($valideUnique && $valideUnique->id != $entity->id) {
            throw new \Exception('No es posible adicionar la información, ya existe una tarea con estas características.');
        }

        if ($isNewRecord) {
            $entityModel->createdAt = Carbon::now();
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        }

        return $entityModel;
    }

    public function parseModelWithRelations(TaskModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->type = $model->type;
            $entity->name = $model->name;
            $entity->isActive = $model->isActive == 1;
            $entity->sector = $model->getSector();
            $entity->program = $model->getProgram();
            $entity->plan = $model->getPlan();
            $entity->actionLine = $model->getActionLine();
            $entity->mainTask = $model->getMainTask();
            $entity->subTask = $model->getSubTask();
            $entity->consecutive = $model->consecutive;
            $entity->addCode = $model->code != null;

            return $entity;
        } else {
            return null;
        }
    }

    public function delete($id)
    {
        $entityModel = $this->find($id);
        if($entityModel->type != "dependenTask") {
            $main = TaskModel::whereMainTask($entityModel->number)->first();
            $subtask = TaskModel::whereNumber($entityModel->number)
                ->whereType("dependenTask")
                ->first();
    
            if ($main || $subtask) {
                throw new Exception("No se puede eliminar este registro porque ya tiene una tarea asociada.", 1);
            }
        }
        $entityModel->delete();
    }

    public function config($config)
    {
        $result = [];
        foreach ($config as $criteria) {
            switch ($criteria->name) {
                case "main_task_list":
                    $result["mainTaskList"] = TaskModel::whereType("main")
                        ->whereIsActive(1)
                        ->select("name AS item", "number AS value")
                        ->get();
                    break;
                case "subtask_list":
                    $result["subTaskList"] = TaskModel::whereType("subtask")
                        ->whereIsActive(1)
                        ->select("name AS item", "number AS value", "code")
                        ->get();
                    break;
            }
        }

        return $result;

    }

}
