<?php

namespace AdeN\Api\Modules\Customer\InternalProject;

use DB;
use AdeN\Api\Classes\BaseService;

class CustomerInternalProjectService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function allYears()
    {
        return DB::table('wg_customer_internal_project')
            ->select(
                DB::raw('YEAR(deliveryDate) value'),
                DB::raw('YEAR(deliveryDate) item')
            )
            ->groupBy(DB::raw('YEAR(deliveryDate)'))
            ->orderBy(DB::raw('YEAR(deliveryDate)'), 'DESC')
            ->get();
    }
}
