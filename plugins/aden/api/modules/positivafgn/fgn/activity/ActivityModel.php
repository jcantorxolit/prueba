<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\Activity;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ActivityModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_activity";

    public $hasMany = [
        "strategy" => ["AdeN\Api\Modules\PositivaFgn\Fgn\Activity\StrategyModel", 'key' => 'fgn_activity_id', 'otherKey' => 'id'],
        "indicator" => ["AdeN\Api\Modules\PositivaFgn\Fgn\Activity\IndicatorModel", 'key' => 'fgn_activity_id', 'otherKey' => 'id']
    ];

    public function getAxis()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_axis")->whereValue($this->axis)->first();
    }

    public function getAction()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_action")->whereValue($this->action)->first();
    }

    public function getType()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_gestpos_activity_type")->whereValue($this->type)->first();
    }

    public function getGroup(){
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_activity_group")->whereValue($this->group)->first();
    }

}