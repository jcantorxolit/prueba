<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\ContractDetail;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerContractDetailModel extends Model
{
	//use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_contract_detail";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];


	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getPeriodicRequirementRelation($table)
    {
        return "(SELECT id, customer_id, requirement, isActive, 1 `month`, jan canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 2 `month`, feb canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 3 `month`, mar canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 4 `month`, apr canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 5 `month`, may canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 6 `month`, jun canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 7 `month`, jul canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 8 `month`, aug canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 9 `month`, sep canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 10 `month`, oct canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 11 `month`, nov canShow FROM wg_customer_periodic_requirement
        UNION ALL
        SELECT id, customer_id, requirement, isActive, 12 `month`, `dec` canShow FROM wg_customer_periodic_requirement) $table ";
    }
}
