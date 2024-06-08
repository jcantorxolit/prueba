<?php

namespace Wgroup\CustomerPollAnswer;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerPollAnswer extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_poll_answer';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'poll' => ['Wgroup\CustomerPoll\CustomerPoll', 'key' => 'customer_poll_id', 'otherKey' => 'id'],
        'question' => ['Wgroup\PollQuestion\PollQuestion', 'key' => 'poll_question_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [

    ];


    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
