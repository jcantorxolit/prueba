<?php

namespace AdeN\Api\Modules\Config\SignatureIndicatorVr;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;

class SignatureIndicatorVrModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_config_vr_indicator_information";

    public $attachOne = [
        'signature' => ['System\Models\File'],
    ];
}
