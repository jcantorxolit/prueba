<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\SafetyInspectionListItemDocument;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerSafetyInspectionListItemDocumentModel extends Model
{
    use CamelCasing;

    const CLASS_NAME = "Wgroup\CustomerSafetyInspectionListItemDocument\CustomerSafetyInspectionListItemDocument";

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_safety_inspection_list_item_document";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'created_by', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updated_by', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

    public function getType()
    {
        return $this->getParameterByValue($this->type, "contract_detail_document_type");
    }

    public function getStatus()
    {
        return $this->getParameterByValue($this->status, "customer_document_status");
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getSystemFile()
    {
        return str_replace('\\', '\\\\', "(SELECT * FROM system_files WHERE field = 'document' AND attachment_type = '" . self::CLASS_NAME . "') system_files");
    }
}
