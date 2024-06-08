<?php

namespace AdeN\Api\Modules\Customer\EconomicGroup;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerEconomicGroupService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList($criteria)
    {
        $q1 = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.parent_id');

            })
            ->join(DB::raw('wg_customers AS wg_economic_group'), function ($join) {
                $join->on('wg_economic_group.id', '=', 'wg_customer_economic_group.customer_id');

            })
            ->select(
                'wg_customers.id',
                'wg_customers.id AS value',
                'wg_customers.businessName AS item'
            )            
            ->where('wg_customers.hasEconomicGroup', 1)
            ->where('wg_customers.status', 1)
            ->groupBy('wg_customers.id')
            ->orderBy('wg_customers.businessName');

        $q2 = DB::table('wg_customers')
            ->join('wg_customer_economic_group', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_economic_group.parent_id');

            })
            ->join(DB::raw('wg_customers AS wg_economic_group'), function ($join) {
                $join->on('wg_economic_group.id', '=', 'wg_customer_economic_group.customer_id');

            })
            ->select(
                'wg_economic_group.id',
                'wg_economic_group.id AS value',
                'wg_economic_group.businessName AS item'
            )            
            ->where('wg_customers.hasEconomicGroup', 1)
            ->where('wg_customers.status', 1)
            ->where('wg_economic_group.status', 1)                                    
            ->orderBy('wg_economic_group.businessName');            

        if (isset($criteria->parentId) && $criteria->parentId) {
            $q1->where('wg_customers.id', $criteria->parentId);
            $q2->where('wg_customers.id', $criteria->parentId);
        }

        $q1->union($q2);

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_customers"))
            ->mergeBindings($q1)
            ->orderBy('wg_customers.item');

        return $query->get();        
    }
}