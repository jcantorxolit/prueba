<?php

namespace Wgroup\CustomerUser;

use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerUser extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_user';

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
    ];

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required',
    ];

    public $hasMany = [
        'skills' => ['Wgroup\CustomerUserSkill\CustomerUserSkill'],
    ];

    public function getType()
    {
        return $this->getParameterByValue($this->type, "agent_type");
    }

    public function getProfile()
    {
        return $this->getParameterByValue($this->profile, "wg_customer_user_profile");
    }

    public function getGender()
    {
        return $this->getParameterByValue($this->gender, "gender");
    }

    public function getIsActive()
    {
        return $this->isActive == 1 ? true : false;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
