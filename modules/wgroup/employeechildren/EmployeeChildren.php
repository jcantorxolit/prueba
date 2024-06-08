<?php

namespace Wgroup\EmployeeChildren;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Models\InfoDetail;
use DB;

/**
 * Agent Model
 */
class EmployeeChildren extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_employee_children';

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public $hasMany = [

    ];

    public function scopeIsEnabled($query)
    {
        return $query->where('active', true);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
