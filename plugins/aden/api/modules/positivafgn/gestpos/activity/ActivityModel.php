<?php

namespace AdeN\Api\Modules\PositivaFgn\GestPos\Activity;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\PositivaFgn\GestPos\Activity\AssociatedTask\AssociatedTaskModel;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ActivityModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_gestpos";

    public $hasMany = [
        "strategys" => ["AdeN\Api\Modules\PositivaFgn\GestPos\Activity\StrategyModel", 'key' => 'gestpos_id', 'otherKey' => 'id']
    ];

    public function getSector()
    {
        return $this->getParameterByValue($this->sector, "positiva_fgn_gestpos_sector");
    }

    public function getProgram()
    {
        return $this->getParameterByValue($this->program, "positiva_fgn_gestpos_program");
    }

    public function getPlan()
    {
        return $this->getParameterByValue($this->plan, "positiva_fgn_gestpos_plan");
    }

    public function getActionLine()
    {
        return $this->getParameterByValue($this->actionLine, "positiva_fgn_gestpos_action_line");
    }

    public function getActivityType()
    {
        return $this->getParameterByValue($this->activityType, "positiva_fgn_gestpos_activity_type");
    }

    public function hasTask()
    {
        $task = AssociatedTaskModel::whereGestposId($this->id)->first();
        if($task){
            return false;
        }

        return true;
    }
    
	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

}