<?php

namespace Wgroup\CustomerHealthDamageQualificationLostOpportunity;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerHealthDamageQualificationLostOpportunityDiagnostic\CustomerHealthDamageQualificationLostOpportunityDiagnostic;
use Wgroup\CustomerHealthDamageQualificationLostOpportunityDiagnostic\CustomerHealthDamageQualificationLostOpportunityDiagnosticDTO;


/**
 * Idea Model
 */
class CustomerHealthDamageQualificationLostOpportunity extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_ql_first_opportunity';

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

    public function  getDiagnostics()
    {
        return CustomerHealthDamageQualificationLostOpportunityDiagnosticDTO::parse(CustomerHealthDamageQualificationLostOpportunityDiagnostic::whereCustomerHealthDamageQlFirstOpportunityId($this->id)->get());
    }

    public function  getQualifyingEntity()
    {
        return $this->getParameterByValue($this->qualifyingEntity, "work_health_damage_entity_qualify");
    }

    public function  getOrigin()
    {
        return $this->getParameterByValue($this->origin, "work_health_damage_entity_qualify_origin");
    }

    public function  getControversyStatus()
    {
        return $this->getParameterByValue($this->controversyStatus, "work_health_damage_controversy_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
