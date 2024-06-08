<?php

namespace Wgroup\CustomerConfigActivity;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerConfigActivity extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_config_activity';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'job' => ['Wgroup\CustomerConfigJob\CustomerConfigJob', 'key' => 'job_id', 'otherKey' => 'id'],
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
