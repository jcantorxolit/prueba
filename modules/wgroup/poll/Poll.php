<?php

namespace Wgroup\Poll;

use BackendAuth;
use Log;
use DB;
use October\Rain\Database\Model;
use System\Models\Parameters;


/**
 * Idea Model
 */
class Poll extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_poll';


    /*
     * Validation
     */
    public $rules = [
        'nit' => 'required',
        'name' => 'required'
    ];

    public $belongsTo = [
        'collection' => ['Wgroup\CollectionData\CollectionData', 'key' => 'collection_id', 'otherKey' => 'id'],
    ];

    public $hasMany = [
        //'dataFields' => ['Wgroup\ReportCollectionDataField\ReportCollectionDataField'],
    ];

    public static function getQuestions($customerPollId)
    {
        $query = "select c.id customer_poll_id, q.*
	, case when pa.id is null then 0 else pa.id end answer_id
	, case when pa.`value` is null then '' else pa.`value` end answer_value
from wg_customer_poll c
inner join wg_poll p on c.poll_id = p.id
inner join wg_poll_question q on p.id = q.poll_id
left join wg_customer_poll_answer pa on c.id = pa.customer_poll_id and q.id = pa.poll_question_id
where c.id = :id
order by q.position asc";

        $results = DB::select( $query, array(
            'id' => $customerPollId,
        ));
        return $results;
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
