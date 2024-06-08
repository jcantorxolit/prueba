<?php

namespace Wgroup\CustomerCertificateProgram;

use DB;
use Exception;
use Log;
use Str;

class CustomerCertificateProgramService {

    protected static $instance;
    protected $sessionKey = 'service_api';
protected $customerCertificateProgramRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
    }

    public function init() {

    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0) {

        $model = new CustomerCertificateProgram();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerCertificateProgramRepository = new CustomerCertificateProgramRepository($model);

        if ($perPage > 0) {
            $this->customerCertificateProgramRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_certificate_program.id',
            'wg_customer_certificate_program.type',
            'wg_customer_certificate_program.cause',
            'wg_customer_certificate_program.firstName',
            'wg_customer_certificate_program.lastName',
            'wg_customer_certificate_program.start',
            'wg_customer_certificate_program.end'
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
                    $this->customerCertificateProgramRepository->sortBy($colName, $dir);
                } else {
                    $this->customerCertificateProgramRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerCertificateProgramRepository->sortBy('wg_customer_certificate_program.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_certificate_program.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_certificate_program.id', $search);
            $filters[] = array('wg_customer_certificate_program.amount', $search);
            $filters[] = array('wg_certificate_program.name', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_certificate_program.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_certificate_program.status', '0');
        }

        $this->customerCertificateProgramRepository->setColumns(['wg_customer_certificate_program.*']);

        return $this->customerCertificateProgramRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerCertificateProgram();
        $this->customerCertificateProgramRepository = new CustomerCertificateProgramRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_certificate_program.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_certificate_program.id', $search);
            $filters[] = array('wg_customer_certificate_program.amount', $search);
            $filters[] = array('wg_certificate_program.name', $search);
        }

        $this->customerCertificateProgramRepository->setColumns(['wg_customer_certificate_program.*']);

        return $this->customerCertificateProgramRepository->getFilteredsOptional($filters, true, "");
    }
}
