<?php

namespace Wgroup\CustomerWorkMedicine;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerWorkMedicine extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_work_medicine';

    public $belongsTo = [
        'employee' => ['Wgroup\CustomerEmployee\CustomerEmployee', 'key' => 'customer_employee_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];

    public function  getMedicalConcept()
    {
        return $this->getParameterByValue($this->medicalConcept, "work_medicine_medical_concept");
    }

    public function  getExaminationType()
    {
        return $this->getParameterByValue($this->examinationType, "work_medicine_examination_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
