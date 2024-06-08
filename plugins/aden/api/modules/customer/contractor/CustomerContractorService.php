<?php

namespace AdeN\Api\Modules\Customer\Contractor;

use AdeN\Api\Classes\BaseService;
use DB;
use Wgroup\SystemParameter\SystemParameter;

class CustomerContractorService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getList($criteria)
    {
        $contractorClassification = SystemParameter::where('group', 'wg_contractor_classification_dashboard')
            ->where('code', 'contractor')
            ->get()
            ->map(function ($item) {
                return $item->value;
            })
            ->toArray();

        $customerContractors = DB::table('wg_customers')
            ->join('wg_customer_contractor', 'wg_customer_contractor.customer_id', '=', 'wg_customers.id')
            ->join('wg_customers AS wg_contractor', 'wg_contractor.id', '=', 'wg_customer_contractor.contractor_id')
            ->select(
                'wg_contractor.id',
                'wg_contractor.id AS value',
                'wg_contractor.businessName AS item',
                'wg_customer_contractor.customer_id as parentId'
            )
            ->whereIn('wg_customers.classification', $contractorClassification ? $contractorClassification : ['Contratante'])
            ->orderBy('wg_contractor.businessName');

        $customerEconomicGroup = DB::table('wg_customers')
            ->join('wg_customer_economic_group as eg', 'wg_customers.id', '=', 'eg.parent_id')
            ->join('wg_customers AS customer_eg', 'customer_eg.id', '=', 'eg.customer_id')
            ->select(
                'customer_eg.id',
                'customer_eg.id AS value',
                'customer_eg.businessName AS item',
                'wg_customers.id as parentId'
            )
            ->where('wg_customers.hasEconomicGroup', '1')
            ->orderBy('customer_eg.businessName');


        if (isset($criteria->parentId) && $criteria->parentId) {
            $customerContractors->where('wg_customers.id', $criteria->parentId);
            //->orWhere('wg_customer_contractor.customer_id', $criteria->parentId);
            $customerEconomicGroup->where('wg_customers.id', $criteria->parentId);
            //->orWhere('eg.parent_id', $criteria->parentId);
        }

        if (isset($criteria->isCustomer) && $criteria->isCustomer) {
            $customerContractors->where('wg_contractor.id', $criteria->customerId);
            $customerEconomicGroup->where('customer_eg.id', $criteria->customerId);
        }

        if (isset($criteria->parentId) && isset($criteria->isCustomer) && $criteria->parentId == $criteria->customerId) {
            $customerContractors->orWhere(function ($query) use ($criteria) {
                $query->where('wg_customer_contractor.customer_id', $criteria->customerId);
            });

            $customerEconomicGroup->orWhere(function ($query) use ($criteria) {
                $query->where('eg.parent_id', $criteria->customerId);
            });
        }

        $customerContractors->union($customerEconomicGroup);

        $query = DB::table(DB::raw("({$customerContractors->toSql()}) as wg_customers"))
            ->mergeBindings($customerContractors)
            ->distinct()
            ->orderBy('wg_customers.item');

        return $query->get();
    }

    public function getC03($criteria)
    {
        $query = DB::table('wg_customers')
            ->rightJoin('wg_customer_contractor', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_contractor.customer_id');
            })
            ->join(DB::raw('wg_customers AS wg_contractor'), function ($join) {
                $join->on('wg_contractor.id', '=', 'wg_customer_contractor.contractor_id');
            })
            ->select(
                'wg_contractor.id',
                'wg_contractor.id AS value',
                'wg_contractor.businessName AS item'
            )
            ->where('wg_customer_contractor.isActive', 1)
            ->orderBy('wg_contractor.businessName');

        if (isset($criteria->parentId) && $criteria->parentId) {
            $query->where('wg_customers.id', $criteria->parentId);
        }

        return $query->get()->toArray();
    }

    public function getCustomerRelationships($criteria)
    {
        $contractorClassification = SystemParameter::where('group', 'wg_contractor_classification_dashboard')
            ->where('code', 'contractor')
            ->get()
            ->map(function ($item) {
                return $item->value;
            })
            ->toArray();

        $customerContractors = DB::table('wg_customers')
            ->join('wg_customer_contractor', 'wg_customer_contractor.customer_id', '=', 'wg_customers.id')
            ->join('wg_customers AS wg_contractor', 'wg_contractor.id', '=', 'wg_customer_contractor.contractor_id')
            ->whereIn('wg_customers.classification', $contractorClassification ? $contractorClassification : ['Contratante'])
            ->groupBy('wg_customers.id')
            ->select(
                'wg_customer_contractor.customer_id as parentId',
                DB::raw("COUNT(DISTINCT wg_contractor.id) AS count"),
                DB::raw("count(DISTINCT IF(wg_customer_contractor.isActive = 1, wg_contractor.id, null)) as actives"),
                DB::raw("count(DISTINCT IF(wg_customer_contractor.isActive <> 1, wg_contractor.id, null)) as inactives")
            );

        $customerEconomicGroup = DB::table('wg_customers')
            ->join('wg_customer_economic_group as eg', 'wg_customers.id', '=', 'eg.parent_id')
            ->join('wg_customers AS customer_eg', 'customer_eg.id', '=', 'eg.customer_id')
            ->where('wg_customers.hasEconomicGroup', '1')
            ->groupBy('wg_customers.id')
            ->select(
                'wg_customers.id as parentId',
                DB::raw("count(wg_customers.id) as count"),
                DB::raw("count(IF(eg.isActive = 1, 1, null)) as actives"),
                DB::raw("count(IF(eg.isActive <> 1, 1, null)) as inactives")
            );

        if (isset($criteria->parentId) && $criteria->parentId) {
            $customerContractors->where('wg_customers.id', $criteria->parentId);
            $customerEconomicGroup->where('wg_customers.id', $criteria->parentId);
        }

        $response = new \stdClass();
        $response->contrators = $customerContractors->first();
        $response->economigGroup = $customerEconomicGroup->first();
        return $response;
    }



    public function getGridCustomerRelationships()
    {
        $customerContractors = DB::table('wg_customers')
            ->join('wg_customer_contractor', 'wg_customer_contractor.customer_id', '=', 'wg_customers.id')
            ->join('wg_customers AS wg_contractor', 'wg_contractor.id', '=', 'wg_customer_contractor.contractor_id')
            ->select(
                'wg_customer_contractor.customer_id as parentId',
                'wg_contractor.documentNumber',
                'wg_contractor.businessName',
                DB::raw("'Empresa Contratista' as relationship"),
                DB::raw("CASE WHEN wg_customer_contractor.isActive = '1' THEN 'Activo' ELSE 'Inactivo' END as status")
            );

        $customerEconomicGroup = DB::table('wg_customers')
            ->join('wg_customer_economic_group as eg', 'wg_customers.id', '=', 'eg.parent_id')
            ->join('wg_customers AS customer_eg', 'customer_eg.id', '=', 'eg.customer_id')
            ->select(
                'wg_customers.id as parentId',
                'customer_eg.documentNumber',
                'customer_eg.businessName',
                DB::raw("'Empresa del Grupo económico' as relationship"),
                DB::raw("CASE WHEN eg.isActive = '1' THEN 'Activo' ELSE 'Inactivo' END as status")
            );

        $customerEconomicGroup->union($customerContractors)->mergeBindings($customerContractors);

        return DB::table(DB::raw("({$customerEconomicGroup->toSql()}) as t"))
            ->mergeBindings($customerEconomicGroup);
    }

    public function getCustomerContractorList($criteria)
    {
        $query = DB::table('wg_customers')
            ->rightJoin('wg_customer_contractor', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_contractor.customer_id');
            })
            ->join(DB::raw('wg_customers AS wg_contractor'), function ($join) {
                $join->on('wg_contractor.id', '=', 'wg_customer_contractor.contractor_id');
            })
            ->select(
                'wg_contractor.id',
                'wg_contractor.id AS value',
                'wg_contractor.businessName AS item'
            )
            ->where('wg_customer_contractor.isActive', 1)
            ->orderBy('wg_contractor.businessName');

        if (isset($criteria->parentId) && $criteria->parentId) {
            $query->where('wg_customers.id', $criteria->parentId);
        }

        return $query->get()->toArray();
    }
}
