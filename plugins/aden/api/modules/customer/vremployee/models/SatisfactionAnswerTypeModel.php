<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\Models;

use October\Rain\Database\Model;
use AdeN\Api\Classes\CamelCasing;

class SatisfactionAnswerTypeModel extends Model
{
    use CamelCasing;

    protected $table = "wg_customer_vr_satisfactions_answers_types";

}
