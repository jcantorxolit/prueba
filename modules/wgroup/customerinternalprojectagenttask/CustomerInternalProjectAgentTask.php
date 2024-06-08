<?php

namespace Wgroup\CustomerInternalProjectAgentTask;

use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInternalProjectAgentTask extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_internal_project_user_task';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required',
    ];

    public $hasMany = [
        'tracking' => ['Wgroup\Models\CustomerInternalProjectAgentTaskTracking'],
    ];

    public function getTasks()
    {
        return CustomerProjectTask::whereCustomerProjectId($this->id)->get();
    }

    public function getType()
    {
        return $this->getCustomerParameterByValue($this->type, "projectTaskType");
    }

    public function getStatus()
    {
        return $this->getParameterByValue($this->status, "project_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    protected function getCustomerParameterByValue($value, $group, $ns = "wgroup")
    {
        return DB::table('wg_customer_parameter')->whereGroup($group)->whereId($value)->first();
    }
}
