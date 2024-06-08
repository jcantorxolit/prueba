<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Olmed;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class OlmedModel extends Model
{
    const CLASS_NAME = "Wgroup\Olmed\Olmed";

    //use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_resource_library";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public $attachOne = [
        'cover' => ['System\Models\File'],
        'document' => ['System\Models\File']
    ];

    public function getType()
    {
        return $this->getParameterByValue($this->type, "resource_library_type");
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
