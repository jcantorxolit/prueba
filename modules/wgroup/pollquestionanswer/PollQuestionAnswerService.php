<?php

namespace Wgroup\PollQuestionAnswer;

use DB;
use Exception;
use Log;
use Str;

class PollQuestionAnswerService {

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

        $model = new PollQuestionAnswer();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->reportRepository = new PollQuestionAnswerRepository($model);

        if ($perPage > 0) {
            $this->reportRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_poll_question_answer.id',
            'wg_poll_question_answer.poll_question_id',
            'wg_poll_question_answer.value',
            'wg_poll_question_answer.isActive',
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
            $this->reportRepository->sortBy('wg_poll_question_answer.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_poll_question_answer.poll_question_id', $pollId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_poll_question_answer.value', $search);
            $filters[] = array('wg_poll_question_answer.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_poll_question_answer.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_poll_question_answer.isActive', '0');
        }


        $this->reportRepository->setColumns(['wg_poll_question_answer.*']);

        return $this->reportRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new PollQuestionAnswer();
        $this->reportRepository = new PollQuestionAnswerRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_poll_question_answer.value', $search);
            $filters[] = array('wg_poll_question_answer.isActive', $search);
        }

        $this->reportRepository->setColumns(['wg_poll_question_answer.*']);

        return $this->reportRepository->getFilteredsOptional($filters, true, "");
    }
}
