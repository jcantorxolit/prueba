<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\Document;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerDocumentModel extends Model
{
	//use CamelCasing;

    const CLASS_NAME = "Wgroup\Models\CustomerDocument";

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_document";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'document' => ['System\Models\File']
    ];

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getSecurityUserRelationTable($table)
    {
        return "(SELECT
        wg_customer_document.id,
        wg_customer_document.customer_id,
        wg_customer_document.type,
        wg_customer_document.origin,
        wg_customer_document_security_user.user_id
    FROM wg_customer_document
    INNER JOIN wg_customer_document_security_user
        ON wg_customer_document_security_user.customer_id = wg_customer_document.customer_id
            AND wg_customer_document_security_user.documentType = wg_customer_document.type
            AND wg_customer_document_security_user.origin = wg_customer_document.origin
    WHERE wg_customer_document_security_user.isActive = 1) $table ";
    }

    public static function getSystemFile()
    {
        return str_replace('\\', '\\\\', "(SELECT * FROM system_files WHERE field = 'document' AND attachment_type = '" . static::CLASS_NAME . "') system_files");
    }
}
