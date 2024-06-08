<?php

namespace Wgroup\CustomerEvaluationMinimumStandardItem;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerEvaluationMinimumStandardItem extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_evaluation_minimum_standard_item';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
    ];

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "diagnostic_prevention_status");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
