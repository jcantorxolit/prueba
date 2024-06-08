<?php

namespace Wgroup\NotifiedAlert;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class NotifiedAlert extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_notified_alert';

    public $belongsTo = [

    ];


    /*
     * Validation
     */
    public $rules = [

    ];

    public $hasMany = [
        //'alerts' => ['Wgroup\Models\CustomerTrackingAlert'],
    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
