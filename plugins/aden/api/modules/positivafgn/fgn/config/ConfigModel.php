<?php

namespace AdeN\Api\Modules\PositivaFgn\Fgn\Config;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;

class ConfigModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_config";


}