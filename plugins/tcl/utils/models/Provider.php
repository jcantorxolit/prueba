<?php

namespace Tcl\Utils\Models;

use Cms\Classes\Controller;
use Cms\Classes\Controller as BaseController;
use Cms\Classes\Page;
use Illuminate\Support\Facades\Log;
use Model;
use Validator;

/**
 * Menu Model
 */
class Provider extends Model {
use \October\Rain\Database\Traits\Validation;

    /**
     * @var Controller A reference to the CMS controller.
     */
    private $controller;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'findideas_providers_ideas';

    /**
     * @var array Translatable fields
     */
    public $translatable = ['name', 'description'];

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['name', 'description', 'urlbase', 'urlproyect', 'url_login',
        'csrf_name', 'session_name', 'auth_required', 'auth_user', 'auth_pwd',
        'auth_type', 'active', 'last_importer', 'import'];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'name' => 'required',
        'urlbase' => 'required'
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
    private static $defaultService;

    public function afterCreate() {
        if ($this->is_default)
            $this->makeDefault();
    }

    public function beforeUpdate() {
        if ($this->isDirty('is_default')) {
            $this->makeDefault();

            if (!$this->is_default)
                throw new ValidationException(['is_default' => Lang::get('tcl.utils::lang.providers.unset_default', ['provider' => $this->name])]);
        }
    }

    /**
     * Makes this model the default
     * @return void
     */
    public function makeDefault() {
        if (!$this->active)
            throw new ValidationException(['active' => Lang::get('tcl.utils::lang.providers.disabled_default', ['provider' => $this->name])]);

        $this->newQuery()->where('id', $this->id)->update(['is_default' => true]);
        $this->newQuery()->where('id', '<>', $this->id)->update(['is_default' => false]);
    }

    /**
     * Returns the default currency defined.
     * @return self
     */
    public static function getDefault($reload = false) {

        if ($reload) {
            self::clearCache(); 
        }

        if (self::$defaultService !== null)
            return self::$defaultService;

        return self::$defaultService = self::where('is_default', true)
                ->remember(1440, 'tcl.ideaprovider.defaultProvider')
                ->first()
        ;
    }

    /**
     * Locate a currency table by its code, cached.
     * @param  string $code
     * @return Model
     */
    public static function findByCode($code = null) {
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
    public function scopeIsEnabled($query) {
        return $query
                        ->whereNotNull('active')
                        ->where('active', true)
        ;
    }

    /**
     * Returns true if there are at least 2 currencies available.
     * @return boolean
     */
    public static function isAvailable() {
        return count(self::listAvailable()) > 1;
    }

    /**
     * Lists available currencies, used on the back-end.
     * @return array
     */
    public static function listAvailable() {
        if (self::$cacheListAvailable)
            return self::$cacheListAvailable;

        return self::$cacheListAvailable = self::lists('name', 'description', 'urlbase', 'urlproyect', 'url_login',
        'csrf_name', 'session_name', 'auth_required', 'auth_user', 'auth_pwd',
        'auth_type', 'active', 'last_importer', 'import');
    }

    /**
     * Lists the enabled currencies, used on the front-end.
     * @return array
     */
    public static function listEnabled() {
        if (self::$cacheListEnabled)
            return self::$cacheListEnabled;

        return self::$cacheListEnabled = self::isEnabled()
                ->remember(1440, 'tcl.ideasproviders')
                ->lists('name', 'code')
        ;
    }

    /**
     * Clears all cache keys used by this model
     * @return void
     */
    public static function clearCache() {
        Cache::forget('tcl.ideasproviders');
        Cache::forget('tcl.ideaprovider.defaultProvider');
    }

}
