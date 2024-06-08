<?php

namespace AdeN\Api\Modules\Customer\ProjectHistorial;

use October\Rain\Database\Model;

use AdeN\Api\Classes\CamelCasing;

class CustomerProjectHistorialModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_project_historial";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];
}