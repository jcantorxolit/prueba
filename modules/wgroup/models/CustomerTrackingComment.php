<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

/**
 * Idea Model
 */
class CustomerTrackingComment extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_tracking_comment';

    public $belongsTo = [
        'agent' => ['Wgroup\Models\Agent', 'key' => 'agent_id', 'otherKey' => 'id'],
        'user' => ['Wgroup\CustomerUser\CustomerUser', 'key' => 'agent_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];


    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
