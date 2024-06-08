<?php

namespace Wgroup\NephosIntegrationProductPlanFeature;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class NephosIntegrationProductPlanFeature extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_product_plan_feature';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [

    ];

    public function  getStatus()
    {
        return $this->getParameterByValue($this->status, "config_workplace_status");
    }


    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
