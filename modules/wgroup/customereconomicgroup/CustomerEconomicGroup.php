<?php

namespace Wgroup\CustomerEconomicGroup;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerEconomicGroup extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_economic_group';

    public $belongsTo = [
        'parent' => ['Wgroup\Models\Customer', 'key' => 'parent_id', 'otherKey' => 'id'],
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
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

    public function  getIsActive()
    {
        return $this->isActive == 1 ? true : false;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
