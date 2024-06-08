<?php

namespace Wgroup\CustomerInvestigationAlMeasureActionPlanRespTask;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInvestigationAlMeasureActionPlanRespTask extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al_measure_action_plan_resp_task';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [
        'tracking' => ['Wgroup\CustomerInvestigationAlMeasureActionPlanRespTask\CustomerInvestigationAlMeasureActionPlanRespTaskTracking'],
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
