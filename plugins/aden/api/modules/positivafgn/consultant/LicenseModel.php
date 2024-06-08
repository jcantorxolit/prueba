<?php

namespace AdeN\Api\Modules\PositivaFgn\Consultant;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;

class LicenseModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_consultant_license";
	
}