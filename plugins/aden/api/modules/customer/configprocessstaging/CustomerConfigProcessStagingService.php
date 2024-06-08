<?php

namespace AdeN\Api\Modules\Customer\ConfigProcessStaging;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerConfigProcessStagingService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function findWorkplace($id)
    {
        return DB::table('wg_customer_config_workplace')->find($id);
    }

    public function findMacroprocess($id)
    {
        return DB::table('wg_customer_config_macro_process')->find($id);
    }
}