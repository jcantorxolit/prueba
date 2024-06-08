<?php

namespace Wgroup\InvestigationAlEconomicActivity;

use BackendAuth;
use Log;
use October\Rain\Database\Model;

/**
 * Idea Model
 */
class InvestigationAlEconomicActivity extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_investigation_economic_activity';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];
}
