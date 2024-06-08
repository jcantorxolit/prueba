<?php

namespace Wgroup\Report;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class Report extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_report';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'collection' => ['Wgroup\CollectionData\CollectionData', 'key' => 'collection_id', 'otherKey' => 'id'],
        'collectionChart' => ['Wgroup\CollectionData\CollectionData', 'key' => 'collection_chart_id', 'otherKey' => 'id']
    ];

    public $hasMany = [
        'dataFields' => ['Wgroup\ReportCollectionDataField\ReportCollectionDataField'],
    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
