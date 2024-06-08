<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\Activity;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;

class IndicatorModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_activity_indicator";

    public function getType()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_type")->whereValue($this->type)->first();
    }

    public function getPeriodicity()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_periodicity")->whereValue($this->periodicity)->first();
    }
	
}