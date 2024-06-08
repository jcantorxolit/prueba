<?php

namespace Wgroup\CustomerHealthDamageQualificationSourceDiagnosticSupport;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerHealthDamageQualificationSourceDiagnosticSupport extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_qs_diagnostic_support';

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

    public function  getSupport()
    {
        return $this->getParameterByValue($this->support, "work_health_damage_diagnostic_support");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
