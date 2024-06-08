<?php

namespace Wgroup\CustomerAbsenteeismIndirectCost;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerAbsenteeismIndirectCost extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_absenteeism_disability_indirect_cost';

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

    public function getConcept()
    {
        return $this->getParameterByValue($this->concept, "absenteeism_concept_cost");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
