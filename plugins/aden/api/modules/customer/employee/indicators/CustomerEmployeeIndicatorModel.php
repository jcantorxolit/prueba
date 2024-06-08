<?php

namespace AdeN\Api\Modules\Customer\Employee\Indicators;

use October\Rain\Database\Model;
use AdeN\Api\Classes\CamelCasing;

class CustomerEmployeeIndicatorModel extends Model
{
	use CamelCasing;

    protected $table = "wg_customer_employee_demographic_consolidate";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

}
