<?php

namespace AdeN\Api\Modules\Dashboard\Commercial;

use October\Rain\Database\Model;
use AdeN\Api\Classes\CamelCasing;

class CommercialDashboardModel extends Model
{
    use CamelCasing;

    protected $table = "wg_customer_licenses_consolidate";

}
