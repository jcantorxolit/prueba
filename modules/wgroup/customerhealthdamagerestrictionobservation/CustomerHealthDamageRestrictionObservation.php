<?php

namespace Wgroup\CustomerHealthDamageRestrictionObservation;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerHealthDamageRestrictionObservation extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_health_damage_restriction_observation';

    public $belongsTo = [
        'medicine' => ['Wgroup\WorkMedicine\WorkMedicine', 'key' => 'customer_work_medicine_id', 'otherKey' => 'id'],
        'restriction' => ['Wgroup\CustomerHealthDamageRestriction\CustomerHealthDamageRestriction', 'key' => 'customer_health_damage_restriction_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
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

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "work_health_damage_restriction_observation_type");
    }

    public function  getAccessLevel()
    {
        return $this->getParameterByValue($this->accessLevel, "work_health_damage_restriction_observation_access");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
