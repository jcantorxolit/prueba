<?php

namespace Wgroup\CustomerInvestigationAlFactor;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInvestigationAlFactor extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al_factor';

    public $belongsTo = [
        'investigation' => ['Wgroup\CustomerInvestigationAl\CustomerInvestigationAl', 'key' => 'customer_investigation_id', 'otherKey' => 'id'],
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

    public function  getFactor()
    {
        return $this->getParameterByValue($this->factor, "investigation_factor");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
