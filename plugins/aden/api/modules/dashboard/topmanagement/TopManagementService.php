<?php

namespace AdeN\Api\Modules\Dashboard\TopManagement;

use AdeN\Api\Classes\BaseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Dashboard\TopManagement\Services\CostByMonth;

class TopManagementService extends BaseService
{
    public function consolidate()
    {
        DB::table("project_consolidate")->delete();

        DB::statement(
            "INSERT INTO project_consolidate (
                          deliveryDate, type, concept, classification, customer_id, administrator,
                          total, total_executed, total_programmed
                       )
            SELECT p.deliveryDate, p.type, c.concept, c.classification, customer_id, p.createdBy as administrator,
                   SUM(c.total_price) as total,
                   sum(IF(c.status = 'SS002', c.total_price, 0)) as total_executed,
                   sum(IF(c.status = 'SS001', c.total_price, 0)) as total_programmed
            FROM wg_customer_project AS p
            INNER JOIN wg_customer_project_costs AS c ON c.project_id = p.id AND c.status IS NOT NULL
            GROUP BY p.deliveryDate, p.type, c.concept, c.classification, customer_id, administrator"
        );


        DB::table("wg_customer_vr_employee_consolidate")->delete();

        DB::statement("
            INSERT INTO wg_customer_vr_employee_consolidate (customer_id, date, experience, total)
            SELECT vr.customer_id, date_format(ae.registration_date, '%Y-%m-%d') AS date,
              exp.experience_code,
              count(DISTINCT customer_employee_id) AS count_employees
            FROM wg_customer_vr_employee vr
            join wg_customer_vr_employee_experience exp ON exp.customer_vr_employee_id = vr.id AND application = 'SI'
            join wg_customer_vr_employee_answer_experience ae ON ae.customer_vr_employee_id = vr.id
            WHERE ae.registration_date IS NOT NULL
            GROUP BY vr.id
        ");


        DB::table("wg_customer_project_agent_consolidate")->delete();

        DB::statement("
            insert INTO wg_customer_project_agent_consolidate
                (agent_id, project_id, delivery_date_project, estimated_hours, start_date_task, end_date_task, status, duration)
                SELECT a.agent_id, p.id AS project_id,
                  p.deliveryDate AS delivery_date_project,
                  max(a.estimatedHours) AS estimated_hours,
                  t.startDateTime AS start_date_task,
                  t.endDateTime AS end_date_task,
                  t.status,
                  IFNULL(t.duration, 0) duration
                FROM wg_customer_project p
                JOIN wg_customer_project_agent a ON a.project_id = p.id
                LEFT JOIN wg_customer_project_agent_task t ON t.project_agent_id = a.id
                GROUP BY a.id, t.id
        ");
    }


    public function getChartLineCostHistorical($criteria)
    {
        $subquery = $this->getQueryHistoricalCosts($criteria);

        list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartLine($query->get(), $config);
    }


    public function getChartBarCostByMonths($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator)
    {
        try {
            $subquery = CostByMonth::getQueryIndicator($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator);

            list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery, ['deliveryDate']);

            $config = array(
                "labelColumn" => 'label',
                "valueColumns" => $valueColumns
            );

            return $this->chart->getChartLine($query->get(), $config);
        } catch (\Exception $exception) {
            Log::debug('error to the get dashboard indicators', [$exception->getMessage()]);
            throw $exception;
        }
    }


    public function getChartBarCostByParam($column, $parameter, $startDate, $endDate, $type, $concept, $classification, $customerId, $administrator)
    {
        try {
            $subquery = DB::table('project_consolidate as c')
                ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'sp')), function ($join) {
                    $join->whereRaw("CONVERT(DATE_FORMAT(deliveryDate, '%m'), UNSIGNED INT) = sp.value");
                })
                ->leftjoin(DB::raw(SystemParameter::getRelationTable($parameter, 'pt')), function ($join) use ($column) {
                    $join->on('pt.value', '=', $column);
                })
                ->where('total_executed', '>', '0')
                ->whereDate('c.deliveryDate', '>=', $startDate)
                ->whereDate('c.deliveryDate', '<=', $endDate)

                ->when($type, function ($query) use ($type) {
                    $query->where('c.type', $type);
                })
                ->when($concept, function ($query) use ($concept) {
                    $query->where('c.concept', $concept);
                })
                ->when($classification, function ($query) use ($classification) {
                    $query->where('c.classification', $classification);
                })
                ->when($customerId, function ($query) use ($customerId) {
                    $query->where('c.customer_id', $customerId);
                })
                ->when($administrator, function ($query) use ($administrator) {
                    $query->where('c.administrator', $administrator);
                })
                ->groupBy(DB::raw('year(deliveryDate)'), DB::raw('month(deliveryDate)'), $column)
                ->orderBy(DB::raw("year(deliveryDate)"), 'sp.value')
                ->select(
                    'deliveryDate',
                    DB::raw("CONCAT(YEAR(deliveryDate), '-', sp.item) AS label"),
                    DB::raw("pt.item as type"),
                    DB::raw("SUM(total_executed) AS total")
                );

            $data = $subquery->get();

            $query = DB::table(DB::raw("({$subquery->toSql()}) as d"))
                ->mergeBindings($subquery)
                ->groupBy('d.label')
                ->orderBy('deliveryDate')
                ->select('d.label as label');

            $dynamicLabel = [];
            $dynamicColumnsCharts = [];
            foreach ($data as $datum) {
                if (in_array($datum->type, $dynamicLabel)) {
                    continue;
                }

                $dynamicLabel[] = $datum->type;
                $dynamicColumnsCharts[] = ['label' => $datum->type, 'field' => $datum->type];

                $query->addSelect(
                    DB::raw("SUM(CASE WHEN type = '{$datum->type}' THEN total ELSE 0 END) AS '{$datum->type}'")
                );
            }

            $config = array(
                "labelColumn" => 'label',
                "valueColumns" => $dynamicColumnsCharts
            );

            return $this->chart->getChartBar($query->get(), $config);
        } catch (\Exception $exception) {
            Log::debug('error to the get dashboard indicators', $exception->getMessage());
            return [];
        }
    }


    public function getChartBarExperiencesByMonths($startDate, $endDate, $customerId)
    {
        try {
            $subquery = $this->getBaseQueryExperienceByMonths($startDate, $endDate, $customerId)
                ->select(
                    DB::raw("CONCAT(YEAR(date), '-', m.item) AS label"),
                    'sp.item as experience',
                    DB::raw("SUM(total) AS total"),
                    'c.date as date'
                );

            $data = $subquery->get();

            $query = DB::table(DB::raw("({$subquery->toSql()}) as d"))
                ->mergeBindings($subquery)
                ->groupBy('d.label')
                ->orderBy('d.date')
                ->select(DB::raw("d.label as label"));


            $dynamicLabel = [];
            $dynamicColumnsCharts = [];
            foreach ($data as $datum) {
                if (in_array($datum->experience, $dynamicLabel)) {
                    continue;
                }

                $dynamicLabel[] = $datum->experience;
                $dynamicColumnsCharts[] = ['label' => $datum->experience, 'field' => $datum->experience];

                $query->addSelect(
                    DB::raw("SUM(CASE WHEN experience = '{$datum->experience}' THEN total ELSE 0 END) AS '{$datum->experience}'")
                );
            }

            $config = array(
                "labelColumn" => 'label',
                "valueColumns" => $dynamicColumnsCharts
            );

            return $this->chart->getChartBar($query->get(), $config);
        } catch (\Exception $exception) {
            Log::debug('error to the get dashboard indicators', [$exception->getMessage()]);
            throw $exception;
        }
    }



    public function getChartLinePerformanceByConsultant($startDate, $endDate, $customerId)
    {

        $subquery = DB::table('wg_customer_project_agent_consolidate')
            ->join('wg_customer_project as p', 'p.id', '=', 'wg_customer_project_agent_consolidate.project_id')
            ->whereRaw("DATE(wg_customer_project_agent_consolidate.delivery_date_project) BETWEEN '$startDate' AND '$endDate' ")
            ->when($customerId, function($query) use ($customerId) {
                return $query->whereRaw("p.customer_id = '$customerId'");
            })
            ->groupBy('wg_customer_project_agent_consolidate.project_id', 'wg_customer_project_agent_consolidate.agent_id')
            ->select(
                'wg_customer_project_agent_consolidate.project_id',
                'wg_customer_project_agent_consolidate.agent_id',
                DB::raw('MAX(wg_customer_project_agent_consolidate.estimated_hours) as estimated_hours')
            );

        $assigned =  DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->join('wg_agent as ag', 'ag.id', '=', 'd.agent_id')
            ->groupBy('agent_id')
            ->select('project_id', 'agent_id', DB::raw('SUM(estimated_hours) as estimated_hours'))
            ->select(
                DB::raw("'Asignado' AS label"),
                DB::raw("ag.name AS dynamicColumn"),
                DB::raw("SUM(estimated_hours) AS total")
            );

        $executed = DB::table('wg_customer_project_agent_consolidate as c')
            ->join('wg_customer_project as p', 'p.id', '=', 'c.project_id')
            ->join('wg_agent as ag', 'ag.id', '=', 'c.agent_id')
            ->where('c.status', 'inactivo')
            ->whereRaw("DATE(c.delivery_date_project) BETWEEN '$startDate' AND '$endDate' ")
            ->when($customerId, function($query) use ($customerId) {
                return $query->whereRaw("p.customer_id = '$customerId'");
            })
            ->groupBy('c.agent_id')
            ->select(
                DB::raw("'Ejecutado' AS label"),
                DB::raw("ag.name AS dynamicColumn"),
                DB::raw('SUM(duration) as total')
            );

        $subquery = $executed->unionAll($assigned);

        $data = $subquery->get();

        $query = DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('d.dynamicColumn')
            ->select('d.dynamicColumn as label');

        $labels = [];
        $valueColumns = [];
        foreach ($data as $datum) {
            if (in_array($datum->label, $labels)) {
                continue;
            }

            $labels[] = $datum->label;
            $valueColumns[] = ['label' => $datum->label, 'field' => $datum->label];

            $query->addSelect(
                DB::raw("SUM(CASE WHEN label = '{$datum->label}' THEN total ELSE 0 END) AS '{$datum->label}'")
            );
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartLine($query->get(), $config);
    }



    public function getCalendar($types, int $year)
    {
        $year = $year ?: now()->year;

        return DB::table('wg_customer_project as p')
            ->join('wg_customers as c', 'c.id', '=', 'p.customer_id')
            ->whereRaw("YEAR(p.deliveryDate) = $year")
            ->when($types, function ($query) use ($types) {
                $query->whereIn('p.type', $types);
            })
            ->select(
                'p.id',
                DB::raw("CONCAT(c.businessName, ' - ', p.name) as title"),
                DB::raw("p.deliveryDate as startsAt"),
                'p.status AS statusName'
            )
            ->orderBy('p.deliveryDate', 'desc')
            ->get();
    }


    public function getKPITotalCosts($criteria)
    {
        $sst = SystemParameter::getParameterByGroupAndValue('SST', 'project_type_group')->pluck('item');
        $sst->push('RV');
        $sst->push('SYL');

        $period = $criteria && isset($criteria->period) && $criteria->period ? $criteria->period : Carbon::now()->year;

        $total = DB::table('wg_customer_project as p')
            ->join('wg_customer_project_costs as c', 'c.project_id', '=', 'p.id')
            ->where('c.status', 'SS002')
            ->whereIn('p.type', $sst)
            ->whereYear('p.deliveryDate', $period)
            ->sum('c.total_price');

        return number_format($total ?? 0);
    }

    private function getQueryHistoricalCosts($criteria = null)
    {
        $baseQuery = DB::table('wg_customer_project as p')
            ->join('wg_customer_project_costs as c', 'c.project_id', '=', 'p.id')
            ->where('c.status', 'SS002')
            ->when($criteria && isset($criteria->period) && $criteria->period, function ($query) use ($criteria) {
                $query->whereYear('p.deliveryDate', $criteria->period);
            })
            ->groupBy(DB::raw("YEAR(p.deliveryDate)"))
            ->orderBy('p.deliveryDate');

        $costsSST = (clone $baseQuery)
            ->join(DB::raw(SystemParameter::getRelationTable('project_type_group', 'sst')), function ($join) {
                $join->whereRaw("sst.value = 'SST' ");
                $join->on('sst.item', '=', 'p.type');
            })
            ->select(
                DB::raw("'SST' AS label"),
                DB::raw("YEAR(p.deliveryDate) AS year"),
                DB::raw("YEAR(p.deliveryDate) AS dynamicColumn"),
                DB::raw("SUM(c.total_price) AS total"),
                DB::raw("'#c26530' AS color")
            );

        $costsRV = (clone $baseQuery)
            ->whereRaw("p.type = 'RV'")
            ->select(
                DB::raw("'Realidad Virtual' AS label"),
                DB::raw("YEAR(p.deliveryDate) AS year"),
                DB::raw("YEAR(p.deliveryDate) AS dynamicColumn"),
                DB::raw("SUM(c.total_price) AS total"),
                DB::raw("'#aeaeb3' AS color")
            );

        $costsSylogi = (clone $baseQuery)
            ->whereRaw("p.type = 'SYL'")
            ->select(
                DB::raw("'SYLOGI' AS label"),
                DB::raw("YEAR(p.deliveryDate) AS year"),
                DB::raw("YEAR(p.deliveryDate) AS dynamicColumn"),
                DB::raw("SUM(c.total_price) AS total"),
                DB::raw("'#6494bf' AS color")
            );

        return $costsSST
            ->unionAll($costsRV)->mergeBindings($costsRV)
            ->unionAll($costsSylogi)->mergeBindings($costsSylogi);
    }

    public function getHistoricalCosts()
    {
        $subquery = $this->getQueryHistoricalCosts();

        return DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('d.year');
    }




    public function getCustomers()
    {
        return DB::table('wg_customer_project as p')
            ->join('wg_customer_project_costs as pc', 'pc.project_id', '=', 'p.id')
            ->join('wg_customers as c', 'c.id', '=', 'p.customer_id')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc', 'td')), function ($join) {
                $join->on("td.value", '=', "c.documentType");
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('tipocliente', 'tc')), function ($join) {
                $join->on("tc.value", '=', "c.type");
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('estado', 'status')), function ($join) {
                $join->on("status.value", 'c.status');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_classification', 'clas')), function ($join) {
                $join->on("clas.value", '=', 'c.classification');
            })
            ->groupBy('c.id');
    }


    public function getAdministrators()
    {
        return DB::table('wg_customer_project as p')
            ->join('wg_customer_project_costs as pc', 'pc.project_id', '=', 'p.id')
            ->join('users as u', 'u.id', '=', 'p.createdBy')
            ->groupBy('u.id');
    }


    public function getBaseQuerySalesByParam($column, $parameter)
    {
        return DB::table('project_consolidate as c')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'sp')), function ($join) {
                $join->whereRaw("CONVERT(DATE_FORMAT(deliveryDate, '%m'), UNSIGNED INT) = sp.value");
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable($parameter, 'pt')), function ($join) use ($column) {
                $join->on('pt.value', '=', $column);
            })
            ->groupBy(
                DB::raw('year(deliveryDate)'),
                DB::raw('month(deliveryDate)'),
                'pt.item'
            );
    }


    public function getBaseQueryExperienceByMonths($startDate, $endDate, $customerId)
    {
        return DB::table('wg_customer_vr_employee_consolidate as c')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'm')), function ($join) {
                $join->whereRaw("MONTH(date) = m.value");
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('experience_vr', 'sp')), function ($join) {
                $join->whereRaw("sp.value = c.experience");
            })
            ->whereDate('c.date', '>=', $startDate)
            ->whereDate('c.date', '<=', $endDate)
            ->when($customerId, function($query) use($customerId) {
                return $query->where('c.customer_id', $customerId);
            })
            ->groupBy(
                DB::raw("year(date)"),
                DB::raw("month(date)"),
                'c.experience'
            )
            ->orderBy('date')
            ->orderBy('c.experience');
    }



    public function getQueryAmountBySatisfactionGrid($startDate, $endDate)
    {
        $defaultQuestion = DB::table('system_parameters')
            ->where('group', 'vr_employee_satisfaction_chart')
            ->where('value', 'question')
            ->first();

        $question = DB::table('wg_customer_vr_satisfactions_questions')
            ->when($defaultQuestion, function ($query) use ($defaultQuestion) {
                $query->where('id', $defaultQuestion->item);
            })
            ->first();

        return DB::table('wg_customer_vr_satisfactions_responses as r')
            ->join('wg_customer_vr_satisfactions_questions as q', function ($join) {
                $join->on('q.id', 'r.question_id');
                $join->where('q.label', 'Calificación Exp.');
            })
            ->join('wg_customer_parameter as cp', function ($join) {
                $join->on('cp.customer_id', 'r.customer_id');
                $join->where('cp.namespace', 'wgroup');
                $join->where('cp.group', 'experienceVR');
                $join->whereRaw('cp.item COLLATE utf8_general_ci = r.experience');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'm')), function ($join) {
                $join->whereRaw("MONTH(date_register) = m.value");
            })
            ->whereDate('r.date_register', '>=', $startDate)
            ->whereDate('r.date_register', '<=', $endDate)
            ->when($question, function ($query) use ($question) {
                $query->where('q.id', $question->id);
            })
            ->groupBy(
                DB::raw("CONCAT(YEAR(date_register), '-', m.item)"),
                'cp.value'
            );
    }


    public function getQueryRegisterVsParticipants($startDate, $endDate, $customerId)
    {

        $participants = DB::table('wg_customer_vr_employee_consolidate as c')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'm')), function ($join) {
                $join->whereRaw("MONTH(date) = m.value");
            })
            ->whereDate('c.date', '>=', $startDate)
            ->whereDate('c.date', '<=', $endDate)
            ->when($customerId, function($query) use ($customerId) {
                return $query->where('c.customer_id', $customerId);
            })
            ->groupBy(DB::raw("CONCAT(YEAR(date), '-', m.item)"))
            ->select(
                DB::raw("'Participantes' AS label"),
                DB::raw("CONCAT(YEAR(date), '-', m.item) AS period"),
                DB::raw("sum(total) AS total"),
                DB::raw('date AS date'),
                'c.customer_id'
            );


        $encuestados = DB::table('wg_customer_vr_satisfactions_responses as r')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month', 'm')), function ($join) {
                $join->whereRaw("MONTH(r.date_register) = m.value");
            })
            ->whereDate('r.date_register', '>=', $startDate)
            ->whereDate('r.date_register', '<=', $endDate)
            ->when($customerId, function($query) use ($customerId) {
                return $query->where('r.customer_id', $customerId);
            })
            ->groupBy(DB::raw("CONCAT(YEAR(date), '-', m.item)"))
            ->select(
                DB::raw("'Encuestados' AS label"),
                DB::raw("CONCAT(YEAR(r.date_register), '-', m.item) AS period"),
                DB::raw("count(DISTINCT r.`group`) AS total"),
                DB::raw('r.date_register AS date'),
                'r.customer_id'
            );

        $subquery = $participants
            ->unionAll($encuestados)->mergeBindings($encuestados);

        return DB::table(DB::raw("( {$subquery->toSql()} ) as o"))
            ->mergeBindings($subquery)
            ->groupBy('period');
    }


    public function getQueryPerformanceByConsultantGrid($startDate, $endDate)
    {

        $subquery = DB::table('wg_customer_project_agent_consolidate')
            ->join('wg_customer_project as p', 'p.id', '=', 'wg_customer_project_agent_consolidate.project_id')
            ->whereRaw("DATE(wg_customer_project_agent_consolidate.delivery_date_project) BETWEEN '$startDate' AND '$endDate' ")
            ->groupBy('wg_customer_project_agent_consolidate.project_id', 'wg_customer_project_agent_consolidate.agent_id')
            ->select(
                'p.customer_id',
                'wg_customer_project_agent_consolidate.project_id',
                'wg_customer_project_agent_consolidate.agent_id',
                DB::raw('MAX(wg_customer_project_agent_consolidate.estimated_hours) as estimated_hours')
            );

        $assigned =  DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->join('wg_agent as ag', 'ag.id', '=', 'd.agent_id')
            ->groupBy('agent_id')
            ->select('customer_id', 'project_id', 'agent_id', DB::raw('SUM(estimated_hours) as estimated_hours'))
            ->select(
                'customer_id',
                DB::raw("'Asignado' AS label"),
                DB::raw("ag.name AS consultant"),
                DB::raw("SUM(estimated_hours) AS total")
            );

        $executed = DB::table('wg_customer_project_agent_consolidate as c')
            ->join('wg_customer_project as p', 'p.id', '=', 'c.project_id')
            ->join('wg_agent as ag', 'ag.id', '=', 'c.agent_id')
            ->where('c.status', 'inactivo')
            ->whereRaw("DATE(c.delivery_date_project) BETWEEN '$startDate' AND '$endDate' ")
            ->groupBy('c.agent_id')
            ->select(
                'customer_id',
                DB::raw("'Ejecutado' AS label"),
                DB::raw("ag.name AS consultant"),
                DB::raw('SUM(c.duration) as total')
            );

        $subquery = $executed
            ->unionAll($assigned)->mergeBindings($assigned);

        $query = DB::table(DB::raw("( {$subquery->toSql()} ) as o"))
            ->mergeBindings($subquery)
            ->groupBy('consultant')
            ->select(
                'customer_id',
                'consultant',
                DB::raw("SUM(CASE WHEN label = 'Asignado' THEN total ELSE 0 END) AS assigned"),
                DB::raw("SUM(CASE WHEN label = 'Ejecutado' THEN total ELSE 0 END) AS executed")
            );

        return DB::table(DB::raw("( {$query->toSql()} ) as t"))
            ->mergeBindings($query)
            ->groupBy('consultant');
    }


    public function getProgrammedVsExecutedSales()
    {
        $sst = DB::table('project_consolidate as p')
            ->join(DB::raw(SystemParameter::getRelationTable('project_type_group', 'sst')), function ($join) {
                $join->whereRaw("sst.value = 'SST' ");
                $join->whereRaw('sst.item COLLATE utf8_general_ci  = p.type');
            })
            ->groupBy(DB::raw("YEAR(deliveryDate)"))
            ->select(
                DB::raw("year(deliveryDate) as label"),
                DB::raw(" 'SST' as stack"),
                DB::raw(" sum(total) as programmed "),
                DB::raw(" sum(total_executed) as executed "),
                DB::raw(" (sum(total_executed) - sum(total)) as balance ")
            );

        $rv = DB::table('project_consolidate as p')
            ->where('p.type', 'RV')
            ->groupBy(DB::raw("YEAR(deliveryDate)"))
            ->select(
                DB::raw("year(deliveryDate) as label"),
                DB::raw(" 'Realidad Virtual' as stack"),
                DB::raw(" sum(total) as programmed "),
                DB::raw(" sum(total_executed) as executed "),
                DB::raw(" (sum(total_executed) - sum(total)) as balance ")
            );

        $sylogi = DB::table('project_consolidate as p')
            ->where('p.type', 'SYL')
            ->groupBy(DB::raw("YEAR(deliveryDate)"))
            ->select(
                DB::raw("year(deliveryDate) as label"),
                DB::raw(" 'Sylogi' as stack"),
                DB::raw(" sum(total) as programmed "),
                DB::raw(" sum(total_executed) as executed "),
                DB::raw(" (sum(total_executed) - sum(total)) as balance ")
            );

        return $rv
            ->union($sst)->mergeBindings($sst)
            ->union($sylogi)->mergeBindings($sylogi);
    }


    public function getProgrammedVsExecutedSalesToCharBar($criteria = null)
    {
        $sst = DB::table('project_consolidate as p')
            ->join(DB::raw(SystemParameter::getRelationTable('project_type_group', 'sst')), function ($join) {
                $join->whereRaw("sst.value = 'SST' ");
                $join->whereRaw('sst.item COLLATE utf8_general_ci  = p.type');
            })
            ->when($criteria && isset($criteria->period) && $criteria->period, function ($query) use ($criteria) {
                $query->whereYear('p.deliveryDate', $criteria->period);
            })
            ->groupBy(DB::raw("YEAR(deliveryDate)"))
            ->select(
                DB::raw("year(deliveryDate) as label"),
                DB::raw(" 'SST' as stack"),
                DB::raw(" sum(total_programmed) as programmed "),
                DB::raw(" sum(total_executed) as executed "),
                DB::raw(" (sum(total_executed) - sum(total)) as balance ")
            );

        $rv = DB::table('project_consolidate as p')
            ->where('p.type', 'RV')
            ->when($criteria && isset($criteria->period) && $criteria->period, function ($query) use ($criteria) {
                $query->whereYear('p.deliveryDate', $criteria->period);
            })
            ->groupBy(DB::raw("YEAR(deliveryDate)"))
            ->select(
                DB::raw("year(deliveryDate) as label"),
                DB::raw(" 'Realidad Virtual' as stack"),
                DB::raw(" sum(total_programmed) as programmed "),
                DB::raw(" sum(total_executed) as executed "),
                DB::raw(" (sum(total_executed) - sum(total)) as balance ")
            );

        $sylogi = DB::table('project_consolidate as p')
            ->where('p.type', 'SYL')
            ->when($criteria && isset($criteria->period) && $criteria->period, function ($query) use ($criteria) {
                $query->whereYear('p.deliveryDate', $criteria->period);
            })
            ->groupBy(DB::raw("YEAR(deliveryDate)"))
            ->select(
                DB::raw("year(deliveryDate) as label"),
                DB::raw(" 'Sylogi' as stack"),
                DB::raw(" sum(total_programmed) as programmed "),
                DB::raw(" sum(total_executed) as executed "),
                DB::raw(" (sum(total_executed) - sum(total)) as balance ")
            );

        return $rv
            ->union($sst)->mergeBindings($sst)
            ->union($sylogi)->mergeBindings($sylogi);
    }


    public function getChartBarStackedTypeSalesByStates($criteria)
    {
        $data = $this->getProgrammedVsExecutedSalesToCharBar($criteria)->get();

        if (empty($data)) {
            return [];
        }

        $labels = $data->pluck('label')->unique()->values();
        $stacks = $data->pluck('stack')->unique()->values();

        $config = array(
            "labelColumn" => $labels,
            "valueColumns" => [
                ['label' => 'Balance', 'field' => 'programmed', 'color' => '#EEA236'],
                ['label' => 'Ejecutado', 'field' => 'executed', 'color' => '#5CB85C']
            ]
        );

        return $this->chart->getChartBarGroupedStack($data, $config, $stacks);
    }

    public function getPeriodList()
    {
        $q1 = DB::table('project_consolidate as p')
            //->where('p.type', 'SYL')
            ->select(
                DB::raw("YEAR(p.deliveryDate) AS item"),
                DB::raw("YEAR(p.deliveryDate) AS value")
            )
            ->groupBy(DB::raw("YEAR(p.deliveryDate)"));

        $q2 = DB::table('wg_customer_project as p')
            //->where('c.status', 'SS002')
            ->select(
                DB::raw("YEAR(p.deliveryDate) AS item"),
                DB::raw("YEAR(p.deliveryDate) AS value")
            )
            ->groupBy(DB::raw("YEAR(p.deliveryDate)"));

        $q3 = DB::table('wg_customer_project as p')
            ->join('wg_customer_project_costs as c', 'c.project_id', '=', 'p.id')
            ->where('c.status', 'SS002')
            ->select(
                DB::raw("YEAR(p.deliveryDate) AS item"),
                DB::raw("YEAR(p.deliveryDate) AS value")
            )
            ->groupBy(DB::raw("YEAR(p.deliveryDate)"));

        $q1
            //->unionAll($q2)->mergeBindings($q2)
            ->unionAll($q3)->mergeBindings($q3);

        return DB::table(DB::raw("( {$q1->toSql()} ) as t"))
            ->mergeBindings($q1)
            ->orderBy('item', 'desc')
            ->groupBy('item')
            ->get();
    }
}
