<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\ConfigQuestionExpress;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Interfaces\IHistorical;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerConfigQuestionExpressModel extends Model implements IHistorical
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_config_question_express";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public function getParentId()
    {
        return $this->customerId;
    }

    public function getModelId()
    {
        return $this->id;
    }

    public function getModelName()
    {
        return "Peligros";
    }

    public function getChanges()
    {
        return null;
    }

    public function getModel()
    {
        return $this;
    }

    public function getIsDirty($field)
    {
        return $this->isDirty($field);
    }

    public function getOriginalValue($field)
    {
        return $this->getOriginal($field);
    }
}
