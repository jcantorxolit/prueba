<?php

namespace Wgroup\CustomerWorkMedicineComplementaryTest;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerWorkMedicineComplementaryTest extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_work_medicine_complementary_test';

    public $belongsTo = [
        'medicine' => ['Wgroup\WorkMedicine\WorkMedicine', 'key' => 'customer_work_medicine_id', 'otherKey' => 'id'],
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

    public function  getIsActive()
    {
        return $this->isActive == 1 ? true : false;
    }

    public function  getComplementaryTest()
    {
        return $this->getParameterByValue($this->complementaryTest, "work_medicine_complementary_test");
    }

    public function  getResult()
    {
        return $this->getParameterById($this->result, "work_medicine_complementary_test_result");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    protected function getParameterById($id, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereId($id)->first();
    }
}
