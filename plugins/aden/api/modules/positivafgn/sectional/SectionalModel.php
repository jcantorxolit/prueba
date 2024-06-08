<?php

namespace AdeN\Api\Modules\PositivaFgn\Sectional;

use AdeN\Api\Classes\CamelCasing;
use Illuminate\Support\Facades\DB;
use October\Rain\Database\Model;

class SectionalModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_positiva_fgn_sectional";

    public function regional()
    {
        return DB::table('wg_positiva_fgn_regional AS r')
            ->where('r.id', $this->regionalId)
            ->select("id AS value","number AS item")
            ->first();
    }

}
