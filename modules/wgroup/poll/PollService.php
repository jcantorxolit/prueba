<?php

namespace Wgroup\Poll;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;

class PollService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "") {

        $model = new Poll();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->reportRepository = new PollRepository($model);

        if ($perPage > 0) {
            $this->reportRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_poll.id',
            'wg_poll.name',
            'wg_poll.description',
            'wg_poll.isActive',
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
            $this->reportRepository->sortBy('wg_poll.id', 'desc');
        }

        $filters = array();

        //$filters[] = array('wg_poll.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_poll.name', $search);
            $filters[] = array('wg_poll.description', $search);
            $filters[] = array('wg_poll.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_poll.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_poll.isActive', '0');
        }


        $this->reportRepository->setColumns(['wg_poll.*']);

        return $this->reportRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new Poll();
        $this->reportRepository = new PollRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_poll.name', $search);
            $filters[] = array('wg_poll.description', $search);
            $filters[] = array('wg_poll.isActive', $search);
        }

        $this->reportRepository->setColumns(['wg_poll.*']);

        return $this->reportRepository->getFilteredsOptional($filters, true, "");
    }


    public function getAllByGenerate($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $reportId = 0, $report = null) {

        //Log::info("getAllByGenerate");

        $model = PollDTO::parse(Poll::find($reportId));

        if ($model != null) {

            $fields = "";

            $index = 0;

            //Log::info("before each");

            foreach ($model->fields as $field) {
                if ($index  == 0) {
                    $fields .= "p." . $field->name . " AS " . $field->alias;
                } else {
                    $fields .= "," . "p." . $field->name . " AS " . $field->alias;
                }
                $index++;
            }

            //Log::info("after each ". $fields);

            $from = $model->collection->viewName;

            $query = "SELECT $fields FROM ($from) p ";


            $query.= $this->getWhere($report->filters);


            //Log::info($query);

            $results = DB::select($query);
        }

        return $results;
    }

    public function getAllByDynamically($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $reportId = 0, $report = null) {

        //Log::info("getAllByDynamically");

        //$model = PollDTO::parse(Poll::find($report->id));

        //if ($model != null) {

            $fields = "";

            $index = 0;

            //Log::info("before each");

            foreach ($report->fields as $field) {
                if ($index  == 0) {
                    $fields .= "p." . $field->name . " AS " . $field->alias;
                } else {
                    $fields .= "," . "p." . $field->name . " AS " . $field->alias;
                }
                $index++;
            }

            //Log::info("after each ". $fields);

            $from = $report->collection->viewName;

            $query = "SELECT $fields FROM ($from) p ";


            $query.= $this->getWhere($report->filters);


            //Log::info($query);

            $results = DB::select($query);
        //}

        return $results;
    }

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter  == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value ."' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value ."%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value ."' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value ."' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value ."' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " = '" . $filter->value ."' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " LIKE '%" . $filter->value ."%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " <> '" . $filter->value ."' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " < '" . $filter->value ."' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " > '" . $filter->value ."' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        //Log::info($where);
        //Log::info(count($filters));

        return $where == "" ? "" : " WHERE ".$where;
    }

    public function getSummary($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $poll_id)
    {
        $startFrom = ($currentPage-1) * $perPage;

        $query = "select pa.businessName, questions, answers, ROUND(IFNULL(((answers / questions) * 100), 0), 2) avance, cp.status  from wg_customer_poll cp
                    inner join (select p.id, SUM(case when pq.id is null then 0 else 1 end) questions
                                            from wg_poll p
                                            left join wg_poll_question pq on p.id = pq.poll_id
                                            group by p.id) pq on cp.poll_id = pq.id
                    inner join (
                                        select wcp.id, wcp.poll_id, wcp.customer_id, c.businessName, SUM(case when wcpa.id is null then 0 else 1 end) answers
                                        from wg_customer_poll wcp
                                        inner join wg_customers c on c.id = wcp.customer_id
                                        left join wg_customer_poll_answer wcpa on wcp.id = wcpa.customer_poll_id
                                        group by wcp.id
                                        ) pa on cp.poll_id = pa.poll_id and cp.customer_id = pa.customer_id
                    where cp.poll_id = :poll_id ";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (pa.businessName like '%$search%')";
            $query.=$where;
        }

        $query.=$limit;

        $results = DB::select( $query, array(
            'poll_id' => $poll_id
        ));

        return $results;
    }

    public function getDashboardPie($poll_id)
    {

        $query = "select count(*) value, cp.status label, '#46BFBD' color, '#46BFBD' highlight
                    from wg_customer_poll cp
                    inner join (select p.id, SUM(case when pq.id is null then 0 else 1 end) questions
                                            from wg_poll p
                                            left join wg_poll_question pq on p.id = pq.poll_id
                                            group by p.id) pq on cp.poll_id = pq.id
                    inner join (
                            select wcp.id, wcp.poll_id, wcp.customer_id, c.businessName, SUM(case when wcpa.id is null then 0 else 1 end) answers
                                        from wg_customer_poll wcp
                                        inner join wg_customers c on c.id = wcp.customer_id
                                        left join wg_customer_poll_answer wcpa on wcp.id = wcpa.customer_poll_id
                                        group by wcp.id
                                        ) pa on cp.poll_id = pa.poll_id and cp.customer_id = pa.customer_id
                    where cp.poll_id = :poll_id
                    group by cp.status";

       //$query.=$limit;

        $results = DB::select( $query, array(
            'poll_id' => $poll_id
        ));

        return $results;
    }
}
