<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerDiagnosticPrevention extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_diagnostic_prevention';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'rate' => ['Wgroup\Models\Rate', 'key' => 'rate_id', 'otherKey' => 'id']
    ];

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "diagnostic_prevention_status");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
