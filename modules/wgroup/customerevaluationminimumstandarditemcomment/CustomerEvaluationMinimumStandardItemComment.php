<?php

namespace Wgroup\CustomerEvaluationMinimumStandardItemComment;

use BackendAuth;
use Illuminate\Support\Facades\DB;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;


/**
 * Idea Model
 */
class CustomerEvaluationMinimumStandardItemComment extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_evaluation_minimum_standard_item_comment';

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
