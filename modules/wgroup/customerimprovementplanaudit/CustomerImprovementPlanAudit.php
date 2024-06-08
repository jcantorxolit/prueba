<?php

namespace Wgroup\CustomerImprovementPlanAudit;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerImprovementPlanAudit extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_improvement_plan_audit';

    public $belongsTo = [
        'improvementPlan' => ['Wgroup\CustomerImprovementPlan\CustomerImprovementPlan', 'key' => 'customer_improvement_plan_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
    ];

    public $hasMany = [

    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
