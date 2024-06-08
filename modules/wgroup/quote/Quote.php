<?php

namespace Wgroup\Quote;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class Quote extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_quote';

    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'agent' => ['Wgroup\Models\Agent', 'key' => 'agent_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [
        'details' => ['Wgroup\QuoteDetail\QuoteDetail'],
    ];

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "quote_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
