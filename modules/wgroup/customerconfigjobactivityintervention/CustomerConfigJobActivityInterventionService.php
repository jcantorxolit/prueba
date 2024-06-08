<?php

namespace Wgroup\CustomerConfigJobActivityIntervention;

use AdeN\Api\Helpers\SqlHelper;
use DB;
use Exception;
use Str;

class CustomerConfigJobActivityInterventionService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigWorkPlaceRepository;

    public function __construct()
    {
        // $this->customerRepository = new CustomerRepository();
    }

    public function init()
    {
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $jobId)
    {

        $model = new CustomerConfigJobActivityIntervention();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobActivityInterventionRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_job_activity_hazard_intervention.name',
            'wg_customer_config_job_activity_hazard_intervention.status',
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
                    $this->customerConfigWorkPlaceRepository->sortBy($colName, $dir);
                } else {
                    $this->customerConfigWorkPlaceRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerConfigWorkPlaceRepository->sortBy('wg_customer_config_job_activity_hazard_intervention.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_config_job_activity_hazard_intervention.job_activity_id', $jobId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_job_activity_hazard_intervention.name', $search);
            $filters[] = array('wg_customer_config_job_activity_hazard_intervention.status', $search);
            $filters[] = array('wg_customer_config_job.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_activity_hazard_intervention.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $jobId)
    {

        $model = new CustomerConfigJobActivityIntervention();
        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobActivityInterventionRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_config_job_activity_hazard_intervention.job_activity_id', $jobId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_job_activity_hazard_intervention.name', $search);
            $filters[] = array('wg_customer_config_job_activity_hazard_intervention.status', $search);
            $filters[] = array('wg_customer_config_job.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_activity_hazard_intervention.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllMatrix($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
	SELECT g.id, f.id activityHazardId, ea.id activityId, a.customer_id, a.`name` workplace, b.`name` macro, c.`name` process, jd.`name` job
		,ea.`name` activity, case when er.isRoutine = 1 then 'Si' else 'No' end routine
		, h.`name` classification , j.`name` type, i.`name` description, k.`name` effect
		, f.time_exposure exposure
		, f.control_method_source_text controlMethodSourceText
		, f.control_method_medium_text controlMethodMediumText
		, f.control_method_person_text controlMethodPersonText
		, f.control_method_administrative_text controlMethodAdministrativeText
		, m.value measureND , n.value measureNE, o.value measureNC
		, (m.value * n.value) levelP
		, case when (m.value * n.value) > 20 then 'Muy Alto'
					when (m.value * n.value) >= 10 and (m.value * n.value) <= 20 then 'Alto'
					when (m.value * n.value) >= 6 and (m.value * n.value) <= 8 then 'Medio'
					when (m.value * n.value) >= 1 and (m.value * n.value) <= 4 then 'Bajo' end levelIP
		, ((m.value * n.value) * o.value) levelR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'I'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'II'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'III'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'IV' end levelIR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'No Aceptable'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'No Aceptable o Aceptable con control especifico'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorable'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Aceptable' end riskValue
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable' end riskText
		, p.item interventionType, g.description interventionDescription
		, IFNULL(q.id,0) planId

		, exposed
		, contractors
		, visitors
		, f.status
		, f.reason
		, p1.item interventionTracking
		, g.observation interventionObservation

	FROM `wg_customer_config_workplace` a
	inner join wg_customer_config_macro_process b on a.id = b.workplace_id
	inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
	inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
	left join wg_customer_config_job_data jd on d.job_id = jd.id
	left join wg_customer_config_job_activity e on e.job_id = d.id
	left join wg_customer_config_activity ea on e.activity_id = ea.id
	left join wg_customer_config_activity_process er on ea.id = er.activity_id
			and er.workplace_id = a.id
			and er.macro_process_id = b.id
			and er.process_id = c.id
	left join wg_customer_config_job_activity_hazard f on f.job_activity_id = ea.id
	left join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
	left join wg_config_job_activity_hazard_classification h on f.classification = h.id
	left join wg_config_job_activity_hazard_description i on f.description = i.id
	left join wg_config_job_activity_hazard_type j on f.type = j.id
	left join wg_config_job_activity_hazard_effect k on f.health_effect = k.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'ND') m on f.measure_nd = m.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NE') n on f.measure_ne = n.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NC') o on f.measure_nc = o.id
	left join
		(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'config_type_measure') p on g.type = p.`value`

				left join
		(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'hazard_tracking') p1 on g.tracking = p1.`value`
    left join wg_customer_config_hazard_intervention_action_plan q on g.id = q.job_activity_hazard_id
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }

        $sql = $query . $where;
        $sql .= $limit;

        //var_dump($limit);

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
        ));

        return $results;
    }

    public function getAllMatrixCount($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT COUNT(*) `count` FROM
(
	SELECT g.id, e.id activityId, a.customer_id, a.`name` workplace, b.`name` macro, c.`name` process, jd.`name` job
		,ea.`name` activity, case when er.isRoutine = 1 then 'Si' else 'No' end routine
		, h.`name` classification , j.`name` type, i.`name` description, k.`name` effect
		, f.time_exposure exposure
		, f.control_method_source_text controlMethodSourceText
		, f.control_method_medium_text controlMethodMediumText
		, f.control_method_person_text controlMethodPersonText
		, f.control_method_administrative_text controlMethodAdministrativeText
		, m.value measureND , n.value measureNE, o.value measureNC
		, (m.value * n.value) levelP
		, case when (m.value * n.value) > 20 then 'Muy Alto'
					when (m.value * n.value) >= 10 and (m.value * n.value) <= 20 then 'Alto'
					when (m.value * n.value) >= 6 and (m.value * n.value) <= 8 then 'Medio'
					when (m.value * n.value) >= 1 and (m.value * n.value) <= 4 then 'Bajo' end levelIP
		, ((m.value * n.value) * o.value) levelR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'I'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'II'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'III'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'IV' end levelIR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'No Aceptable'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'No Aceptable o Aceptable con control especifico'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorable'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Aceptable' end riskValue
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable' end riskText
		, p.item interventionType, g.description interventionDescription
		, IFNULL(q.id,0) planId
	FROM `wg_customer_config_workplace` a
	inner join wg_customer_config_macro_process b on a.id = b.workplace_id
	inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
	inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
	left join wg_customer_config_job_data jd on d.job_id = jd.id
	left join wg_customer_config_job_activity e on e.job_id = d.id
	left join wg_customer_config_activity ea on e.activity_id = ea.id
	left join wg_customer_config_activity_process er on ea.id = er.activity_id
			and er.workplace_id = a.id
			and er.macro_process_id = b.id
			and er.process_id = c.id
	left join wg_customer_config_job_activity_hazard f on f.job_activity_id = ea.id
	left join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
	left join wg_config_job_activity_hazard_classification h on f.classification = h.id
	left join wg_config_job_activity_hazard_description i on f.description = i.id
	left join wg_config_job_activity_hazard_type j on f.type = j.id
	left join wg_config_job_activity_hazard_effect k on f.health_effect = k.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'ND') m on f.measure_nd = m.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NE') n on f.measure_ne = n.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NC') o on f.measure_nc = o.id
	left join
		(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'config_type_measure') p on g.type = p.`value`
    left join wg_customer_config_hazard_intervention_action_plan q on g.id = q.job_activity_hazard_id
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }

        $sql = $query . $where;

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
        ));

        return $results && count($results) > 0 ? $results[0]->count : 0;
    }

    public function getAllMatrixPrioritize($search, $perPage = 10, $currentPage = 0, $customerId = 0, $workplaceId = 0, $riskLevel = "", $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
	SELECT g.id, e.id activityId, a.id workplaceId, a.customer_id, a.`name` workplace, b.`name` macro, c.`name` process, jd.`name` job
		,ea.`name` activity, case when er.isRoutine = 1 then 'Si' else 'No' end routine
		, h.`name` classification , j.`name` type, i.`name` description, k.`name` effect
		, f.time_exposure exposure
		, f.control_method_source_text controlMethodSourceText
		, f.control_method_medium_text controlMethodMediumText
		, f.control_method_person_text controlMethodPersonText
		, f.control_method_administrative_text controlMethodAdministrativeText
		, m.value measureND , n.value measureNE, o.value measureNC
		, (m.value * n.value) levelP
		, case when (m.value * n.value) > 20 then 'Muy Alto'
					when (m.value * n.value) >= 10 and (m.value * n.value) <= 20 then 'Alto'
					when (m.value * n.value) >= 6 and (m.value * n.value) <= 8 then 'Medio'
					when (m.value * n.value) >= 1 and (m.value * n.value) <= 4 then 'Bajo' end levelIP
		, ((m.value * n.value) * o.value) levelR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'I'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'II'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'III'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'IV' end levelIR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'No Aceptable'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'No Aceptable o Aceptable con control especifico'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorable'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Aceptable' end riskValue
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable' end riskText
		, p.item interventionType, g.description interventionDescription
		, IFNULL(q.id,0) planId
		, q.description planDescription
		, CONCAT_WS(' ',ct.name,ct.firstName,ct.lastName) planResponsible
		, q.closeDateTime planStartDate
		, s.endDateTime planEndDate
		, s.observation planTaskObservation

		, exposed
		, contractors
		, visitors
		, f.status
		, f.reason
		, p1.item interventionTracking
		, g.observation interventionObservation
	FROM `wg_customer_config_workplace` a
	inner join wg_customer_config_macro_process b on a.id = b.workplace_id
	inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
	inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
	left join wg_customer_config_job_data jd on d.job_id = jd.id
	LEFT join wg_customer_config_job_activity e on e.job_id = d.id
	left join wg_customer_config_activity ea on e.activity_id = ea.id
	left join wg_customer_config_activity_process er on ea.id = er.activity_id
			and er.workplace_id = a.id
			and er.macro_process_id = b.id
			and er.process_id = c.id
	LEFT join wg_customer_config_job_activity_hazard f on f.job_activity_id = ea.id
	LEFT join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
	LEFT join wg_config_job_activity_hazard_classification h on f.classification = h.id
	LEFT join wg_config_job_activity_hazard_description i on f.description = i.id
	LEFT join wg_config_job_activity_hazard_type j on f.type = j.id
	LEFT join wg_config_job_activity_hazard_effect k on f.health_effect = k.id
	LEFT join (SELECT *  FROM `wg_config_general` WHERE `type` = 'ND') m on f.measure_nd = m.id
	LEFT join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NE') n on f.measure_ne = n.id
	LEFT join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NC') o on f.measure_nc = o.id
	LEFT join
		(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'config_type_measure') p on g.type = p.`value`
		LEFT join
					(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'hazard_tracking') p1 on g.tracking = p1.`value`
	LEFT join wg_customer_config_hazard_intervention_action_plan q on g.id = q.job_activity_hazard_id
	LEFT JOIN
		wg_customer_config_hazard_intervention_action_plan_resp r on q.id = r.job_activity_hazard_action_plan_id
	LEFT JOIN wg_contact ct ON ct.id = r.contact_id
	LEFT JOIN wg_customer_config_hazard_intervention_action_plan_resp_task s on s.job_activity_hazard_action_plan_resp_id = r.id
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }

        if ($workplaceId != 0) {
            if (empty($where)) {
                $where .= " WHERE p.workplaceId = $workplaceId";
            } else {
                $where .= " AND p.workplaceId = $workplaceId";
            }
        }

        if ($riskLevel != '') {
            if (empty($where)) {
                $where .= " WHERE p.levelIR = '$riskLevel'";
            } else {
                $where .= " AND p.levelIR = '$riskLevel'";
            }
        }

        $sql = $query . $where;
        $sql .= $limit;

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
        ));

        return $results;
    }

    public function getAllMatrixPrioritizeCount($search, $perPage = 10, $currentPage = 0, $customerId = 0, $workplaceId = 0, $riskLevel = "", $filter = null)
    {

        $query = "SELECT COUNT(*) `count` FROM
(
	SELECT g.id, e.id activityId, a.id workplaceId, a.customer_id, a.`name` workplace, b.`name` macro, c.`name` process, jd.`name` job
		,ea.`name` activity, case when er.isRoutine = 1 then 'Si' else 'No' end routine
		, h.`name` classification , j.`name` type, i.`name` description, k.`name` effect
		, f.time_exposure exposure
		, f.control_method_source_text controlMethodSourceText
		, f.control_method_medium_text controlMethodMediumText
		, f.control_method_person_text controlMethodPersonText
		, f.control_method_administrative_text controlMethodAdministrativeText
		, m.value measureND , n.value measureNE, o.value measureNC
		, (m.value * n.value) levelP
		, case when (m.value * n.value) > 20 then 'Muy Alto'
					when (m.value * n.value) >= 10 and (m.value * n.value) <= 20 then 'Alto'
					when (m.value * n.value) >= 6 and (m.value * n.value) <= 8 then 'Medio'
					when (m.value * n.value) >= 1 and (m.value * n.value) <= 4 then 'Bajo' end levelIP
		, ((m.value * n.value) * o.value) levelR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'I'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'II'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'III'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'IV' end levelIR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'No Aceptable'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'No Aceptable o Aceptable con control especifico'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorable'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Aceptable' end riskValue
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable' end riskText
		, p.item interventionType, g.description interventionDescription
		, IFNULL(q.id,0) planId
		, q.description planDescription
		, CONCAT_WS(' ',ct.name,ct.firstName,ct.lastName) planResponsible
		, q.closeDateTime planStartDate
		, s.endDateTime planEndDate
		, s.observation planTaskObservation
	FROM `wg_customer_config_workplace` a
	inner join wg_customer_config_macro_process b on a.id = b.workplace_id
	inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
	inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
	left join wg_customer_config_job_data jd on d.job_id = jd.id
	LEFT join wg_customer_config_job_activity e on e.job_id = d.id
	left join wg_customer_config_activity ea on e.activity_id = ea.id
	left join wg_customer_config_activity_process er on ea.id = er.activity_id
			and er.workplace_id = a.id
			and er.macro_process_id = b.id
			and er.process_id = c.id
	LEFT join wg_customer_config_job_activity_hazard f on f.job_activity_id = ea.id
	LEFT join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
	LEFT join wg_config_job_activity_hazard_classification h on f.classification = h.id
	LEFT join wg_config_job_activity_hazard_description i on f.description = i.id
	LEFT join wg_config_job_activity_hazard_type j on f.type = j.id
	LEFT join wg_config_job_activity_hazard_effect k on f.health_effect = k.id
	LEFT join (SELECT *  FROM `wg_config_general` WHERE `type` = 'ND') m on f.measure_nd = m.id
	LEFT join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NE') n on f.measure_ne = n.id
	LEFT join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NC') o on f.measure_nc = o.id
	LEFT join
		(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'config_type_measure') p on g.type = p.`value`
		LEFT join
					(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'hazard_tracking') p1 on g.tracking = p1.`value`
	LEFT join wg_customer_config_hazard_intervention_action_plan q on g.id = q.job_activity_hazard_id
	LEFT JOIN
		wg_customer_config_hazard_intervention_action_plan_resp r on q.id = r.job_activity_hazard_action_plan_id
	LEFT JOIN wg_contact ct ON ct.id = r.contact_id
	LEFT JOIN wg_customer_config_hazard_intervention_action_plan_resp_task s on s.job_activity_hazard_action_plan_resp_id = r.id
) p";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }

        if ($workplaceId != 0) {
            if (empty($where)) {
                $where .= " WHERE p.workplaceId = $workplaceId";
            } else {
                $where .= " AND p.workplaceId = $workplaceId";
            }
        }

        if ($riskLevel != '') {
            if (empty($where)) {
                $where .= " WHERE p.levelIR = '$riskLevel'";
            } else {
                $where .= " AND p.levelIR = '$riskLevel'";
            }
        }

        $sql = $query . $where;

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
        ));

        return $results && count($results) > 0 ? $results[0]->count : 0;
    }

    public function getAllMatrixHistorical($search, $perPage = 10, $currentPage = 0, $customerId = 0, $workplaceId = 0, $riskLevel = "", $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
	SELECT g1.id, e.id activityId, a.id workplaceId, a.customer_id, a.`name` workplace, b.`name` macro, c.`name` process, jd.`name` job
		,ea.`name` activity, case when er.isRoutine = 1 then 'Si' else 'No' end routine
		, h.`name` classification , j.`name` type, i.`name` description, k.`name` effect
		, f.time_exposure exposure
		, f.control_method_source_text controlMethodSourceText
		, f.control_method_medium_text controlMethodMediumText
		, f.control_method_person_text controlMethodPersonText
		, f.control_method_administrative_text controlMethodAdministrativeText
		, m.value measureND , n.value measureNE, o.value measureNC
		, (m.value * n.value) levelP
		, case when (m.value * n.value) > 20 then 'Muy Alto'
					when (m.value * n.value) >= 10 and (m.value * n.value) <= 20 then 'Alto'
					when (m.value * n.value) >= 6 and (m.value * n.value) <= 8 then 'Medio'
					when (m.value * n.value) >= 1 and (m.value * n.value) <= 4 then 'Bajo' end levelIP
		, ((m.value * n.value) * o.value) levelR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'I'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'II'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'III'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'IV' end levelIR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'No Aceptable'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'No Aceptable o Aceptable con control especifico'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorable'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Aceptable' end riskValue
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable' end riskText
		, g1.type historicalType, g1.description historicalDescription, g1.source historicalSource, u.`name` historicalCreator, CONVERT_TZ(g1.created_at,'+00:00','-5:00') historicalCreatedAt
		, exposed
		, contractors
		, visitors
		, f.status
		, f.reason
	FROM `wg_customer_config_workplace` a
	inner join wg_customer_config_macro_process b on a.id = b.workplace_id
	inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
	inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
	left join wg_customer_config_job_data jd on d.job_id = jd.id
	left join wg_customer_config_job_activity e on e.job_id = d.id
	left join wg_customer_config_activity ea on e.activity_id = ea.id
	left join wg_customer_config_activity_process er on ea.id = er.activity_id
			and er.workplace_id = a.id
			and er.macro_process_id = b.id
			and er.process_id = c.id
	left join wg_customer_config_job_activity_hazard f on f.job_activity_id = ea.id
	left join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
	inner join wg_customer_config_job_activity_hazard_tracking g1 on g1.job_activity_hazard_id = f.id
	left JOIN users u on u.id = g1.createdBy
	left join wg_config_job_activity_hazard_classification h on f.classification = h.id
	left join wg_config_job_activity_hazard_description i on f.description = i.id
	left join wg_config_job_activity_hazard_type j on f.type = j.id
	left join wg_config_job_activity_hazard_effect k on f.health_effect = k.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'ND') m on f.measure_nd = m.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NE') n on f.measure_ne = n.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NC') o on f.measure_nc = o.id
	left join
		(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'config_type_measure') p on g.type = p.`value`

				left join
		(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'hazard_tracking') p1 on g.tracking = p1.`value`
    left join wg_customer_config_hazard_intervention_action_plan q on g.id = q.job_activity_hazard_id
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }

        if ($workplaceId != 0) {
            if (empty($where)) {
                $where .= " WHERE p.workplaceId = $workplaceId";
            } else {
                $where .= " AND p.workplaceId = $workplaceId";
            }
        }

        if ($riskLevel != '') {
            if (empty($where)) {
                $where .= " WHERE p.levelIR = '$riskLevel'";
            } else {
                $where .= " AND p.levelIR = '$riskLevel'";
            }
        }

        $sql = $query . $where;
        $sql .= $limit;

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
        ));

        return $results;
    }

    public function getAllMatrixHistoricalCount($customerId = 0, $workplaceId = 0, $riskLevel = "", $filter = null)
    {

        $query = "SELECT COUNT(*) total FROM
(
	SELECT g1.id, e.id activityId, a.id workplaceId, a.customer_id, a.`name` workplace, b.`name` macro, c.`name` process, jd.`name` job
		,ea.`name` activity, case when er.isRoutine = 1 then 'Si' else 'No' end routine
		, h.`name` classification , j.`name` type, i.`name` description, k.`name` effect
		, f.time_exposure exposure
		, f.control_method_source_text controlMethodSourceText
		, f.control_method_medium_text controlMethodMediumText
		, f.control_method_person_text controlMethodPersonText
		, f.control_method_administrative_text controlMethodAdministrativeText
		, m.value measureND , n.value measureNE, o.value measureNC
		, (m.value * n.value) levelP
		, case when (m.value * n.value) > 20 then 'Muy Alto'
					when (m.value * n.value) >= 10 and (m.value * n.value) <= 20 then 'Alto'
					when (m.value * n.value) >= 6 and (m.value * n.value) <= 8 then 'Medio'
					when (m.value * n.value) >= 1 and (m.value * n.value) <= 4 then 'Bajo' end levelIP
		, ((m.value * n.value) * o.value) levelR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'I'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'II'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'III'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'IV' end levelIR
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'No Aceptable'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'No Aceptable o Aceptable con control especifico'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorable'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Aceptable' end riskValue
		, case when ((m.value * n.value) * o.value) >= 600 and ((m.value * n.value) * o.value) <= 4000 then 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
						when ((m.value * n.value) * o.value) >= 150 and ((m.value * n.value) * o.value) <= 500 then 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
						when ((m.value * n.value) * o.value) >= 40 and ((m.value * n.value) * o.value) <= 120 then 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
						when ((m.value * n.value) * o.value) >= 20 and ((m.value * n.value) * o.value) <= 39 then 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable' end riskText
		, g1.type historicalType, g1.description historicalDescription, g1.source historicalSource, u.`name` historicalCreator, g1.created_at historicalCreatedAt
		, exposed
		, contractors
		, visitors
		, f.status
		, f.reason
	FROM `wg_customer_config_workplace` a
	inner join wg_customer_config_macro_process b on a.id = b.workplace_id
	inner join wg_customer_config_process c on b.workplace_id = c.workplace_id and c.macro_process_id = b.id
	inner join wg_customer_config_job d on c.workplace_id = d.workplace_id and c.macro_process_id = d.macro_process_id and c.id = d.process_id
	left join wg_customer_config_job_data jd on d.job_id = jd.id
	left join wg_customer_config_job_activity e on e.job_id = d.id
	left join wg_customer_config_activity ea on e.activity_id = ea.id
	left join wg_customer_config_activity_process er on ea.id = er.activity_id
			and er.workplace_id = a.id
			and er.macro_process_id = b.id
			and er.process_id = c.id
	left join wg_customer_config_job_activity_hazard f on f.job_activity_id = ea.id
	left join wg_customer_config_job_activity_hazard_intervention g on g.job_activity_hazard_id = f.id
	inner join wg_customer_config_job_activity_hazard_tracking g1 on g1.job_activity_hazard_id = f.id
	left JOIN users u on u.id = g1.createdBy
	left join wg_config_job_activity_hazard_classification h on f.classification = h.id
	left join wg_config_job_activity_hazard_description i on f.description = i.id
	left join wg_config_job_activity_hazard_type j on f.type = j.id
	left join wg_config_job_activity_hazard_effect k on f.health_effect = k.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'ND') m on f.measure_nd = m.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NE') n on f.measure_ne = n.id
	left join (SELECT *  FROM `wg_config_general` WHERE `type` = 'NC') o on f.measure_nc = o.id
	left join
		(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'config_type_measure') p on g.type = p.`value`

				left join
		(SELECT `id`,`item`, `value` COLLATE utf8_general_ci AS `value` FROM `system_parameters`
			WHERE `namespace` = 'wgroup' AND `group` = 'hazard_tracking') p1 on g.tracking = p1.`value`
    left join wg_customer_config_hazard_intervention_action_plan q on g.id = q.job_activity_hazard_id
) p";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        }

        if ($where == "") {
            $where = ' WHERE p.customer_id = :customer_id';
        } else {
            $where .= ' AND p.customer_id = :customer_id';
        }

        if ($workplaceId != 0) {
            if (empty($where)) {
                $where .= " WHERE p.workplaceId = $workplaceId";
            } else {
                $where .= " AND p.workplaceId = $workplaceId";
            }
        }

        if ($riskLevel != '') {
            if (empty($where)) {
                $where .= " WHERE p.levelIR = '$riskLevel'";
            } else {
                $where .= " AND p.levelIR = '$riskLevel'";
            }
        }

        $sql = $query . $where;

        $results = DB::select($sql, array(
            'customer_id' => $customerId,
        ));

        return count($results) > 0 ? $results[0]->total : 0;
    }

    public function getAllMatrixExport($customerId = 0, $criteria = null)
    {
        $sql = "SELECT `wg_customer_config_job_activity_hazard`.`id`,
			`wg_customer_config_workplace`.`name` AS `workPlace`,
			`wg_customer_config_macro_process`.`name` AS `macroProcess`,
			`wg_customer_config_process`.`name` AS `process`,
			`wg_customer_config_job_data`.`name` AS `job`,
			`wg_customer_config_activity`.`name` AS `activity`,
			CASE
				WHEN `wg_customer_config_activity_process`.isRoutine = 1 THEN 'SI'
				ELSE 'NO'
			END AS isRoutine,
			`wg_config_job_activity_hazard_classification`.`name` AS `classification`,
			`wg_config_job_activity_hazard_type`.`name` AS `type`,
			`wg_config_job_activity_hazard_description`.`name` AS `description`,
			`wg_config_job_activity_hazard_effect`.`name` AS `effect`,
			`wg_customer_config_job_activity_hazard`.`time_exposure` AS `timeExposure`,
			`wg_customer_config_job_activity_hazard`.`control_method_source_text` AS `controlMethodSourceText`,
			`wg_customer_config_job_activity_hazard`.`control_method_medium_text` AS `controlMethodMediumText`,
			`wg_customer_config_job_activity_hazard`.`control_method_person_text` AS `controlMethodPersonText`,
			`wg_customer_config_job_activity_hazard`.`control_method_administrative_text` AS `controlMethodAdministrativeText`,
			`measure_nd`.`value` AS `measureND`,
			`measure_ne`.`value` AS `measureNE`,
			`measure_nc`.`value` AS `measureNC`,
			( measure_nd. VALUE * measure_ne. VALUE ) AS levelP,
			CASE
				WHEN ( measure_nd. VALUE * measure_ne. VALUE ) > 20 THEN 'Muy Alto'
				WHEN ( measure_nd. VALUE * measure_ne. VALUE ) >= 10
					AND ( measure_nd. VALUE * measure_ne. VALUE ) <= 20 THEN 'Alto'
				WHEN ( measure_nd. VALUE * measure_ne. VALUE ) >= 6
					AND ( measure_nd. VALUE * measure_ne. VALUE ) <= 8 THEN 'Medio'
				WHEN ( measure_nd. VALUE * measure_ne. VALUE ) >= 1
					AND ( measure_nd. VALUE * measure_ne. VALUE ) <= 4 THEN 'Bajo'
			END AS levelIP,
			( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) AS levelR,
			CASE
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 600
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 4000 THEN 'I'
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 150
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 500 THEN 'II'
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 40
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 120 THEN 'III'
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 20
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 39 THEN 'IV'
			END AS levelIR,
			CASE
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 600
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 4000 THEN 'No Aceptable'
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 150
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 40
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 120 THEN 'Mejorable'
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 20
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 39 THEN 'Aceptable'
			END AS riskValue,
			CASE
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 600
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 4000 THEN 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 150
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 500 THEN 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 40
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 120 THEN 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
				WHEN ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) >= 20
					AND ( ( measure_nd. VALUE * measure_ne. VALUE ) * measure_nc. VALUE ) <= 39 THEN 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable'
			END AS riskText,
			`wg_customer_config_job_activity_hazard`.`exposed`,
			`wg_customer_config_job_activity_hazard`.`contractors`,
			`wg_customer_config_job_activity_hazard`.`visitors`,
			`wg_customer_config_job_activity_hazard`.`status`,
			`wg_customer_config_job_activity_hazard`.`reason`,
			`config_type_measure`.`item` AS `intervention_type`,
			`wg_customer_config_job_activity_hazard_intervention`.`description` AS `intervention_description`,
			`hazard_tracking`.`item` AS `intervention_tracking`,
			`wg_customer_config_job_activity_hazard_intervention`.`observation` AS `intervention_observation`,
			`wg_customer_config_workplace`.`customer_id`,
			`wg_customer_config_activity`.`id` AS `activityId`,
			`wg_customer_config_workplace`.`id` AS `workPlaceId`
	FROM `wg_customer_config_workplace`
	INNER JOIN `wg_customer_config_macro_process` ON `wg_customer_config_macro_process`.`workplace_id` = `wg_customer_config_workplace`.`id`
	INNER JOIN `wg_customer_config_process` ON `wg_customer_config_process`.`workplace_id` = `wg_customer_config_macro_process`.`workplace_id`
	AND `wg_customer_config_process`.`macro_process_id` = `wg_customer_config_macro_process`.`id`
	INNER JOIN `wg_customer_config_job` ON `wg_customer_config_job`.`workplace_id` = `wg_customer_config_process`.`workplace_id`
	AND `wg_customer_config_job`.`macro_process_id` = `wg_customer_config_process`.`macro_process_id`
	AND `wg_customer_config_job`.`process_id` = `wg_customer_config_process`.`id`
	LEFT JOIN `wg_customer_config_job_data` ON `wg_customer_config_job_data`.`id` = `wg_customer_config_job`.`job_id`
	LEFT JOIN `wg_customer_config_job_activity` ON `wg_customer_config_job_activity`.`job_id` = `wg_customer_config_job`.`id`
	LEFT JOIN `wg_customer_config_activity` ON `wg_customer_config_activity`.`id` = `wg_customer_config_job_activity`.`activity_id`
	LEFT JOIN `wg_customer_config_activity_process` ON `wg_customer_config_activity_process`.`activity_id` = `wg_customer_config_activity`.`id`
	AND `wg_customer_config_activity_process`.`workplace_id` = `wg_customer_config_workplace`.`id`
	AND `wg_customer_config_activity_process`.`macro_process_id` = `wg_customer_config_macro_process`.`id`
	AND `wg_customer_config_activity_process`.`process_id` = `wg_customer_config_process`.`id`
	LEFT JOIN `wg_customer_config_job_activity_hazard` ON `wg_customer_config_job_activity_hazard`.`job_activity_id` = `wg_customer_config_activity`.`id`
	LEFT JOIN `wg_config_job_activity_hazard_classification` ON `wg_customer_config_job_activity_hazard`.`classification` = `wg_config_job_activity_hazard_classification`.`id`
	LEFT JOIN `wg_config_job_activity_hazard_description` ON `wg_customer_config_job_activity_hazard`.`description` = `wg_config_job_activity_hazard_description`.`id`
	LEFT JOIN `wg_config_job_activity_hazard_type` ON `wg_customer_config_job_activity_hazard`.`type` = `wg_config_job_activity_hazard_type`.`id`
	LEFT JOIN `wg_config_job_activity_hazard_effect` ON `wg_customer_config_job_activity_hazard`.`health_effect` = `wg_config_job_activity_hazard_effect`.`id`
	LEFT JOIN
	( SELECT *
		FROM `wg_config_general`
		WHERE `type` = 'ND' ) measure_nd ON `wg_customer_config_job_activity_hazard`.`measure_nd` = `measure_nd`.`id`
	LEFT JOIN
	( SELECT *
		FROM `wg_config_general`
		WHERE `type` = 'NE' ) measure_ne ON `wg_customer_config_job_activity_hazard`.`measure_ne` = `measure_ne`.`id`
	LEFT JOIN
	( SELECT *
		FROM `wg_config_general`
		WHERE `type` = 'NC' ) measure_nc ON `wg_customer_config_job_activity_hazard`.`measure_nc` = `measure_nc`.`id`
	LEFT JOIN `users` ON `wg_customer_config_activity_process`.`createdBy` = `users`.`id`
	LEFT JOIN `wg_customer_config_job_activity_hazard_intervention` ON `wg_customer_config_job_activity_hazard`.id = `wg_customer_config_job_activity_hazard_intervention`.job_activity_hazard_id
	LEFT JOIN
	( SELECT `id`,
				`namespace`,
				`group`,
				`item`,
				`value` COLLATE utf8_general_ci AS `value`,
								`code`
		FROM `system_parameters`
		WHERE `namespace` = 'wgroup'
		AND `group` = 'config_type_measure' ) config_type_measure ON `wg_customer_config_job_activity_hazard_intervention`.`type` = `config_type_measure`.`value`
	LEFT JOIN
	( SELECT `id`,
				`namespace`,
				`group`,
				`item`,
				`value` COLLATE utf8_general_ci AS `value`,
								`code`
		FROM `system_parameters`
		WHERE `namespace` = 'wgroup'
		AND `group` = 'hazard_tracking' ) hazard_tracking ON `wg_customer_config_job_activity_hazard_intervention`.`tracking` = `hazard_tracking`.`value`";

        $query = DB::table(DB::raw("($sql) as report"))
            ->where('report.customer_id', $customerId)
            ->orderBy('report.id');

        if ($criteria != null) {
            if (isset($criteria->mandatoryFilters) && $criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where($item->field, SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where($item->field, SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $result = $query->get();

        return array_map(function ($row) {

            $workplaceTitle = $_ENV['instance'] == 'isa' ? 'GRUPO OCUPACIONAL O INSTALACIÓN' : 'CENTRO DE TRABAJO';
            $macroprocessTitle = $_ENV['instance'] == 'isa' ? 'SUBESTACIÓN' : 'MACROPROCESO';
            $processTitle = $_ENV['instance'] == 'isa' ? 'UBICACIÓN, SITIO O ÁREA' : 'PROCESO';
            $activityTitle = $_ENV['instance'] == 'isa' ? 'LABOR/TAREA' : 'ACTIVIDAD';
            $controlMethodSourceTitle = $_ENV['instance'] == 'isa' ? 'M. Control Fuente' : 'M. Control Fuente';
            $controlMethodMediumTitle = $_ENV['instance'] == 'isa' ? 'M. Control Medio' : 'M. Control Medio';
            $controlMethodPersonTitle = $_ENV['instance'] == 'isa' ? 'M. Control Persona' : 'M. Control Persona';

            return [
                $workplaceTitle => $row->workPlace,
                $macroprocessTitle => $row->macroProcess,
                $processTitle => $row->process,
                "CARGO" => $row->job,
                $activityTitle => $row->activity,
                "RECURRENTE" => $row->isRoutine,
                "CLASIFICACIÓN" => $row->classification,
                "TIPO PELIGRO" => $row->type,
                "DESCRIPCIÓN PELIGRO" => $row->description,
                "EFECTOS A LA SALUD" => $row->effect,
                "T. EXPUESTO" => $row->timeExposure,
                $controlMethodSourceTitle => $row->controlMethodSourceText,
                $controlMethodMediumTitle => $row->controlMethodMediumText,
                $controlMethodPersonTitle => $row->controlMethodPersonText,
                "N. DEFICIENCIA" => $row->measureND,
                "N. EXPOSICIÓN" => $row->measureNE,
                "N. CONSECUENCIA" => $row->measureNC,
                "N. PROBABILIDAD" => $row->levelP,
                "INTERP N. PROBABILIDAD" => $row->levelIP,
                "NIVEL RIESGO" => $row->levelR,
                "INTERP RIESGO" => $row->status,
                "VALORACIÓN RIESGO" => $row->status,
                "TRABAJADORES VINCULADOS O EN MISIÓN" => $row->exposed,
                "TRABAJADORES CONTRATISTAS" => $row->contractors,
                "VISITANTES" => $row->visitors,
                "VERIFICADO" => $row->status,
                "MOTIVO" => $row->reason,
                "MEDIDA DE INTERVENCIÓN" => $row->intervention_type,
                "DESCRIPCIÓN MEDIDA DE INTERVENCIÓN" => $row->intervention_description,
                "SEGUIMIENTO Y MEDICIÓN" => $row->intervention_tracking,
                "OBSERVACIÓN" => $row->intervention_observation,
            ];
        }, $result);
    }

    public function getAllMatrixPrioritizeExport($customerId = 0, $workplaceId = null, $riskLevel = null, $criteria = null)
    {

        $sql = "SELECT `wg_customer_config_job_activity_hazard_intervention`.`id`,
					`wg_customer_config_workplace`.`name` AS `workPlace`,
					`wg_customer_config_macro_process`.`name` AS `macroProcess`,
					`wg_customer_config_process`.`name` AS `process`,
					`wg_customer_config_job_data`.`name` AS `job`,
					`wg_customer_config_activity`.`name` AS `activity`,
					CASE
						WHEN wg_customer_config_activity_process.isRoutine = 1 THEN 'SI'
						ELSE 'NO'
					END AS isRoutine,
					`wg_config_job_activity_hazard_classification`.`name` AS `classification`,
					`wg_config_job_activity_hazard_type`.`name` AS `type`,
					`wg_config_job_activity_hazard_description`.`name` AS `description`,
					`wg_config_job_activity_hazard_effect`.`name` AS `effect`,
					`wg_customer_config_job_activity_hazard`.`time_exposure` AS `timeExposure`,
					`wg_customer_config_job_activity_hazard`.`control_method_source_text` AS `controlMethodSourceText`,
					`wg_customer_config_job_activity_hazard`.`control_method_medium_text` AS `controlMethodMediumText`,
					`wg_customer_config_job_activity_hazard`.`control_method_person_text` AS `controlMethodPersonText`,
					`wg_customer_config_job_activity_hazard`.`control_method_administrative_text` AS `controlMethodAdministrativeText`,
					`measure_nd`.`value` AS `measureND`,
					`measure_ne`.`value` AS `measureNE`,
					`measure_nc`.`value` AS `measureNC`,
					(measure_nd.value * measure_ne.value) AS levelP,
					CASE
						WHEN (measure_nd.value * measure_ne.value) > 20 THEN 'Muy Alto'
						WHEN (measure_nd.value * measure_ne.value) >= 10
							AND (measure_nd.value * measure_ne.value) <= 20 THEN 'Alto'
						WHEN (measure_nd.value * measure_ne.value) >= 6
							AND (measure_nd.value * measure_ne.value) <= 8 THEN 'Medio'
						WHEN (measure_nd.value * measure_ne.value) >= 1
							AND (measure_nd.value * measure_ne.value) <= 4 THEN'Bajo'
					END AS levelIP,
					((measure_nd.value * measure_ne.value) * measure_nc.value) AS levelR,
					CASE
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'I'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'II'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'III'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 20
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'IV'
					END AS levelIR,
					CASE
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 20
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
					END AS riskValue,
					CASE
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 20
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable'
					END AS riskText,
					`config_type_measure`.`item` AS `interventionType`,
					`wg_customer_config_job_activity_hazard_intervention`.`description` AS `interventionDescription`,
					`hazard_tracking`.`item` AS `interventionTracking`,
					`wg_customer_config_job_activity_hazard_intervention`.`observation` AS `interventionObservation`,
					`wg_customer_config_job_activity_hazard`.`exposed`,
					`wg_customer_config_job_activity_hazard`.`contractors`,
					`wg_customer_config_job_activity_hazard`.`visitors`,
					`wg_customer_config_job_activity_hazard`.`status`,
					`wg_customer_config_job_activity_hazard`.`reason`,
					`wg_customer_config_workplace`.`customer_id`,
					`wg_customer_config_activity`.`id` AS `activityId`,
					`wg_customer_config_job_activity_hazard`.`id` AS `activityHazardId`,
					`wg_customer_config_workplace`.`id` AS `workPlaceId`
			FROM `wg_customer_config_workplace`
			INNER JOIN `wg_customer_config_macro_process` ON `wg_customer_config_macro_process`.`workplace_id` = `wg_customer_config_workplace`.`id`
			INNER JOIN `wg_customer_config_process` ON `wg_customer_config_process`.`workplace_id` = `wg_customer_config_macro_process`.`workplace_id`
			AND `wg_customer_config_process`.`macro_process_id` = `wg_customer_config_macro_process`.`id`
			INNER JOIN `wg_customer_config_job` ON `wg_customer_config_job`.`workplace_id` = `wg_customer_config_process`.`workplace_id`
			AND `wg_customer_config_job`.`macro_process_id` = `wg_customer_config_process`.`macro_process_id`
			AND `wg_customer_config_job`.`process_id` = `wg_customer_config_process`.`id`
			LEFT JOIN `wg_customer_config_job_data` ON `wg_customer_config_job_data`.`id` = `wg_customer_config_job`.`job_id`
			LEFT JOIN `wg_customer_config_job_activity` ON `wg_customer_config_job_activity`.`job_id` = `wg_customer_config_job`.`id`
			INNER JOIN `wg_customer_config_activity` ON `wg_customer_config_activity`.`id` = `wg_customer_config_job_activity`.`activity_id`
			LEFT JOIN `wg_customer_config_activity_process` ON `wg_customer_config_activity_process`.`activity_id` = `wg_customer_config_activity`.`id`
			AND `wg_customer_config_activity_process`.`workplace_id` = `wg_customer_config_workplace`.`id`
			AND `wg_customer_config_activity_process`.`macro_process_id` = `wg_customer_config_macro_process`.`id`
			AND `wg_customer_config_activity_process`.`process_id` = `wg_customer_config_process`.`id`
			INNER JOIN `wg_customer_config_job_activity_hazard` ON `wg_customer_config_job_activity_hazard`.`job_activity_id` = `wg_customer_config_activity`.`id`
			LEFT JOIN `wg_config_job_activity_hazard_classification` ON `wg_customer_config_job_activity_hazard`.`classification` = `wg_config_job_activity_hazard_classification`.`id`
			LEFT JOIN `wg_config_job_activity_hazard_description` ON `wg_customer_config_job_activity_hazard`.`description` = `wg_config_job_activity_hazard_description`.`id`
			LEFT JOIN `wg_config_job_activity_hazard_type` ON `wg_customer_config_job_activity_hazard`.`type` = `wg_config_job_activity_hazard_type`.`id`
			LEFT JOIN `wg_config_job_activity_hazard_effect` ON `wg_customer_config_job_activity_hazard`.`health_effect` = `wg_config_job_activity_hazard_effect`.`id`
			LEFT JOIN
			(SELECT *
				FROM `wg_config_general`
				WHERE `type` = 'ND') measure_nd ON `wg_customer_config_job_activity_hazard`.`measure_nd` = `measure_nd`.`id`
			LEFT JOIN
			(SELECT *
				FROM `wg_config_general`
				WHERE `type` = 'NE') measure_ne ON `wg_customer_config_job_activity_hazard`.`measure_ne` = `measure_ne`.`id`
			LEFT JOIN
			(SELECT *
				FROM `wg_config_general`
				WHERE `type` = 'NC') measure_nc ON `wg_customer_config_job_activity_hazard`.`measure_nc` = `measure_nc`.`id`
			LEFT JOIN `wg_customer_config_job_activity_hazard_intervention` ON `wg_customer_config_job_activity_hazard_intervention`.`job_activity_hazard_id` = `wg_customer_config_job_activity_hazard`.`id`
			LEFT JOIN
			(SELECT `id`,
					`namespace`,
					`group`,
					`item`,
					`value` COLLATE utf8_general_ci AS `value`,
									`code`
				FROM `system_parameters`
				WHERE `namespace` = 'wgroup'
				AND `group` = 'config_type_measure') config_type_measure ON `wg_customer_config_job_activity_hazard_intervention`.`type` = `config_type_measure`.`value`
			LEFT JOIN
			(SELECT `id`,
					`namespace`,
					`group`,
					`item`,
					`value` COLLATE utf8_general_ci AS `value`,
									`code`
				FROM `system_parameters`
				WHERE `namespace` = 'wgroup'
				AND `group` = 'hazard_tracking') hazard_tracking ON `wg_customer_config_job_activity_hazard_intervention`.`tracking` = `hazard_tracking`.`value`
			LEFT JOIN `users` ON `wg_customer_config_activity_process`.`createdBy` = `users`.`id`";

        $query = DB::table(DB::raw("($sql) as report"))
            ->where('report.customer_id', $customerId)
            ->orderBy('report.id');

        if ($workplaceId != null && !empty($workplaceId) && $workplaceId != 0) {
            $query->where('report.workPlaceId', $workplaceId);
        }

        if ($riskLevel != null && !empty($riskLevel)) {
            $query->where('report.levelIR', $riskLevel);
        }

        if ($criteria != null) {
            if (isset($criteria->mandatoryFilters) && $criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where($item->field, SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where($item->field, SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $result = $query->get();

        return array_map(function ($row) {

            $workplaceTitle = $_ENV['instance'] == 'isa' ? 'GRUPO OCUPACIONAL O INSTALACIÓN' : 'CENTRO DE TRABAJO';
            $macroprocessTitle = $_ENV['instance'] == 'isa' ? 'SUBESTACIÓN' : 'MACROPROCESO';
            $processTitle = $_ENV['instance'] == 'isa' ? 'UBICACIÓN, SITIO O ÁREA' : 'PROCESO';
            $activityTitle = $_ENV['instance'] == 'isa' ? 'LABOR/TAREA' : 'ACTIVIDAD';
            $controlMethodSourceTitle = $_ENV['instance'] == 'isa' ? 'M. Control Fuente' : 'M. Control Fuente';
            $controlMethodMediumTitle = $_ENV['instance'] == 'isa' ? 'M. Control Medio' : 'M. Control Medio';
            $controlMethodPersonTitle = $_ENV['instance'] == 'isa' ? 'M. Control Persona' : 'M. Control Persona';

            return [
                $workplaceTitle => $row->workPlace,
                $macroprocessTitle => $row->macroProcess,
                $processTitle => $row->process,
                "CARGO" => $row->job,
                $activityTitle => $row->activity,
                "RECURRENTE" => $row->isRoutine,
                "CLASIFICACIÓN" => $row->classification,
                "TIPO PELIGRO" => $row->type,
                "DESCRIPCIÓN PELIGRO" => $row->description,
                "EFECTOS A LA SALUD" => $row->effect,
                "T. EXPUESTO" => $row->timeExposure,
                $controlMethodSourceTitle => $row->controlMethodSourceText,
                $controlMethodMediumTitle => $row->controlMethodMediumText,
                $controlMethodPersonTitle => $row->controlMethodPersonText,
                "N. DEFICIENCIA" => $row->measureND,
                "N. EXPOSICIÓN" => $row->measureNE,
                "N. CONSECUENCIA" => $row->measureNC,
                "N. PROBABILIDAD" => $row->levelP,
                "INTERP N. PROBABILIDAD" => $row->levelIP,
                "NIVEL RIESGO" => $row->levelR,
                "INTERP RIESGO" => $row->status,
                "VALORACIÓN RIESGO" => $row->status,
                "TRABAJADORES VINCULADOS O EN MISIÓN" => $row->exposed,
                "TRABAJADORES CONTRATISTAS" => $row->contractors,
                "VISITANTES" => $row->visitors,
                "VERIFICADO" => $row->status,
                "MOTIVO" => $row->reason,
                "MEDIDA DE INTERVENCIÓN" => $row->interventionType,
                "DESCRIPCIÓN MEDIDA DE INTERVENCIÓN" => $row->interventionDescription,
                "SEGUIMIENTO Y MEDICIÓN" => $row->interventionTracking,
                "OBSERVACIÓN" => $row->interventionObservation,
            ];
        }, $result);
    }

    public function getAllMatrixHistoricalExport($customerId = 0, $workplaceId = null, $riskLevel = null, $criteria = null)
    {

        $sql = "SELECT `wg_customer_config_job_activity_hazard`.`id`,
					`wg_customer_config_workplace`.`name` AS `workPlace`,
					`wg_customer_config_macro_process`.`name` AS `macroProcess`,
					`wg_customer_config_process`.`name` AS `process`,
					`wg_customer_config_job_data`.`name` AS `job`,
					`wg_customer_config_activity`.`name` AS `activity`,
					CASE
						WHEN wg_customer_config_activity_process.isRoutine = 1 THEN 'SI'
						ELSE 'NO'
					END AS isRoutine,
					`wg_config_job_activity_hazard_classification`.`name` AS `classification`,
					`wg_config_job_activity_hazard_type`.`name` AS `type`,
					`wg_config_job_activity_hazard_description`.`name` AS `description`,
					`wg_config_job_activity_hazard_effect`.`name` AS `effect`,
					`wg_customer_config_job_activity_hazard`.`time_exposure` AS `timeExposure`,
					`wg_customer_config_job_activity_hazard`.`control_method_source_text` AS `controlMethodSourceText`,
					`wg_customer_config_job_activity_hazard`.`control_method_medium_text` AS `controlMethodMediumText`,
					`wg_customer_config_job_activity_hazard`.`control_method_person_text` AS `controlMethodPersonText`,
					`wg_customer_config_job_activity_hazard`.`control_method_administrative_text` AS `controlMethodAdministrativeText`,
					`measure_nd`.`value` AS `measureND`,
					`measure_ne`.`value` AS `measureNE`,
					`measure_nc`.`value` AS `measureNC`,
					(measure_nd.value * measure_ne.value) AS levelP,
					CASE
						WHEN (measure_nd.value * measure_ne.value) > 20 THEN 'Muy Alto'
						WHEN (measure_nd.value * measure_ne.value) >= 10
							AND (measure_nd.value * measure_ne.value) <= 20 THEN 'Alto'
						WHEN (measure_nd.value * measure_ne.value) >= 6
							AND (measure_nd.value * measure_ne.value) <= 8 THEN 'Medio'
						WHEN (measure_nd.value * measure_ne.value) >= 1
							AND (measure_nd.value * measure_ne.value) <= 4 THEN'Bajo'
					END AS levelIP,
					((measure_nd.value * measure_ne.value) * measure_nc.value) AS levelR,
					CASE
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'I'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'II'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'III'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 20
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'IV'
					END AS levelIR,
					CASE
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'No Aceptable'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'No Aceptable o Aceptable con control especifico'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorable'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 20
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Aceptable'
					END AS riskValue,
					CASE
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 600
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 4000 THEN 'Situación crítica. Suspender actividades hasta que el riesgo esté bajo control. Intervención urgente'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 150
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 500 THEN 'Corregir y adoptar medidas de control de inmediato. Sin embargo, suspenda actividades si el nivel de riesgo está por encima o igual de 360'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 40
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 120 THEN 'Mejorar si es posible. Sería conveniente justificar la intervención y su rentabilidad'
						WHEN ((measure_nd.value * measure_ne.value) * measure_nc.value) >= 20
							AND ((measure_nd.value * measure_ne.value) * measure_nc.value) <= 39 THEN 'Mantener las medidas de control existentes, pero se deberían considerar soluciones o mejoras y se deben hacer comprobaciones periódicas para asegurar que el riesgo aún es aceptable'
					END AS riskText,
					`wg_customer_config_job_activity_hazard`.`exposed`,
					`wg_customer_config_job_activity_hazard`.`contractors`,
					`wg_customer_config_job_activity_hazard`.`visitors`,
					`wg_customer_config_job_activity_hazard`.`status`,
					`wg_customer_config_job_activity_hazard`.`reason`,
							users.`name` historicalCreator,
							wg_customer_config_job_activity_hazard_tracking.source historicalSource,
							wg_customer_config_job_activity_hazard_tracking.type as historicalType,
							CONVERT_TZ(wg_customer_config_job_activity_hazard_tracking.created_at,'+00:00','-5:00') as historicalCreatedAt,
					`wg_customer_config_workplace`.`customer_id`,
					`wg_customer_config_activity`.`id` AS `activityId`,
					`wg_customer_config_workplace`.`id` AS `workPlaceId`
			FROM `wg_customer_config_workplace`
			INNER JOIN `wg_customer_config_macro_process` ON `wg_customer_config_macro_process`.`workplace_id` = `wg_customer_config_workplace`.`id`
			INNER JOIN `wg_customer_config_process` ON `wg_customer_config_process`.`workplace_id` = `wg_customer_config_macro_process`.`workplace_id`
			AND `wg_customer_config_process`.`macro_process_id` = `wg_customer_config_macro_process`.`id`
			INNER JOIN `wg_customer_config_job` ON `wg_customer_config_job`.`workplace_id` = `wg_customer_config_process`.`workplace_id`
			AND `wg_customer_config_job`.`macro_process_id` = `wg_customer_config_process`.`macro_process_id`
			AND `wg_customer_config_job`.`process_id` = `wg_customer_config_process`.`id`
			LEFT JOIN `wg_customer_config_job_data` ON `wg_customer_config_job_data`.`id` = `wg_customer_config_job`.`job_id`
			LEFT JOIN `wg_customer_config_job_activity` ON `wg_customer_config_job_activity`.`job_id` = `wg_customer_config_job`.`id`
			LEFT JOIN `wg_customer_config_activity` ON `wg_customer_config_activity`.`id` = `wg_customer_config_job_activity`.`activity_id`
			LEFT JOIN `wg_customer_config_activity_process` ON `wg_customer_config_activity_process`.`activity_id` = `wg_customer_config_activity`.`id`
			AND `wg_customer_config_activity_process`.`workplace_id` = `wg_customer_config_workplace`.`id`
			AND `wg_customer_config_activity_process`.`macro_process_id` = `wg_customer_config_macro_process`.`id`
			AND `wg_customer_config_activity_process`.`process_id` = `wg_customer_config_process`.`id`
			LEFT JOIN `wg_customer_config_job_activity_hazard` ON `wg_customer_config_job_activity_hazard`.`job_activity_id` = `wg_customer_config_activity`.`id`
			LEFT JOIN `wg_config_job_activity_hazard_classification` ON `wg_customer_config_job_activity_hazard`.`classification` = `wg_config_job_activity_hazard_classification`.`id`
			LEFT JOIN `wg_config_job_activity_hazard_description` ON `wg_customer_config_job_activity_hazard`.`description` = `wg_config_job_activity_hazard_description`.`id`
			LEFT JOIN `wg_config_job_activity_hazard_type` ON `wg_customer_config_job_activity_hazard`.`type` = `wg_config_job_activity_hazard_type`.`id`
			LEFT JOIN `wg_config_job_activity_hazard_effect` ON `wg_customer_config_job_activity_hazard`.`health_effect` = `wg_config_job_activity_hazard_effect`.`id`
			LEFT JOIN
			(SELECT *
				FROM `wg_config_general`
				WHERE `type` = 'ND') measure_nd ON `wg_customer_config_job_activity_hazard`.`measure_nd` = `measure_nd`.`id`
			LEFT JOIN
			(SELECT *
				FROM `wg_config_general`
				WHERE `type` = 'NE') measure_ne ON `wg_customer_config_job_activity_hazard`.`measure_ne` = `measure_ne`.`id`
			LEFT JOIN
			(SELECT *
				FROM `wg_config_general`
				WHERE `type` = 'NC') measure_nc ON `wg_customer_config_job_activity_hazard`.`measure_nc` = `measure_nc`.`id`
			INNER JOIN wg_customer_config_job_activity_hazard_tracking ON wg_customer_config_job_activity_hazard.id = wg_customer_config_job_activity_hazard_tracking.job_activity_hazard_id
			LEFT JOIN `users` ON `wg_customer_config_job_activity_hazard_tracking`.`createdBy` = `users`.`id`";

        $query = DB::table(DB::raw("($sql) as report"))
            ->where('report.customer_id', $customerId)
            ->orderBy('report.id');

        if ($workplaceId != null && !empty($workplaceId) && $workplaceId != 0) {
            $query->where('report.workPlaceId', $workplaceId);
        }

        if ($riskLevel != null && !empty($riskLevel)) {
            $query->where('report.levelIR', $riskLevel);
        }

        if ($criteria != null) {
            if (isset($criteria->mandatoryFilters) && $criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where($item->field, SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where($item->field, SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $result = $query->get();

        return array_map(function ($row) {

            $workplaceTitle = $_ENV['instance'] == 'isa' ? 'GRUPO OCUPACIONAL O INSTALACIÓN' : 'CENTRO DE TRABAJO';
            $macroprocessTitle = $_ENV['instance'] == 'isa' ? 'SUBESTACIÓN' : 'MACROPROCESO';
            $processTitle = $_ENV['instance'] == 'isa' ? 'UBICACIÓN, SITIO O ÁREA' : 'PROCESO';
            $activityTitle = $_ENV['instance'] == 'isa' ? 'LABOR/TAREA' : 'ACTIVIDAD';
            $controlMethodSourceTitle = $_ENV['instance'] == 'isa' ? 'M. Control Fuente' : 'M. Control Fuente';
            $controlMethodMediumTitle = $_ENV['instance'] == 'isa' ? 'M. Control Medio' : 'M. Control Medio';
            $controlMethodPersonTitle = $_ENV['instance'] == 'isa' ? 'M. Control Persona' : 'M. Control Persona';

            return [
                "USUARIO" => $row->historicalCreator,
                "ORIGEN" => $row->historicalSource,
                "TIPO ACCIÓN" => $row->historicalType,
                "FECHA ACCIÓN" => $row->historicalCreatedAt,
                $workplaceTitle => $row->workPlace,
                $macroprocessTitle => $row->macroProcess,
                $processTitle => $row->process,
                "CARGO" => $row->job,
                $activityTitle => $row->activity,
                "RECURRENTE" => $row->isRoutine,
                "CLASIFICACIÓN" => $row->classification,
                "TIPO PELIGRO" => $row->type,
                "DESCRIPCIÓN PELIGRO" => $row->description,
                "EFECTOS A LA SALUD" => $row->effect,
                "T. EXPUESTO" => $row->timeExposure,
                $controlMethodSourceTitle => $row->controlMethodSourceText,
                $controlMethodMediumTitle => $row->controlMethodMediumText,
                $controlMethodPersonTitle => $row->controlMethodPersonText,
                "N. DEFICIENCIA" => $row->measureND,
                "N. EXPOSICIÓN" => $row->measureNE,
                "N. CONSECUENCIA" => $row->measureNC,
                "N. PROBABILIDAD" => $row->levelP,
                "INTERP N. PROBABILIDAD" => $row->levelIP,
                "NIVEL RIESGO" => $row->levelR,
                "INTERP RIESGO" => $row->status,
                "VALORACIÓN RIESGO" => $row->status,
                "TRABAJADORES VINCULADOS O EN MISIÓN" => $row->exposed,
                "TRABAJADORES CONTRATISTAS" => $row->contractors,
                "VISITANTES" => $row->visitors,
                "VERIFICADO" => $row->status,
                "MOTIVO" => $row->reason,
            ];
        }, $result);
    }

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        return $where == "" ? "" : " WHERE " . $where;
    }

}
