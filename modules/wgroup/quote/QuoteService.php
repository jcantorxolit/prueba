<?php

namespace Wgroup\Quote;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;


class QuoteService {

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

        $model = new Quote();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteRepository = new QuoteRepository($model);

        if ($perPage > 0) {
            $this->quoteRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_quote.id',
            'wg_quote.customer_id',
            'wg_quote.status',
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
            $this->quoteRepository->sortBy('wg_quote.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_quote.status', $search);
            $filters[] = array('wg_customers.businessName', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_quote.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_quote.status', '0');
        }


        $this->quoteRepository->setColumns(['wg_quote.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new Quote();
        $this->quoteRepository = new QuoteRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_quote.status', $search);
            $filters[] = array('wg_customers.businessName', $search);
        }

        $this->quoteRepository->setColumns(['wg_quote.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, true, "");
    }

    public function getResponsible($customerId)
    {
        $query = "select cn.id, cn.firstName, cn.lastName, cn.`name`, info.value email, p.item role from wg_customers c
inner join wg_contact cn on c.id = cn.customer_id
inner join ( select * from wg_info_detail WHERE type = 'email' and entityName like '%Contact%') info on cn.id = info.entityId
inner join ( select * from system_parameters where namespace='wgroup' and `group`='rolescontact') p on cn.role = p.value
where cn.customer_id = :customer_id";

        $results = DB::select( $query, array(
            'customer_id' => $customerId,
        ));
        ////Log::info(count($results). " count");
        ////Log::info(json_encode($results));
        return $results;
    }
}
