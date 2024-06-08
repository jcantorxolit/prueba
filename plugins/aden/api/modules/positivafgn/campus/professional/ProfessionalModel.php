<?php

namespace AdeN\Api\Modules\PositivaFgn\Campus\Professional;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ProfessionalModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_campus_professional";

    public function getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "employee_document_type");
    }
	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


}