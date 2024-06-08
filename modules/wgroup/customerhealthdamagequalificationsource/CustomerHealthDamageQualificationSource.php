<?php

namespace Wgroup\CustomerHealthDamageQualificationSource;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerHealthDamageQualificationSourceDiagnostic\CustomerHealthDamageQualificationSourceDiagnostic;
use Wgroup\CustomerHealthDamageQualificationSourceDiagnostic\CustomerHealthDamageQualificationSourceDiagnosticDTO;
use Wgroup\CustomerHealthDamageQualificationSourceJustice\CustomerHealthDamageQualificationSourceJustice;
use Wgroup\CustomerHealthDamageQualificationSourceNational\CustomerHealthDamageQualificationSourceNational;
use Wgroup\CustomerHealthDamageQualificationSourceOpportunity\CustomerHealthDamageQualificationSourceOpportunity;
use Wgroup\CustomerHealthDamageQualificationSourceRegional\CustomerHealthDamageQualificationSourceRegional;

/**
 * Idea Model
 */
class CustomerHealthDamageQualificationSource extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_qs';

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

    public function  getDiagnostic()
    {
        return CustomerHealthDamageQualificationSourceDiagnostic::where('customer_health_damage_qualification_source_id', $this->id)->first();
    }

    public function  getOpportunity()
    {
        return CustomerHealthDamageQualificationSourceOpportunity::where('customer_health_damage_qualification_source_id', $this->id)->first();
    }

    public function  getRegional()
    {
        return CustomerHealthDamageQualificationSourceRegional::where('customer_health_damage_qualification_source_id', $this->id)->first();
    }

    public function  getNational()
    {
        return CustomerHealthDamageQualificationSourceNational::where('customer_health_damage_qualification_source_id', $this->id)->first();
    }

    public function  getJusticeFirst()
    {
        return CustomerHealthDamageQualificationSourceJustice::where('customer_health_damage_qualification_source_id', $this->id)->where('instance', 'first')->first();
    }

    public function  getJusticeSecond()
    {
        return CustomerHealthDamageQualificationSourceJustice::where('customer_health_damage_qualification_source_id', $this->id)->where('instance', 'second')->first();
    }

    public function  getJusticeThird()
    {
        return CustomerHealthDamageQualificationSourceJustice::where('customer_health_damage_qualification_source_id', $this->id)->where('instance', 'third')->first();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
