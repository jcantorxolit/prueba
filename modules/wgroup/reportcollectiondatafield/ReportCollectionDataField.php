<?php

namespace Wgroup\ReportCollectionDataField;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class ReportCollectionDataField extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_report_collection_data_field';

    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'report' => ['Wgroup\Report\Report', 'key' => 'report_id', 'otherKey' => 'id'],
        'dataField' => ['Wgroup\CollectionDataField\CollectionDataField', 'key' => 'collection_data_field_id', 'otherKey' => 'id']
    ];

    public $hasMany = [

    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
