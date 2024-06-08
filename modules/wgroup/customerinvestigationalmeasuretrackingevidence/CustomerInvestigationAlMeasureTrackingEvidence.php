<?php

namespace Wgroup\CustomerInvestigationAlMeasureTrackingEvidence;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CustomerInvestigationAlMeasureTrackingEvidence extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al_measure_tracking_evidence';

    public $belongsTo = [
        'agent' => ['RainLab\User\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
    ];


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    public function  getType()
    {
        return $this->getParameterByValue($this->type, "investigation_measure_tracking_evidence_type");
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
