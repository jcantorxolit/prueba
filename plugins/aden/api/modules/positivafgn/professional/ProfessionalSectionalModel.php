<?php

namespace AdeN\Api\Modules\PositivaFgn\Professional;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class ProfessionalSectionalModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_positiva_fgn_sectional_professionals";

    public function getDocumentType()
    {
        return $this->getParameterByValue($this->documentType, "employee_document_type");
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


}
