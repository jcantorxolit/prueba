<?php

namespace Wgroup\Classes;

use Exception;
use Log;
use Str;

use Illuminate\Support\Facades\DB;
use Wgroup\Models\CustomerContribution;
use Wgroup\Models\CustomerContributionReporistory;


class ServiceCustomerContribution {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContributionRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0, $year = null) {

        $model = new CustomerContribution();

        // set current page
        \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContributionRepository = new CustomerContributionReporistory($model);

        if ($perPage > 0) {
            $this->customerContributionRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_arl_contribution.id',
            'wg_customer_arl_contribution.year',
            'wg_customer_arl_contribution.month',
            'wg_customer_arl_contribution.input',
            'wg_customer_arl_contribution.percent_reinvestment_arl',
            'reinvestmentARL',
            'wg_customer_arl_contribution.percent_reinvestment_wg',
            'reinvestmentWG',
            'sales',
            'balance'
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
                    $this->customerContributionRepository->sortBy($colName, $dir);
                } else {
                    $this->customerContributionRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerContributionRepository->sortBy('wg_customer_arl_contribution.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_arl_contribution.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_arl_contribution.year', $search);
            $filters[] = array('month.item', $search);
            $filters[] = array('wg_customer_arl_contribution.input', $search);
            $filters[] = array('wg_customer_arl_contribution.percent_reinvestment_arl', $search);
            $filters[] = array('wg_customer_arl_contribution.percent_reinvestment_wg', $search);
            $filters[] = array('sales', $search);
        }

        $this->customerContributionRepository->setColumns([
            'wg_customer_arl_contribution.id',
            'wg_customer_arl_contribution.year',
            'wg_customer_arl_contribution.month',
            'wg_customer_arl_contribution.input',
            'wg_customer_arl_contribution.percent_reinvestment_arl as ',
            'wg_customer_arl_contribution.percent_reinvestment_wg',
            DB::raw('input * percent_reinvestment_arl / 100 as reinvestmentARL'),
            DB::raw('(input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100 as reinvestmentWG'),
            DB::raw('COALESCE(sales.sales, 0) AS sales'),
            DB::raw('COALESCE(((input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100) - COALESCE(sales.sales, 0), 0) AS balance'),
            'wg_customer_arl_contribution.customer_id',
            'wg_customer_arl_contribution.created_at',
            'wg_customer_arl_contribution.updated_at'
        ]);

        return $this->customerContributionRepository->getFilteredsOptional($filters, false, "", $year, $search);
    }

    public function getCount($search = "", $customerId, $year) {

        $model = new CustomerContribution();
        $this->customerContributionRepository = new CustomerContributionReporistory($model);

        $filters = array();

        $filters[] = array('wg_customer_arl_contribution.customer_id', $customerId);
        $filters[] = array('wg_customer_arl_contribution.year', $year);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_arl_contribution.year', $search);
            $filters[] = array('wg_customer_arl_contribution.month', $search);
            $filters[] = array('wg_customer_arl_contribution.input', $search);
            $filters[] = array('COALESCE(sales.sales, 0) AS sales', $search);
            $filters[] = array('COALESCE(((input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100) - COALESCE(sales.sales, 0), 0) AS balance', $search);
        }

        $this->customerContributionRepository->setColumns(['wg_customer_arl_contribution.*']);

        return $this->customerContributionRepository->getFilteredsOptional($filters, true, "");
    }
}
