<?php namespace Tcl\Utils\Models;

use Cache;
use Lang;
use Model;
use October\Rain\Database\Builder;
use October\Rain\Support\ValidationException;

/**
 * Locale Model
 */
class Currency extends Model
{

    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'tcl_currency_currencies';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'code' => 'required',
        'name' => 'required',
    ];

    public $timestamps = false;

    /**
     * @var array Object cache of self, by code.
     */
    protected static $cacheByCode = [];

    /**
     * @var array A cache of enabled currencies.
     */
    protected static $cacheListEnabled;

    /**
     * @var array A cache of available currencies.
     */
    protected static $cacheListAvailable;

    /**
     * @var self Default currency cache.
     */
    private static $defaultCurrency;

    public function afterCreate()
    {
        if ($this->is_default)
            $this->makeDefault();
    }

    public function beforeUpdate()
    {
        if ($this->isDirty('is_default')) {
            $this->makeDefault();

            if (!$this->is_default)
                throw new ValidationException(['is_default' => Lang::get('tcl.utils::lang.currency.unset_default', ['currency'=>$this->name])]);
        }
    }

    /**
     * Makes this model the default
     * @return void
     */
    public function makeDefault()
    {
        if (!$this->is_enabled)
            throw new ValidationException(['is_enabled' => Lang::get('tcl.utils::lang.currency.disabled_default', ['currency'=>$this->name])]);

        $this->newQuery()->where('id', $this->id)->update(['is_default' => true]);
        $this->newQuery()->where('id', '<>', $this->id)->update(['is_default' => false]);
    }

    /**
     * Returns the default currency defined.
     * @return self
     */
    public static function getDefault($reload = false)
    {
        if($reload){
            self::clearCache();
        }
        
        if (self::$defaultCurrency !== null)
            return self::$defaultCurrency;

        return self::$defaultCurrency = self::where('is_default', true)
            ->remember(1440, 'tcl.currency.defaultCurrency')
            ->first()
        ;
    }

    /**
     * Locate a currency table by its code, cached.
     * @param  string $code
     * @return Model
     */
    public static function findByCode($code = null)
    {
        if (!$code)
            return null;

        if (isset(self::$cacheByCode[$code]))
            return self::$cacheByCode[$code];

        return self::$cacheByCode[$code] = self::whereCode($code)->first();
    }

    /**
     * Scope for checking if model is enabled
     * @param  Builder $query
     * @return Builder
     */
    public function scopeIsEnabled($query)
    {
        return $query
            ->whereNotNull('is_enabled')
            ->where('is_enabled', true)
        ;
    }

    /**
     * Returns true if there are at least 2 currencies available.
     * @return boolean
     */
    public static function isAvailable()
    {
        return count(self::listAvailable()) > 1;
    }

    /**
     * Lists available currencies, used on the back-end.
     * @return array
     */
    public static function listAvailable()
    {
        if (self::$cacheListAvailable)
            return self::$cacheListAvailable;

        return self::$cacheListAvailable = self::lists('name', 'code');
    }

    /**
     * Lists the enabled currencies, used on the front-end.
     * @return array
     */
    public static function listEnabled()
    {
        if (self::$cacheListEnabled)
            return self::$cacheListEnabled;

        return self::$cacheListEnabled = self::isEnabled()
            ->remember(1440, 'tcl.currency.currencies')
            ->lists('name', 'code')
        ;
    }

    /**
     * Clears all cache keys used by this model
     * @return void
     */
    public static function clearCache()
    {
        Cache::forget('tcl.currency.currencies');
        Cache::forget('tcl.currency.defaultCurrency');
    }

}