<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerContribution extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_arl_contribution';

    public $belongsTo = [

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

    public function getAlerts(){
        return CustomerTrackingAlert::whereCustomerTrackingId($this->id)->get();
    }

    public function  getMonth(){
        return $this->getParameterByValue($this->month, "month");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
