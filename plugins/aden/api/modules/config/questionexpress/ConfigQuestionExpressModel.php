<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Config\QuestionExpress;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class ConfigQuestionExpressModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_config_question_express";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File']
    ];

    public function  getPriority()
    {
        return $this->getParameterByValue($this->priority, "wg_professor_event_xxx");
    }


    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
