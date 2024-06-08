<?php

namespace AdeN\Api\Modules\User;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;

class UserModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "users";

}
