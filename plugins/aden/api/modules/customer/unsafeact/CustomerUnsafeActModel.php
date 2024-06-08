<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\UnsafeAct;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;

class CustomerUnsafeActModel extends Model
{
    //use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_unsafe_act";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File'],
    ];

    public function getStatus()
    {
        return $this->getParameterByValue($this->status, "wg_professor_event_xxx");
    }

    public function getWorkPlace()
    {
        return $this->getParameterByValue($this->workPlace, "wg_professor_event_xxx");
    }

    public function getRiskType()
    {
        return $this->getParameterByValue($this->riskType, "wg_professor_event_xxx");
    }

    public function getClassificationId()
    {
        return $this->getParameterByValue($this->classificationId, "wg_professor_event_xxx");
    }

    public function getLat()
    {
        return $this->getParameterByValue($this->lat, "wg_professor_event_xxx");
    }

    public function getLng()
    {
        return $this->getParameterByValue($this->lng, "wg_professor_event_xxx");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
