<?php

namespace Wgroup\CustomerHealthDamageQualificationLost;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerHealthDamageQualificationLostJustice\CustomerHealthDamageQualificationLostJustice;
use Wgroup\CustomerHealthDamageQualificationLostNational\CustomerHealthDamageQualificationLostNational;
use Wgroup\CustomerHealthDamageQualificationLostOpportunity\CustomerHealthDamageQualificationLostOpportunity;
use Wgroup\CustomerHealthDamageQualificationLostRegional\CustomerHealthDamageQualificationLostRegional;

/**
 * Idea Model
 */
class CustomerHealthDamageQualificationLost extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_ql';

    public $belongsTo = [
        'employee' => ['Wgroup\CustomerEmployee\CustomerEmployee', 'key' => 'customer_employee_id', 'otherKey' => 'id'],
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

    public function  getArl()
    {
        return $this->getParameterByValue($this->arl, "arl");
    }

    public function  getOpportunity()
    {
        return CustomerHealthDamageQualificationLostOpportunity::where('customer_health_damage_qualification_lost_id', $this->id)->first();
    }

    public function  getRegional()
    {
        return CustomerHealthDamageQualificationLostRegional::where('customer_health_damage_qualification_lost_id', $this->id)->first();
    }

    public function  getNational()
    {
        return CustomerHealthDamageQualificationLostNational::where('customer_health_damage_qualification_lost_id', $this->id)->first();
    }

    public function  getJusticeFirst()
    {
        return CustomerHealthDamageQualificationLostJustice::where('customer_health_damage_qualification_lost_id', $this->id)->where('sentenceType', 'first')->first();
    }

    public function  getJusticeSecond()
    {
        return CustomerHealthDamageQualificationLostJustice::where('customer_health_damage_qualification_lost_id', $this->id)->where('sentenceType', 'second')->first();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
