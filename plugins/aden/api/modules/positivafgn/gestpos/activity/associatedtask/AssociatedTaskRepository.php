<?php

namespace AdeN\Api\Modules\PositivaFgn\GestPos\Activity\AssociatedTask;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use Carbon\Carbon;
use DB;
use Exception;

class AssociatedTaskRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new AssociatedTaskModel());
    }

    public function index($criteria, $showDependents)
    {
        $this->setColumns([
            "id" => "id",
            "number" => "number",
            "name" => "name",
            "type" => "type",
            "mainTask" => "mainTask",
        ]);

        $this->parseCriteria($criteria);
        $task = DB::table("wg_positiva_fgn_gestpos_associated_task")
            ->join("wg_positiva_fgn_gestpos", function ($join) {
                $join->on('wg_positiva_fgn_gestpos_associated_task.task_id', '=', 'wg_positiva_fgn_gestpos.id');
            })
            ->select(
                "wg_positiva_fgn_gestpos_associated_task.id",
                "wg_positiva_fgn_gestpos.number",
                "wg_positiva_fgn_gestpos.name",
                DB::raw("'Principal' AS type"),
                "wg_positiva_fgn_gestpos.main_task AS mainTask"
            )
            ->orderBy("number")
            ->orderBy("mainTask");

        $subtask = null;
        if ($showDependents) {
            $subtask = DB::table("wg_positiva_fgn_gestpos_associated_task")
                ->join("wg_positiva_fgn_gestpos", function ($join) {
                    $join->on('wg_positiva_fgn_gestpos_associated_task.task_id', '=', 'wg_positiva_fgn_gestpos.id');
                })
                ->join(DB::raw("wg_positiva_fgn_gestpos as subtask"), function ($join) {
                    $join->on('wg_positiva_fgn_gestpos.number', '=', 'subtask.main_task');
                })
                ->select(
                    "wg_positiva_fgn_gestpos_associated_task.id",
                    "subtask.number",
                    "subtask.name",
                    DB::raw("'Dependiente' AS type"),
                    "subtask.main_task AS mainTask"
                )
                ->orderBy("number")
                ->orderBy("mainTask");
        }

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'gestposId') {
                        $task->whereRaw(SqlHelper::getPreparedField('wg_positiva_fgn_gestpos_associated_task.gestpos_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        if ($subtask) {
                            $subtask->whereRaw(SqlHelper::getPreparedField('wg_positiva_fgn_gestpos_associated_task.gestpos_id') . " " . SqlHelper::getOperator($item->operator) . " " . SqlHelper::getPreparedData($item));
                        }
                    }
                }
            }
        }

        if ($subtask) {
            $task->union($subtask);
        }

        $query = $this->query(DB::table(DB::raw("({$task->toSql()}) as tasks")))
            ->orderByRaw('COALESCE(`mainTask`, `number`), `mainTask` IS NOT NULL, `number`');

        if ($subtask) {
            $query->mergeBindings($subtask);
        }

        return $this->get($query, $criteria);
    }

    public function mainTask($criteria, $gestposId, $showDependents)
    {
        $this->setColumns([
            "id" => "id",
            "number" => "number",
            "name" => "name",
            "type" => "type",
            "mainTask" => "mainTask",
        ]);

        $this->parseCriteria($criteria);
        $currents = "SELECT task_id
                        FROM wg_positiva_fgn_gestpos_associated_task
                        WHERE wg_positiva_fgn_gestpos_associated_task.gestpos_id = $gestposId";

        $task = DB::table("wg_positiva_fgn_gestpos")
            ->whereRaw("id NOT IN ($currents)")
            ->where("is_active", 1)
            ->where("type", "main")
            ->select(
                "id",
                "number",
                "name",
                DB::raw("'Principal' AS type"),
                "main_task AS mainTask"
            )
            ->orderBy("number")
            ->orderBy("mainTask");

        $subtask = null;
        if ($showDependents) {
            $subtask = DB::table("wg_positiva_fgn_gestpos")
                ->join(DB::raw("wg_positiva_fgn_gestpos as subtask"), function ($join) {
                    $join->on('wg_positiva_fgn_gestpos.number', '=', 'subtask.main_task');
                })
                ->whereRaw("wg_positiva_fgn_gestpos.id NOT IN ($currents)")
                ->where("subtask.is_active", 1)
                ->where("subtask.type", "dependenTask")
                ->select(
                    "subtask.id",
                    "subtask.number",
                    "subtask.name",
                    DB::raw("'Dependiente' AS type"),
                    "subtask.main_task AS mainTask"
                )
                ->orderBy("number")
                ->orderBy("mainTask");
        }

        if ($subtask) {
            $task->union($subtask);
        }

        $query = $this->query(DB::table(DB::raw("({$task->toSql()}) as tasks")))
            ->mergeBindings($task)
            ->orderByRaw('COALESCE(`mainTask`, `number`), `mainTask` IS NOT NULL, `number`');

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $authUser = $this->getAuthUser();
        $entityModel = $this->model->newInstance();
        $entityModel->id = null;
        $entityModel->gestposId = $entity->gestposId;
        $entityModel->taskId = $entity->taskId;

        $entityModel->createdAt = Carbon::now();
        $entityModel->createdBy = $authUser ? $authUser->id : 1;
        $entityModel->save();

        return $entity;
    }

    public function parseModelWithRelations(AssociatedTaskModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->campusId = $model->campusId;
            $entity->isActive = $model->isActive == 1;
            $entity->documentType = $model->getDocumentType();
            $entity->documentNumber = $model->documentNumber;
            $entity->fullName = $model->fullName;
            $entity->job = $model->job;
            $entity->telephone = $model->telephone;
            $entity->email = $model->email;

            return $entity;
        } else {
            return null;
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();
    }

}
