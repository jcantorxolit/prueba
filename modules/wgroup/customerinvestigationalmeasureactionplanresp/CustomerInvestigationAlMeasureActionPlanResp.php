<?php

namespace Wgroup\CustomerInvestigationAlMeasureActionPlanResp;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInvestigationAlMeasureActionPlanResp extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al_measure_action_plan_resp';

    public $belongsTo = [
        'contact' => ['Wgroup\Models\Contact', 'key' => 'contact_id', 'otherKey' => 'id'],
    ];

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public function  getStatusType()
    {
        return $this->getParameterByValue($this->status, "action_plan_resp_status");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
