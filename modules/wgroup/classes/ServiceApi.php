<?php

namespace Wgroup\Classes;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use RainLab\User\Facades\Auth;
use Str;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDto;
use Wgroup\Models\CustomerReporistory;

class ServiceApi {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerRepository;

    function __construct() {
        //$this->customerRepository = new CustomerReporistory();
    }

    public function init() {

    }

    /**
     *
     * @param type $perPage
     * @param type $currentPage
     * @param type $sort 1 = Alphabetical (ASC), 2 = Raitng (DESC), 3 = Date (DESC)
     * @return type
     */
    public function listCustomers($perPage = 10, $currentPage = 0, $sort = 1) {

        // Get model
        $model = new Customer();

        //get all
        // $data = $model->get();
        // Pagination
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $data = $model->paginate($perPage);

        return $data;
    }

    public function getCustomersTempSorteds($provider = '-1', $perPage = 10, $currentPage = 0, $sortBy = 'businessname', $sortDir = 'asc', $filters = array()) {

        $model = new CustomerImportedTemp();
        $addDefaultSort = true;

        if ($sortBy == "1") {
            $sortBy = "businessname";
            $addDefaultSort = false;
        }

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerRepository = new CustomerImporteTempRepository($model);

        if ($perPage > 0) {
            $this->customerRepository->paginate($perPage);
        }

        // Add sortings
        $this->customerRepository->sortBy($sortBy, $sortDir);

        if ($addDefaultSort) {
            $this->customerRepository->addSortField('businessname', 'ASC');
        }

        //$filters[] = array('provider_customer_id', array( "value" => $provider, "operator" => "and"));

        return $data = $this->customerRepository->getFiltereds($filters, "provider");
    }

    /**
     * Retrieves all Customers, optionally sort and paginate results
     * @param string $sortBy The field by which to sort results
     * @param string $sortDir The direction to sort results
     * @param string $perPage If specified, paginates results by the given number of items per page
     */
    public function getCustomersSorteds($category = '-1', $perPage = 10, $currentPage = 0, $sortBy = 'businessname', $sortDir = 'asc') {
        $data = array();
        $model = new Customer();
        $addDefaultSort = true;

        if ($sortBy == "1") {
            $sortBy = "businessname";
            $addDefaultSort = false;
        }

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerRepository = new CustomerReporistory($model);

        $this->customerRepository->setOnlyActives(true);

        if ($perPage > 0) {
            $this->customerRepository->paginate($perPage);
        }

        // Add sortings
        $this->customerRepository->sortBy($sortBy, $sortDir);

        if ($addDefaultSort) {
            $this->customerRepository->addSortField('businessname', 'ASC');
        }


            $data = $this->customerRepository->getAllCustomers();


        return $data;
    }

    public function getCustomersSortedsByUser($userid = 0, $perPage = 10, $currentPage = 0, $sortBy = 'businessname', $sortDir = 'asc', $includepub = false) {
        $data = array();
        $model = new Customer();
        $addDefaultSort = true;

        if ($sortBy == "1") {
            $sortBy = "businessname";
            $addDefaultSort = false;
        }

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerRepository = new CustomerReporistory($model);

        $this->customerRepository->setOnlyActives(0);

        if ($perPage > 0) {
            $this->customerRepository->paginate($perPage);
        }

        // Add sortings
        $this->customerRepository->sortBy($sortBy, $sortDir);

        if ($addDefaultSort) {
            $this->customerRepository->addSortField('businessname', 'ASC');
        }

        $filters = array();
        $filters[] = array('findcustomers_customers.id_user', $userid);
        // Filter by category
        $data = $this->customerRepository->getFiltereds($filters, "user");


        return $data;
    }

    public function getCustomersTempSortedsByUser($userid = 0, $perPage = 10, $currentPage = 0, $sortBy = 'businessname', $sortDir = 'asc') {

        $model = new CustomerTemp();
        $addDefaultSort = true;

        if ($sortBy == "1") {
            $sortBy = "businessname";
            $addDefaultSort = false;
        }

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerRepository = new CustomerTempReporistory($model);

        $this->customerRepository->setOnlyActives(0);

        if ($perPage > 0) {
            $this->customerRepository->paginate($perPage);
        }

        // Add sortings
        $this->customerRepository->sortBy($sortBy, $sortDir);

        if ($addDefaultSort) {
            $this->customerRepository->addSortField('businessname', 'ASC');
        }

        $filters = array();
        $filters[] = array('findcustomers_customers.id_user', $userid);
        // Filter by category
        $data = $this->customerRepository->getFiltereds($filters, "user");
        ////Log::info(json_encode($data));

        return $data;
    }

    public function getCustomersOrderedsByUser($userid = 0, $perPage = 10, $currentPage = 0, $sortBy = 'businessname', $sortDir = 'asc') {
        $data = array();
        $model = new Customer();
        $addDefaultSort = true;

        if ($sortBy == "1") {
            $sortBy = "businessname";
            $addDefaultSort = false;
        }

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerRepository = new CustomerReporistory($model);

        $this->customerRepository->setOnlyActives(0);

        if ($perPage > 0) {
            $this->customerRepository->paginate($perPage);
        }

        // Add sortings
        $this->customerRepository->sortBy($sortBy, $sortDir);

        if ($addDefaultSort) {
            $this->customerRepository->addSortField('businessname', 'ASC');
        }

        $filters = array();
        $filters[] = array('user_id', $userid);



        // Filter by category
        $data = $this->customerRepository->getFiltereds($filters, "userpursheds");

        return $data;
    }

    public function getCustomersSortedsByUserFavs($userid = 0, $perPage = 10, $currentPage = 0, $sortBy = 'businessname', $sortDir = 'asc') {
        $data = array();
        $model = new Customer();
        $addDefaultSort = true;

        if ($sortBy == "1") {
            $sortBy = "businessname";
            $addDefaultSort = false;
        }

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerRepository = new CustomerReporistory($model);

        $this->customerRepository->setOnlyActives(true);

        if ($perPage > 0) {
            $this->customerRepository->paginate($perPage);
        }

        // Add sortings
        $this->customerRepository->sortBy($sortBy, $sortDir);

        if ($addDefaultSort) {
            $this->customerRepository->addSortField('businessname', 'ASC');
        }

        $filters = array();
        $filters[] = array('findcustomers_customer_favorites.user_id', $userid);
        // Filter by category
        $data = $this->customerRepository->getFiltereds($filters, "userfavs");

        return $data;
    }

    public function getCustomersSortedsByUserCount($userid = 0, $includepub = false) {

        $model = new Customer();

        $this->customerRepository = new CustomerReporistory($model);

        $this->customerRepository->setOnlyActives(false);


        $filters = array();
        $filters[] = array('findcustomers_customers.id_user', $userid);
        // Filter by category
        $data = $this->customerRepository->getFiltereds($filters, "user", true);

        if ($includepub) {
            $data = $data + $this->getCustomersTempSortedsByUserCount($userid);
        }

        return $data;
    }

    public function getCustomersTempSortedsByUserCount($userid = 0) {

        $model = new CustomerTemp();

        $this->customerRepository = new CustomerTempReporistory($model);

        $this->customerRepository->setOnlyActives(false);


        $filters = array();
        $filters[] = array('findcustomers_customers.id_user', $userid);
        // Filter by category
        $data = $this->customerRepository->getFiltereds($filters, "user", true);

        return $data;
    }

    public function getCustomersOrderedsByUserCount($userid = 0) {

        $model = new Customer();

        $this->customerRepository = new CustomerReporistory($model);

        $this->customerRepository->setOnlyActives(false);


        $filters = array();
        $filters[] = array('user_id', $userid);
        // Filter by category
        $data = $this->customerRepository->getFiltereds($filters, "userpursheds", true);

        return $data;
    }

    public function getCustomersSortedsByUserFavsCount($userid = 0) {
        $data = array();
        $model = new Customer();

        $this->customerRepository = new CustomerReporistory($model);

        $this->customerRepository->setOnlyActives(true);


        $filters = array();
        $filters[] = array('findcustomers_customer_favorites.user_id', $userid);
        // Filter by category
        $data = $this->customerRepository->getFiltereds($filters, "userfavs", true);

        return $data;
    }

    public function getCustomersFilteredsOptional($filters = array(), $perPage = 10, $isCount = false, $currentPage = 0, $sortBy = 'findcustomers_customers_release.businessname', $sortDir = 'asc') {
        $data = array();
        $model = new Customer();
        $addDefaultSort = true;

        if ($sortBy == "1") {
            $sortBy = "findcustomers_customers_release.businessname";
            $addDefaultSort = false;
        } else if ($sortBy == "2") {
            $sortBy = "findcustomers_customers_release.rating";
        } else if ($sortBy == "3") {
            //$sortBy = "created_at";
            $sortBy = "findcustomers_customers_release.updated_at";
        }

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerRepository = new CustomerReporistory($model);
        $this->customerRepository->setOnlyActives(true);

        if ($perPage > 0) {
            $this->customerRepository->paginate($perPage);
        }

        if ($isCount) {
            // dont sort
            $data = $this->customerRepository->getFilteredsOptional($filters, $isCount);
        } else {

            // Add sortings
            $this->customerRepository->sortBy($sortBy, $sortDir);

            if ($addDefaultSort) {
                $this->customerRepository->addSortField('findcustomers_customers_release.businessname', 'ASC');
            }

            $data = $this->customerRepository->getFilteredsOptional($filters);
        }
        return $data;
    }



    public function saveOrUpdate(CustomerDto $dto, $push = false, $onlyUpdate = false) {

        $model = new Customer();

        if ($dto->id) {
            $model = Customer::find($dto->id);
        }

        //Is only update?
        if ($onlyUpdate && (!$model || !$model->id)) {
            throw new Exception("Customer to update not found.");
        }

        $data = [
            'businessname' => $dto->businessname,
            'id_user' => $dto->id_user,
            'rating' => $dto->rating,
            'short_description' => $dto->short_description,
        ];

        if ($dto->complementary) {

            ////Log::info(json_encode($dto->complementary));

            foreach ($dto->complementary as $key => $value) {
                $data[$key] = $value;
            }
        }

        ////Log::info("Data final:");
        ////Log::info(json_encode($data));

        $model->fill($data);

        if ($push) {
            $model->push();
        } else {
            $model->save();
        }
        return $model;
    }

    public function getAllCustomers($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $joinAgent = false, $agentId = 0) {

        $model = new Customer();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerRepository = new CustomerReporistory($model);

        if ($perPage > 0) {
            $this->customerRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_customers.documentType',
            'wg_customers.documentNumber',
            'wg_customers.businessName',
            'wg_customers.type',
            'wg_customers.status'
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
                    $this->customerRepository->sortBy($colName, $dir);
                } else {
                    $this->customerRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerRepository->sortBy('wg_customers.businessname', 'asc');
        }

        $filters = array();

        $filters[] = array('wg_customers.isDeleted', 0);

        if ($joinAgent) {
            $filters[] = array('agent.agent_id', $agentId);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers.type', $search);
            $filters[] = array('wg_customers.documentType', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.status', $search);
            $filters[] = array('tipoc.item', $search);
            $filters[] = array('tipod.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customers.active', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customers.active', '0');
        }


        $this->customerRepository->setColumns(['wg_customers.*']);

        return $this->customerRepository->getFilteredsOptional($filters, false, "", $joinAgent);
    }

    public function getAllCustomersCount($search = "", $joinAgent = false, $agentId = 0) {

        $model = new Customer();
        $this->customerRepository = new CustomerReporistory($model);

        $filters = array();

        if ($joinAgent) {
            $filters[] = array('agent.agent_id', $agentId);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers.type', $search);
            $filters[] = array('wg_customers.documentType', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.businessname', $search);
            $filters[] = array('wg_customers.status', $search);
        }

        $this->customerRepository->setColumns(['wg_customers.*']);

        return $this->customerRepository->getFilteredsOptional($filters, true, "", $joinAgent);
    }

    public function getAllCustomersAgent($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $joinAgent = false, $agentId = 0) {

        $model = new Customer();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerRepository = new CustomerReporistory($model);

        if ($perPage > 0) {
            $this->customerRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_customers.documentType',
            'wg_customers.documentNumber',
            'wg_customers.businessName',
            'wg_customers.type',
            'wg_customers.status'
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
                    $this->customerRepository->sortBy($colName, $dir);
                } else {
                    $this->customerRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerRepository->sortBy('wg_customers.businessname', 'asc');
        }

        $filters = array();

        $filters[] = array('wg_customers.isDeleted', 0);

        if ($joinAgent) {
            $filters[] = array('agent.agent_id', $agentId);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers.type', $search);
            $filters[] = array('wg_customers.documentType', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.status', $search);
            $filters[] = array('tipoc.item', $search);
            $filters[] = array('tipod.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customers.active', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customers.active', '0');
        }


        $this->customerRepository->setColumns(['wg_customers.*']);

        return $this->customerRepository->getFilteredsOptional($filters, false, "", $joinAgent);
    }

    public function getAllCustomersAgentCount($search = "", $joinAgent = false, $agentId = 0) {

        $model = new Customer();
        $this->customerRepository = new CustomerReporistory($model);

        $filters = array();

        if ($joinAgent) {
            $filters[] = array('agent.agent_id', $agentId);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers.type', $search);
            $filters[] = array('wg_customers.documentType', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.businessname', $search);
            $filters[] = array('wg_customers.status', $search);
        }

        $this->customerRepository->setColumns(['wg_customers.*']);

        return $this->customerRepository->getFilteredsOptional($filters, true, "", $joinAgent);
    }

    public function getCustomers($excludeId = 0)
    {
        return Customer::where("id", "<>", $excludeId)->where("classification", "Contratista")->select('id', 'businessName', 'documentNumber')
            ->whereNotIn('id', function ($q) use ($excludeId) {
                $q->select('contractor_id')
                    ->from('wg_customer_contractor')
                    ->where("customer_id", $excludeId);
            })->get();
    }

    public function getAgents($id = 0)
    {
        $query = "SELECT
	a.id,
	a.`name`,
	a.availability availabilityHours
FROM
	wg_agent a
INNER JOIN wg_customer_agent wca ON wca.agent_id = a.id
INNER JOIN wg_customers c ON c.id = wca.customer_id
WHERE
	wca.customer_id = :customer_id
ORDER BY
	a.`name`";

        $results = DB::select( $query, array(
            ':customer_id' => $id
        ));

        return $results;
    }

    public function getAllCustomerContractor($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerId=0) {

        //Log::info("customerId".$customerId);
        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM
(SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status  FROM `wg_customers` c
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where c.id = :customer_id_1 and isDeleted = 0

UNION ALL

SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status
FROM wg_customer_contractor cc
inner join `wg_customers` c on cc.contractor_id = c.id
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where cc.customer_id = :customer_id_2 and c.isDeleted = 0 and c.classification = 'Contratista') p";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " WHERE (p.documentType like '%$search%' or p.documentNumber like '%$search%' or p.businessName like '%$search%' or p.type like '%$search%' or p.classification like '%$search%' or p.status like '%$search%')";
            $query.=$where;
        }

        $order = " Order by p.businessName DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            ':customer_id_1' => $customerId,
            ':customer_id_2' => $customerId,
        ));

        return $results;

    }

    public function getAllCustomerContractorCount($search = "", $customerId) {

        $query = "SELECT * FROM
(SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status  FROM `wg_customers` c
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where c.id = :customer_id_1 and isDeleted = 0

UNION ALL

SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status
FROM wg_customer_contractor cc
inner join `wg_customers` c on cc.contractor_id = c.id
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where cc.customer_id = :customer_id_2 and c.isDeleted = 0 and c.classification = 'Contratista') p";

        if ($search != "") {
            $where = " WHERE (p.documentType like '%$search%' or p.documentNumber like '%$search%' or p.businessName like '%$search%' or p.type like '%$search%' or p.classification like '%$search%' or p.status like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            ':customer_id_1' => $customerId,
            ':customer_id_2' => $customerId,
        ));

        return $results;
    }


    public function getAllCustomerEconomicGroup($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerId=0) {

        //Log::info("customerId".$customerId);
        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM
(SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status  FROM `wg_customers` c
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where c.id = :customer_id_1 and isDeleted = 0

UNION ALL

SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status
FROM wg_customer_economic_group cc
inner join `wg_customers` c on cc.customer_id = c.id
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where cc.parent_id = :customer_id_2 and c.isDeleted = 0) p";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " WHERE (p.documentType like '%$search%' or p.documentNumber like '%$search%' or p.businessName like '%$search%' or p.type like '%$search%' or p.classification like '%$search%' or p.status like '%$search%')";
            $query.=$where;
        }

        $order = " Order by p.businessName DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            ':customer_id_1' => $customerId,
            ':customer_id_2' => $customerId,
        ));

        return $results;

    }

    public function getAllCustomerEconomicGroupCount($search = "", $customerId) {

        $query = "SELECT * FROM
(SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status  FROM `wg_customers` c
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where c.id = :customer_id_1 and isDeleted = 0

UNION ALL

SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status
FROM wg_customer_contractor cc
inner join `wg_customers` c on cc.contractor_id = c.id
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where cc.customer_id = :customer_id_2 and c.isDeleted = 0 and c.hasEconomicGroup = 1) p";

        if ($search != "") {
            $where = " WHERE (p.documentType like '%$search%' or p.documentNumber like '%$search%' or p.businessName like '%$search%' or p.type like '%$search%' or p.classification like '%$search%' or p.status like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            ':customer_id_1' => $customerId,
            ':customer_id_2' => $customerId,
        ));

        return $results;
    }

    public function getAllCustomerContractAndEconomicGroup($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerId=0) {

        //Log::info("customerId".$customerId);
        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT DISTINCT * FROM
(SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status, '' economicGroup  FROM `wg_customers` c
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where c.id = :customer_id_1 and isDeleted = 0

UNION ALL

SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status, '' economicGroup
FROM wg_customer_contractor cc
inner join `wg_customers` c on cc.contractor_id = c.id
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where cc.customer_id = :customer_id_2 and c.isDeleted = 0 and c.classification = 'Contratista'

UNION ALL

SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status, 'Pertenece Grupo Economico' economicGroup
FROM wg_customer_economic_group cc
inner join `wg_customers` c on cc.customer_id = c.id
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where cc.parent_id = :customer_id_3 and c.isDeleted = 0) p";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " WHERE (p.documentType like '%$search%' or p.documentNumber like '%$search%' or p.businessName like '%$search%' or p.type like '%$search%' or p.classification like '%$search%' or p.status like '%$search%')";
            $query.=$where;
        }

        $order = " Order by p.businessName DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            ':customer_id_1' => $customerId,
            ':customer_id_2' => $customerId,
            ':customer_id_3' => $customerId,
        ));

        return $results;

    }

    public function getAllCustomerContractAndEconomicGroupCount($search = "", $customerId) {

        $query = "SELECT DISTINCT * FROM
(SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status  FROM `wg_customers` c
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where c.id = :customer_id_1 and isDeleted = 0

UNION ALL

SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status
FROM wg_customer_contractor cc
inner join `wg_customers` c on cc.contractor_id = c.id
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where cc.customer_id = :customer_id_2 and c.isDeleted = 0 and c.classification = 'Contratista'

UNION ALL

SELECT c.id, dt.item documentType, c.documentNumber, c.businessName, tc.item type, c.classification, s.`item` status
FROM wg_customer_contractor cc
inner join `wg_customers` c on cc.contractor_id = c.id
left join (select * from system_parameters where `group` = 'tipodoc') dt on c.documentType = dt.value
left join (select * from system_parameters where `group` = 'tipocliente') tc on c.type = tc.value
left join (select * from system_parameters where `group` = 'estado') s on c.status = s.value
where cc.customer_id = :customer_id_3 and c.isDeleted = 0) p";

        if ($search != "") {
            $where = " WHERE (p.documentType like '%$search%' or p.documentNumber like '%$search%' or p.businessName like '%$search%' or p.type like '%$search%' or p.classification like '%$search%' or p.status like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            ':customer_id_1' => $customerId,
            ':customer_id_2' => $customerId,
            ':customer_id_3' => $customerId
        ));

        return $results;
    }

    private function user()
    {
        if (!Auth::check())
            return null;


        return Auth::getUser();
    }

    public function getCustomerIdByUserGroup()
    {
        $customer = null;

        foreach ($this->user()->groups as $rol) {
            if ($this->startsWith($rol->name, "GU_"))
            {
                $customer = Customer::whereGroupId($rol->id)->first();
                break;
            }
        }

        return $customer;
    }

    protected function startsWith($haystack, $needle) {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }


    public function getDashboardContributionBy($customerId, $year)
    {
        $sql = "Select
                    sum(case when wgc.month = 1 then wgc.input else 0 end) 'Enero',
                    sum(case when wgc.month = 2 then wgc.input else 0 end) 'Febrero',
                    sum(case when wgc.month = 3 then wgc.input else 0 end) 'Marzo',
                    sum(case when wgc.month = 4 then wgc.input else 0 end) 'Abril',
                    sum(case when wgc.month = 5 then wgc.input else 0 end) 'Mayo',
                    sum(case when wgc.month = 6 then wgc.input else 0 end) 'Junio',
                    sum(case when wgc.month = 7 then wgc.input else 0 end) 'Julio',
                    sum(case when wgc.month = 8 then wgc.input else 0 end) 'Agosto',
                    sum(case when wgc.month = 9 then wgc.input else 0 end) 'Septiembre',
                    sum(case when wgc.month = 10 then wgc.input else 0 end) 'Octubre',
                    sum(case when wgc.month = 11 then wgc.input else 0 end) 'Noviembre',
                    sum(case when wgc.month = 12 then wgc.input else 0 end) 'Diciembre'
                from wg_customer_arl_contribution wgc
                where `year` = :currentYear and customer_id = :customer_id";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'currentYear' => $year
        ));

        return $results;
    }

    public function getContributionYears($customerId)
    {
        $sql = "select distinct wgc.year id, wgc.year item, wgc.year value
                from wg_customer_arl_contribution wgc
                where customer_id = :customer_id
                order by 1 desc";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

}
