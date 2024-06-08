<?php

namespace Wgroup\CustomerAbsenteeismIndicatorTarget;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerAbsenteeismIndicatorTarget extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_absenteeism_indicator_target';

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
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

    public function getPeriod()
    {
        return $this->getParameterByValue($this->period, "absenteeism_indicator_period");
    }

    public function getClassification()
    {
        return $this->getParameterByValue($this->classification, "absenteeism_disability_classification");
    }

    public function getWorkCenter()
    {
        $workCenter = new \stdClass();
        $workCenter->id = $this->workCenter;
        $workCenter->item = $this->workCenter;
        $workCenter->value = $this->workCenter;

        return $workCenter;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
