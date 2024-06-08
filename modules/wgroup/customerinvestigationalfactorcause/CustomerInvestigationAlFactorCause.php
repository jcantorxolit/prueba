<?php

namespace Wgroup\CustomerInvestigationAlFactorCause;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerInvestigationAlFactorCauseRootCause\CustomerInvestigationAlFactorCauseRootCause;
use Wgroup\CustomerInvestigationAlFactorCauseRootCause\CustomerInvestigationAlFactorCauseRootCauseDTO;
use Wgroup\CustomerInvestigationAlFactorCauseSubCause\CustomerInvestigationAlFactorCauseSubCause;
use Wgroup\CustomerInvestigationAlFactorCauseSubCause\CustomerInvestigationAlFactorCauseSubCauseDTO;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategory;
use Wgroup\ImprovementPlanCauseCategory\ImprovementPlanCauseCategoryDTO;

/**
 * Idea Model
 */
class CustomerInvestigationAlFactorCause extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al_factor_cause';

    public $belongsTo = [
        'improvementPlan' => ['Wgroup\CustomerInvestigationAl\CustomerInvestigationAl', 'key' => 'customer_investigation_id', 'otherKey' => 'id'],
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
        return  ImprovementPlanCauseCategoryDTO::parse(ImprovementPlanCauseCategory::find($this->cause));
    }

    public function getSubCauses()
    {
        return CustomerInvestigationAlFactorCauseSubCauseDTO::parse(CustomerInvestigationAlFactorCauseSubCause::whereCustomerInvestigationFactorCauseId($this->id)->get());
    }

    public function getRootCauses()
    {
        return CustomerInvestigationAlFactorCauseRootCauseDTO::parse(CustomerInvestigationAlFactorCauseRootCause::whereCustomerInvestigationFactorCauseId($this->id)->get());
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
