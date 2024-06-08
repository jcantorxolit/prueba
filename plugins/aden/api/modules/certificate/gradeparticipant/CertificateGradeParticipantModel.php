<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Certificate\GradeParticipant;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CertificateGradeParticipantModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_certificate_grade_participant";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'city' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File']
    ];

	public function  getDocumenttype()
    {
        return $this->getParameterByValue($this->documenttype, "wg_professor_event_xxx");
    }

public function  getChannel()
    {
        return $this->getParameterByValue($this->channel, "wg_professor_event_xxx");
    }

public function  getCountryOriginId()
    {
        return $this->getParameterByValue($this->countryOriginId, "wg_professor_event_xxx");
    }

public function  getCountryResidenceId()
    {
        return $this->getParameterByValue($this->countryResidenceId, "wg_professor_event_xxx");
    }


	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
