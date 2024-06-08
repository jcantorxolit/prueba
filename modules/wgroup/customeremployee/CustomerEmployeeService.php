<?php

namespace Wgroup\CustomerEmployee;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class CustomerEmployeeService
{

    protected static $instance;
    protected $sessionKey = 'service_agent';
    protected $employeeRepository;

    function __construct()
    {
        //$this->employeeRepository = new CustomerReporistory();
    }

    public function init()
    {
        parent::init();
    }

    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId)
    {

        $model = new CustomerEmployee();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new CustomerEmployeeRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_customer_employee.id',
            'wg_customer_employee.customer_id',
            'wg_customer_employee.employee_id',
            'wg_customer_employee.type',
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
                    $this->employeeRepository->sortBy($colName, $dir);
                } else {
                    $this->employeeRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->employeeRepository->sortBy('wg_employee.lastName', 'asc');
        }

        $filters = array();

        $filters[] = array('wg_customer_employee.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee.documentType', $search);
            $filters[] = array('wg_employee.documentNumber', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_customer_employee.job', $search);
            $filters[] = array('wg_customer_employee.workPlace', $search);
            $filters[] = array('wg_gender.item', $search);
            $filters[] = array('wg_document_type.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_employee.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_employee.isActive', '0');
        }

        $this->employeeRepository->setColumns(['wg_employee.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerEmployee();
        $this->employeeRepository = new CustomerEmployeeRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_employee.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee.documentType', $search);
            $filters[] = array('wg_employee.documentNumber', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_customer_employee.job', $search);
            $filters[] = array('wg_customer_employee.workPlace', $search);
            $filters[] = array('wg_gender.item', $search);
            $filters[] = array('wg_document_type.item', $search);
        }

        $this->employeeRepository->setColumns(['wg_customer_employee.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sort = array(), $customerId = 0, $filter = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $columns = [
            "documentNumber",
            "firstName",
            "lastName",
            "workPlace",
            "job",
            "neighborhood",
            "countAttachment",
            "isActive",
            "isAuthorized",
        ];

        $colName = "p.id";
        $dir = " asc ";

        foreach ($sort as $key => $value) {
            try {

                if (isset($value["column"]) === false) {
                    continue;
                }

                if ($value["column"] == '0') {
                    continue;
                }

                $col = $value["column"];
                $dir = $value["dir"];

                $colName = $columns[$col - 1];

                if ($colName == "") {
                    continue;
                }

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

            } catch (Exception $exc) {

            }
        }

        $query = "SELECT * FROM
(
SELECT
	ce.id,
	ce.customer_id,
	e.documentNumber,
	e.firstName,
	e.lastName,
	w.`name` workPlace,
	jd.`name` job,
	ce.occupation,
	e.neighborhood,
	case when ce.isActive = 1 then 'Activo' else 'Inactivo' end AS isActive,
	case when ce.isAuthorized = 1 then 'Autorizado'
	      when ce.isAuthorized = 0 then 'No Autorizado' else 'N/A' end AS `isAuthorized`,
	IFNULL(ed.countAttachment,0) countAttachment
FROM `wg_customer_employee` ce
inner join wg_employee e on ce.employee_id = e.id
left join wg_customer_config_workplace w on ce.workPlace = w.id
left join wg_customer_config_job j on ce.job = j.id
left join wg_customer_config_job_data jd on j.job_id = jd.id
left join (select count(*) countAttachment, customer_employee_id from wg_customer_employee_document group by customer_employee_id) ed on ce.id = ed.customer_employee_id
) p
";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY $colName $dir ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.documentNumber like '%$search%' or p.firstName like '%$search%' or p.lastName like '%$search%' or p.neighborhood like '%$search%'
                            or p.workPlace like '%$search%' or p.job like '%$search%' or p.isActive like '%$search%' or p.isAuthorized like '%$search%')";
        }

        if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }

        $sql = $query.$where.$orderBy;
        $sql.=$limit;

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllCountBy($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
	ce.id,
	ce.customer_id,
	e.documentNumber,
	e.firstName,
	e.lastName,
	w.`name` workPlace,
	jd.`name` job,
	ce.occupation,
	e.neighborhood,
	case when ce.isActive = 1 then 'Activo' else 'Inactivo' end AS isActive,
	case when ce.isAuthorized = 1 then 'Autorizado'
	      when ce.isAuthorized = 0 then 'No Autorizado' else 'N/A' end AS `isAuthorized`,
	IFNULL(ed.countAttachment,0) countAttachment
FROM `wg_customer_employee` ce
inner join wg_employee e on ce.employee_id = e.id
left join wg_customer_config_workplace w on ce.workPlace = w.id
left join wg_customer_config_job j on ce.job = j.id
left join wg_customer_config_job_data jd on j.job_id = jd.id
left join (select count(*) countAttachment, customer_employee_id from wg_customer_employee_document group by customer_employee_id) ed on ce.id = ed.customer_employee_id
) p
";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.documentNumber like '%$search%' or p.firstName like '%$search%' or p.lastName like '%$search%' or p.neighborhood like '%$search%'
                            or p.workPlace like '%$search%' or p.job like '%$search%' or p.isActive like '%$search%' or p.isAuthorized like '%$search%')";
        }

        if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }

        $sql = $query.$where;

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return count($results);
    }


    public function getAllByActive($search, $perPage = 10, $currentPage = 0, $sort = array(), $customerId = 0, $filter = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $columns = [
            "documentNumber",
            "firstName",
            "lastName",
            "workPlace",
            "job",
            "neighborhood",
            "countAttachment",
            "isActive",
            "isAuthorized",
        ];

        $colName = "p.id";
        $dir = " asc ";

        foreach ($sort as $key => $value) {
            try {

                if (isset($value["column"]) === false) {
                    continue;
                }

                if ($value["column"] == '0') {
                    continue;
                }

                $col = $value["column"];
                $dir = $value["dir"];

                $colName = $columns[$col - 1];

                if ($colName == "") {
                    continue;
                }

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

            } catch (Exception $exc) {

            }
        }

        $query = "SELECT * FROM
(
SELECT
	ce.id,
	ce.customer_id,
	e.documentNumber,
	e.firstName,
	e.lastName,
	w.`name` workPlace,
	jd.`name` job,
	ce.occupation,
	e.neighborhood,
	case when ce.isActive = 1 then 'Activo' else 'Inactivo' end AS isActive,
	case when ce.isAuthorized = 1 then 'Autorizado'
	      when ce.isAuthorized = 0 then 'No Autorizado' else 'N/A' end AS `isAuthorized`,
	IFNULL(ed.countAttachment,0) countAttachment
FROM `wg_customer_employee` ce
inner join wg_employee e on ce.employee_id = e.id
left join wg_customer_config_workplace w on ce.workPlace = w.id
left join wg_customer_config_job j on ce.job = j.id
left join wg_customer_config_job_data jd on j.job_id = jd.id
left join (select count(*) countAttachment, customer_employee_id from wg_customer_employee_document group by customer_employee_id) ed on ce.id = ed.customer_employee_id
) p
";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY $colName $dir ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.documentNumber like '%$search%' or p.firstName like '%$search%' or p.lastName like '%$search%' or p.neighborhood like '%$search%'
                            or p.workPlace like '%$search%' or p.job like '%$search%' or p.isAuthorized like '%$search%')";
        }

        if ($where == "") {
            $where = " WHERE p.customer_id = :customer_id ";
        } else {
            $where .= " AND p.customer_id = :customer_id ";
        }

        $sql = $query.$where.$orderBy;
        $sql.=$limit;

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllCountByActive($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
	ce.id,
	ce.customer_id,
	e.documentNumber,
	e.firstName,
	e.lastName,
	w.`name` workPlace,
	jd.`name` job,
	ce.occupation,
	e.neighborhood,
	case when ce.isActive = 1 then 'Activo' else 'Inactivo' end AS isActive,
	case when ce.isAuthorized = 1 then 'Autorizado'
	      when ce.isAuthorized = 0 then 'No Autorizado' else 'N/A' end AS `isAuthorized`,
	IFNULL(ed.countAttachment,0) countAttachment
FROM `wg_customer_employee` ce
inner join wg_employee e on ce.employee_id = e.id
left join wg_customer_config_workplace w on ce.workPlace = w.id
left join wg_customer_config_job j on ce.job = j.id
left join wg_customer_config_job_data jd on j.job_id = jd.id
left join (select count(*) countAttachment, customer_employee_id from wg_customer_employee_document group by customer_employee_id) ed on ce.id = ed.customer_employee_id
) p
";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.firstName like '%$search%' or p.lastName like '%$search%')";
        }

        if ($where == "") {
            $where = " WHERE p.customer_id = :customer_id AND p.isActive = 'Activo' ";
        } else {
            $where .= " AND p.customer_id = :customer_id AND p.isActive = 'Activo' ";
        }

        $sql = $query.$where;

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return count($results);
    }


    public function getAllExportBy($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
	e.documentNumber,
	e.firstName,
	e.lastName,
	w.`name` workPlace,
	jd.`name` job,
	ce.occupation,
	e.neighborhood,
	case when ce.isActive = 1 then 'Activo' else 'Inactivo' end AS `isActive`,
	case when ce.isAuthorized = 1 then 'Autorizado'
	      when ce.isAuthorized = 0 then 'No Autorizado' else 'N/A' end AS `isAuthorized`,
	IFNULL(ed.countAttachment,0) `Anexos`

	,IFNULL(d.TRA, 'N/A') TRABAJO_ALTURA
	,IFNULL(d.CTA, 'N/A') COORDINADOR_TRABAJO_ALTURA
	,IFNULL(d.REL, 'N/A') RIESGO_ELECTRICO
	,IFNULL(d.MDF, 'N/A') MANEJO_DEFENSIVO
	,IFNULL(d.MDQ, 'N/A') MANEJO_DE_QUIMICOS
	,IFNULL(d.MDG, 'N/A') MANEJO_DE_GRUAS
	,IFNULL(d.ECF, 'N/A') ESPACIOS_CONFINADOS
	,IFNULL(d.MDA, 'N/A') MANIPULACION_DE_ALIMENTOS
	,IFNULL(d.MDP, 'N/A') MANEJO_DE_PLAGUICIDAS
	,IFNULL(d.MDM, 'N/A') MANEJO_DE_MONTACARGA
	,IFNULL(d.MDT, 'N/A') MANEJO_DE_TELEHANDER
	,IFNULL(d.MDS, 'N/A') SOLDADURA
	,IFNULL(d.CDV, 'N/A') CONDUCCIÓN_DE_VEHÍCULOS

FROM `wg_customer_employee` ce
inner join wg_employee e on ce.employee_id = e.id
left join wg_customer_config_workplace w on ce.workPlace = w.id
left join wg_customer_config_job j on ce.job = j.id
left join wg_customer_config_job_data jd on j.job_id = jd.id
left join (select count(*) countAttachment, customer_employee_id from wg_customer_employee_document group by customer_employee_id) ed on ce.id = ed.customer_employee_id
left join (SELECT count(*) quantity,
           customer_employee_id
	, MAX(case when requirement = 'TRA' AND isApprove = 1 THEN 'SI' when requirement = 'TRA' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `TRA`
	, MAX(case when requirement = 'CTA' AND isApprove = 1 THEN 'SI' when requirement = 'CTA' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `CTA`
	, MAX(case when requirement = 'REL' AND isApprove = 1 THEN 'SI' when requirement = 'REL' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `REL`
	, MAX(case when requirement = 'MDF' AND isApprove = 1 THEN 'SI' when requirement = 'MDF' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDF`
	, MAX(case when requirement = 'MDQ' AND isApprove = 1 THEN 'SI' when requirement = 'MDQ' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDQ`
	, MAX(case when requirement = 'MDG' AND isApprove = 1 THEN 'SI' when requirement = 'MDG' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDG`
	, MAX(case when requirement = 'ECF' AND isApprove = 1 THEN 'SI' when requirement = 'ECF' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `ECF`
	, MAX(case when requirement = 'MDA' AND isApprove = 1 THEN 'SI' when requirement = 'MDA' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDA`
	, MAX(case when requirement = 'MDP' AND isApprove = 1 THEN 'SI' when requirement = 'MDP' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDP`

	, MAX(case when requirement = 'MDM' AND isApprove = 1 THEN 'SI' when requirement = 'MDM' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDM`
	, MAX(case when requirement = 'MDT' AND isApprove = 1 THEN 'SI' when requirement = 'MDT' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDT`
	, MAX(case when requirement = 'MDS' AND isApprove = 1 THEN 'SI' when requirement = 'MDS' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDS`
	, MAX(case when requirement = 'CDV' AND isApprove = 1 THEN 'SI' when requirement = 'CDV' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `CDV`
   FROM wg_customer_employee_document
   GROUP BY
            customer_employee_id) d on ce.id = d.customer_employee_id
where ce.customer_id = :customer_id
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        $sql = $query.$where;

        $sql = "SELECT
        	        documentNumber AS `Numero Identificacion`,
	                firstName AS `Nombre`,
                    lastName AS `Apellidos`,
                    `workPlace` AS `Centro de Trabajo`,
                    `job` AS `Cargo`,
                    `occupation` AS `Ocupacion`,
                    neighborhood AS `Centro de Costos`,
                    isActive AS `Estado`,
                    isAuthorized AS `Autoizacion`,
                    `Anexos`

                    ,TRABAJO_ALTURA
                    ,COORDINADOR_TRABAJO_ALTURA
                    ,RIESGO_ELECTRICO
                    ,MANEJO_DEFENSIVO
                    ,MANEJO_DE_QUIMICOS
                    ,MANEJO_DE_GRUAS
                    ,ESPACIOS_CONFINADOS
					,MANIPULACION_DE_ALIMENTOS
                    ,MANEJO_DE_PLAGUICIDAS
                    ,MANEJO_DE_MONTACARGA
                    ,MANEJO_DE_TELEHANDER
                    ,SOLDADURA
                    ,CONDUCCIÓN_DE_VEHÍCULOS

				FROM (".$sql.") T";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }


    public function getAllExportByTemplate( $customerId = 0, $filter = null) {



        $sql = "SELECT
	*
FROM
	(
		SELECT
			ce.id,
			p.item AS documentType,
			e.documentNumber,
			e.expeditionPlace,
			e.expeditionDate,
			e.birthdate,
			e.gender,
			e.firstName,
			e.lastName,
			pc.item AS contractType,
			ep.item AS profession,
			ce.occupation,
			jd.`name` job,
			w.`name` workPlace,
			ce.salary,
			eps.item AS eps,
			afp.item AS afp,
			arl.item AS arl,
			c.`name` AS country,
			s.`name` AS state,
			t.`name` AS city,
			e.neighborhood,
			e.observation,
			ecel.value as cel,
	etel.value as tel,
	edir.value as address,
	eemail.value as mail,
			CASE
		WHEN ce.isActive = 1 THEN
			'Si'
		ELSE
			'No'
		END AS `isActive`
		FROM
			`wg_customer_employee` ce
		INNER JOIN wg_employee e ON ce.employee_id = e.id
		LEFT JOIN wg_customer_config_workplace w ON ce.workPlace = w.id
		LEFT JOIN wg_customer_config_job j ON ce.job = j.id
		LEFT JOIN wg_customer_config_job_data jd ON j.job_id = jd.id

		LEFT JOIN view_system_parameter p ON e.documentType = p.`value` and p.`group` = 'employee_document_type'

		LEFT JOIN view_system_parameter pc ON ce.contractType = pc.`value` and pc.`group` = 'employee_contract_type'

		LEFT JOIN view_system_parameter ep ON e.profession = ep.`value` and ep.`group` = 'employee_profession'

		LEFT JOIN view_system_parameter eps ON e.eps = eps.`value` and eps.`group` = 'eps'

		LEFT JOIN view_system_parameter afp ON e.afp = afp.`value` and afp.`group` = 'afp'

		LEFT JOIN view_system_parameter arl ON e.arl = arl.`value` and arl.`group` = 'arl'

		LEFT JOIN rainlab_user_countries c ON e.country_id = c.id
		LEFT JOIN rainlab_user_states s ON e.state_id = s.id
		LEFT JOIN wg_towns t ON e.city_id = t.id
		left join view_customer_employee_info ecel on  ecel.entityId = e.id and  ecel.type = 'cel'
		left join view_customer_employee_info edir on  edir.entityId = e.id and  edir.type = 'dir'
		left join view_customer_employee_info etel on  etel.entityId = e.id and  etel.type = 'tel'
		left join view_customer_employee_info eemail on  eemail.entityId = e.id and  eemail.type = 'email'

where ce.customer_id = :customer_id
) p ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        $sql .= $where;

        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllExportPDF($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
	e.documentNumber,
	e.firstName,
	e.lastName,
	w.`name` workPlace,
	jd.`name` job,
	ce.occupation,
	e.neighborhood,
	case when ce.isActive = 1 then 'Activo' else 'Inactivo' end AS `isActive`,
	case when ce.isAuthorized = 1 then 'Autorizado'
	      when ce.isAuthorized = 0 then 'No Autorizado' else 'N/A' end AS `isAuthorized`,
	IFNULL(ed.countAttachment,0) `Anexos`

	,IFNULL(d.TRA, 'N/A') TRABAJO_ALTURA
	,IFNULL(d.CTA, 'N/A') COORDINADOR_TRABAJO_ALTURA
	,IFNULL(d.REL, 'N/A') RIESGO_ELECTRICO
	,IFNULL(d.MDF, 'N/A') MANEJO_DEFENSIVO
	,IFNULL(d.MDQ, 'N/A') MANEJO_DE_QUIMICOS
	,IFNULL(d.MDG, 'N/A') MANEJO_DE_GRUAS
	,IFNULL(d.ECF, 'N/A') ESPACIOS_CONFINADOS
	,IFNULL(d.MDA, 'N/A') MANIPULACION_DE_ALIMENTOS
	,IFNULL(d.MDP, 'N/A') MANEJO_DE_PLAGUICIDAS

FROM `wg_customer_employee` ce
inner join wg_employee e on ce.employee_id = e.id
left join wg_customer_config_workplace w on ce.workPlace = w.id
left join wg_customer_config_job j on ce.job = j.id
left join wg_customer_config_job_data jd on j.job_id = jd.id
left join (select count(*) countAttachment, customer_employee_id from wg_customer_employee_document group by customer_employee_id) ed on ce.id = ed.customer_employee_id
left join (SELECT count(*) quantity,
           customer_employee_id
	, MAX(case when requirement = 'TRA' AND isApprove = 1 THEN 'SI' when requirement = 'TRA' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `TRA`
	, MAX(case when requirement = 'CTA' AND isApprove = 1 THEN 'SI' when requirement = 'CTA' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `CTA`
	, MAX(case when requirement = 'REL' AND isApprove = 1 THEN 'SI' when requirement = 'REL' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `REL`
	, MAX(case when requirement = 'MDF' AND isApprove = 1 THEN 'SI' when requirement = 'MDF' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDF`
	, MAX(case when requirement = 'MDQ' AND isApprove = 1 THEN 'SI' when requirement = 'MDQ' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDQ`
	, MAX(case when requirement = 'MDG' AND isApprove = 1 THEN 'SI' when requirement = 'MDG' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDG`
	, MAX(case when requirement = 'ECF' AND isApprove = 1 THEN 'SI' when requirement = 'ECF' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `ECF`
	, MAX(case when requirement = 'MDA' AND isApprove = 1 THEN 'SI' when requirement = 'MDA' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDA`
	, MAX(case when requirement = 'MDP' AND isApprove = 1 THEN 'SI' when requirement = 'MDP' AND isDenied = 1 THEN 'NO' ELSE 'N/A' END) `MDP`
   FROM wg_customer_employee_document
   GROUP BY
            customer_employee_id) d on ce.id = d.customer_employee_id
where ce.customer_id = :customer_id
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        $sql = $query.$where;


        $results = DB::select( $sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter  == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value ."' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value ."%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value ."' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value ."' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value ."' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " = '" . $filter->value ."' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " LIKE '%" . $filter->value ."%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " <> '" . $filter->value ."' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " < '" . $filter->value ."' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " > '" . $filter->value ."' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        return $where == "" ? "" : " WHERE ".$where;
    }

}
