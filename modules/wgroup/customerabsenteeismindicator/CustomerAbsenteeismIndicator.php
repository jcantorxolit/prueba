<?php

namespace Wgroup\CustomerAbsenteeismIndicator;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlace;

/**
 * Idea Model
 */
class CustomerAbsenteeismIndicator extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_absenteeism_indicator';

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
       /* $workCenter = new \stdClass();
        $workCenter->id = $this->workCenter;
        $workCenter->item = $this->workCenter;
        $workCenter->value = $this->workCenter;

        return $workCenter;*/
        return CustomerConfigWorkPlace::whereId($this->workCenter)->first();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
