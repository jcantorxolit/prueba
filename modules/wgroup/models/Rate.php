<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;

/**
 * Agent Model
 */
class Rate extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_rate';

    /*
     * Validation
     */
    public $rules = [
        'id' => 'required'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        
    ];

   public function scopeIsEnabled($query)
    {
        return $query->where('active', true);
    }

   
}
