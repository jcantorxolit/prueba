<?php

namespace Wgroup\CustomerHealthDamageQualificationSourceNationalDetail;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnostic;

/**
 * Idea Model
 */
class CustomerHealthDamageQualificationSourceNationalDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_qs_national_board_detail';

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

    public function  getDiagnostic()
    {
        //return $this->getParameterByValue($this->diagnostic, "work_health_damage_diagnostic");
        return DisabilityDiagnostic::find($this->diagnostic);
    }

    public function  getQualifiedOrigin()
    {
        return $this->getParameterByValue($this->qualifiedOrigin, "work_health_damage_entity_qualify_origin");
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
