<?php

namespace Wgroup\Classes;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerDocument;
use Wgroup\Models\CustomerDocumentReporistory;

class ServiceCustomerDocument
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDocumentRepository;

    function __construct()
    {
        // $this->customerRepository = new CustomerReporistory();
    }

    public function init()
    {

    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0)
    {

        $model = new CustomerDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDocumentRepository = new CustomerDocumentReporistory($model);

        if ($perPage > 0) {
            $this->customerDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_document.id',
            'wg_customer_document.type',
            'wg_customer_document.classification',
            'wg_customer_document.description',
            'wg_customer_document.version',
            'wg_customer_document.agent_id',
            'wg_customer_document.status'
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
            $this->customerDocumentRepository->sortBy('wg_customer_document.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_document.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_document.type', $search);
            $filters[] = array('wg_customer_document.classification', $search);
            $filters[] = array('wg_customer_document.description', $search);
            $filters[] = array('wg_customer_document.version', $search);
            $filters[] = array('wg_customer_document.agent_id', $search);
            $filters[] = array('wg_customer_document.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_document.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_document.status', '0');
        }


        $this->customerDocumentRepository->setColumns(['wg_customer_document.*']);

        return $this->customerDocumentRepository->getFilteredsOptional($filters, false, "");
    }


    public function getAllByPermission($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0, $agentId = 0, $program = "", $hideCanceled = true)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "select DISTINCT d.id, d.customer_id, case when p.item is null then dt.item else p.item end documentType, c.item classification, d.description, d.version, st.item status, '' agent
			, s.protectionType, case when ds.user_id is null then 0 else 1 end hasPermission
			, ds.user_id, ds.protectionType protectionType1
            , d.created_at
from wg_customer_document d
left join (select id, item, `value` from
system_parameters
where namespace = 'wgroup' and `group` = 'customer_document_type'
union all
Select id, `value` item, id `value` from
wg_customer_parameter
where namespace = 'wgroup' and `group` = 'customerDocumentType' and customer_id = $customerId) p on d.type = p.value
left join (select id, item, `value` from
system_parameters
where namespace = 'wgroup' and `group` = 'customer_document_type') dt ON dt.id = d.type
left join (select * from system_parameters where `group` = 'customer_document_classification') c on d.classification COLLATE utf8_general_ci = c.value
left join (select * from system_parameters where `group` = 'customer_document_status') st on d.status COLLATE utf8_general_ci = st.value
left join wg_customer_document_security s on s.customer_id = d.customer_id and s.documentType = d.type
left join (select doc.*, sec.user_id, sec.protectionType from wg_customer_document doc
						inner join wg_customer_document_security sec on sec.customer_id = doc.customer_id and sec.documentType = doc.type
						where sec.user_id = :agent_id and sec.isActive = 1) ds on d.id = ds.id
where (d.customer_id = :customer_id and (s.protectionType = 'public' or s.protectionType is null) or (ds.protectionType = 'private' and ds.user_id is not null))";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($program != "") {
            $where = " AND (d.program = '$program')";
        }

        if ($search != "") {
            $where .= " AND (p.item like '%$search%' or c.item like '%$search%' or d.description like '%$search%')";

        }

        if ($hideCanceled) {
            $where .= " AND (st.item <> 'Anulado')";
        }

        $query .= $where;

        $order = " Order by d.created_at DESC ";

        $query .= $order . $limit;

        $results = DB::select($query, array(
            'agent_id' => $agentId,
            'customer_id' => $customerId,
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
LEFT JOIN (SELECT * FROM wg_customer_document_security WHERE customer_id = :customer_id2 AND documentType = :document_type) ds ON users.id = ds.user_id";

        $results = DB::select($query, array(
            'document_type' => $documentType,
            'customer_id0' => $customerId,
            'customer_id1' => $customerId,
            'customer_id2' => $customerId,
        ));

        return $results;
    }

    public function getCount($search = "", $customerId, $agentId, $program = "", $hideCanceled = true)
    {

        $query = "select DISTINCT d.id, d.customer_id, p.item documentType, c.item classification, d.description, d.version, st.item status, '' agent
			, s.protectionType, case when ds.user_id is null then 0 else 1 end hasPermission
			, ds.user_id, ds.protectionType protectionType1

from wg_customer_document d
left join (select id, item, `value` from
system_parameters
where namespace = 'wgroup' and `group` = 'customer_document_type'
union all
Select id, `value` item, id `value` from
wg_customer_parameter
where namespace = 'wgroup' and `group` = 'customerDocumentType' and customer_id = $customerId) p on d.type = p.value
left join (select * from system_parameters where `group` = 'customer_document_classification') c on d.classification COLLATE utf8_general_ci = c.value
left join (select * from system_parameters where `group` = 'customer_document_status') st on d.status COLLATE utf8_general_ci = st.value
left join wg_customer_document_security s on s.customer_id = d.customer_id and s.documentType = d.type
left join (select doc.*, sec.user_id, sec.protectionType from wg_customer_document doc
						inner join wg_customer_document_security sec on sec.customer_id = doc.customer_id and sec.documentType = doc.type
						where sec.user_id = :agent_id and sec.isActive = 1) ds on d.id = ds.id
where (d.customer_id = :customer_id and (s.protectionType = 'public' or s.protectionType is null) or (ds.protectionType = 'private' and ds.user_id is not null))";

        $where = '';

        if ($program != "") {
            $where = " AND (d.program = '$program')";
        }

        if ($search != "") {
            $where .= " AND (p.item like '%$search%' or c.item like '%$search%' or d.description like '%$search%')";

        }

        if ($hideCanceled) {
            $where .= " AND (st.item <> 'Anulado')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            'agent_id' => $agentId,
            'customer_id' => $customerId,
        ));

        return $results;
    }
}
