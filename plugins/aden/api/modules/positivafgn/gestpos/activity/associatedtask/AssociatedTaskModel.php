<?php

namespace AdeN\Api\Modules\PositivaFgn\GestPos\Activity\AssociatedTask;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use DB;
use System\Models\Parameters;

class AssociatedTaskModel extends Model
{    
	use CamelCasing;
	
    /**
     * @var string The database table used by the model.
     */	
    protected $table = "wg_positiva_fgn_gestpos_associated_task";


}