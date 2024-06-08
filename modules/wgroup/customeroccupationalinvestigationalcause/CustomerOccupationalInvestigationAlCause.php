<?php

namespace Wgroup\CustomerOccupationalInvestigationAlCause;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\InvestigationAlCause\InvestigationAlCause;
use Wgroup\InvestigationAlCause\InvestigationAlCauseDTO;

/**
 * Idea Model
 */
class CustomerOccupationalInvestigationAlCause extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_occupational_investigation_al_cause';

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

    public function getCause()
    {
        return InvestigationAlCauseDTO::parse(InvestigationAlCause::find($this->cause));
    }

    public function getFactor()
    {
        $type = $this->type == 'basic' ? 'investigation_cause_classification_basic' : 'investigation_cause_classification_immediate';
        return $this->getParameterByValue($this->factor, $type);
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
