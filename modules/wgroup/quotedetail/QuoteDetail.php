<?php

namespace Wgroup\QuoteDetail;

use BackendAuth;
use DB;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class QuoteDetail extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_quote_detail';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'service' => ['Wgroup\QuoteService\QuoteService', 'key' => 'service_id', 'otherKey' => 'id']
    ];

    public $hasMany = [

    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
