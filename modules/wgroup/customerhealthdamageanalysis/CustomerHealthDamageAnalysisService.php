<?php

namespace Wgroup\CustomerHealthDamageAnalysis;

use DB;
use Exception;
use Log;
use Str;

class CustomerHealthDamageAnalysisService
{

    protected static $instance;
    protected $sessionKey = 'service_api';

    function __construct()
    {
    }

    public function init()
    {
        parent::init();
    }

    public function getYearFilter($customerId) {

        $query = "SELECT * FROM
(
	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`created_at`) item,
		YEAR (o.`created_at`) `value`
	FROM
		wg_customer_health_damage_diagnostic_source o
	INNER JOIN wg_customer_employee e ON o.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`created_at`), e.customer_id

	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOfIssue`) item,
		YEAR (o.`dateOfIssue`) `value`
	FROM
		wg_customer_health_damage_restriction r
	INNER JOIN wg_customer_health_damage_restriction_detail o ON r.id = o.customer_health_damage_restriction_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOfIssue`), e.customer_id


	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOf`) item,
		YEAR (o.`dateOf`) `value`
	FROM
		wg_customer_health_damage_qs r
	INNER JOIN wg_customer_health_damage_qs_diagnostic o ON r.id = o.customer_health_damage_qualification_source_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOf`), e.customer_id

	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOf`) item,
		YEAR (o.`dateOf`) `value`
	FROM
		wg_customer_health_damage_qs r
	INNER JOIN wg_customer_health_damage_qs_first_opportunity o ON r.id = o.customer_health_damage_qualification_source_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOf`), e.customer_id

	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOf`) item,
		YEAR (o.`dateOf`) `value`
	FROM
		wg_customer_health_damage_qs r
	INNER JOIN wg_customer_health_damage_qs_national_board o ON r.id = o.customer_health_damage_qualification_source_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOf`), e.customer_id

	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOf`) item,
		YEAR (o.`dateOf`) `value`
	FROM
		wg_customer_health_damage_qs r
	INNER JOIN wg_customer_health_damage_qs_regional_board o ON r.id = o.customer_health_damage_qualification_source_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOf`), e.customer_id

	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOf`) item,
		YEAR (o.`dateOf`) `value`
	FROM
		wg_customer_health_damage_qs r
	INNER JOIN wg_customer_health_damage_qs_labor_justice o ON r.id = o.customer_health_damage_qualification_source_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOf`), e.customer_id


	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOf`) item,
		YEAR (o.`dateOf`) `value`
	FROM
		wg_customer_health_damage_ql r
	INNER JOIN wg_customer_health_damage_ql_first_instance o ON r.id = o.customer_health_damage_qualification_lost_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOf`), e.customer_id

	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOf`) item,
		YEAR (o.`dateOf`) `value`
	FROM
		wg_customer_health_damage_ql r
	INNER JOIN wg_customer_health_damage_ql_first_opportunity o ON r.id = o.customer_health_damage_qualification_lost_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOf`), e.customer_id

	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOf`) item,
		YEAR (o.`dateOf`) `value`
	FROM
		wg_customer_health_damage_ql r
	INNER JOIN wg_customer_health_damage_ql_second_instance o ON r.id = o.customer_health_damage_qualification_lost_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOf`), e.customer_id

	UNION ALL

	SELECT
		0 id,
		e.customer_id,
		YEAR (o.`dateOf`) item,
		YEAR (o.`dateOf`) `value`
	FROM
		wg_customer_health_damage_ql r
	INNER JOIN wg_customer_health_damage_ql_ordinary_labor_law o ON r.id = o.customer_health_damage_qualification_lost_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	GROUP BY
		YEAR (o.`dateOf`), e.customer_id
) o
WHERE o.customer_id = :customer_id
GROUP BY o.item, o.`value`, o.customer_id";

        $results = DB::select( $query, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getDashboardPieAccidentType($customerId, $year)
    {
        $sql = "select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
from
wg_customer_occupational_report_incident cora
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_accident_type' ) lty ON lty.value = cora.accident_type COLLATE utf8_general_ci
WHERE customer_id = :customer_id and YEAR(accident_date) = :year
group by customer_id, YEAR(accident_date), accident_type";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardPieDeathCause($customerId, $year)
    {
        $sql = "select count(*) value,  'SI' label, '#F7464A' highlight, '#F7464A' color, YEAR(accident_date) yearValue
from
wg_customer_occupational_report_incident cora
WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and accident_death_cause = 1
group by customer_id, YEAR(accident_date)

union ALL

select count(*) value,  'NO' label, '#FDB45C' highlight, '#FFC870' color, YEAR(accident_date) yearValue
from
wg_customer_occupational_report_incident cora
WHERE customer_id = :customer_id_2 and YEAR(accident_date) = :year_2 and accident_death_cause = 0
group by customer_id, YEAR(accident_date)";

        $results = DB::select( $sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getDashboardPieLocation($customerId, $year)
    {
        $sql = "select count(*) value,  lty.item label, '#F7464A' highlight, '#FF5A5E' color, YEAR(accident_date) yearValue
from
wg_customer_occupational_report_incident cora
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_location' ) lty ON lty.value = cora.accident_location COLLATE utf8_general_ci
WHERE customer_id = :customer_id and YEAR(accident_date) = :year
group by customer_id, YEAR(accident_date), accident_location";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardBarLink($customerId, $year)
    {
        $sql = "select  o.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_employment_relationship' ) lty ON lty.value = o.customer_type_employment_relationship COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by o.customer_type_employment_relationship";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardWorkTime($customerId, $year)
    {
        $sql = "SELECT
	dd.`code`, '#FF5A5E' color
	, SUM(case when MONTH(o.`created_at`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`created_at`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`created_at`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`created_at`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`created_at`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`created_at`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`created_at`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`created_at`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`created_at`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`created_at`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`created_at`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`created_at`) = 12 then 1 end) DIC
	FROM
		wg_customer_health_damage_diagnostic_source o
	INNER JOIN wg_customer_employee e ON o.customer_employee_id = e.id
	INNER JOIN wg_customer_health_damage_diagnostic_source_detail sd ON o.id = sd.customer_health_damage_diagnostic_source_id
	INNER JOIN wg_disability_diagnostic dd ON sd.diagnostic = dd.id
	WHERE e.customer_id = :customer_id AND YEAR(o.`created_at`) = :year
	GROUP BY
		YEAR(o.created_at), MONTH(o.created_at), sd.diagnostic, e.customer_id";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardWeekDay($customerId, $year)
    {
        $sql = "	SELECT
		cw.`name`, '#FF5A5E' color
		, SUM(case when MONTH(o.`created_at`) = 1 then 1 end) ENE
		, SUM(case when MONTH(o.`created_at`) = 2 then 1 end) FEB
		, SUM(case when MONTH(o.`created_at`) = 3 then 1 end) MAR
		, SUM(case when MONTH(o.`created_at`) = 4 then 1 end) ABR
		, SUM(case when MONTH(o.`created_at`) = 5 then 1 end) MAY
		, SUM(case when MONTH(o.`created_at`) = 6 then 1 end) JUN
		, SUM(case when MONTH(o.`created_at`) = 7 then 1 end) JUL
		, SUM(case when MONTH(o.`created_at`) = 8 then 1 end) AGO
		, SUM(case when MONTH(o.`created_at`) = 9 then 1 end) SEP
		, SUM(case when MONTH(o.`created_at`) = 10 then 1 end) OCT
		, SUM(case when MONTH(o.`created_at`) = 11 then 1 end) NOV
		, SUM(case when MONTH(o.`created_at`) = 12 then 1 end) DIC
	FROM
		wg_customer_health_damage_diagnostic_source o
	INNER JOIN wg_customer_employee e ON o.customer_employee_id = e.id
	INNER JOIN wg_customer_health_damage_diagnostic_source_detail sd ON o.id = sd.customer_health_damage_diagnostic_source_id
	INNER JOIN wg_disability_diagnostic dd ON sd.diagnostic = dd.id
	INNER JOIN wg_customer_config_workplace cw ON e.workPlace = cw.id
	WHERE e.customer_id = :customer_id AND YEAR(o.`created_at`) = :year
	GROUP BY
		YEAR(o.created_at), MONTH(o.created_at), e.workPlace, e.customer_id";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardPlace($customerId, $year)
    {
        $sql = "	SELECT
		p.`item` `code`, '#FF5A5E' color
		, SUM(case when MONTH(o.`dateOfIssue`) = 1 then 1 end) ENE
		, SUM(case when MONTH(o.`dateOfIssue`) = 2 then 1 end) FEB
		, SUM(case when MONTH(o.`dateOfIssue`) = 3 then 1 end) MAR
		, SUM(case when MONTH(o.`dateOfIssue`) = 4 then 1 end) ABR
		, SUM(case when MONTH(o.`dateOfIssue`) = 5 then 1 end) MAY
		, SUM(case when MONTH(o.`dateOfIssue`) = 6 then 1 end) JUN
		, SUM(case when MONTH(o.`dateOfIssue`) = 7 then 1 end) JUL
		, SUM(case when MONTH(o.`dateOfIssue`) = 8 then 1 end) AGO
		, SUM(case when MONTH(o.`dateOfIssue`) = 9 then 1 end) SEP
		, SUM(case when MONTH(o.`dateOfIssue`) = 10 then 1 end) OCT
		, SUM(case when MONTH(o.`dateOfIssue`) = 11 then 1 end) NOV
		, SUM(case when MONTH(o.`dateOfIssue`) = 12 then 1 end) DIC
	FROM
		wg_customer_health_damage_restriction r
	INNER JOIN wg_customer_health_damage_restriction_detail o ON r.id = o.customer_health_damage_restriction_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	INNER JOIN ( SELECT *
   FROM system_parameters
   WHERE `group` = 'work_health_damage_restriction' ) p ON o.restriction = p.value
	WHERE e.customer_id = :customer_id AND YEAR(o.`dateOfIssue`) = :year
	GROUP BY
		YEAR (o.`dateOfIssue`), e.customer_id";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardLesion($customerId, $year)
    {
        $sql = "	SELECT
		cw.`name`, '#FF5A5E' color
		, SUM(case when MONTH(o.`dateOfIssue`) = 1 then 1 end) ENE
		, SUM(case when MONTH(o.`dateOfIssue`) = 2 then 1 end) FEB
		, SUM(case when MONTH(o.`dateOfIssue`) = 3 then 1 end) MAR
		, SUM(case when MONTH(o.`dateOfIssue`) = 4 then 1 end) ABR
		, SUM(case when MONTH(o.`dateOfIssue`) = 5 then 1 end) MAY
		, SUM(case when MONTH(o.`dateOfIssue`) = 6 then 1 end) JUN
		, SUM(case when MONTH(o.`dateOfIssue`) = 7 then 1 end) JUL
		, SUM(case when MONTH(o.`dateOfIssue`) = 8 then 1 end) AGO
		, SUM(case when MONTH(o.`dateOfIssue`) = 9 then 1 end) SEP
		, SUM(case when MONTH(o.`dateOfIssue`) = 10 then 1 end) OCT
		, SUM(case when MONTH(o.`dateOfIssue`) = 11 then 1 end) NOV
		, SUM(case when MONTH(o.`dateOfIssue`) = 12 then 1 end) DIC
	FROM
		wg_customer_health_damage_restriction r
	INNER JOIN wg_customer_health_damage_restriction_detail o ON r.id = o.customer_health_damage_restriction_id
	INNER JOIN wg_customer_employee e ON r.customer_employee_id = e.id
	INNER JOIN wg_customer_config_workplace cw ON e.workPlace = cw.id
	INNER JOIN ( SELECT *
   FROM system_parameters
   WHERE `group` = 'work_health_damage_restriction' ) p ON o.restriction = p.value
	WHERE e.customer_id = :customer_id AND YEAR(o.`dateOfIssue`) = :year
	GROUP BY
		YEAR (o.`dateOfIssue`), e.customer_id, e.workPlace";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardBody($customerId, $year)
    {
        $sql = "select  p.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join wg_customer_occupational_report_incident_body p on o.id = p.customer_occupational_report_incident_id
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_body_part' ) lty ON lty.value = p.body_part_id COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by p.body_part_id";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardFactor($customerId, $year)
    {
        $sql = "select  p.`id`, lty.item `name`, lty.item abbreviation, '#FF5A5E' color
	, SUM(case when MONTH(o.`accident_date`) = 1 then 1 end) ENE
	, SUM(case when MONTH(o.`accident_date`) = 2 then 1 end) FEB
	, SUM(case when MONTH(o.`accident_date`) = 3 then 1 end) MAR
	, SUM(case when MONTH(o.`accident_date`) = 4 then 1 end) ABR
	, SUM(case when MONTH(o.`accident_date`) = 5 then 1 end) MAY
	, SUM(case when MONTH(o.`accident_date`) = 6 then 1 end) JUN
	, SUM(case when MONTH(o.`accident_date`) = 7 then 1 end) JUL
	, SUM(case when MONTH(o.`accident_date`) = 8 then 1 end) AGO
	, SUM(case when MONTH(o.`accident_date`) = 9 then 1 end) SEP
	, SUM(case when MONTH(o.`accident_date`) = 10 then 1 end) OCT
	, SUM(case when MONTH(o.`accident_date`) = 11 then 1 end) NOV
	, SUM(case when MONTH(o.`accident_date`) = 12 then 1 end) DIC
from
	wg_customer_occupational_report_incident o
inner join wg_customer_occupational_report_incident_factor p on o.id = p.customer_occupational_report_incident_id
inner join ( SELECT *
   FROM system_parameters
   WHERE `group` = 'wg_report_factor' ) lty ON lty.value = p.factor_id COLLATE utf8_general_ci
where customer_id = :customer_id and YEAR(o.`accident_date`) = :year
group by p.factor_id";

        $results = DB::select( $sql, array(
            'customer_id' => $customerId,
            'year' => $year
        ));

        return $results;
    }
}