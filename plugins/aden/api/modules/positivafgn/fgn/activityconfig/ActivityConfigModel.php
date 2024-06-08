<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfig;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\PositivaFgn\GestPos\Task\TaskModel;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ActivityConfigModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_activity_config";

    public function getStrategy()
    {
        return ["strategy" => Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_consultant_strategy")->whereValue($this->strategy)->first()];
    }

    public function getModality()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_modality")->whereValue($this->modality)->first();
    }

    public function getExecutionType()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_execution_type")->whereValue($this->executionType)->first();
    }

    public function getGestposActivity()
    {
        return TaskModel::whereId($this->gestposActivityId)
                ->select(
                    "name as item",
                    "id as value"
                )->first();
    }

    public function getTask()
    {
        return TaskModel::whereId($this->gestposTaskId)
                ->select(
                    "name as item",
                    "id as value",
                    "number"
                )->first();
    }

}