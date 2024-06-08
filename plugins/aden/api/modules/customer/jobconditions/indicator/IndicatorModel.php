<?php

namespace AdeN\Api\Modules\Customer\JobConditions\Indicator;

use AdeN\Api\Modules\Customer\JobConditions\Models\JobConditionWorkplaceModel;
use Illuminate\Support\Facades\DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

class IndicatorModel extends Model
{
    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_job_condition_self_evaluation";


}
