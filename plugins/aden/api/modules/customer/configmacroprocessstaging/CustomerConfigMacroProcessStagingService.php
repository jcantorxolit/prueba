<?php

namespace AdeN\Api\Modules\Customer\ConfigMacroProcessStaging;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerConfigMacroProcessStagingService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function findWorkplace($id)
    {
        return DB::table('wg_customer_config_workplace')->find($id);
    }
}
