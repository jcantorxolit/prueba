<?php

namespace AdeN\Api\Modules\EmployeeInformationDetail;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Eloquent Model
 */
class EmployeeInformationDetailModel extends Model
{

    /**
     * @var array Cache for nameList() method
     */
    protected static $nameList = null;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_employee_info_detail';

    /**
     * @var bool Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /*
     * Validation
     */
    public $rules = [
    ];

    public $belongsTo = [
    ];

    public function getTypes()
    {
        return $this->getParameterByValue($this->type, "extrainfo");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}