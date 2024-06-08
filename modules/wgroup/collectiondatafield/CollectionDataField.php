<?php

namespace Wgroup\CollectionDataField;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CollectionDataField extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_collection_data_field';


    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [
        'customer' => ['Wgroup\CollectionData\CollectionData', 'key' => 'collection_id', 'otherKey' => 'id']
    ];

    public $hasMany = [

    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
