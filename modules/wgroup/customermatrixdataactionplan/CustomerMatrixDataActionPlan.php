<?php

namespace Wgroup\CustomerMatrixDataActionPlan;

use BackendAuth;
use Illuminate\Support\Facades\DB;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerMatrixDataActionPlanAlert\CustomerMatrixDataActionPlanAlert;

/**
 * Idea Model
 */
class CustomerMatrixDataActionPlan extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_matrix_data_action_plan';

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
        'alerts' => ['Wgroup\CustomerMatrixDataActionPlanAlert\CustomerMatrixDataActionPlanAlert', 'key' => 'customer_matrix_data_action_plan_id', 'otherKey' => 'id'],
    ];

    public function getAlerts(){
        return CustomerMatrixDataActionPlanAlert::whereCustomerMatrixDataActionPlanId($this->id)->get();
    }

    public function getResponsible(){

        $sql = "SELECT
	 CASE WHEN ISNULL(r.id) THEN 0 ELSE r.id END id
	, p.id actionPlanId,  param.item role, ct.id contactId, CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName) fullName
	, i.`value` email
	, CASE WHEN ISNULL(r.id) THEN 0 ELSE 1 END isActive
FROM
	wg_customers cs
	inner join wg_customer_matrix m on m.customer_id = cs.id
	inner join wg_customer_matrix_data md on md.customer_matrix_id = m.id
	inner join wg_customer_matrix_data_action_plan p on p.customer_matrix_data_id = md.id
INNER JOIN (
		SELECT ct.* FROM wg_contact ct INNER JOIN wg_customers c ON c.id = ct.customer_id
	) ct ON ct.customer_id = cs.id
LEFT JOIN
	wg_customer_matrix_data_action_plan_resp r on p.id = r.job_activity_hazard_action_plan_id and r.contact_id = ct.id
LEFT JOIN (
							SELECT MIN(id) id, entityId, `value` FROM wg_info_detail
							WHERE entityName = 'Wgroup\\\\Models\\\\Contact' AND type = 'email'
							GROUP BY entityId
					) i ON i.entityId = ct.id
LEFT JOIN (
							select * from system_parameters where system_parameters.group = 'rolescontact'
					) param on ct.role = param.value
WHERE p.id = :id";

        $results = DB::select( $sql, array(
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
