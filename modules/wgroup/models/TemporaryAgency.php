<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;

/**
 * Town Model
 */
class TemporaryAgency extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_temporary_agencies';

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

    ];

    /**
     * @var bool Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * @var array Cache for nameList() method
     */
    protected static $nameList = [];

    public static function getNameList($oder = "name", $typeOrder = "asc")
    {
        if (isset(self::$nameList) && count(self::$nameList))
            return self::$nameList;

        return self::$nameList = self::isEnabled()->orderBy($oder, $typeOrder)->get();
    }

    public static function formSelect($name, $depId = null, $selectedValue = null, $options = [])
    {
        return Form::select($name, self::getNameList($depId), $selectedValue, $options);
    }

    public function scopeIsEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

}
