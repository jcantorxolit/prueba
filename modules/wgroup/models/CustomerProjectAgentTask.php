<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerProjectAgentTask extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_project_agent_task';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'typeTask' => ['Wgroup\ProjectTaskType\ProjectTaskType', 'key' => 'type', 'otherKey' => 'code'],
    ];

    public $hasMany = [
        'alerts' => ['Wgroup\Models\CustomerProjectTask'],
        'tracking' => ['Wgroup\Models\CustomerProjectAgentTaskTracking'],
    ];

    public function getTasks()
    {
        return CustomerProjectTask::whereCustomerProjectId($this->id)->get();
    }

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "project_status");
    }

    public function availableHours()
    {
        return $this->getParameterByValue('monthly_hour', "agent_available_hour");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
