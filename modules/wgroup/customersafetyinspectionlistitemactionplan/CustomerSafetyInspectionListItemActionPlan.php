<?php

namespace Wgroup\CustomerSafetyInspectionListItemActionPlan;

use BackendAuth;
use Illuminate\Support\Facades\DB;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerSafetyInspectionListItemActionPlanAlert\CustomerSafetyInspectionListItemActionPlanAlert;

/**
 * Idea Model
 */
class CustomerSafetyInspectionListItemActionPlan extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_safety_inspection_list_item_action_plan';

    public $belongsTo = [

    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [
        'alerts' => ['Wgroup\CustomerSafetyInspectionListItemActionPlan\CustomerSafetyInspectionListItemActionPlanAlert', 'key' => 'action_plan_id', 'otherKey' => 'id'],
    ];

    public function getAlerts(){
        return CustomerSafetyInspectionListItemActionPlanAlert::whereActionPlanId($this->id)->get();
    }

    public function getResponsible(){

        $sql = "SELECT DISTINCT
	CASE
			 WHEN ISNULL(r.id) THEN 0
			 ELSE r.id
	 END id,
	 p.id actionPlanId,
	 param.item role,
							ct.id contactId,
							CONCAT( ct. NAME, ' ', ct.firstName, ' ', ct.lastName) fullName,
							i.`value` email,
							CASE
									WHEN ISNULL(r.id) THEN 0
									ELSE 1
							END isActive
FROM wg_customers cs
INNER JOIN wg_customer_safety_inspection csi ON cs.id = csi.customer_id
INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
INNER JOIN wg_customer_safety_inspection_list_item csili on csili.customer_safety_inspection_list_id = csicl.id and csili.customer_safety_inspection_config_list_item_id = csicli.id
INNER JOIN wg_customer_safety_inspection_list_item_action_plan p ON p.customer_safety_inspection_list_item_id = csili.id
INNER JOIN
  ( SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = cs.id
LEFT JOIN wg_customer_safety_inspection_list_item_action_plan_resp r ON p.id = r.action_plan_id
AND r.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\Models\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId) i ON i.entityId = ct.id
LEFT JOIN
  ( SELECT *
   FROM system_parameters
   WHERE system_parameters.`group` = 'rolescontact' ) param ON ct.role = param. VALUE
WHERE p.id = :id";

        $results = DB::select( $sql, array(
            'id' => $this->id,
        ));

        return $results;

        return CustomerSafetyInspectionListItemActionPlanAlert::whereActionPlanId($this->id)->get();
    }

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "action_plan_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
