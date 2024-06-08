<?php

namespace Wgroup\CustomerEvaluationMinimumStandardItemDetail;

use BackendAuth;
use Illuminate\Support\Facades\DB;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;


/**
 * Idea Model
 */
class CustomerEvaluationMinimumStandardItemDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_evaluation_minimum_standard_item_detail';

    public $belongsTo = [
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
