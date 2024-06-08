<?php

namespace Wgroup\EmployeeStaging;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Models\InfoDetail;

/**
 * Agent Model
 */
class EmployeeStaging extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_employee_staging';

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

    ];

    public $attachOne = [

    ];

    public $hasMany = [

    ];
}
