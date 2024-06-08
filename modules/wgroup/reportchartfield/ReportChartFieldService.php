<?php

namespace Wgroup\ReportChartField;

use DB;
use Exception;
use Log;
use Str;

class ReportChartFieldService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $reportRepository;

    function __construct() {

    }

    public function init() {
        parent::init();
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $reportId = 0) {

        $model = new ReportChartField();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->reportRepository = new ReportChartFieldRepository($model);

        if ($perPage > 0) {
            $this->reportRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_report_chart_field.id',
            'wg_report_chart_field.report_id',
            'wg_report_chart_field.collection_data_field_id',
            'wg_report_chart_field.isActive',
        ];

        $i = 0;

        foreach ($sorting as $key => $value) {
            try {

                if (isset($value["column"]) === false) {
                    continue;
                }

                $col = $value["column"];
                $dir = $value["dir"];

                $colName = $columns[$col];

                if ($colName == "") {
                    continue;
                }

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

                if ($i == 0) {
                    $this->reportRepository->sortBy($colName, $dir);
                } else {
                    $this->reportRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->reportRepository->sortBy('wg_report_chart_field.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_report_chart_field.report_id', $reportId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_report_chart_field.collection_data_field_id', $search);
            $filters[] = array('wg_report_chart_field.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_report_chart_field.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_report_chart_field.isActive', '0');
        }


        $this->reportRepository->setColumns(['wg_report_chart_field.*']);

        return $this->reportRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new ReportChartField();
        $this->reportRepository = new ReportChartFieldRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_report_chart_field.report_id', $search);
            $filters[] = array('wg_report_chart_field.collection_data_field_id', $search);
            $filters[] = array('wg_report_chart_field.isActive', $search);
        }

        $this->reportRepository->setColumns(['wg_report_chart_field.*']);

        return $this->reportRepository->getFilteredsOptional($filters, true, "");
    }
}
