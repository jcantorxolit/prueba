<?php

namespace Wgroup\Models;

use BackendAuth;
use Log;
use October\Rain\Database\Model;
use RainLab\User\Models\State as StateBase;


/**
 * Town Model
 */
class State extends StateBase
{
    public $hasMany = [      
        'towns' => ['Wgroup\Models\Town', 'key' => 'state_id', 'otherKey' => 'id']
    ];
}
