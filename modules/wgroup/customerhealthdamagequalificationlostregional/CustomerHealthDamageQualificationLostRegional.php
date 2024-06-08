<?php

namespace Wgroup\CustomerHealthDamageQualificationLostRegional;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerHealthDamageQualificationLostRegionalDiagnostic\CustomerHealthDamageQualificationLostRegionalDiagnostic;
use Wgroup\CustomerHealthDamageQualificationLostRegionalDiagnostic\CustomerHealthDamageQualificationLostRegionalDiagnosticDTO;


/**
 * Idea Model
 */
class CustomerHealthDamageQualificationLostRegional extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_ql_first_instance';

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
        return CustomerHealthDamageQualificationLostRegionalDiagnosticDTO::parse(CustomerHealthDamageQualificationLostRegionalDiagnostic::whereCustomerHealthDamageQlFirstInstanceId($this->id)->get());
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
