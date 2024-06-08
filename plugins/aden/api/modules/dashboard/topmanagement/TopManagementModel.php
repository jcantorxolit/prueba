<?php

namespace AdeN\Api\Modules\Dashboard\TopManagement;

use October\Rain\Database\Model;
use AdeN\Api\Classes\CamelCasing;

class TopManagementModel extends Model
{
    use CamelCasing;

    protected $table = "wg_customer_project_agent_consolidate";

}
