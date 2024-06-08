<?php

namespace AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class MainContactModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_vendor_main_contact";

    public $hasMany = [
        "info" => ["AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact\InfoModel", 'key' => 'main_contact_id', 'otherKey' => 'id']
    ];

    public function getContactType()
    {
        return $this->getParameterByValue($this->contactType, "rolescontact");
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


}