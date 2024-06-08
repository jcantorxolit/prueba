<?php

namespace Wgroup\RoadSafetyItemDetail;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class RoadSafetyItemDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_road_safety_item_detail';

    public $belongsTo = [
        'item' => ['Wgroup\RoadSafetyItem\RoadSafetyItem', 'key' => 'road_safety_item_id', 'otherKey' => 'id'],
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
