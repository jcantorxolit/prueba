<?php

namespace Wgroup\CertificateGradeCalendar;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;


class CertificateGradeCalendarService {

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

        $model = new CertificateGradeCalendar();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteRepository = new CertificateGradeCalendarRepository($model);

        if ($perPage > 0) {
            $this->quoteRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_certificate_grade_calendar.id',
            'wg_certificate_grade_calendar.certificate_grade_id',
            'wg_certificate_grade_calendar.startDate',
            'wg_certificate_grade_calendar.hourDuration',
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
            $this->quoteRepository->sortBy('wg_certificate_grade_calendar.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_certificate_grade_calendar.startDate', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_certificate_grade_calendar.isMandatory', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_certificate_grade_calendar.isMandatory', '0');
        }


        $this->quoteRepository->setColumns(['wg_certificate_grade_calendar.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CertificateGradeCalendar();
        $this->quoteRepository = new CertificateGradeCalendarRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_certificate_grade_calendar.startDate', $search);
        }

        $this->quoteRepository->setColumns(['wg_certificate_grade_calendar.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, true, "");
    }
}
