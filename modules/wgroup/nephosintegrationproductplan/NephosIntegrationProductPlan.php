<?php

namespace Wgroup\NephosIntegrationProductPlan;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\NephosIntegrationProductPlanFeature\NephosIntegrationProductPlanFeature;
use Wgroup\NephosIntegrationProductPlanFeature\NephosIntegrationProductPlanFeatureDTO;

/**
 * Idea Model
 */
class NephosIntegrationProductPlan extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_product_plan';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [

    ];

    public function getFeatures()
    {
        return NephosIntegrationProductPlanFeatureDTO::parse(NephosIntegrationProductPlanFeature::whereProductPlanId($this->id)->get());
    }
}
