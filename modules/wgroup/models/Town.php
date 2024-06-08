<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;

/**
 * Town Model
 */
class Town extends Model {

    /**
     * @var string The database table used by the model.
     */
    public $table = 'wg_towns';

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
        'state' => ['Wgroup\Models\State','key' => 'state_id', 'otherKey' => 'code']
    ];

    /**
     * @var bool Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * @var array Cache for nameList() method
     */
    protected static $nameList = [];

    public static function getNameList($depId)
    {
        if (isset(self::$nameList[$depId]))
            return self::$nameList[$depId];

        return self::$nameList[$depId] = self::whereStateId($depId)->lists('name', 'id');
    }

    public static function formSelect($name, $depId = null, $selectedValue = null, $options = [])
    {
        return Form::select($name, self::getNameList($depId), $selectedValue, $options);
    }
}
