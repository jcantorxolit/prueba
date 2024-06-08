<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\Employee\CriticalActivity;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\Customer\CustomerModel;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerEmployeeCriticalActivityModel extends Model
{
    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_employee_critical_activity";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];
}
