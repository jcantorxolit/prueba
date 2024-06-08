<?php

namespace Wgroup\PollQuestionAnswer;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class PollQuestionAnswer extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_poll_question_answer';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'question' => ['Wgroup\PollQuestion\PollQuestion', 'key' => 'poll_question_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [

    ];


    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
