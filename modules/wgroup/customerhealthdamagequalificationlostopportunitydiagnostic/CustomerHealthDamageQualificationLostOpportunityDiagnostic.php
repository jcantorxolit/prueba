<?php

namespace Wgroup\CustomerHealthDamageQualificationLostOpportunityDiagnostic;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnostic;

/**
 * Idea Model
 */
class CustomerHealthDamageQualificationLostOpportunityDiagnostic extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_ql_first_opportunity_detail';

    public $belongsTo = [
        'medicine' => ['Wgroup\WorkMedicine\WorkMedicine', 'key' => 'customer_work_medicine_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];

    public function  getIsActive()
    {
        return $this->isActive == 1;
    }

    public function  getCodeCIE10()
    {
        return DisabilityDiagnostic::find($this->codeCIE10);
        //return $this->getParameterByValue($this->codeCIE10, "work_health_damage_code_cie_10");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
