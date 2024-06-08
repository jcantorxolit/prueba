<?php

namespace Wgroup\RoadSafetyItemQuestion;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;


/**
 * Idea Model
 */
class RoadSafetyItemQuestion extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_road_safety_item_question';

    public $belongsTo = [
        'item' => ['Wgroup\RoadSafetyItem\RoadSafetyItem', 'key' => 'road_safety_item_id', 'otherKey' => 'id'],
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
