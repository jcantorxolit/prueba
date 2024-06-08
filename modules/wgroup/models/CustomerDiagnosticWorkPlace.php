<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerDiagnosticWorkPlace extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customers_diagnostic_workplace';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'town' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
    ];

    public function  getStatus(){
        return $this->getParameterByValue($this->status, "diagnostic_workplace_status");
    }

    public function  getActivity(){
        return $this->getParameterByValue($this->activity, "diagnostic_workplace_activity");
    }

    public function  getArea(){
        return $this->getParameterByValue($this->area, "diagnostic_workplace_area");
    }

    public function  getRisk(){
        return $this->getParameterByValue($this->risk, "diagnostic_workplace_risk");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
