<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerDiagnosticRiskFactor extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customers_diagnostic_risk_factor';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];


    public function  getRisk()
    {
        return $this->getParameterByValue($this->risk, "diagnostic_risk_factor");
    }

    public function  getStatusType()
    {
        return null;//$this->getParameterByValue($this->status, "diagnostic_prevention_status");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
