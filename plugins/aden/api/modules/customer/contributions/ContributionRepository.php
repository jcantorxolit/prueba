<?php

namespace AdeN\Api\Modules\Customer\Contributions;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Classes\SnappyPdfOptions;
use AdeN\Api\Helpers\ExportHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Log;


class ContributionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new ContributionModel());
        $this->service = new ContributionService();
    }


    public function getGeneralBalance($criteria)
    {
        $this->setColumns([
            'year' => 'o.year',
            'previousBalance' => DB::raw("IF(IFNULL(p.balance, 0) < 0, 0, IFNULL(p.balance, 0)) AS previousBalance"),
            'contributions' => "o.contributions",
            'commissions' => "o.commissions",
            'reinvesments' => DB::raw("o.reinvesments + IF(IFNULL(p.balance, 0) < 0, 0, IFNULL(p.balance, 0)) AS reinvesments"),
            'sales' => "o.sales",
            'balance' => "o.balance",
            'customerId' => 'o.customerId',
        ]);

        $this->parseCriteria($criteria);

        $queryMaster = $this->service->getQueryBalance();
        $queryMasterPrevious = $this->service->getQueryBalance();

        $query = $this->query(DB::table(DB::raw("({$queryMaster->toSql()}) as o")))
            ->mergeBindings($queryMaster)
            ->leftjoin(DB::raw("({$queryMasterPrevious->toSql()}) AS p"), function ($join) {
                $join->on('p.year', '=', DB::raw('o.year - 1'));
                $join->on('p.customerId', '=', 'o.customerId');
            })
            ->mergeBindings($queryMasterPrevious);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function getDetailBalance($criteria)
    {
        $this->setColumns([
            'period' => 'o.period',
            'type' => 'o.type',
            'activity' => 'o.activity',
            'concept' => 'o.concept',
            'classification' => 'o.classification',
            'total' => "o.total",
            'year' => 'o.year',
            'month' => 'o.month',
            'customerId' => 'customerId'
        ]);

        $this->parseCriteria($criteria);

        $queryBase = $this->service->getQuerySalesByMonth();

        $query = $this->query(
            DB::table(DB::raw("({$queryBase->toSql()}) as o"))
        )
            ->mergeBindings($queryBase);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function getSalesMonth(int $customerId, int $year)
    {
        return $this->service->getSalesMonth($customerId, $year);
    }

    public function getSalesTypesByMonth(int $customerId, int $year)
    {
        return $this->service->getSalesTypesByMonth($customerId, $year);
    }

    public function generateReportPdf($data)
    {
        $pdfData = $this->service->getReportPdfData($data);

        $filename = 'INFORME_DE_INTERMEDIACIÓN_' . Carbon::now()->timestamp . '.pdf';

        $header = \View::make('aden.pdf::html.customer_arl_report_header', $pdfData);
        $footer = \View::make('aden.pdf::html.customer_arl_report_footer', $pdfData);

        $pdfOptions = (new SnappyPdfOptions('A3'))
            ->setJavascriptDelay(2500)
            ->setEnableJavascript(true)
            ->setEnableSmartShrinking(true)
            ->setNoStopSlowScripts(true)
            ->setMarginBottom(10)
            ->setMarginTop(35)
            ->setMarginLeft(0)
            ->setMarginRight(0)
            //->setOption('header-center', '-[page]-')
            //->setOption('header-right', "$project - EP. $episode")
            // ->setOption('footer-left', 'Para uso exclusivo de CRYSTAL DUBS')
            ->setOption('header-spacing', 5)
            ->setOption('footer-spacing', 2)
            ->setOption('debug-javascript', true)
            ->setOption('header-html', $header)
            ->setOption('footer-html', $footer);
        return ExportHelper::pdf("aden.pdf::html.customer_arl_report", $pdfData, $filename, $pdfOptions);
    }
}
