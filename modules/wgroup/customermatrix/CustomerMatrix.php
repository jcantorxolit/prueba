<?php

namespace Wgroup\CustomerMatrix;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerMatrix extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_matrix';

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

    public function  getStatus(){
        return $this->getParameterByValue($this->status, "config_matrix_status");
    }


    public function  getType(){
        return $this->getParameterByValue($this->type, "wg_matrix_type");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
