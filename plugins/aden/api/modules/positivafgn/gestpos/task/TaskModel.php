<?php

namespace AdeN\Api\Modules\PositivaFgn\GestPos\Task;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class TaskModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_gestpos";

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

    public function getMainTask()
    {
        if($this->type == "dependenTask") {
            return DB::table("wg_positiva_fgn_gestpos","code")
                    ->select("name AS item","number AS value")
                    ->where("number", $this->mainTask)
                    ->where("type", "main")
                    ->first();
        }
        return  null;
    }

    public function getSubTask()
    {
        if($this->type == "dependenTask") {
            return DB::table("wg_positiva_fgn_gestpos")
                    ->select("name AS item","number AS value", "code")
                    ->where("number", $this->number)
                    ->where("type", "subtask")
                    ->first();
        }
        return  null;
    }
    
	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

}