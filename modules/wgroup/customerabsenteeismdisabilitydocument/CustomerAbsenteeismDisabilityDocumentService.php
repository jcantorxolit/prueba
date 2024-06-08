<?php

namespace Wgroup\CustomerAbsenteeismDisabilityDocument;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerAbsenteeismDisabilityDocument;
use Wgroup\Models\CustomerAbsenteeismDisabilityDocumentRepository;

class CustomerAbsenteeismDisabilityDocumentService {

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

        $model = new CustomerAbsenteeismDisabilityDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CustomerAbsenteeismDisabilityDocumentRepository($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_absenteeism_disability_document.id',
            'wg_customer_absenteeism_disability_document.type',
            'wg_customer_absenteeism_disability_document.classification',
            'wg_customer_absenteeism_disability_document.description',
            'wg_customer_absenteeism_disability_document.version',
            'wg_customer_absenteeism_disability_document.agent_id',
            'wg_customer_absenteeism_disability_document.status'
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
            $this->customerDocumentRepository->sortBy('wg_customer_absenteeism_disability_document.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_absenteeism_disability_document.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_absenteeism_disability_document.type', $search);
            $filters[] = array('wg_customer_absenteeism_disability_document.classification', $search);
            $filters[] = array('wg_customer_absenteeism_disability_document.description', $search);
            $filters[] = array('wg_customer_absenteeism_disability_document.version', $search);
            $filters[] = array('wg_customer_absenteeism_disability_document.agent_id', $search);
            $filters[] = array('wg_customer_absenteeism_disability_document.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_absenteeism_disability_document.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_absenteeism_disability_document.status', '0');
        }


        $this->customerDocumentRepository->setColumns(['wg_customer_absenteeism_disability_document.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, false, "");
    }


    public function getAllByPermission($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerDisabilityId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select DISTINCT d.id, d.customer_disability_id, p.item documentType, d.description, d.version, '' agent
            , d.created_at
from wg_customer_absenteeism_disability_document d
left join (select * from system_parameters where `group` = 'absenteeism_disability_document_type') p on d.type = p.value
where (d.customer_disability_id = :customer_disability_id)";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (p.item like '%$search%' or d.description like '%$search%')";
            $query.=$where;
        }

        $order = " Order by d.created_at DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'customer_disability_id' => $customerDisabilityId
        ));

        return $results;

    }

    public function getAllUsersPermissionByDocType($customerId = 0, $documentType = 0)
    {
        $query = "SELECT users.id, users.`name`, users.type
, CASE WHEN ds.isActive = 1 THEN 1 ELSE 0 END hasPermission
, CASE WHEN ds.protectionType = 'public' THEN 1 ELSE 0 END isPublic
, CASE WHEN ds.isPasswordProtected = 1 THEN 1 ELSE 0 END isProtected
, CASE WHEN ISNULL(ds.id) then 0 else ds.id end securityId
FROM (
SELECT id, name, 'Cliente' type from users where wg_type = 'customer' and company = :customer_id0
UNION ALL
(SELECT u.id, u.name, 'Asesor' type
FROM users u
INNER JOIN wg_customer_agent ca ON u.id = ca.agent_id
WHERE wg_type = 'agent' AND ca.customer_id = :customer_id1)) users
LEFT JOIN (SELECT * FROM wg_customer_absenteeism_disability_document_security WHERE customer_id = :customer_id2 AND documentType = :document_type) ds ON users.id = ds.user_id";

        $results = DB::select( $query, array(
            'document_type' => $documentType,
            'customer_id0' => $customerId,
            'customer_id1' => $customerId,
            'customer_id2' => $customerId,
        ));

        return $results;
    }

    public function getCount($search = "", $customerDisabilityId) {

        $query = "select DISTINCT d.id, d.customer_disability_id, p.item documentType, d.description, d.version, '' agent
            , d.created_at
from wg_customer_absenteeism_disability_document d
left join (select * from system_parameters where `group` = 'absenteeism_disability_document_type') p on d.type = p.value
where (d.customer_disability_id = :customer_disability_id)";

        if ($search != "") {
            $where = " AND (p.item like '%$search%' or d.description like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            'customer_disability_id' => $customerDisabilityId
        ));

        return $results;
    }
}
