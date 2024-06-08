<?php

namespace Wgroup\CustomerInvestigationAlMeasureTracking;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\CustomerInvestigationAlMeasure\CustomerInvestigationAlMeasure;
use Wgroup\CustomerInvestigationAlMeasure\CustomerInvestigationAlMeasureDTO;

/**
 * Idea Model
 */
class CustomerInvestigationAlMeasureTracking extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_customer_investigation_al_measure_tracking';

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

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "investigation_measure_tracking_status");
    }

    public function  getParent()
    {
        return CustomerInvestigationAlMeasureDTO::parse(CustomerInvestigationAlMeasure::find($this->customer_investigation_measure_id));
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
