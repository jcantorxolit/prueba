<?php

namespace AdeN\Api\Modules\PositivaFgn\Vendor\Contract;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;

class ContractModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_vendor_contract";


}