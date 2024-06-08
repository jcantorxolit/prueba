<?php

namespace Wgroup\CustomerInvestigationAlFactorCauseRootCause;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerInvestigationAlFactorCause\CustomerInvestigationAlFactorCause;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategory;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategoryDTO;

/**
 * Idea Model
 */
class CustomerInvestigationAlFactorCauseRootCause extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al_factor_cause_root_cause';

    public $belongsTo = [
        'improvementPlan' => ['Wgroup\CustomerInvestigationAlFactorCause\CustomerInvestigationAlFactorCause', 'key' => 'customer_investigation_factor_cause_id', 'otherKey' => 'id'],
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

    public function getParent()
    {
        //return  CustomerImprovementPlanCauseDTO::parse(CustomerImprovementPlanCause::find($this->customer_improvement_plan_cause_id));
        $parent = CustomerInvestigationAlFactorCause::find($this->customer_investigation_factor_cause_id);
        if ($parent != null) {
            $parent->cause = ImprovementPlanCauseCategoryDTO::parse(ImprovementPlanCauseCategory::find($parent->cause));
        }
        return  $parent;
    }

    public function getProbabilityOccurrence()
    {
        $this->getParameterByValue($this->probabilityOccurrence, 'improvement_plan_root_cause_probability_occur');
    }

    public function getEffect()
    {
        $this->getParameterByValue($this->effect, 'improvement_plan_root_cause_effect');
    }

    public function getDetectionLevel()
    {
        $this->getParameterByValue($this->detectionLevel, 'improvement_plan_root_detection_level');
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
