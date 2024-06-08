<?php

namespace Wgroup\InvestigationAlCause;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use Wgroup\InvestigationAlCauseCategory\InvestigationAlCauseCategory;

/**
 * Idea Model
 */
class InvestigationAlCause extends Model {

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
    
    public function getCategory()
    {
        return InvestigationAlCauseCategory::find($this->cause_category_id);
    }

    public function items()
    {
        return $this->hasMany('Wgroup\InvestigationAlCauseCategory\InvestigationAlCauseCategory', 'parent_id');
    }

    public function childContent()
    {
        return $this->hasMany('Wgroup\InvestigationAlCause\InvestigationAlCause', 'parent_id', 'id');
    }

    public function parentContent()
    {
        return InvestigationAlCause::find($this->parent_id);

    }
}
