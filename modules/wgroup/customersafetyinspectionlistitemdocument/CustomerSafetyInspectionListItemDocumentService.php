<?php

namespace Wgroup\CustomerSafetyInspectionListItemDocument;

use DB;
use Exception;
use Log;
use Str;


class CustomerSafetyInspectionListItemDocumentService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDocumentRepository;

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
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0) {

        $model = new CustomerSafetyInspectionListItemDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CustomerSafetyInspectionListItemDocumentRepository($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_safety_inspection_list_item_document.id',
            'wg_customer_safety_inspection_list_item_document.type',
            'wg_customer_safety_inspection_list_item_document.description',
            'wg_customer_safety_inspection_list_item_document.version',
            'wg_customer_safety_inspection_list_item_document.status'
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
                    $this->customerDocumentRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDocumentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDocumentRepository->sortBy('wg_customer_safety_inspection_list_item_document.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_safety_inspection_list_item_document.customer_safety_inspection_list_item_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_safety_inspection_list_item_document.type', $search);
            $filters[] = array('wg_customer_safety_inspection_list_item_document.classification', $search);
            $filters[] = array('wg_customer_safety_inspection_list_item_document.description', $search);
            $filters[] = array('wg_customer_safety_inspection_list_item_document.version', $search);
            $filters[] = array('wg_customer_safety_inspection_list_item_document.agent_id', $search);
            $filters[] = array('wg_customer_safety_inspection_list_item_document.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_safety_inspection_list_item_document.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_safety_inspection_list_item_document.status', '0');
        }


        $this->customerDocumentRepository->setColumns(['wg_customer_safety_inspection_list_item_document.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, false, "");
    }


    public function getAllByPermission($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerSafetyInspectionListItemId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select DISTINCT d.id, d.customer_safety_inspection_list_item_id, p.item documentType, d.description, d.version, '' agent
            , d.created_at
from wg_customer_safety_inspection_list_item_document d
left join (select * from system_parameters where `group` = 'customer_safety_inspection_document_type') p on d.type = p.value
where (d.customer_safety_inspection_list_item_id = :customer_safety_inspection_list_item_id)";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (p.item like '%$search%' or d.description like '%$search%')";
            $query.=$where;
        }

        $order = " Order by d.created_at DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'customer_safety_inspection_list_item_id' => $customerSafetyInspectionListItemId
        ));

        return $results;

    }


    public function getCount($search = "", $customerSafetyInspectionListItemId) {

        $query = "select DISTINCT d.id, d.customer_safety_inspection_list_item_id, p.item documentType, d.description, d.version, '' agent
            , d.created_at
from wg_customer_safety_inspection_list_item_document d
left join (select * from system_parameters where `group` = 'customer_safety_inspection_document_type') p on d.type = p.value
where (d.customer_safety_inspection_list_item_id = :customer_safety_inspection_list_item_id)";

        if ($search != "") {
            $where = " AND (p.item like '%$search%' or d.description like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            'customer_safety_inspection_list_item_id' => $customerSafetyInspectionListItemId
        ));

        return $results;
    }
}
