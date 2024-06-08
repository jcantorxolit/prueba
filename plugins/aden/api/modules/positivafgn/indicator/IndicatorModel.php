<?php

namespace AdeN\Api\Modules\PositivaFgn\Indicator;

use AdeN\Api\Classes\CamelCasing;
use Illuminate\Support\Facades\DB;
use October\Rain\Database\Model;

class IndicatorModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_positiva_fgn_indicators_config";

}
