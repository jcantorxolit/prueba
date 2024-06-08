<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\OccupationalInvestigationAlResponsible;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;

class CustomerOccupationalInvestigationAlResponsibleModel extends Model
{
    //use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_occupational_investigation_al_responsible";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File'],
    ];

    public function getType()
    {
        return $this->getParameterByValue($this->type, "wg_customer_productivity_stata_person_type");
    }

    public function getEntitytype()
    {
        return $this->getParameterByValue($this->entitytype, "wg_occupational_investigation_responsible_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}