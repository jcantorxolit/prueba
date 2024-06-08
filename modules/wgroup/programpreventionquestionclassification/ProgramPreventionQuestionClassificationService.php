<?php

namespace Wgroup\ProgramPreventionQuestionClassification;

use DB;
use Exception;
use Log;
use Str;

class ProgramPreventionQuestionClassificationService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
    }

    public function init() {
        parent::init();
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param int $questionId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $questionId = 0) {

        $model = new ProgramPreventionQuestionClassification();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new ProgramPreventionQuestionClassificationRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_progam_prevention_question_classification.id',
            'wg_progam_prevention_question_classification.program_prevention_question_id',
            'wg_progam_prevention_question_classification.customer_size',
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
                    $this->customerContractorRepository->sortBy($colName, $dir);
                } else {
                    $this->customerContractorRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerContractorRepository->sortBy('wg_progam_prevention_question_classification.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_progam_prevention_question_classification.program_prevention_question_id', $questionId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_size.item', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_progam_prevention_question_classification.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $questionId) {

        $model = new ProgramPreventionQuestionClassification();
        $this->customerContractorRepository = new ProgramPreventionQuestionClassificationRepository($model);

        $filters = array();

        $filters[] = array('wg_progam_prevention_question_classification.program_prevention_question_id', $questionId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_size.item', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_progam_prevention_question_classification.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }
}
