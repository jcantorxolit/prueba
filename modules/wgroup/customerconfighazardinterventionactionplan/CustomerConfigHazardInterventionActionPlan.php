<?php

namespace Wgroup\CustomerConfigHazardInterventionActionPlan;

use BackendAuth;
use Illuminate\Support\Facades\DB;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerConfigHazardInterventionActionPlanAlert\CustomerConfigHazardInterventionActionPlanAlert;

/**
 * Idea Model
 */
class CustomerConfigHazardInterventionActionPlan extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_config_hazard_intervention_action_plan';

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
        'alerts' => ['Wgroup\CustomerConfigHazardInterventionActionPlanAlert\CustomerConfigHazardInterventionActionPlanAlert', 'key' => 'contract_action_plan_id', 'otherKey' => 'id'],
    ];

    public function getAlerts()
    {
        return CustomerConfigHazardInterventionActionPlanAlert::whereJobActivityHazardActionPlanId($this->id)->get();
    }

    public function getResponsible()
    {

        $sql = "SELECT
	 CASE WHEN ISNULL(r.id) THEN 0 ELSE r.id END id
	, p.id actionPlanId,  param.item role, ct.id contactId, CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName) fullName
	, i.`value` email
	, CASE WHEN ISNULL(r.id) THEN 0 ELSE 1 END isActive
FROM
	wg_customers cs
	INNER JOIN `wg_customer_config_workplace` a ON a.customer_id = cs.id
	inner join wg_customer_config_macro_process b on a.id = b.workplace_id
	inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
	inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
	inner join wg_customer_config_job_activity e on e.job_id = d.id
	inner join wg_customer_config_job_activity_hazard f on f.job_activity_id = e.id
	inner join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
	inner join wg_customer_config_hazard_intervention_action_plan p on g.id = p.job_activity_hazard_id
INNER JOIN (
		SELECT ct.* FROM wg_contact ct INNER JOIN wg_customers c ON c.id = ct.customer_id
	) ct ON ct.customer_id = cs.id
LEFT JOIN
	wg_customer_config_hazard_intervention_action_plan_resp r on p.id = r.job_activity_hazard_action_plan_id and r.contact_id = ct.id
LEFT JOIN (
							SELECT MIN(id) id, entityId, `value` FROM wg_info_detail
							WHERE entityName = 'Wgroup\\\\Models\\\\Contact' AND type = 'email'
							GROUP BY entityId
					) i ON i.entityId = ct.id
LEFT JOIN (
							select * from system_parameters where system_parameters.group = 'rolescontact'
					) param on ct.role = param.value
WHERE p.id = :id";

        $results = DB::select($sql, array(
            'id' => $this->id,
        ));

        return $results;
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
