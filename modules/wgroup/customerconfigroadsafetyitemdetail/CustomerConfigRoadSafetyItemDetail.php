<?php

namespace Wgroup\CustomerConfigRoadSafetyItemDetail;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerConfigRoadSafetyItemDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_config_road_safety_item_detail';

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
    ];

    /*
     * Validation
     */
    public $rules = [

    ];

    public $hasMany = [

    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
