<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\PositivaFgn\Fgn\Activity\IndicatorModel;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ActivityConfigSectionalModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_positiva_fgn_activity_indicator_sectional";

    public function getIndicatorsConfig()
    {
        $im = IndicatorModel::find($this->activityIndicatorId);
        $im->periodicity = $im->getPeriodicity();
        $im->type = $im->getType();
        return $im;
    }
}
