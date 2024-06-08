<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Town Model
 */
class AgentSkill extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_agent_skill';

    /*
     * Validation
     */
    public $rules = [
        'agent_id' => 'required',
        'type' => 'required',
    ];

    public $belongsTo = [
        'agent' => ['Wgroup\Models\Agent', 'key' => 'agent_id', 'otherKey' => 'id'],
    ];

    public $timestamps = false;

    public function  getType(){
        return $this->getParameterByValue($this->skill, "agent_skill");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


}
