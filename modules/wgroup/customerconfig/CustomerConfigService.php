<?php

namespace Wgroup\CustomerConfig;

use DB;
use Exception;
use Log;
use Str;


class CustomerConfigService {

    protected static $instance;
    protected $sessionKey = 'service_api';


    function __construct() {
       // $this->customerRepository = new CustomerRepository();
    }

    public function init() {
        parent::init();
    }

    public function getSummary($customerId = 0) {


        $query = "SELECT * FROM
(
	select
			customer_id
		, 'CENTROS DE TRABAJO' `name`
		, count(*) created
		, SUM(case when d.workplace_id is null then 0 else 1 end) configured
		, count(*) - SUM(case when d.workplace_id is null then 0 else 1 end) pending
	from wg_customer_config_workplace o
	left join (select count(*) qty, workplace_id from wg_customer_config_macro_process group by workplace_id) d on o.id = d.workplace_id
	WHERE `status` = 'Activo'
	GROUP BY customer_id

	union ALL

	select
			customer_id
		, 'MACROPROCESOS' `name`
		, count(*) created
		, SUM(case when d.macro_process_id is null then 0 else 1 end) configured
		, count(*) - SUM(case when d.macro_process_id is null then 0 else 1 end) pending
	from wg_customer_config_macro_process o
	left join (select count(*) qty, macro_process_id from wg_customer_config_process group by macro_process_id) d on o.id = d.macro_process_id
	WHERE `status` = 'Activo'
	GROUP BY customer_id

	union ALL

	select
		customer_id
		, 'PROCESOS' `name`
		, count(*) created
		, SUM(case when d.process_id is null then 0 else 1 end) configured
		, count(*) - SUM(case when d.process_id is null then 0 else 1 end) pending
	from wg_customer_config_process o
	left join (select count(*) qty, process_id from wg_customer_config_job group by process_id) d on o.id = d.process_id
	WHERE (`status` = 'Activo' OR `status` IS NULL)
	GROUP BY customer_id

	union ALL

	select
			customer_id
		, 'CARGOS' `name`
		, count(*) created
		, SUM(case when d.job_id is null then 0 else 1 end) configured
		, count(*) - SUM(case when d.job_id is null then 0 else 1 end) pending
	from wg_customer_config_job_data o
	left join (select count(*) qty, job_id from wg_customer_config_job_activity group by job_id) d on o.id = d.job_id
	WHERE `status` = 'Activo'
	GROUP BY customer_id

	union ALL

	select
		customer_id
		, 'ACTIVIDADES' `name`
		, count(*) created
		, SUM(case when d.activity_id is null then 0 else 1 end) configured
		, count(*) - SUM(case when d.activity_id is null then 0 else 1 end) pending
	from wg_customer_config_activity o	
	left join (select count(*) qty, activity_id from wg_customer_config_activity_process group by activity_id) d on o.id = d.activity_id
	WHERE `status` = 'Activo'
	GROUP BY customer_id
) p
where customer_id = :customer_id;";


        $results = DB::select( $query, array(
            'customer_id' => $customerId
        ));

        return $results;

    }


    public function getDashboardPieWorkPlace($customerId = 0) {


        $query = "SELECT * FROM
(
    select
        customer_id, count(*) `value`,  'Creados' label, '#5AD3D1' highlight, '#46BFBD' color
    from
    wg_customer_config_workplace o
    GROUP BY customer_id

    UNION ALL

    select
            customer_id, SUM(case when d.workplace_id is null then 0 else 1 end) `value`, 'Configurados' `name`, '#5AD3D1' highlight, '#46BFBD' color
    from wg_customer_config_workplace o
    left join (select count(*) qty, workplace_id from wg_customer_config_macro_process group by workplace_id) d on o.id = d.workplace_id
    GROUP BY customer_id

    UNION ALL

    select
            customer_id, count(*) - SUM(case when d.workplace_id is null then 0 else 1 end) `value`, 'Pendientes' `name`, '#5AD3D1' highlight, '#46BFBD' color
    from wg_customer_config_workplace o
    left join (select count(*) qty, workplace_id from wg_customer_config_macro_process group by workplace_id) d on o.id = d.workplace_id
    GROUP BY customer_id
) p
where customer_id = :customer_id;";


        $results = DB::select( $query, array(
            'customer_id' => $customerId
        ));

        return $results;

    }

}