<?php

namespace Wgroup\CustomerInternalCertificateProgram;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;


class CustomerInternalCertificateProgramService {

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
    public function getAllBy($search, $perPage = 20, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0) {

        $model = new CustomerInternalCertificateProgram();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteRepository = new CustomerInternalCertificateProgramRepository($model);

        if ($perPage > 0) {
            $this->quoteRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_internal_certificate_program.id',
            'wg_customer_internal_certificate_program.code',
            'wg_customer_internal_certificate_program.name',
            'wg_customer_internal_certificate_program.description',
            'wg_customer_internal_certificate_program.isActive',
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
            $this->quoteRepository->sortBy('wg_customer_internal_certificate_program.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_internal_certificate_program.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_internal_certificate_program.isActive', $search);
            $filters[] = array('wg_customer_internal_certificate_program.name', $search);
            $filters[] = array('wg_customer_internal_certificate_program.code', $search);
            $filters[] = array('wg_customer_internal_certificate_program.description', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_internal_certificate_program.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_internal_certificate_program.isActive', '0');
        }


        $this->quoteRepository->setColumns(['wg_customer_internal_certificate_program.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId = 0) {

        $model = new CustomerInternalCertificateProgram();
        $this->quoteRepository = new CustomerInternalCertificateProgramRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_internal_certificate_program.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_internal_certificate_program.isActive', $search);
            $filters[] = array('wg_customer_internal_certificate_program.name', $search);
            $filters[] = array('wg_customer_internal_certificate_program.code', $search);
            $filters[] = array('wg_customer_internal_certificate_program.description', $search);
        }

        $this->quoteRepository->setColumns(['wg_customer_internal_certificate_program.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, true, "");
    }
}
