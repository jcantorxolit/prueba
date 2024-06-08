<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerDiagnosticDisease extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_diagnostic_disease';

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

    public function  getRiskFactor()
    {
        return $this->getParameterByValue($this->riskFactor, "diagnostic_risk_factor");
    }

    public function  getDiagnosed(){
        return $this->getParameterByValue($this->diagnosed, "diagnostic_disease_diagnosed");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
