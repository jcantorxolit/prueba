<?php

namespace Wgroup\CustomerInternalCertificateProgramSpeciality;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;


class CustomerInternalCertificateProgramSpecialityService {

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

        $model = new CustomerInternalCertificateProgramSpeciality();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteRepository = new CustomerInternalCertificateProgramSpecialityRepository($model);

        if ($perPage > 0) {
            $this->quoteRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_internal_certificate_program_speciality.id',
            'wg_customer_internal_certificate_program_speciality.certificate_program_id',
            'wg_customer_internal_certificate_program_speciality.category',
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
            $this->quoteRepository->sortBy('wg_customer_internal_certificate_program_speciality.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_internal_certificate_program_speciality.category', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_internal_certificate_program_speciality.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_internal_certificate_program_speciality.status', '0');
        }


        $this->quoteRepository->setColumns(['wg_customer_internal_certificate_program_speciality.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerInternalCertificateProgramSpeciality();
        $this->quoteRepository = new CustomerInternalCertificateProgramSpecialityRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_internal_certificate_program_speciality.category', $search);
        }

        $this->quoteRepository->setColumns(['wg_customer_internal_certificate_program_speciality.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, true, "");
    }
}
