<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class ProgramManagement extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_program_management';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public function  getStatus(){
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


}
