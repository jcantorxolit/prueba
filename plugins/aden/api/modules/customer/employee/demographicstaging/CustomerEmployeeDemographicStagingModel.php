<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\Employee\DemographicStaging;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerEmployeeDemographicStagingModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_employee_demographic_staging";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [

    ];

    public function getTypeHousing()
    {
        return $this->getParameterByValue($this->typeHousing, "type_housing");
    }

    public function getAntiquityCompany()
    {
        return $this->getParameterByValue($this->antiquityCompany, "antiquity");
    }

    public function getAntiquityJob()
    {
        return $this->getParameterByValue($this->antiquityJob, "antiquity");
    }

    public function getStratum()
    {
        return $this->getParameterByValue($this->stratum, "stratum");
    }

    public function getCivilStatus()
    {
        return $this->getParameterByValue($this->civilStatus, "civil_status");
    }

    public function getScholarship()
    {
        return $this->getParameterByValue($this->scholarship, "scholarship");
    }

    public function getRace()
    {
        return $this->getParameterByValue($this->race, "race");
    }

    public function getWorkArea()
    {
        return $this->getParameterByValue($this->workArea, "work_area");
    }

    public function getFrequencySports()
    {
        return $this->getParameterByValue($this->frequencyPracticeSports, "frequency");
    }

    public function getFrequencyDrinkAlcoholic()
    {
        return $this->getParameterByValue($this->frequencyDrinkAlcoholic, "frequency");
    }

    public function getFrequencySmokes()
    {
        return $this->getParameterByValue($this->frequencySmokes, "frequency");
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
