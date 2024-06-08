<?php

namespace AdeN\Api\Modules\Dashboard\TopManagement\Services;

use Illuminate\Support\Facades\DB;
use Wgroup\SystemParameter\SystemParameter;

class CostByMonth {

    public static function getQueryIndicator($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator) {

        $queryBase = DB::table('project_consolidate as c')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'sp')), function ($join) {
                $join->whereRaw("CONVERT(DATE_FORMAT(deliveryDate, '%m'), UNSIGNED INT) = sp.value");
            })
            ->whereDate('c.deliveryDate', '>=', $startDate)
            ->whereDate('c.deliveryDate', '<=', $endDate)
            ->when($type, function($query) use ($type) {
                $query->where('c.type', $type);
            })
            ->when($concept, function($query) use ($concept) {
                $query->where('c.concept', $concept);
            })
            ->when($classification, function($query) use ($classification) {
                $query->where('c.classification', $classification);
            })
            ->when($customerId, function($query) use ($customerId) {
                $query->where('c.customer_id', $customerId);
            })
            ->when($administrator, function($query) use ($administrator) {
                $query->where('c.administrator', $administrator);
            })
            ->groupBy(DB::raw("CONCAT(YEAR(deliveryDate), '-', sp.item)")) ;


        $executed = (clone $queryBase)
            ->select(
                'deliveryDate',
                DB::raw("'Ejecutado' as label"),
                DB::raw("CONCAT(YEAR(deliveryDate), '-', sp.item) AS dynamicColumn"),
                DB::raw("SUM(total_executed) AS total"),
                DB::raw("'#6494bf' AS color")
            );

        $programmed = (clone $queryBase)
            ->select(
                'deliveryDate',
                DB::raw("'Programado' as label"),
                DB::raw("CONCAT(YEAR(deliveryDate), '-', sp.item) AS dynamicColumn"),
                DB::raw("SUM(total) AS total"),
                DB::raw("'#c26530' AS color")
            );

        return $executed
            ->unionAll($programmed)->mergeBindings($programmed);
    }


    public static function getQueryGrid() {
        return DB::table('project_consolidate as c')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'sp')), function ($join) {
                $join->whereRaw("CONVERT(DATE_FORMAT(deliveryDate, '%m'), UNSIGNED INT) = sp.value");
            })
            ->groupBy(DB::raw("CONCAT(YEAR(deliveryDate), '-', sp.item)"));
        }
}
