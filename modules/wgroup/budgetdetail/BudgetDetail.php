<?php

namespace Wgroup\BudgetDetail;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class BudgetDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_budget_detail';

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

    public function getYear()
    {
        $year = new \stdClass();
        $year->item = $this->year;
        $year->value = $this->year;
        return $year;
    }

    public function getMonth()
    {
        $month = $this->getParameterByValue($this->month, "month");
        $month->value = str_pad($month->value, 2, "0", STR_PAD_LEFT);  ;
        return $month;
    }

    public function  getControversyStatus()
    {
        return $this->getParameterByValue($this->month, "month");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
