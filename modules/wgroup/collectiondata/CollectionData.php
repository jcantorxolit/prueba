<?php

namespace Wgroup\CollectionData;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;

/**
 * Idea Model
 */
class CollectionData extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_collection_data';


    /*
     * Validation
     */
    public $rules = [

    ];

    public $belongsTo = [

    ];

    public $hasMany = [
        'fields' => ['Wgroup\CollectionDataField\CollectionDataField'],
    ];

    public function getFields($reportId)
    {
        $query = "select case when rf.id is null then 0 else rf.id end collectionDataFieldId
			, case when rf.report_id is null then 0 else rf.report_id end reportId
			, cdf.id
			, cdf.`table`
			, cdf.`name`
			, cdf.alias
			, cdf.dataType
			, cdf.isActive
from wg_collection_data_field cdf
left join (select rdf.* from wg_report r
						inner join wg_report_collection_data_field rdf on r.id = rdf.report_id
						where r.id = :report_id) rf on cdf.id = rf.collection_data_field_id
where cdf.collection_data_id = :collection_data_id AND cdf.visible = 1";

        $results = DB::select( $query, array(
            'report_id' => $reportId,
            'collection_data_id' => $this->id,
        ));

        if ($this->type == "report") {
            return $results;
        } else {
            return $this->fields;
        }
    }

    public function getFieldsChart($reportId)
    {
        $query = "select case when rf.id is null then 0 else rf.id end collectionDataFieldId
			, case when rf.report_id is null then 0 else rf.report_id end reportId
			, cdf.id
			, cdf.`table`
			, cdf.`name`
			, cdf.alias
			, cdf.dataType
			, cdf.isActive
from wg_collection_data_field cdf
left join (select rdf.* from wg_report r
						inner join wg_report_chart_field rdf on r.id = rdf.report_id
						where r.id = :report_id) rf on cdf.id = rf.collection_data_field_id
where cdf.collection_data_id = :collection_data_id";

        $results = DB::select( $query, array(
            'report_id' => $reportId,
            'collection_data_id' => $this->id,
        ));


        return $results;

    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
