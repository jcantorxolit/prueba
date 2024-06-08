<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;

/**
 * Idea Model
 */
class ProgramPreventionQuestion extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_progam_prevention_question';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

}
