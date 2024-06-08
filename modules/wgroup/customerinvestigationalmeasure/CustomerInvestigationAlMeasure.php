<?php

namespace Wgroup\CustomerInvestigationAlMeasure;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerInvestigationAlMeasureActionPlan\CustomerInvestigationAlMeasureActionPlan;

/**
 * Idea Model
 */
class CustomerInvestigationAlMeasure extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al_measure';

    public $belongsTo = [
        'investigation' => ['Wgroup\CustomerInvestigationAl\CustomerInvestigationAl', 'key' => 'customer_investigation_id', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $hasMany = [

    ];

    public function  getIsActive()
    {
        return $this->isActive == 1;
    }

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "investigation_measure");
    }

    public function  getControlType()
    {
        return $this->getParameterByValue($this->controlType, "investigation_control_type");
    }

    public function getActionPlan()
    {
        return CustomerInvestigationAlMeasureActionPlan::whereCustomerInvestigationMeasureId($this->id)->first();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
