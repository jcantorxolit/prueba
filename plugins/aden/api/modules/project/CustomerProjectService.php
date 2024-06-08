<?php

namespace AdeN\Api\Modules\Project;


use DB;
use AdeN\Api\Classes\BaseService;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use Wgroup\Traits\UserSecurity;

class CustomerProjectService extends BaseService
{
    use UserSecurity;

    public function __construct()
    {
        parent::__construct();
    }

    public function allYears()
    {
        return DB::table('wg_customer_project')
            ->select(
                DB::raw('YEAR(deliveryDate) value'),
                DB::raw('YEAR(deliveryDate) item')
            )
            ->groupBy(DB::raw('YEAR(deliveryDate)'))
            ->orderBy(DB::raw('YEAR(deliveryDate)'), 'DESC')
            ->get();
    }

    public function allTaskType()
    {
        return DB::table('wg_project_task_type')
            ->select(
                'id',
                'code',
                'description',
                'price'
            )
            ->where("isActive", 1)
            ->orderBy("description", 'ASC')
            ->get();
    }

    public function getSummary($criteria)
    {
        $qInner = $this->prepareQuerySummaryBase($criteria);

        $query = DB::table(DB::raw("(SELECT SUM(availability) availability FROM wg_agent) x, wg_agent"))
            ->leftjoin(DB::raw("({$qInner->toSql()}) AS wg_customer_project_agent"), function ($join) {
                $join->on('wg_customer_project_agent.agent_id', '=', 'wg_agent.id');
            })
            ->mergeBindings($qInner)
            ->select(
                DB::raw("SUM(ROUND(IFNULL(wg_customer_project_agent.estimatedHours, 0),0)) AS assignedHours"),
                DB::raw("SUM(ROUND(IFNULL(wg_customer_project_agent.planned, 0),0) + ROUND(IFNULL(wg_customer_project_agent.executed, 0),0)) AS scheduledHours"),
                DB::raw("SUM(ROUND(IFNULL(wg_customer_project_agent.executed, 0),0)) AS runningHours")
            );

        if ($criteria->type == 'system') {
            if (!empty($criteria->agentId)) {
                $query->addSelect(DB::raw("wg_agent.availability - SUM(ROUND(IFNULL(wg_customer_project_agent.estimatedHours, 0),0)) AS availabilityHours"));
            } else if ($criteria->customerId) {
                $query->addSelect(DB::raw("SUM(ROUND(IFNULL(wg_customer_project_agent.estimatedHours, 0),0)) AS availabilityHours"));
            } else {
                $query->addSelect(DB::raw("x.availability - SUM(ROUND(IFNULL(wg_customer_project_agent.estimatedHours, 0),0)) AS availabilityHours"));
            }

        } else if ($criteria->type == 'agent') {
            $this->run();
            $criteria->agentId = $criteria->agentId ? $criteria->agentId : $this->isUserRelatedAgent();
            $query->addSelect("wg_agent.availability AS availabilityHours");

        } else if ($criteria->type = 'customerAdmin' || $criteria->type = 'customerUser') {
            $this->run();
            $criteria->customerId = $criteria->customerId ? $criteria->customerId : $this->getUserRelatedCustomer();
            $query->addSelect("wg_agent.availability AS availabilityHours");
        }

        if (!empty($criteria->agentId)) {
            $query->where("wg_customer_project_agent.agent_id", $criteria->agentId);
        }

        if ($criteria->customerId) {
            $query->where("wg_customer_project_agent.customer_id", $criteria->customerId);
        }

        return $query->first();
    }

    public function getSummaryChartPie($criteria)
    {
        $resultQSummary = $this->getSummary($criteria);

        $data = [
            $this->parseChartPieData("Programadas", $resultQSummary ? $resultQSummary->scheduledHours : 0),
            $this->parseChartPieData("Ejecutadas", $resultQSummary ? $resultQSummary->runningHours : 0)
        ];

        return $this->chart->getChartPie($data);
    }

    public function getList($criteria)
    {
        $qAgentTask = $this->prepareQueryAgentTask();

        $query = DB::table("wg_customer_project")
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_project.customer_id');
            })
            ->join("wg_customer_project_agent", function ($join) {
                $join->on('wg_customer_project_agent.project_id', '=', 'wg_customer_project.id');
            })
            ->join("wg_agent", function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_project_agent.agent_id');
            })
            ->join("users", function ($join) {
                $join->on('users.id', '=', 'wg_agent.user_id');
            })
            ->join("users as users2", function ($join) {
                $join->on('users2.id', '=', 'wg_customer_project.createdBy');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable("project_type")), function ($join) {
                $join->on('project_type.value', '=', 'wg_customer_project.type');
            })
            ->leftJoin('wg_customer_project_agent_task as wg_customer_project_agent_stats', 'wg_customer_project_agent_stats.project_agent_id', '=', 'wg_customer_project_agent.id')
            ->select(
                'wg_customer_project.id',
                'wg_customer_project.name',
                'wg_customer_project.description',
                'wg_customer_project.estimatedHours',
                'wg_customer_project.type',
                'wg_customer_project.serviceOrder',
                'wg_customer_project.isBilled',
                'wg_customer_project.invoiceNumber',
                'project_type.item AS typeDescription',

                'wg_customers.id AS customerId',
                'wg_customers.businessName AS customerName',

                'wg_agent.name AS agentName',
                'users.email',

                'wg_customer_project_agent.id AS projectAgentId',

                'users2.name as administrator',
                'wg_customer_project.deliveryDate',

                DB::raw("ROUND(IFNULL(wg_customer_project_agent.estimatedHours, 0),0) AS assignedHours"),
                DB::raw("ROUND(IFNULL(sum(if(wg_customer_project_agent_stats.status = 'activo', duration, 0)), 0),0) + ROUND(IFNULL(sum(if(wg_customer_project_agent_stats.status = 'inactivo', duration, 0)), 0),0) AS scheduledHours"),
                DB::raw("ROUND(IFNULL(sum(if(wg_customer_project_agent_stats.status = 'inactivo', duration, 0)), 0),0) AS runningHours")
            )
            ->whereMonth('wg_customer_project.deliveryDate', '=', $criteria->month ? $criteria->month : Carbon::now('America/Bogota')->month)
            ->whereYear('wg_customer_project.deliveryDate', '=', $criteria->year ? $criteria->year : Carbon::now('America/Bogota')->year);


        if ($criteria->type == 'agent') {
            $this->run();
            $criteria->agentId = !empty($criteria->agentId) ? $criteria->agentId : $this->isUserRelatedAgent();
        } else if ($criteria->type = 'customerAdmin' || $criteria->type = 'customerUser') {
            $this->run();
            $criteria->customerId = $criteria->customerId ? $criteria->customerId : $this->getUserRelatedCustomer();
        }

        if (!empty($criteria->agentId)) {
            $query->where("wg_customer_project_agent.agent_id", $criteria->agentId);
        }

        if ($criteria->customerId) {
            $query->where("wg_customer_project.customer_id", $criteria->customerId);
        }

        if ($criteria->arl) {
            $query->where("wg_customers.arl", $criteria->arl);
        }

        if ($criteria->odes) {
            $query->where("wg_customer_project.serviceOrder", $criteria->odes);
        }

        if ($criteria->projectType) {
            $query->where("wg_customer_project.type", $criteria->projectType);
        }

        if ($criteria->isBilled) {
            $query->whereRaw("(wg_customer_project.isBilled IS NULL OR wg_customer_project.isBilled = ?)", [$criteria->isBilled]);
        }

        if ($criteria->administrator) {
            $query->where("wg_customer_project.createdBy", $criteria->administrator);
        }

        $query->groupBy('wg_customer_project.id');

        return array_map(function ($row) {
            $chart = [
                $this->parseChartPieData("Programadas", $row ? $row->scheduledHours : 0),
                $this->parseChartPieData("Ejecutadas", $row ? $row->runningHours : 0)
            ];

            $row->chart = $this->chart->getChartPie($chart);

            return $row;
        }, $query->get()->toArray());
    }

    private function prepareQuerySummaryBase($criteria)
    {
        $query = DB::table('wg_customer_project')
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_project.customer_id');
            })
            ->join("wg_customer_project_agent", function ($join) {
                $join->on('wg_customer_project_agent.project_id', '=', 'wg_customer_project.id');
            })
            ->leftJoin('wg_customer_project_agent_task as pat', 'pat.project_agent_id', '=', 'wg_customer_project_agent.id')
            ->leftJoin('users', 'users.id', '=', 'wg_customer_project.createdBy')
            ->groupBy('wg_customer_project_agent.id')
            ->select(
                'wg_customer_project.id',
                'wg_customer_project_agent.agent_id',
                'wg_customers.id AS customer_id',
                'wg_customer_project_agent.id AS project_agent_id',
                DB::raw("sum(CASE WHEN pat.status = 'activo' THEN pat.duration ELSE 0 END) as planned"),
                DB::raw("sum(CASE WHEN pat.status = 'inactivo' THEN pat.duration ELSE 0 END) as executed"),
                'wg_customer_project_agent.estimatedHours',
                'users.name as createdBy'
            )
            ->whereMonth('wg_customer_project.deliveryDate', '=', $criteria->month ? $criteria->month : Carbon::now('America/Bogota')->month)
            ->whereYear('wg_customer_project.deliveryDate', '=', $criteria->year ? $criteria->year : Carbon::now('America/Bogota')->year);

        if ($criteria->arl) {
            $query->where("wg_customers.arl", $criteria->arl);
        }

        if ($criteria->odes) {
            $query->where("wg_customer_project.serviceOrder", $criteria->odes);
        }

        if ($criteria->projectType) {
            $query->where("wg_customer_project.type", $criteria->projectType);
        }

        if ($criteria->administrator) {
            $query->where("wg_customer_project.createdBy", $criteria->administrator);
        }

        return $query;
    }

    private function prepareQueryAgentTask()
    {
        return DB::table('wg_customer_project_agent_task')
            ->select(
                'id',
                'project_agent_id',
                DB::raw("SUM( CASE WHEN `status` = 'activo' THEN (TIME_TO_SEC(TIMEDIFF(endDateTime, startDateTime)) / 60) / 60 ELSE 0 END) planned"),
                DB::raw("SUM( CASE WHEN `status` = 'inactivo' THEN (TIME_TO_SEC(TIMEDIFF(endDateTime, startDateTime)) / 60) / 60 ELSE 0 END) executed")
            )
            ->groupBy('project_agent_id');
    }

    private function parseChartPieData($label, $value)
    {
        $data = new \stdClass();
        $data->label = $label;
        $data->value = $value;
        return $data;
    }


    public function getContributationsVsExecutionsChartPie($criteria)
    {
        $executions = DB::table('wg_customer_project as pr')
            ->join('wg_customer_project_costs as c', function($join) {
                $join->on('c.project_id', '=', 'pr.id');
                $join->whereRaw("c.status = 'SS002' ");
            })
            ->where('pr.customer_id', $criteria->customerId)
            ->whereYear('pr.deliveryDate', $criteria->year)
            ->whereRaw("( pr.type = 'Intm' OR 
                         (pr.type = 'SYL' AND c.concept = 'PCOS014') OR 
                         (pr.type = 'RV'  AND c.concept = 'C03')
                        )")
            ->select(DB::raw("SUM(c.total_price) AS total"))
            ->first();

        $available = DB::table('wg_customer_arl_contribution as con')
            ->where('con.customer_id', $criteria->customerId)
            ->where('con.year', $criteria->year)
            ->select(
                DB::raw("SUM( (input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100 ) AS available")
            )
            ->first();

        $data = [
            $this->parseChartPieData("Reinversión WG", $available->available),
            $this->parseChartPieData("Ejecutadas", $executions->total)
        ];

        return $this->chart->getChartPie($data);
    }


    public function getContributationsVsExecutionsChartLineByMonth($criteria) {

        $subquery = DB::table("wg_customer_project AS p")
            ->join('wg_customer_project_costs as c', function($join) {
                $join->on('c.project_id', '=', 'p.id');
                $join->whereRaw("c.status = 'SS002' ");
            })
            ->where('p.customer_id', $criteria->customerId)
            ->whereRaw("YEAR(p.deliveryDate) = $criteria->year")
            ->whereRaw("( p.type = 'Intm' OR 
                         (p.type = 'SYL' AND c.concept = 'PCOS014') OR 
                         (p.type = 'RV'  AND c.concept = 'C03')
                        )")
            ->groupBy(DB::raw('MONTH(p.deliveryDate)'))
            ->select(
                DB::raw("'Ejecuciones por año' AS label"),
                DB::raw('MONTH(p.deliveryDate) AS month'),
                DB::raw('SUM(total_price) AS total')
            );

        $data = DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('d.label')
            ->select(
                'd.label',
                DB::raw("MAX(CASE when d.month = 1 then d.total END) AS 'JAN'"),
                DB::raw("MAX(CASE when d.month = 2 then d.total END) AS 'FEB'"),
                DB::raw("MAX(CASE when d.month = 3 then d.total END) AS 'MAR'"),
                DB::raw("MAX(CASE when d.month = 4 then d.total END) AS 'APR'"),
                DB::raw("MAX(CASE when d.month = 5 then d.total END) AS 'MAY'"),
                DB::raw("MAX(CASE when d.month = 6 then d.total END) AS 'JUN'"),
                DB::raw("MAX(CASE when d.month = 7 then d.total END) AS 'JUL'"),
                DB::raw("MAX(CASE when d.month = 8 then d.total END) AS 'AUG'"),
                DB::raw("MAX(CASE when d.month = 9 then d.total END) AS 'SEP'"),
                DB::raw("MAX(CASE when d.month = 10 then d.total END) AS 'OCT'"),
                DB::raw("MAX(CASE when d.month = 11 then d.total END) AS 'NOV'"),
                DB::raw("MAX(CASE when d.month = 12 then d.total END) AS 'DEC'")
            )
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }


    public function getAllYearsContributations($customerId) {
        return DB::table('wg_customer_arl_contribution')
            ->where('customer_id', $customerId)
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->select('year')
            ->get()
            ->toArray();
    }

    public function getAllUserAdministrators(int $year, int $month, $type, $customerId) {
        if ($type = 'customerAdmin' || $type = 'customerUser') {
            $this->run();
            $customerId = $customerId ?: $this->getUserRelatedCustomer();
        }

        return DB::table('wg_customer_project as p')
            ->join('users as u', 'u.id', '=', 'p.createdBy')
            ->whereYear('deliveryDate', $year)
            ->whereMonth('deliveryDate', $month)
            ->when($customerId, function ($query) use ($customerId) {
                $query->where('p.customer_id', $customerId);
            })
            ->groupBy('u.id')
            ->orderBy('u.name')
            ->select('u.id as value', 'u.name as item')
            ->get()
            ->toArray();
    }

}
