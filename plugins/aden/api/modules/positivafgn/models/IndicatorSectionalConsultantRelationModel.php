<?php

namespace AdeN\Api\Modules\PositivaFgn\Models;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;

class IndicatorSectionalConsultantRelationModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_positiva_fgn_activity_indicator_sectional_consultant_relation";

}
