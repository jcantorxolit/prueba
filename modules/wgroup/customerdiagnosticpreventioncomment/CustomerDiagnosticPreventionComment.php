<?php

namespace Wgroup\CustomerDiagnosticPreventionComment;

use BackendAuth;
use Illuminate\Support\Facades\DB;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;


/**
 * Idea Model
 */
class CustomerDiagnosticPreventionComment extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_diagnostic_prevention_comment';

    public $belongsTo = [
        'agent' => ['Wgroup\Models\Agent', 'key' => 'agent_id', 'otherKey' => 'id'],
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

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
