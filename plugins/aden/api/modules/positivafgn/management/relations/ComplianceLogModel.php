<?php

namespace AdeN\Api\Modules\PositivaFgn\Management\Relations;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ComplianceLogModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_positiva_fgn_management_indicator_compliance_logs";


    public function getActivityState($ns = 'wgroup')
    {
        return Parameters::whereNamespace($ns)->whereGroup('positiva_fgn_gestpos_activity_states')->whereValue($this->activityState)->first();
    }

}
