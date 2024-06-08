<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\PositivaFgn\Fgn\Activity\IndicatorModel;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ActivityConfigSectionalRelationModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_activity_indicator_sectional_relation";

    public $hasMany = [
        "sectional_indicator" => ["AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ActivityConfigSectionalModel", 'key' => 'sectional_relation_id', 'otherKey' => 'id']
    ];

    public function getRegional()
    {
        return DB::table("wg_positiva_fgn_regional")
                ->select("id AS value","number AS item")
                ->where("id", $this->regional_id)
                ->first();
    }

    public function getSectional()
    {
        return DB::table("wg_positiva_fgn_sectional")
                ->select("id AS value","name AS item")
                ->where("id", $this->sectional_id)
                ->first();
    }

    public function getIndicatorsConfig()
    {
        return IndicatorModel::whereFgnActivityId($this->fgnActivityId)
            ->get()
            ->each(function ($value) {
                $value->periodicity = $value->getPeriodicity();
                $value->type = $value->getType();
            });
    }

}