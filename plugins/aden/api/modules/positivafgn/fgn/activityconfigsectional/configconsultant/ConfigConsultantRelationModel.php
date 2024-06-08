<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\PositivaFgn\Consultant\ConsultantModel;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ConfigConsultantRelationModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_activity_indicator_sectional_consultant_relation";

    public function getConsultant()
    {
        return ConsultantModel::whereId($this->consultantId)
                        ->select(
                            "full_name AS item",
                            "wg_positiva_fgn_consultant.id AS value"
                        )
                        ->first();
    }

}