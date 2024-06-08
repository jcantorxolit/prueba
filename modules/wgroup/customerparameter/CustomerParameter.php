<?php

namespace Wgroup\CustomerParameter;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Town Model
 */
class CustomerParameter extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_parameter';

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
    ];

    public $timestamps = false;

    public function  getType(){
        return $this->getParameterByValue($this->type, "bunit");
    }

    public function agents($customerId){

        $query = (new Agent())->query();
        return $query
            ->join("wg_customer_agent", "wg_customer_agent.agent_id", "=", "wg_agent.id")
            ->where("wg_customer_agent.type",$this->type)
            ->where("wg_customer_agent.customer_id",$customerId)
            ->get(["wg_agent.*"]);
    }

    protected  function getParameterByValue($value, $group, $ns = "wgroup"){
        return  Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


    public static function getRelationTable($group, $alias = '') {
        $alias = trim($alias) != '' ? $alias : $group;
        return "(SELECT `id`, customer_id, `namespace`, `group`, `item` COLLATE utf8_general_ci as item, `value` COLLATE utf8_general_ci AS `value` FROM `wg_customer_parameter` WHERE `namespace` = 'wgroup' AND `group` = '$group') $alias ";
    }

}
