<?php

namespace AdeN\Api\Modules\PositivaFgn\Management\Relations;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class HeaderModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_management_indicator_header";


}