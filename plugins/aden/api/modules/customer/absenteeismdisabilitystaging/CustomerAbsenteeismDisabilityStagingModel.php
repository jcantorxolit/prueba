<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\AbsenteeismDisabilityStaging;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerAbsenteeismDisabilityStagingModel extends Model
{
	//use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_absenteeism_disability_staging";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [

    ];

    public function  getCategory()
    {
        return $this->getParameterByValue($this->category, "absenteeism_category");
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "absenteeism_disability_type");
    }

    public function  getCause()
    {
        if ($this->category == 'Administrativo') {
            return $this->getParameterByValue($this->cause, "absenteeism_disability_causes_admin");
        } else {
            return $this->getParameterByValue($this->cause, "absenteeism_disability_causes");
        }
    }

    public function  getAccidentType()
    {
        return $this->getParameterByValue($this->accidentType, "absenteeism_disability_accident_type");
    }

    public function  getWorkplace()
    {
        return DB::table('wg_customer_config_workplace')->find($this->workplace_id);
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
