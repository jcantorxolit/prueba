<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\Activity;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;

class StrategyModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_activity_strategy";

    public function getStrategy()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_consultant_strategy")->whereValue($this->strategy)->first();
    }
	
}