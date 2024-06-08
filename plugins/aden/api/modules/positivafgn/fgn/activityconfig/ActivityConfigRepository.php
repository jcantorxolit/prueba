<?php


namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\PositivaFgn\Fgn\Activity\ActivityModel;
use AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant\ConfigConsultantRelationModel;
use AdeN\Api\Modules\PositivaFgn\GestPos\Task\TaskModel;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class ActivityConfigRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ActivityConfigModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_activity_config.id",
            "strategy" => "positiva_fgn_consultant_strategy.item AS strategy",
            "modality" => "positiva_fgn_activity_modality.item AS modality",
            "executionType" => "positiva_fgn_activity_execution_type.item AS executionType",
            "activity" => "activity.name AS activity",
            "task" => "task.name as task",
            "fgnActivityId" => "wg_positiva_fgn_activity_config.fgn_activity_id"
        ]);


        $this->parseCriteria($criteria);
        $query = $this->query()
        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
            $join->on('wg_positiva_fgn_activity_config.strategy', '=', 'positiva_fgn_consultant_strategy.value');
        })
        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_modality')), function ($join) {
            $join->on('wg_positiva_fgn_activity_config.modality', '=', 'positiva_fgn_activity_modality.value');
        })
        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_execution_type')), function ($join) {
            $join->on('wg_positiva_fgn_activity_config.execution_type', '=', 'positiva_fgn_activity_execution_type.value');
        })
        ->join(DB::raw("wg_positiva_fgn_gestpos as activity"), function($join) {
            $join->on("wg_positiva_fgn_activity_config.gestpos_activity_id","=","activity.id");
        })
        ->leftjoin(DB::raw("wg_positiva_fgn_gestpos AS task"), function($join) {
            $join->on("wg_positiva_fgn_activity_config.gestpos_task_id","=","task.id");
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function allActivityStrategy($criteria, $strategy)
    {
        $this->setColumns([
            "code" => "activity.code",
            "activity" => "activity.name AS activity",
            "task" => "task.name AS task",
            "idActivity" => "activity.id AS idActivity",
            "idTask" => "task.id AS idTask",
            "number" => "task.number"
        ]);

        $strategysAct = "SELECT gestpos_id
            FROM wg_positiva_fgn_gestpos_strategy
            WHERE strategy = '{$strategy}' AND is_active = 1";

        $this->parseCriteria($criteria);
        $query = DB::table(DB::raw("wg_positiva_fgn_gestpos as activity"));
        $query->leftjoin("wg_positiva_fgn_gestpos_associated_task", function($join) {
            $join->on("activity.id","=","wg_positiva_fgn_gestpos_associated_task.gestpos_id");
        })
        ->leftjoin(DB::raw("wg_positiva_fgn_gestpos AS task"), function($join) {
            $join->on("wg_positiva_fgn_gestpos_associated_task.task_id","=","task.id");
        })
        ->where("activity.type","activity")
        ->whereRaw("activity.id IN ({$strategysAct})");

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function allSubtask($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_activity_config_subtask.id",
            "subtask" => "subtask.name AS subtask",
            "executionType" => "positiva_fgn_activity_execution_type.item AS executionType",
            "providesCoverage" => DB::raw("IF(wg_positiva_fgn_activity_config_subtask.provides_coverage=1,'SI','NO') AS providesCoverage"),
            "providesCompliance" => DB::raw("IF(wg_positiva_fgn_activity_config_subtask.provides_compliance=1,'SI','NO') AS providesCompliance"),
            "activityConfigId" => "wg_positiva_fgn_activity_config_subtask.activity_config_id AS activityConfigId"
        ]);


        $this->parseCriteria($criteria);
        $query = DB::table(DB::raw("wg_positiva_fgn_activity_config_subtask"))
        ->join(DB::raw("wg_positiva_fgn_gestpos AS subtask"), function($join) {
            $join->on("wg_positiva_fgn_activity_config_subtask.gestpos_subtask_id","=","subtask.id");
        })
        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_activity_execution_type')), function ($join) {
            $join->on('wg_positiva_fgn_activity_config_subtask.execution_type', '=', 'positiva_fgn_activity_execution_type.value');
        })
        ->where("subtask.type","dependenTask");

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
        $entityModel->fgnActivityId = $entity->fgnActivityId;
        $entityModel->strategy = $entity->strategy->strategy->value;
        $entityModel->modality = $entity->modality->value;
        $entityModel->executionType = $entity->executionType->value;
        $entityModel->gestposActivityId = $entity->activity->value;
        $entityModel->gestposTaskId = $entity->task ? $entity->task->value : null;
        $entityModel->providesCoverage = $entity->providesCoverage == 1;
        $entityModel->providesCompliance = $entity->providesCompliance == 1;
        
        $valideUnique = ActivityConfigModel::where("fgn_activity_id", $entityModel->fgnActivityId)
        ->where("strategy", $entityModel->strategy)
        ->where("modality", $entityModel->modality)
        ->where("execution_type", $entityModel->executionType)
        ->where("gestpos_activity_id", $entityModel->gestposActivityId);
        
        if($entityModel->gestposTaskId){
            $valideUnique = $valideUnique->where("gestpos_task_id", $entityModel->gestposTaskId);
        }
        
        $valideUnique = $valideUnique->first();
        if($valideUnique && $valideUnique->id != $entity->id) {
            throw new \Exception('No es posible adicionar la información, ya existe una configuración con estas características.');
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

        if($entity->task){
            $this->saveSubtask($entity, $entityModel);
        }
        $entity->id = $entityModel->id;
        return $entity;
    }

    private function saveSubtask($entity, $entityModel)
    {
        $subTasks = TaskModel::whereMainTask($entity->task->number)->whereType("dependenTask")->get();
        foreach ($subTasks as $subTask) {
            $SubtaskModel = SubtaskModel::whereGestposSubtaskId($subTask->id)->whereActivityConfigId($entityModel->id)->first();
            if(!$SubtaskModel){
                $SubtaskModel = new SubtaskModel();
            }
            $SubtaskModel->activityConfigId = $entityModel->id;
            $SubtaskModel->gestposSubtaskId = $subTask->id;
            $SubtaskModel->providesCoverage = $SubtaskModel->id ? $SubtaskModel->providesCoverage : $entityModel->providesCoverage;
            $SubtaskModel->providesCompliance = $SubtaskModel->id ? $SubtaskModel->providesCompliance : $entityModel->providesCompliance;
            $SubtaskModel->executionType = $SubtaskModel->id ? $SubtaskModel->executionType : $entityModel->executionType;
            $SubtaskModel->save();
        }

    }

    public function parseModelWithRelations(ActivityConfigModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->fgnActivityId = $model->fgnActivityId;
            $entity->strategy = $model->getStrategy();
            $entity->modality = $model->getModality();
            $entity->executionType = $model->getExecutionType();
            $entity->activity = $model->getGestposActivity();
            $entity->task = $model->getTask();
            $entity->providesCoverage = $model->providesCoverage == 1;
            $entity->providesCompliance = $model->providesCompliance == 1;

            return $entity;
        }
         else {
            return null;
        }
    }


    public function insertOrUpdateSubtask($entity)
    {
            $SubtaskModel = SubtaskModel::find($entity->id);
            $SubtaskModel->providesCoverage = $entity->providesCoverage == 1;
            $SubtaskModel->providesCompliance = $entity->providesCompliance == 1;
            $SubtaskModel->executionType = $entity->executionType->value;
            $SubtaskModel->save();
    }

    public function parseModelWithRelationsSubtask($idSubtask, $activityId)
    {
        $model = SubtaskModel::find($idSubtask);
        //Mapping fields
        $entity = new \stdClass();
        $entity->id = $model->id;
        $entity->executionType = $model->getExecutionType();
        $entity->providesCoverage = $model->providesCoverage == 1;
        $entity->providesCompliance = $model->providesCompliance == 1;
        $entity->mainTask = $model->getMainTask();
        $entity->dependenTask = $model->getDependenTask();

        $activityModel = ActivityModel::find($activityId);
        $entity->axis = $activityModel->getAxis();
        $entity->action = $activityModel->getAction();
        $entity->code = $activityModel->code;
        $entity->name = $activityModel->name;
        $entity->hasCoverage = false;
        $entity->hasCompliance = false;
        $activityModel->indicator->each(function($value) use($entity){
            $value->type = $value->getType();
            if($value->type->value == "T001"){
                $entity->hasCoverage = true;
            } elseif($value->type->value == "T002"){
                $entity->hasCompliance = true;
            }
        });

        return $entity;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $hasConsultant = ConfigConsultantRelationModel::whereActivityConfigId($id)->first();
        if($hasConsultant){
            throw new Exception("No se puede eliminar esta actividad porque ya tiene asesores asignados.", 1);
        }

        $entityModel->delete();
        SubtaskModel::whereActivityConfigId($id)->delete();

    }


}
