<?php

namespace AdeN\Api\Modules\PositivaFgn\Consultant;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;

class StrategyModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_positiva_fgn_consultant_strategy";

    public function getStrategy()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("positiva_fgn_consultant_strategy")->whereValue($this->strategy)->first();
    }

    public function getStrategyType()
    {
        return Parameters::whereNamespace("wgroup")
            ->whereGroup("positiva_fgn_strategy_type")
            ->whereValue($this->type)
            ->first();
    }

}
