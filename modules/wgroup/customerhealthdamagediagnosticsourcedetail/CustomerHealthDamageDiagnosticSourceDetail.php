<?php

namespace Wgroup\CustomerHealthDamageDiagnosticSourceDetail;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnostic;

/**
 * Idea Model
 */
class CustomerHealthDamageDiagnosticSourceDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_diagnostic_source_detail';

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
    }

    public function  getPresumedSource()
    {
        return $this->getParameterByValue($this->presumedSource, "work_health_damage_presumed_diagnostic_source");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
