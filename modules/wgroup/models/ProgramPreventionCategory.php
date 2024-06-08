<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;

/**
 * Idea Model
 */
class ProgramPreventionCategory extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_progam_prevention_category';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];


    public function items()
    {
        return $this->hasMany('Wgroup\Models\ProgramPreventionCategory', 'parent_id');
    }

    public function childContent()
    {
        return $this->hasMany('ProgramPreventionCategory', 'parent_id', 'id');
    }

    public function parentContent()
    {
        return $this->hasOne('ProgramPreventionCategory', 'id', 'parent_id');

    }
}
