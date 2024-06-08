<?php

namespace Wgroup\CustomerPollAnswer;

use DB;
use Exception;
use Log;
use Str;

class CustomerPollAnswerService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $pollId = 0) {

        $model = new CustomerPollAnswer();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->reportRepository = new CustomerPollAnswerRepository($model);

        if ($perPage > 0) {
            $this->reportRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_poll_answer.id',
            'wg_customer_poll_answer.customer_poll_id',
            'wg_customer_poll_answer.poll_question_id',
            'wg_customer_poll_answer.value',
            'wg_customer_poll_answer.isActive',
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
            $this->reportRepository->sortBy('wg_customer_poll_answer.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_poll_answer.customer_poll_id', $pollId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_poll_answer.value', $search);
            $filters[] = array('wg_customer_poll_answer.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_poll_answer.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_poll_answer.isActive', '0');
        }


        $this->reportRepository->setColumns(['wg_customer_poll_answer.*']);

        return $this->reportRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerPollAnswer();
        $this->reportRepository = new CustomerPollAnswerRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_poll_answer.value', $search);
            $filters[] = array('wg_customer_poll_answer.isActive', $search);
        }

        $this->reportRepository->setColumns(['wg_customer_poll_answer.*']);

        return $this->reportRepository->getFilteredsOptional($filters, true, "");
    }
}
