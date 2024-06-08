<?php

namespace Wgroup\CustomerImprovementPlanCauseSubCause;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\ImprovementPlanCause\ImprovementPlanCause;
use Wgroup\ImprovementPlanCause\ImprovementPlanCauseDTO;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategory;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategoryDTO;

/**
 * Idea Model
 */
class CustomerImprovementPlanCauseSubCause extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_improvement_plan_cause_sub_cause';

    public $belongsTo = [
        'causeParent' => ['Wgroup\CustomerImprovementPlanCause\CustomerImprovementPlanCause', 'key' => 'customer_improvement_plan_cause_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
    ];

    public $hasMany = [

    ];

    public function  getIsActive()
    {
        return $this->isActive == 1;
    }

    public function getCause()
    {
        return  ImprovementPlanCauseDTO::parse(ImprovementPlanCause::find($this->cause));
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
