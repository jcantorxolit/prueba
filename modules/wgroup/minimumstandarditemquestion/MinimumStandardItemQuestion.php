<?php

namespace Wgroup\MinimumStandardItemQuestion;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;


/**
 * Idea Model
 */
class MinimumStandardItemQuestion extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_minimum_standard_item_question';

    public $belongsTo = [
        'item' => ['Wgroup\MinimumStandardItem\MinimumStandardItem', 'key' => 'minimum_standard_item_id', 'otherKey' => 'id'],
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
    ];

    /*
     * Validation
     */
    public $rules = [

    ];

    public $hasMany = [

    ];

    public function getQuestion()
    {
        //$this->program_prevention_question_id
        return null;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
