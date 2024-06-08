<?php

namespace Wgroup\CustomerInternalProjectAgent;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInternalProjectAgent extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_internal_project_user';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];

    public function getTasks(){
        return CustomerProjectTask::whereCustomerInternalProjectAgentId($this->id)->get();
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "project_task_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
