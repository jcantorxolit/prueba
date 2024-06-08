<?php

namespace Wgroup\CustomerRoadSafetyItem;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerRoadSafetyItem extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_road_safety_item';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
    ];

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "diagnostic_prevention_status");
    }

    public function  getApply()
    {
        return $this->getParameterByValue($this->apply, "safety_road_detail_option");
    }

    public function  getEvidence()
    {
        return $this->getParameterByValue($this->evidence, "safety_road_detail_option");
    }

    public function  getRequirement()
    {
        return $this->getParameterByValue($this->requirement, "safety_road_detail_option");
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
