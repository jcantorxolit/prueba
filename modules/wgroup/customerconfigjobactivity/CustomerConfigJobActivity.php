<?php

namespace Wgroup\CustomerConfigJobActivity;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfigActivity\CustomerConfigActivity;

/**
 * Idea Model
 */
class CustomerConfigJobActivity extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_config_job_activity';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'job' => ['Wgroup\CustomerConfigJob\CustomerConfigJob', 'key' => 'job_id', 'otherKey' => 'id'],
        'activity' => ['Wgroup\CustomerConfigActivity\CustomerConfigActivity', 'key' => 'activity_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public function  getStatus(){
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }

    public function getActivity()
    {
        return CustomerConfigActivity::whereId($this->activity_id)->first();
    }


    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
