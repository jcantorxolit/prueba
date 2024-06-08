<?php

namespace Wgroup\CertificateGradeAgent;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;


class CertificateGradeAgentService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $quoteRepository;

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

        $model = new CertificateGradeAgent();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteRepository = new CertificateGradeAgentRepository($model);

        if ($perPage > 0) {
            $this->quoteRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_certificate_grade_agent.id',
            'wg_certificate_grade_agent.certificate_grade_id',
            'wg_certificate_grade_agent.agent_id',
            'wg_certificate_grade_agent.estimatedHours',
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
                    $this->quoteRepository->sortBy($colName, $dir);
                } else {
                    $this->quoteRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->quoteRepository->sortBy('wg_certificate_grade_agent.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_certificate_grade_agent.agent_id', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_certificate_grade_agent.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_certificate_grade_agent.status', '0');
        }


        $this->quoteRepository->setColumns(['wg_certificate_grade_agent.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CertificateGradeAgent();
        $this->quoteRepository = new CertificateGradeAgentRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_certificate_grade_agent.agent_id', $search);
        }

        $this->quoteRepository->setColumns(['wg_certificate_grade_agent.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, true, "");
    }
}
