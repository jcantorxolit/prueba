<?php

namespace Wgroup\Employee;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\EmployeeInfoDetail\EmployeeInfoDetail;
use Wgroup\Models\InfoDetail;
use DB;

/**
 * Agent Model
 */
class Employee extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_employee';

    /*
     * Validation
     */
    public $rules = [
        'name' => 'required'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'town' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'logo' => ['System\Models\File'],
    ];

    public $hasMany = [

    ];

    public function scopeIsEnabled($query)
    {
        return $query->where('active', true);
    }

    public function  getProfession()
    {
        return $this->getParameterByValue($this->profession, "employee_profession");
    }

    public function  getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "employee_document_type");
    }

    public function  getGender()
    {
        return $this->getParameterByValue($this->gender, "gender");
    }

    public function  getAfp()
    {
        return $this->getParameterByValue($this->afp, "afp");
    }

    public function  getArl()
    {
        return $this->getParameterByValue($this->arl, "arl");
    }

    public function  getEps()
    {
        return $this->getParameterByValue($this->eps, "eps");
    }

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

    public function getFrequencyPracticeSports()
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

    public function getRh()
    {
        return $this->getParameterByValue($this->rh, "wg_employee_type_rh");
    }

    public function getInfoDetail()
    {
        return EmployeeInfoDetail::whereEntityname(get_class($this))->whereEntityid($this->id)->get();
    }

    public static function getNameList()
    {
        if (self::$nameList)
            return self::$nameList;

        return self::$nameList = self::all()->lists('name', 'id');
    }

    /**
     * Returns the public image file path to this user's avatar.
     */
    public function getAvatarThumb($size = 25, $default = null)
    {
        if (!$default)
            $default = 'mm'; // Mystery man

        if ($this->logo)
            return $this->logo->getThumb($size, $size);
        else
            return '//www.gravatar.com/avatar/' . md5(strtolower(trim($this->documentNumber))) . '?s=' . $size . '&d=' . urlencode($default);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


    public function  getIllness()
    {
        $query = "SELECT
		IFNULL(coral.id, 0) id
	, IFNULL(coral.employee_id, 0) employeeId
	, 'illness' category
	, lty.value item
	, lty.item name
	, coral.value
	, case when coral.id is not null then 1 else 0 end isActive
FROM
  ( SELECT *
   FROM system_parameters
   WHERE `group` = 'demographic_disease' ) lty
LEFT JOIN
  ( SELECT coral.*
   FROM wg_employee_demographic coral
   WHERE category = 'illness' and employee_id = :id) coral ON lty.value = coral.item
   ORDER BY lty.value";

        $results = DB::select($query, array(
            'id' => $this->id,
        ));

        foreach ($results as $record) {
            $record->isActive = $record->isActive == 1;
        }

        return $results;
    }

}
