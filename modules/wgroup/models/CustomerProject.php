<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Budget\Budget;
use Wgroup\SystemParameter\SystemParameter;

/**
 * Idea Model
 */
class CustomerProject extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_project';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'customer' => ['Wgroup\Models\Customer', 'key' => 'customer_id', 'otherKey' => 'id'],
        'createdUser' => ['RainLab\User\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
    ];

    public $hasMany = [
        'alerts' => ['Wgroup\Models\CustomerProjectAgent'],
    ];

    public function getAgents(){
        return CustomerProjectAgent::whereCustomerProjectId($this->id)->get();
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "project_type");
    }

    public function  getDefaultSkill()
    {
        return $this->getParameterByValue($this->defaultSkill, "agent_skill");
    }

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "project_status");
    }

    public function  getInvoiceStatus()
    {
        return $this->getParameterByValue($this->invoiceStatus, "project_invoice_status");
    }

    public function  getItem()
    {
        return Budget::find($this->item);
    }

    public function  getCustomer()
    {
        $query = "select c.id, businessName name, p.item arl
                    from wg_customers c
                    left join (
                                            select * from system_parameters
                                            where system_parameters.group = 'arl'
                                            ) p on c.arl = p.value
                    where c.id = :customer_id
                    order by businessName";

        $results = DB::select( $query, array(
            'customer_id' => $this->customer_id,
        ));
        return $results;
    }

    public function getAgentsBy()
    {
        $query = "Select pa.id, a.id agentId, a.`name`
                                    , pa.project_id projectId
                                    , (a.availability -  ROUND(IFNULL(notAssignedHours, 0), 0)) notAssignedHours
                                    , pa.estimatedHours scheduledHours
                        from wg_agent a
                            inner join wg_customer_project_agent pa on pa.agent_id = a.id
                            LEFT JOIN (SELECT p.id, pa.agent_id, pa.id project_agent_id, pa.project_id, pa.estimatedHours
                                    , SUM(ROUND(IFNULL(pa.estimatedHours, 0), 0)) assignedHours
                                    , SUM(ROUND(IFNULL(pa.estimatedHours, 0), 0)) notAssignedHours
                                    , SUM(ROUND(IFNULL(patp.planeadas, 0), 0)) scheduledHours
                                    , SUM(ROUND(IFNULL(pate.ejecutadas, 0), 0)) runningHours
                    FROM wg_customer_project p
                            inner join wg_customers c on p.customer_id = c.id
                            inner join wg_customer_project_agent pa on p.id = pa.project_id
                            left join (
                                                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                                                            from wg_customer_project_agent_task pat
                                                                            where `status` = 'activo'
                                                                            group by pat.project_agent_id
                                                                    ) patp On pa.id = patp.project_agent_id
                            left join (
                                                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                                                            from wg_customer_project_agent_task pat
                                                                            where `status` = 'inactivo'
                                                                            group by pat.project_agent_id
                                                                    ) pate on pa.id = pate.project_agent_id
                    WHERE MONTH(p.deliveryDate) =  MONTH(NOW()) and YEAR(p.deliveryDate) =  YEAR(NOW())
                    group by pa.agent_id) pat on a.id = pat.agent_id
                WHERE pa.project_id = :project_id";
        $results = DB::select( $query, array(
            'project_id' => $this->id,
        ));
        return $results;
    }

    public function getAssociatedCosts() {
        return CustomerProjectCost::whereProjectId($this->id)
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customers_projects_sales_status', 'status')), function ($join) {
                $join->on('wg_customer_project_costs.status', '=', 'status.value');
            })
            ->select(
                'wg_customer_project_costs.id', 'concept', 'classification', 'amount',
                'unit_price as price', 'total_price as totalValue',
                'status.item as status'
            )
            ->get()
            ->map(function($row) {
                $row->price = (int) $row->price;
                return $row;
            });
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
