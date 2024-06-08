<?php

namespace Wgroup\Budget;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class Budget extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_budget';

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

    public function  getIsActive()
    {
        return $this->isActive == 1;
    }

    public function  getClassification()
    {
        return $this->getParameterByValue($this->classification, "project_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
