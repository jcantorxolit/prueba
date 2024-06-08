<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\ActivityConfigSectional\ConfigConsultant;

use AdeN\Api\Classes\CamelCasing;
use AdeN\Api\Modules\PositivaFgn\Consultant\ConsultantModel;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ConfigConsultantModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_activity_indicator_sectional_consultant";

}