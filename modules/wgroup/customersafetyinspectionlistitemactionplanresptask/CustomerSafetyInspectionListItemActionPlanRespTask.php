<?php

namespace Wgroup\CustomerSafetyInspectionListItemActionPlanRespTask;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerSafetyInspectionListItemActionPlanRespTask extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_safety_inspection_list_item_action_plan_resp_task';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [
        'tracking' => ['Wgroup\CustomerSafetyInspectionListItemActionPlanRespTask\CustomerSafetyInspectionListItemActionPlanRespTaskTracking'],
    ];

    public function getTasks(){
        //return CustomerProjectTask::whereCustomerProjectId($this->id)->get();
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "project_task_type");
    }

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "project_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
