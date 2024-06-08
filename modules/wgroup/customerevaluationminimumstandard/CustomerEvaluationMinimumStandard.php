<?php

namespace Wgroup\CustomerEvaluationMinimumStandard;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerEvaluationMinimumStandard extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_evaluation_minimum_standard';

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
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
