<?php

namespace Wgroup\CustomerPoll;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;
use Wgroup\CustomerPollAnswer\CustomerPollAnswer;

/**
 * Idea Model
 */
class CustomerPoll extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_poll';

    public $belongsTo = [
        'poll' => ['Wgroup\Poll\Poll', 'key' => 'poll_id', 'otherKey' => 'id'],
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [
        'answers' => ['Wgroup\CustomerPollAnswer\CustomerPollAnswer'],
    ];

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "customer_poll_status");
    }

    public function getPoll()
    {
        $query = "select c.id customer_poll_id, p.*
from wg_customer_poll c
inner join wg_poll p on c.poll_id = p.id
where c.id = :id";

        $results = DB::select( $query, array(
            'id' => $this->id,
        ));
        return $results;
    }

    public function getAnswerCount()
    {
        $count = CustomerPollAnswer::where('customer_poll_id', $this->id)->count();

        return $count;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
