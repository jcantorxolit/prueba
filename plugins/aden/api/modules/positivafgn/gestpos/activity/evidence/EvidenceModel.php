<?php

namespace AdeN\Api\Modules\PositivaFgn\GestPos\Activity\Evidence;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class EvidenceModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_gestpos_evidence";

    public function getEvidence()
    {
        return $this->getParameterByValue($this->evidence, "positiva_fgn_gestpos_evidence");
    }
	protected function getParameterByValue($value, $group, $ns = "wgroup") {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


}