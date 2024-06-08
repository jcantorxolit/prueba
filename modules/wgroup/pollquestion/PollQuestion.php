<?php

namespace Wgroup\PollQuestion;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\PollQuestionAnswer\PollQuestionAnswer;

/**
 * Idea Model
 */
class PollQuestion extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_poll_question';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'poll' => ['Wgroup\Poll\Poll', 'key' => 'poll_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [
        'answers' => ['Wgroup\PollQuestionAnswer\PollQuestionAnswer'],
    ];

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "poll_question_type");
    }

    public static function getTypeValue($value)
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("poll_question_type")->whereValue($value)->first();
    }

    public static function  getAnswerValues($id)
    {
        return PollQuestionAnswer::where("poll_question_id",$id)->get();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
