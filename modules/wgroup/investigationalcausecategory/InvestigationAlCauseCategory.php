<?php

namespace Wgroup\InvestigationAlCauseCategory;

use BackendAuth;
use Log;
use October\Rain\Database\Model;

/**
 * Idea Model
 */
class InvestigationAlCauseCategory extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_investigation_cause';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];


    public function items()
    {
        return $this->hasMany('Wgroup\InvestigationAlCauseCategory\InvestigationAlCauseCategory', 'cause_category_id');
    }

    public function childContent()
    {
        return $this->hasMany('Wgroup\InvestigationAlCauseCategory\InvestigationAlCauseCategory', 'cause_category_id');
    }
}