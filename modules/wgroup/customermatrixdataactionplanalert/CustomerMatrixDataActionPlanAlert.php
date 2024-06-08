<?php

namespace Wgroup\CustomerMatrixDataActionPlanAlert;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerMatrixDataActionPlanAlert extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_matrix_data_action_plan_alert';

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


    public function  getType()
    {
        return $this->getParameterByValue($this->type, "tracking_alert_type");
    }

    public function  getTimeType()
    {
        return $this->getParameterByValue($this->timeType, "tracking_alert_timeType");
    }

    public function  getPreference()
    {
        return $this->getParameterByValue($this->preference, "tracking_alert_preference");
    }

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "tracking_alert_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
