<?php

namespace AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class InfoModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_vendor_main_contact_info";

    public function getType()
    {
        return $this->getParameterByValue($this->type, "extrainfo");
    }
    
	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


}