<?php

namespace Wgroup\SystemParameter;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use System\Models\Parameters;
use Wgroup\Models\InfoDetail;

/**
 * Agent Model
 */
class SystemParameter extends Model
{

    /**
     * @var string The database table used by the model.
     */
    public $table = 'system_parameters';
    public $timestamps = false;
    /*
     * Validation
     */
    public $rules = [
        'name' => 'required'
    ];

    /**
     * @var array Relations
     */
    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id'],
        'country' => ['RainLab\User\Models\Country', 'key' => 'country_id', 'otherKey' => 'id'],
        'state' => ['RainLab\User\Models\State', 'key' => 'state_id', 'otherKey' => 'id'],
        'town' => ['Wgroup\Models\Town', 'key' => 'city_id', 'otherKey' => 'id'],
    ];

    public $attachOne = [
        'logo' => ['System\Models\File'],
    ];

    public $hasMany = [

    ];

    public function scopeIsEnabled($query)
    {
        return $query->where('active', true);
    }


    /**
     * Returns the public image file path to this user's avatar.
     */
    public function getAvatarThumb($size = 25, $default = null)
    {
        if (!$default)
            $default = 'mm'; // Mystery man

        if ($this->logo)
            return $this->logo->getThumb($size, $size);
        else
            return '//www.gravatar.com/avatar/' . md5(strtolower(trim($this->documentNumber))) . '?s=' . $size . '&d=' . urlencode($default);
    }

    public static function getRelationTable($table, $alias = '')
    {
        $alias = trim($alias) != '' ? $alias : $table;
        return "(SELECT `id`, `namespace`, `group`, `item`, `value` COLLATE utf8_general_ci AS `value`, `code` FROM `system_parameters` WHERE `namespace` = 'wgroup' AND `group` = '$table') $alias ";
    }

    public static function getRelationAsTable($table)
    {
        return "(SELECT `id`, `namespace`, `group`, `item`, `code`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters` WHERE `namespace` = 'wgroup') $table ";
    }

    public static function finAll($group, $ns = "wgroup")
    {
        return SystemParameter::whereNamespace($ns)->whereGroup($group)->get();
    }

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getFirstByGroup($group) {
        return SystemParameter::whereNamespace('wgroup')->whereGroup($group)->first();
    }

    public static function getByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


    public static function getParameterByGroupAndValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->get();
    }

}
