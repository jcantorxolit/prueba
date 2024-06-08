<?php

namespace Wgroup\QuoteService;

use BackendAuth;
use Log;
use October\Rain\Database\Model;

/**
 * Idea Model
 */
class QuoteService extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_quote_service';

    /*
     * Validation
     */
    public $rules = [

    ];

}
