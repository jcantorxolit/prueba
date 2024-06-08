<?php

namespace AdeN\Api\Modules\Customer\Employee\Indicators;

use Illuminate\Support\Facades\DB;
use October\Rain\Database\Builder;

use AdeN\Api\Classes\BaseService;

class CustomerEmployeeDocumentIndicatorService extends BaseService
{

    private $customerId;

    private $year;

    private $workplace;


    public function setCustomerId(int $customerId){
        $this->customerId = $customerId;
    }

    public function setYear(int $year) {
        $this->year = $year;
    }

    public function setWorkplace($workplace){
        $this->workplace = $workplace;
    }


    public function getTotalDocuments() {
        $workplace = $this->workplace;

        return DB::table('wg_customer_employee_status_documents_consolidate')
            ->where('customer_id', $this->customerId)
            ->whereYear('period', $this->year)
            ->when($workplace, function($query) use ($workplace) {
                $query->where('workplace_id', $workplace);
            })
            ->groupBy(DB::raw("MONTH(period)"))
            ->orderBy(DB::raw("MONTH(period)"), 'DESC')
            ->limit(1)
            ->sum('total');
    }


    public function getDocumentsByStatusChartPie()
    {
        $workplace = $this->workplace;

        $data = DB::table('wg_customer_employee_status_documents_consolidate')
            ->where('customer_id', $this->customerId)
            ->whereYear('period', $this->year)
            ->when($workplace, function($query) use ($workplace) {
                $query->where('workplace_id', $workplace);
            })
            ->groupBy(DB::raw("MONTH(period)"))
            ->orderBy(DB::raw("MONTH(period)"), 'DESC')
            ->select(
                DB::raw('sum(countActive) AS countActive'),
                DB::raw('sum(countExpired) AS countExpired'),
                DB::raw('sum(countAnnuled) AS countAnnuled'),
                DB::raw('coalesce(round(sum(countActive)  / sum(total) * 100, 2), 0) AS percentActive'),
                DB::raw('coalesce(round(sum(countExpired) / sum(total) * 100, 2), 0) AS percentExpired'),
                DB::raw('coalesce(round(sum(countAnnuled) / sum(total) * 100, 2), 0) AS percentInactive')
            )
            ->first();

        if (empty($data)) {
            return [];
        }

        $chart = [
            [
                "label" => "{$data->percentActive}% Vigente",
                "value" => $data->countActive,
                'color' => '#5CB85C'
            ],
            [
                "label" => "{$data->percentExpired}% Vencido",
                "value" => $data->countExpired,
                'color' => '#D43F3A'
            ],
            [
                "label" => "{$data->percentInactive}% Anulado",
                "value" => $data->countAnnuled,
                'color' => '#EEA236'
            ]
        ];

        return $this->chart->getChartPie(json_decode(json_encode($chart)));
    }


    public function getAuthorizedDocumentsChartPie()
    {
        $workplace = $this->workplace;

        $data = DB::table('wg_customer_employee_status_documents_consolidate')
            ->where('customer_id', $this->customerId)
            ->whereYear('period', $this->year)
            ->when($workplace, function($query) use ($workplace) {
                $query->where('workplace_id', $workplace);
            })
            ->groupBy(DB::raw("MONTH(period)"))
            ->orderBy(DB::raw("MONTH(period)"), 'DESC')
            ->select(
                DB::raw('sum(total) AS total'),
                DB::raw('sum(count_active_approved) AS isApprove'),
                DB::raw('sum(count_active_denied_expired) AS isDenied'),
                DB::raw("coalesce(round( (sum(count_active_approved) / sum(total) * 100 ) , 2), 0) as percentApprove"),
                DB::raw("coalesce(round( (sum(count_active_denied_expired) / sum(total) * 100 ) , 2), 0) as percentDenied")
            )
            ->first();

        if (empty($data)) {
            return [];
        }

        $chart = [
            [
                "label" => "{$data->percentApprove}% Vigentes Aprobados",
                "value" => $data->isApprove,
                "color" => '#5CB85C'
            ],
            [
                "label" => "{$data->percentDenied}% Vigentes Denegados + Vencidos",
                "value" => $data->isDenied,
                "color" => '#D43F3A'
            ]
        ];

        return $this->chart->getChartPie(json_decode(json_encode($chart)));
    }



    public function getCharLineDocumentsByWorkplaces() {
        $subquery = $this->getBaseQuery()
            ->select(
                DB::raw("'Documentos' AS label"),
                DB::raw("CASE WHEN wp.name IS NULL THEN 'Sin definir' ELSE wp.name END AS dynamicColumn"),
                DB::raw("sum(c.total) AS total")
            );

        list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartLine($query->get(), $config);
    }


    public function getChartBarStatusByDocuments()
    {
        $data = $this->getBaseQuery()
            ->select(
                DB::raw("CASE WHEN wp.name IS NULL THEN 'Sin definir' ELSE wp.name END AS label"),
                DB::raw("sum(c.countActive) AS countActive"),
                DB::raw("sum(c.countAnnuled) AS countAnnuled"),
                DB::raw("sum(c.countExpired) AS countExpired")
            )
            ->get();

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Vigentes', 'field' => 'countActive', 'color' => '#5CB85C' ],
                ['label' => 'Anulados', 'field' => 'countAnnuled', 'color' => '#EEA236'],
                ['label' => 'Vencidos', 'field' => 'countExpired', 'color' => '#D43F3A'],
            ],
        );

        return $this->chart->getChartBar($data, $config);
    }


    public function getChartBarAmountAuthorizedDocuments()
    {
        $data = $this->getBaseQuery()
            ->select(
                DB::raw("CASE WHEN wp.name IS NULL THEN 'Sin definir' ELSE wp.name END AS label"),
                DB::raw("sum(c.countApproved) AS countApproved"),
                DB::raw("sum(c.countDenied) AS countDenied")
            )
            ->get();

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Aprobados', 'field' => 'countApproved', 'color' => '#5CB85C' ],
                ['label' => 'Denegados', 'field' => 'countDenied', 'color' => '#D43F3A']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }


    /**
     * @return Builder
     */
    private function getBaseQuery() {
        $workplace = $this->workplace;

        return DB::table('wg_customer_employee_status_documents_consolidate as c')
            ->leftJoin('wg_customer_config_workplace as wp', function($join) {
                $join->on('wp.customer_id', 'c.customer_id');
                $join->on('wp.id', 'c.workplace_id');
            })
            ->leftJoin('wg_customer_employee_status_documents_consolidate as c2', function($join) {
                $join->on('c2.customer_id', 'c.customer_id');
                $join->on('c.period', '<', 'c2.period');
            })
            ->where('c.customer_id', $this->customerId)
            ->whereYear('c.period', $this->year)
            ->whereNull('c2.id')
            ->when($workplace, function($query) use ($workplace) {
                $query->where('c.workplace_id', $workplace);
            })
            ->groupBy('c.workplace_id')
            ->orderBy('wp.name');
    }


    private function getBasicQuery() {
        $workplace = $this->workplace;

        return DB::table('wg_customer_employee_status_documents_consolidate')
            ->where('customer_id', $this->customerId)
            ->whereYear('period', $this->year)
            ->when($workplace, function($query) use ($workplace) {
                $query->where('workplace_id', $workplace);
            });
    }


    private function getQueryGroupByMonths() {
        return $this->getBasicQuery()
            ->groupBy(DB::raw('month(period)'))
            ->select(
                DB::raw("month(period) AS month")
            );
    }


    public function getChartLineAmountDocumentsByPeriod() {
        $subquery = $this->getQueryGroupByMonths()
            ->addSelect( DB::raw("'Total' AS label"))
            ->addSelect(DB::raw('sum(total) as value'));

        $data = $this->applyTextToMonths($subquery)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartLine($data, $config);
    }


    public function getChartLineDocumentsComparativeByStateAndPeriod()
    {
        $queryTotal = $this->getQueryGroupByMonths()
            ->addSelect( DB::raw("'Total' AS label"))
            ->addSelect(DB::raw('sum(total) as value'));

        $queryActive = $this->getQueryGroupByMonths()
            ->addSelect( DB::raw("'Vigentes' AS label"))
            ->addSelect(DB::raw('sum(countActive) as value'));

        $queryExpired = $this->getQueryGroupByMonths()
            ->addSelect( DB::raw("'Vencidos' AS label"))
            ->addSelect(DB::raw('sum(countExpired) as value'));

        $queryAnnuled = $this->getQueryGroupByMonths()
            ->addSelect( DB::raw("'Anulados' AS label"))
            ->addSelect(DB::raw('sum(countAnnuled) as value'));


        $subquery = $queryTotal
            ->union($queryActive)->mergeBindings($queryActive)
            ->union($queryExpired)->mergeBindings($queryExpired)
            ->union($queryAnnuled)->mergeBindings($queryAnnuled);

        $data = $this->applyTextToMonths($subquery)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartLine($data, $config);
    }



    public function getChartLineAutorizedVsUnautorizedByPeriod() {

        $queryApproved = $this->getQueryGroupByMonths()
            ->addSelect( DB::raw("'Aprobados' AS label"))
            ->addSelect(DB::raw('sum(countApproved) as value'));

        $queryDenied = $this->getQueryGroupByMonths()
            ->addSelect( DB::raw("'Denegados' AS label"))
            ->addSelect(DB::raw('sum(countDenied) as value'));

        $subquery = $queryApproved
            ->union($queryDenied)->mergeBindings($queryDenied);


        $data = $this->applyTextToMonths($subquery)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartLine($data, $config);
    }

}
