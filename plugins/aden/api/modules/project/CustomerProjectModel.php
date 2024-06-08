<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Project;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;

class CustomerProjectModel extends Model
{
    //use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_project";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File'],
    ];

    public function getType()
    {
        return $this->getParameterByValue($this->type, "wg_professor_event_xxx");
    }

    public function getInvoicenumber()
    {
        return $this->getParameterByValue($this->invoicenumber, "wg_professor_event_xxx");
    }

    public static function getRelationDocumentCount($table)
    {
        return "(SELECT count(*) qryAttachment, project_id FROM wg_customer_project_document GROUP BY project_id) $table ";
    }


    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }
}
