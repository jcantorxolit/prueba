<?php

namespace Wgroup\CustomerInvestigationAlFactorCauseSubCause;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\ImprovementPlanCause\ImprovementPlanCause;
use Wgroup\ImprovementPlanCause\ImprovementPlanCauseDTO;

/**
 * Idea Model
 */
class CustomerInvestigationAlFactorCauseSubCause extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al_factor_cause_sub_cause';

    public $belongsTo = [
        'causeParent' => ['Wgroup\CustomerInvestigationAlFactorCause\CustomerInvestigationAlFactorCause', 'key' => 'customer_investigation_factor_cause_id', 'otherKey' => 'id'],
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
