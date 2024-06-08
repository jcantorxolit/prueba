<?php

namespace AdeN\Api\Modules\PositivaFgn\Management\Relations;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class PoblationModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_positiva_fgn_management_indicator_coverage_poblation";


    public static function getActivityState($activityState)
    {
        return Parameters::whereNamespace('wgroup')->whereGroup('positiva_fgn_gestpos_activity_states')->whereValue($activityState)->first();
    }

}
