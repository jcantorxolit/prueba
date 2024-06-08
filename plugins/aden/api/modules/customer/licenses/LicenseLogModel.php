<?php

namespace AdeN\Api\Modules\Customer\Licenses;

use October\Rain\Database\Model;
use AdeN\Api\Classes\CamelCasing;

class LicenseLogModel extends Model
{
    use CamelCasing;

    protected $table = "wg_customer_licenses_logs";

}
