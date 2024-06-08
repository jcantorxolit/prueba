<?php

namespace AdeN\Api\Modules\Config\General;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class ConfigGeneralService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList($type)
    {
        return DB::table('wg_config_general')
            ->select(
                'id',
                'name',
                'value AS qualification',
                'description AS justification',
                'code AS color'
            )
            ->where('type', $type)
            ->get();
    }
}