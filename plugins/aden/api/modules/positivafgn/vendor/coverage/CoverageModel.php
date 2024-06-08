<?php

namespace AdeN\Api\Modules\PositivaFgn\Vendor\Coverage;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;

class CoverageModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_vendor_coverage";

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

    public function getDepartment()
    {
        return DB::table("rainlab_user_states")
                ->select("id","name")
                ->where("id", $this->departmentId)
                ->first();
    }

    public function getTown()
    {
        return DB::table("wg_towns")
                ->select("id","name")
                ->where("id", $this->townId)
                ->first();
    }

}