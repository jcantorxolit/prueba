<?php

namespace Wgroup\CustomerConfigJobActivityIntervention;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerConfigJobActivityIntervention extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_config_job_activity_hazard_intervention';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'activity' => ['Wgroup\CustomerConfigJobActivity\CustomerConfigJobActivity', 'key' => 'job_activity_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public function  getType(){
        return $this->getParameterByValue($this->type, "config_type_measure");
    }

    public function  getTracking(){
        return $this->getParameterByValue($this->tracking, "hazard_tracking");
    }


    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
