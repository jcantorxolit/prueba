<?php

namespace Wgroup\CustomerOccupationalInvestigationAlResponsible;

use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Models\Agent;
use Wgroup\Models\AgentDTO;

/**
 * Idea Model
 */
class CustomerOccupationalInvestigationAlResponsible extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_occupational_investigation_al_responsible';

    public $belongsTo = [
        'report' => ['Wgroup\CustomerOccupationalReportIncident\CustomerOccupationalReportIncident', 'key' => 'customer_occupational_report_incident_id', 'otherKey' => 'id'],
    ];

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required',
    ];

    public $hasMany = [

    ];

    public function getType()
    {
        return $this->getParameterByValue($this->type, 'wg_customer_productivity_stata_person_type');
    }

    public function getResponsible()
    {
        return AgentDTO::parse(Agent::find($this->agent_id));
    }

    public function getRole()
    {
        return $this->getParameterByValue($this->role, 'role');
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
