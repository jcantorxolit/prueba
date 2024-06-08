<?php

namespace Wgroup\CustomerPoll;

use DB;
use Exception;
use Log;
use Str;

class CustomerPollService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerTrackingRepository;

    function __construct() {
       // $this->customerRepository = new CustomerRepository();
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0, $isCustomerVisible = false) {

        $model = new CustomerPoll();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerTrackingRepository = new CustomerPollRepository($model);

        if ($perPage > 0) {
            $this->customerTrackingRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_poll.id',
            'wg_customer_poll.type',
            'wg_customer_poll.agent_id',
            'wg_customer_poll.observation',
            'wg_customer_poll.status',
            'wg_customer_poll.eventDateTime'
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
                    $this->customerTrackingRepository->sortBy($colName, $dir);
                } else {
                    $this->customerTrackingRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerTrackingRepository->sortBy('wg_customer_poll.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_poll.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_poll.name', $search);
            $filters[] = array('wg_poll.description', $search);
            $filters[] = array('wg_poll.endDate', $search);
            $filters[] = array('wg_customer_poll.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_poll.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_poll.status', '0');
        }


        $this->customerTrackingRepository->setColumns(['wg_customer_poll.*']);

        return $this->customerTrackingRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllByPollId($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $pollId = 0) {

        $model = new CustomerPoll();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerTrackingRepository = new CustomerPollRepository($model);

        if ($perPage > 0) {
            $this->customerTrackingRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_poll.id',
            'wg_customer_poll.customer_id',
            'wg_customer_poll.poll_id',
            'wg_customer_poll.status',
            'wg_customer_poll.eventDateTime'
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
                    $this->customerTrackingRepository->sortBy($colName, $dir);
                } else {
                    $this->customerTrackingRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerTrackingRepository->sortBy('wg_customer_poll.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_poll.poll_id', $pollId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_poll.name', $search);
            $filters[] = array('wg_poll.description', $search);
            $filters[] = array('wg_poll.endDate', $search);
            $filters[] = array('wg_customer_poll.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_poll.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_poll.status', '0');
        }


        $this->customerTrackingRepository->setColumns(['wg_customer_poll.*']);

        return $this->customerTrackingRepository->getFilteredOptional($filters, false, "");
    }

    public  function dashboard()
    {

    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerPoll();
        $this->customerTrackingRepository = new CustomerPollRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_poll.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_poll.name', $search);
            $filters[] = array('wg_poll.description', $search);
            $filters[] = array('wg_poll.endDate', $search);
            $filters[] = array('wg_customer_poll.status', $search);
        }

        $this->customerTrackingRepository->setColumns(['wg_customer_poll.*']);

        return $this->customerTrackingRepository->getFilteredsOptional($filters, true, "");
    }

    public function getCountPoll($search = "", $pollId) {

        $model = new CustomerPoll();
        $this->customerTrackingRepository = new CustomerPollRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_poll.poll_id', $pollId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_poll.name', $search);
            $filters[] = array('wg_poll.description', $search);
            $filters[] = array('wg_poll.endDate', $search);
            $filters[] = array('wg_customer_poll.status', $search);
        }

        $this->customerTrackingRepository->setColumns(['wg_customer_poll.*']);

        return $this->customerTrackingRepository->getFilteredOptional($filters, true, "");
    }

    public function getAllAgentBy($sorting = array(), $customerId) {

        $query = "	Select a.* from wg_agent a
                    inner join wg_customer_agent ca on a.id = ca.agent_id
                    where ca.customer_id = :customer_id
                    order by a.lastName";
        //Log::info($query);
        //Log::info($customerId);
        $results = DB::select( $query, array(
            'customer_id' => $customerId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllByGenerate($pollCustomer = null) {

        //Log::info("getAllByGenerate");

        if ($pollCustomer->poll != null && $pollCustomer->poll->collection != null) {

            $fields = "";

            $index = 0;

            //Log::info("before each");

            foreach ($pollCustomer->poll->collection->dataFields as $field) {
                if ($index  == 0) {
                    $fields .= "p." . $field->name . " AS " . $field->alias;
                } else {
                    $fields .= "," . "p." . $field->name . " AS " . $field->alias;
                }
                $index++;
            }

            //Log::info("after each ". $fields);

            $from = $pollCustomer->poll->collection->viewName;

            $query = "SELECT $fields FROM ($from) p ";

            $query.= $this->getWhere($pollCustomer->filters);

            //Log::info($query);

            $results = DB::select($query);

            return $results;
        }

        return array();
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
}
