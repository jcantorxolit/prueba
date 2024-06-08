<?php

namespace Wgroup\MinimumStandardItemDetail;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class MinimumStandardItemDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_minimum_standard_item_detail';

    public $belongsTo = [
        'item' => ['Wgroup\MinimumStandardItem\MinimumStandardItem', 'key' => 'minimum_standard_item_id', 'otherKey' => 'id'],
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
