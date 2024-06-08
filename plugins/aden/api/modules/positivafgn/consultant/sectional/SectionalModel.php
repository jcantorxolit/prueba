<?php

namespace AdeN\Api\Modules\PositivaFgn\Consultant\Sectional;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class SectionalModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_consultant_sectional";

    public function getType()
    {
        return $this->getParameterByValue($this->type, "positiva_fgn_consultant_sectional_type");
    }

    public function getRegional()
    {
        return DB::table("wg_positiva_fgn_regional")
                ->select("id AS value","number AS item")
                ->where("id", $this->regionalId)
                ->first();
    }

    public function getSectional()
    {
        return DB::table("wg_positiva_fgn_sectional")
                ->select("id AS value","name AS item")
                ->where("id", $this->sectionalId)
                ->first();
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}