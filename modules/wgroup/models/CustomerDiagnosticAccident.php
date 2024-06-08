<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerDiagnosticAccident extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_diagnostic_accident';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'accident' => ['Wgroup\Models\Accident', 'key' => 'accident_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];


    public function  getUnsafeAct(){
        return $this->getParameterByValue($this->unsafeAct, "diagnostic_accident_status");
    }

    public function  getUnsafeCondition(){
        return $this->getParameterByValue($this->unsafeCondition, "diagnostic_accident_status");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
