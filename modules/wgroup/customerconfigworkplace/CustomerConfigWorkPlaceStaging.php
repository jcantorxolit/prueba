<?php

namespace Wgroup\CustomerConfigWorkPlace;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerConfigWorkPlaceStaging extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_config_workplace_staging';

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
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }


    public function  getType(){
        return $this->getParameterByValue($this->type, "wg_structure_type");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
