<?php

namespace AdeN\Api\Modules\Customer\Contributions;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;

class ContributionModel extends Model
{
	use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_arl_contribution";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];
}
