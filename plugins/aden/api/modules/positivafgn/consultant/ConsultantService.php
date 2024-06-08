<?php

namespace AdeN\Api\Modules\PositivaFgn\Consultant;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class ConsultantService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getRegionalList(){
        return DB::table("wg_positiva_fgn_regional")
                ->select("id AS value","number AS item")
                ->get();
    }

    public function getSectionalList($criteria)
    {
        $query = DB::table("wg_positiva_fgn_sectional")
            ->select("id AS value", "name AS item", "nit")
            ->where("regional_id", $criteria->regionalId);

        return $query->get();
    }


    public function getAllSectionalList($criteria)
    {
        $consultant = ConsultantModel::whereUserId($criteria->userId)->first();
        if($consultant) {
            return DB::table("wg_positiva_fgn_sectional")
                ->join("wg_positiva_fgn_consultant_sectional",
                        "wg_positiva_fgn_sectional.id", "=","wg_positiva_fgn_consultant_sectional.sectional_id")
                ->select("wg_positiva_fgn_sectional.id AS value", "wg_positiva_fgn_sectional.name AS item")
                ->where("wg_positiva_fgn_consultant_sectional.consultant_id", $consultant->id)
                ->groupBy("wg_positiva_fgn_sectional.id")
                ->get();
        } else {
            return [];
        }
    }


    public function getAllSectionalList2()
    {
        return DB::table("wg_positiva_fgn_sectional")
            ->select("id AS value", "name AS item")
            ->get();
    }

}
