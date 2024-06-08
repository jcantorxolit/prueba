<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerDiagnostic extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_diagnostic';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'agent' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
    ];

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "diagnostic_status");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
