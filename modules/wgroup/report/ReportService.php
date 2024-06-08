<?php

namespace Wgroup\Report;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;

class ReportService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $reportRepository;

    function __construct()
    {

    }

    public function init()
    {
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $isAgent = false, $isCustomer = false, $module = 'customer')
    {

        $model = new Report();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->reportRepository = new ReportRepository($model);

        if ($perPage > 0) {
            $this->reportRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_report.id',
            'wg_report.name',
            'wg_report.description',
            'wg_report.isActive',
            'wg_report.allowAgent',
            'wg_report.allowCustomer',
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
            $this->reportRepository->sortBy('wg_report.id', 'desc');
        }

        $filters = array();

        //$filters[] = array('wg_report.customer_id', $customerId);

        if ($isAgent) {
            $filters[] = array('wg_report.allowAgent', '1');
        } else if ($isCustomer) {
            $filters[] = array('wg_report.allowCustomer', '1');
        }

        $filters[] = array('wg_collection_data.module', $module);


        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_report.name', $search);
            $filters[] = array('wg_report.description', $search);
            $filters[] = array('wg_report.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_report.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_report.isActive', '0');
        }


        $this->reportRepository->setColumns(['wg_report.*']);

        return $this->reportRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $isAgent = false, $isCustomer = false, $module = 'customer')
    {

        $model = new Report();
        $this->reportRepository = new ReportRepository($model);

        $filters = array();

        if ($isAgent) {
            $filters[] = array('wg_report.allowAgent', '1');
        } else if ($isCustomer) {
            $filters[] = array('wg_report.allowCustomer', '1');
        }

        $filters[] = array('wg_collection_data.module', $module);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_report.name', $search);
            $filters[] = array('wg_report.description', $search);
            $filters[] = array('wg_report.isActive', $search);
        }

        $this->reportRepository->setColumns(['wg_report.*']);

        return $this->reportRepository->getFilteredsOptional($filters, true, "");
    }


    public function getAllByGenerate($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $reportId = 0, $report = null, $mandatoryFilters = null)
    {
        //ini_set('memory_limit','256M');

        //Log::info("getAllByGenerate" . $reportId);

        $model = ReportDTO::parse(Report::find($reportId));

        if ($model != null) {

            $fields = "";

            $index = 0;

            //Log::info("before each");

            foreach ($model->fields as $field) {
                //var_dump($field);
                if ($index == 0) {
                    $fields .= "p." . $field->name . " AS " . $field->alias;
                } else {
                    $fields .= "," . "p." . $field->name . " AS " . $field->alias;
                }
                $index++;
            }

            foreach ($model->collection->fields as $field) {
                if ($field->visible == 0) {
                    if ($index == 0) {
                        $fields .= "p." . $field->name . " AS " . $field->alias;
                    } else {
                        $fields .= "," . "p." . $field->name . " AS " . $field->alias;
                    }
                    $index++;
                }
            }

            //Log::info("after each " . $fields);

            $from = $model->collection->viewName;

            $query = "SELECT $fields FROM ($from) p ";

            $where = $this->getWhere($report->filters, $mandatoryFilters);

            $query .= $where;

            //Log::info($query);

            //var_dump($query);
            DB::connection()->disableQueryLog();

            $results = DB::select($query);
        }

        return $results;
    }

    public function getAllByDynamically($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $reportId = 0, $report = null)
    {

        //Log::info("getAllByDynamically");

        //echo "getAllByDynamically";
        //$model = ReportDTO::parse(Report::find($report->id));

        //if ($model != null) {

        $fields = "";

        $index = 0;

        //Log::info("before each");

        foreach ($report->fields as $field) {
            if ($index == 0) {
                $fields .= "p." . $field->name . " AS " . $field->alias;
            } else {
                $fields .= "," . "p." . $field->name . " AS " . $field->alias;
            }
            $index++;
        }

        //Log::info("after each " . $fields);

        $from = $report->collection->viewName;

        $query = "SELECT $fields FROM ($from) p ";


        $query .= $this->getWhere($report->filters);


        //Log::info($query);

        $results = DB::select($query);
        //}

        return $results;
    }

    public function getAllByDynamicallyExport($report = null, $view = "")
    {

        //Log::info("getAllByDynamically");

        //echo "getAllByDynamically";
        //$model = ReportDTO::parse(Report::find($report->id));

        //if ($model != null) {

        $fields = "";

        $index = 0;

        //Log::info("before each");

        foreach ($report->fields as $field) {
            if ($index == 0) {
                $fields .= "p." . $field->name . " AS " . $field->alias;
            } else {
                $fields .= "," . "p." . $field->name . " AS " . $field->alias;
            }
            $index++;
        }

        //Log::info("after each " . $fields);

        $from = $view;

        $query = "SELECT $fields FROM ($from) p ";


        $query .= $this->getWhere($report->filters);


        //Log::info($query);

        $results = DB::select($query);
        //}

        return $results;
    }

    private function getWhere($filters, $mandatoryFilers = null)
    {
        $where = $this->getWhereRaw($filters);
        $whereMandatory = $this->getWhereRaw($mandatoryFilers);

        if ($whereMandatory != '' && $where != '') {
            $where = $whereMandatory . ' AND ' . $where;
        } else if ($whereMandatory != '' && $where == '') {
            $where = $whereMandatory;
        }

        return $where == "" ? "" : " WHERE " . $where;
    }

    private function getWhereRaw($filters)
    {

        if ($filters == null) {
            return '';
        }

        $where = "";
        $lastFilter = null;

        foreach ($filters as $filter) {
            if ($lastFilter == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        return $where == "" ? "" : '(' . $where . ')';
    }
}
