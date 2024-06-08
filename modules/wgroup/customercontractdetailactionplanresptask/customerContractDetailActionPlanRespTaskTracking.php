<?php

namespace Wgroup\customerContractDetailActionPlanRespTask;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class customerContractDetailActionPlanRespTaskTracking extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_internal_project_user_task_tracking';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
