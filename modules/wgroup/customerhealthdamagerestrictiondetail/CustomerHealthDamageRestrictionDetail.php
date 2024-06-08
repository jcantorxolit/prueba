<?php

namespace Wgroup\CustomerHealthDamageRestrictionDetail;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerHealthDamageRestrictionDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_restriction_detail';

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

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    public $hasMany = [

    ];

    public function  getIsActive()
    {
        return $this->isActive == 1;
    }

    public function  getRestriction()
    {
        return $this->getParameterByValue($this->restriction, "work_health_damage_restriction");
    }

    public function  getWhoPerceived()
    {
        return $this->getParameterByValue($this->whoPerceived, "work_health_damage_who_perceived");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
