<?php

namespace AdeN\Api\Modules\Config\SignatureCertificateVr;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;

class SignatureCertificateVrModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_config_vr_certificate_information";

    public $attachOne = [
        'signature' => ['System\Models\File'],
        'logo'      => ['System\Models\File']
    ];
}
