<?php

namespace Wgroup\CustomerConfig;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class ConfigGeneral extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_config_general';

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

    public function  getStatus(){
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }

    public static function getRelationTable($table)
    {
        return "(SELECT *  FROM `wg_config_general` WHERE `type` = '$table') $table ";
    }


    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
