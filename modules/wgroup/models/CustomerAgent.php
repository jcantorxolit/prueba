<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Town Model
 */
class CustomerAgent extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_agent';

    /*
     * Validation
     */
    public $rules = [
        'agent_id' => 'required',
        'type' => 'required',
        'customer_id' => 'required'
    ];

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'agent' => ['Wgroup\Models\Agent', 'key' => 'agent_id', 'otherKey' => 'id'],
    ];

    public $timestamps = false;

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "bunit");
    }

    public function agents($customerId)
    {

        $query = (new Agent())->query();
        return $query
            ->join("wg_customer_agent", "wg_customer_agent.agent_id", "=", "wg_agent.id")
            ->where("wg_customer_agent.type", $this->type)
            ->where("wg_customer_agent.customer_id", $customerId)
            ->get(["wg_agent.*"]);
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
