<?php

namespace AdeN\Api\Modules\Customer\Licenses;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;

use Wgroup\SystemParameter\SystemParameter;

/**
 * @property string $license
 * @property string $state
 */
class LicenseModel extends Model
{
    use CamelCasing;

    protected $table = "wg_customer_licenses";

    const STATE_ACTIVE   = 'LS001';
    const STATE_INACTIVE = 'LS002';
    const STATE_FINISH   = 'LS003';


     protected static function boot() {
         parent::boot();
         static::observe(LicenseObserver::class);
     }


    public function getLicense() {
        return SystemParameter::getByValue($this->license, "wg_customer_licenses_types");
    }

    public function getState() {
        return SystemParameter::getByValue($this->state, "wg_customer_licenses_states");
    }

}
