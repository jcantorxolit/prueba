<?php

namespace AdeN\Api\Modules\PositivaFgn\Vendor;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;

class ContactInformationModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_vendor_contact_information";

    public function getType()
    {
        return Parameters::whereNamespace("wgroup")->whereGroup("extrainfo")->whereValue($this->type)->first();
    }
	
}