<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\PositivaFgn\GestPos\Task\TaskModel;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class SubtaskModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_activity_config_subtask";


    public function getExecutionType()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_execution_type")->whereValue($this->executionType)->first();
    }

    public function getMainTask()
    {
        return TaskModel::where("wg_positiva_fgn_gestpos.id",$this->gestposSubtaskId)
                ->join(DB::raw("wg_positiva_fgn_gestpos AS main"), function($join) {
                    $join->on("wg_positiva_fgn_gestpos.main_task", "=", "main.number");
                    $join->where("main.type", "=", "main");
                })
                ->select("main.name")->first()->name;
    }

    public function getDependenTask()
    {
        return TaskModel::whereId($this->gestposSubtaskId)
                ->select("name")->first()->name;
        
    }

}