<?php

namespace Wgroup\NephosIntegration;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class NephosIntegration extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_nephos_customer_tracking';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [

    ];

    public function  getStatus(){
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }


    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
