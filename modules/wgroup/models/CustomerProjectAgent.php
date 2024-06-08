<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerProjectAgent extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_project_agent';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [
        'alerts' => ['Wgroup\Models\CustomerProjectTask'],
    ];

    public function getTasks()
    {
        return CustomerProjectTask::whereCustomerProjectAgentId($this->id)->get();
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
