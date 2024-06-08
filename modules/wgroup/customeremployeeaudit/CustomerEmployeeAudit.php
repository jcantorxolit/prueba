<?php

namespace Wgroup\CustomerEmployeeAudit;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerEmployeeAudit extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_employee_audit';

    public $belongsTo = [
        'user' => ['October\Rain\Auth\Models\User', 'key' => 'user_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [
        //'alerts' => ['Wgroup\Models\CustomerTrackingAlert'],
    ];

    public function  getUserType()
    {
        switch ($this->user_type) {
            case "system":
                $userType = "Sistema";
                break;
            case "agent":
                $userType = "Asesor";
                break;
            case "customer":
                $userType = "Cliente";
                break;
            default:
                $userType = "Sistema";
        }

        return $userType;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
