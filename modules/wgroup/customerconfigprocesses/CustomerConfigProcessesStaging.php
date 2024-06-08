<?php

namespace Wgroup\CustomerConfigProcesses;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerConfigProcessesStaging extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_config_process_staging';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'workplace' => ['Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlace', 'key' => 'workplace_id', 'otherKey' => 'id'],
        'macro' => ['Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcesses', 'key' => 'macro_process_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public function  getStatus(){
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }


    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
