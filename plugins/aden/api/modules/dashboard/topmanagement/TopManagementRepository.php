<?php

namespace AdeN\Api\Modules\Dashboard\TopManagement;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CriteriaHelper;
use Wgroup\SystemParameter\SystemParameter;

use AdeN\Api\Modules\Dashboard\TopManagement\Services\CostByMonth;

class TopManagementRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new TopManagementModel());
        $this->service = new TopManagementService();
    }

    public function all($criteria)
    {
        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate')->value ?? null;
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate')->value ?? now();
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');

        $this->setColumns([
            "id" => "d.agentId as id",
            "consultant" => "d.consultant as consultant",
            "availability" => "d.availability as availability",
            "assigned" => "d.assigned",
            "executed" => "d.executed",
            "percentCompliance" => "d.percentCompliance",
            "levelCompliance" => "sp.item as levelCompliance",
            "customerId" => "d.customer_id"
        ]);

        $this->parseCriteria($criteria);

        $queryByProject = $this->getQueryProject($startDate, $endDate, $customerId);
        $queryByTasks   = $this->getQueryTasks($startDate, $endDate, $customerId);

        $subQuery = $queryByProject->union($queryByTasks);

        $subQuery2 = DB::table(DB::raw("({$subQuery->toSql()}) as d"))
            ->mergeBindings($subQuery)
            ->join('wg_agent as ag', 'ag.id', '=', 'd.agent_id')
            ->groupBy('agent_id')
            ->select(
                'ag.id as agentId',
                'ag.name as consultant',
                'ag.availability as availability',
                'd.customer_id',
                DB::raw("SUM(estimated_hours) as assigned"),
                DB::raw("SUM(hours) as executed"),
                DB::raw("ROUND( ( SUM(hours) / SUM(estimated_hours) * 100), 2) as percentCompliance")
            );

        $masterQuery = DB::table(DB::raw("({$subQuery2->toSql()}) as d"))->mergeBindings($subQuery2);

        $query = $this->query($masterQuery)
            ->join(DB::raw(SystemParameter::getRelationTable('project_performance_level', 'sp')), function ($join) {
                $join->whereRaw("d.percentCompliance >= sp.value");
                $join->whereRaw("if(sp.code is null, true, d.percentCompliance < sp.code)");
            });

        $this->applyCriteria($query, $criteria, ['startDate', 'endDate', 'customerId']);
        return $this->get($query, $criteria);
    }


    private function getQueryProject($startDate, $endDate, $customerId)
    {
        $subquery = DB::table('wg_customer_project_agent_consolidate')
            ->join('wg_customer_project', 'wg_customer_project.id', '=', 'wg_customer_project_agent_consolidate.project_id')
            ->whereRaw("DATE(wg_customer_project_agent_consolidate.delivery_date_project) BETWEEN '$startDate' AND '$endDate' ")
            ->when($customerId, function($query) use ($customerId) {
                return $query->whereRaw("wg_customer_project.customer_id = {$customerId->value}");
            })
            ->groupBy('wg_customer_project_agent_consolidate.project_id', 'wg_customer_project_agent_consolidate.agent_id')
            ->select(
                'wg_customer_project.customer_id',
                'wg_customer_project_agent_consolidate.project_id',
                'wg_customer_project_agent_consolidate.agent_id',
                DB::raw('MAX(wg_customer_project_agent_consolidate.estimated_hours) as estimated_hours')
            );

        return DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->groupBy('agent_id')
            ->select('customer_id', 'project_id', 'agent_id', DB::raw('SUM(estimated_hours) as estimated_hours'), DB::raw("0 as hours"));
    }

    private function getQueryTasks($startDate, $endDate, $customerId)
    {
        return DB::table('wg_customer_project_agent_consolidate')
            ->join('wg_customer_project', 'wg_customer_project.id', '=', 'wg_customer_project_agent_consolidate.project_id')
            ->whereRaw("wg_customer_project_agent_consolidate.status = 'inactivo' ")
            ->whereRaw("DATE(wg_customer_project_agent_consolidate.delivery_date_project) BETWEEN '$startDate' AND '$endDate' ")
            ->when($customerId, function($query) use ($customerId) {
                return $query->whereRaw("wg_customer_project.customer_id = {$customerId->value}");
            })
            ->groupBy('wg_customer_project_agent_consolidate.project_id', 'wg_customer_project_agent_consolidate.agent_id')
            ->select(
                'wg_customer_project.customer_id',
                'wg_customer_project_agent_consolidate.project_id',
                'wg_customer_project_agent_consolidate.agent_id',
                DB::raw("0 AS estimated_hours"),
                DB::raw('SUM(wg_customer_project_agent_consolidate.duration) as hours')
            );
    }


    public function consolidate()
    {
        $this->service->consolidate();
    }

    public function getChartLineCostHistorical($criteria)
    {
        return $this->service->getChartLineCostHistorical($criteria);
    }

    public function getChartBarCostByMonths($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator)
    {
        return $this->service->getChartBarCostByMonths($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator);
    }

    public function getChartBarCostByType($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator)
    {
        $column = 'type';
        $parameter = 'project_type';
        return $this->service->getChartBarCostByParam($column, $parameter, $startDate, $endDate, $type, $concept, $classification, $customerId, $administrator);
    }

    public function getChartBarCostByConcept($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator)
    {
        $column = 'concept';
        $parameter = 'project_concepts';
        return $this->service->getChartBarCostByParam($column, $parameter, $startDate, $endDate, $type, $concept, $classification, $customerId, $administrator);
    }

    public function getChartBarCostByClassification($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator)
    {
        $column = 'classification';
        $parameter = 'project_classifications';
        return $this->service->getChartBarCostByParam($column, $parameter, $startDate, $endDate, $type, $concept, $classification, $customerId, $administrator);
    }

    public function getChartBarExperiencesByMonths($startDate, $endDate, $customerId)
    {
        return $this->service->getChartBarExperiencesByMonths($startDate, $endDate, $customerId);
    }

    public function getChartLinePerformanceByConsultant($startDate, $endDate, $customerId)
    {
        return $this->service->getChartLinePerformanceByConsultant($startDate, $endDate, $customerId);
    }

    public function getCalendar($types, $year)
    {
        return $this->service->getCalendar($types, $year);
    }

    public function getKPITotalCosts($criteria)
    {
        return $this->service->getKPITotalCosts($criteria);
    }

    public function getChartBarStackedTypeSalesByStates($criteria)
    {
        return $this->service->getChartBarStackedTypeSalesByStates($criteria);
    }


    public function getHistoricalCosts($criteria)
    {
        $this->setColumns([
            'year' => 'year',
            'sst' => DB::raw("FORMAT(SUM(CASE WHEN label = 'SST' THEN total ELSE 0 END), 2) AS sst"),
            'vr' => DB::raw("FORMAT(SUM(CASE WHEN label = 'Realidad Virtual' THEN total ELSE 0 END), 2) AS vr"),
            'sylogi' => DB::raw("FORMAT(SUM(CASE WHEN label = 'SYLOGI' THEN total ELSE 0 END), 2) AS sylogi"),
            'total' => DB::raw("FORMAT(SUM(total), 2) as total")
        ]);

        $query = $this->service->getHistoricalCosts();

        $this->parseCriteria($criteria);
        $this->applyCriteria($query, $criteria);

        $query = $this->query($query);
        return $this->get($query, $criteria);
    }


    public function getCustomers($criteria)
    {
        $this->setColumns([
            'id' => 'c.id',
            'documentType' => 'td.item as documentType',
            'documentNumber' => 'c.documentNumber',
            'businessName' => 'c.businessName',
            'type' => 'tc.item as type',
            'classification' => 'clas.item as classification',
            'status' => 'status.item as status',
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query(DB::table('wg_customer_project as p'))
            //->join('wg_customer_project_costs as pc', 'pc.project_id', '=', 'p.id')
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

        $this->applyCriteria($query, $criteria);


        return $this->get($query, $criteria);
    }


    public function getAdministrators($criteria)
    {
        $this->setColumns([
            'id' => 'u.id',
            'name' => 'u.name',
            'customerId' => 'p.customer_id as customerId'
        ]);

        $query = $this->service->getAdministrators();

        $this->parseCriteria($criteria);
        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function getPeriodList()
    {
        return $this->service->getPeriodList();
    }


    public function getTotalSales($criteria)
    {
        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate')->value ?? null;
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate')->value;
        $endDate = Carbon::parse($endDate)->endOfDay();

        $this->setColumns([
            'period' => DB::raw("CONCAT(YEAR(deliveryDate), '-', sp.item) as period"),
            'programmed' => DB::raw("FORMAT(SUM(total), 2) AS programmed"),
            'executed' => DB::raw("FORMAT(SUM(total_executed), 2) AS executed"),
            'balance' => DB::raw("FORMAT(SUM(total_executed) - SUM(total), 2)  AS balance"),
            'type' => 'c.type',
            'concept' => 'c.concept',
            'classification' => 'c.classification',
            'customer' => 'c.customer_id as customer',
            'administrator' => 'c.administrator',
            'deliveryDate' => 'deliveryDate'
        ]);

        $this->parseCriteria($criteria);
        $this->addSortColumn('deliveryDate');

        $query = CostByMonth::getQueryGrid();

        $this->query($query)
            ->whereDate('c.deliveryDate', '>=', $startDate)
            ->whereDate('c.deliveryDate', '<=', $endDate);

        $this->applyCriteria($query, $criteria, ['startDate', 'endDate']);
        return $this->get($query, $criteria);
    }


    public function getSalesByType($criteria)
    {
        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate')->value ?? null;
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate')->value ?? now();

        $this->setColumns([
            'period' => DB::raw("CONCAT(YEAR(deliveryDate), '-', sp.item) as period"),
            'group' => "pt.item as group",
            'sales' => DB::raw("FORMAT(SUM(total_executed), 2)  AS sales"),
            'type' => 'c.type',
            'concept' => 'c.concept',
            'classification' => 'c.classification',
            'customer' => 'c.customer_id as customer',
            'administrator' => 'c.administrator',
            'deliveryDate' => 'deliveryDate'
        ]);

        $this->parseCriteria($criteria);
        $this->addSortColumn('deliveryDate');

        $query = $this->service->getBaseQuerySalesByParam('type', 'project_type');

        $this->query($query)
            ->where('total_executed', '>', '0')
            ->whereDate('c.deliveryDate', '>=', $startDate)
            ->whereDate('c.deliveryDate', '<=', $endDate);

        $this->applyCriteria($query, $criteria, ['startDate', 'endDate']);
        return $this->get($query, $criteria);
    }


    public function getSalesByConcept($criteria)
    {
        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate')->value ?? null;
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate')->value ?? now();

        $this->setColumns([
            'period' => DB::raw("CONCAT(YEAR(deliveryDate), '-', sp.item) as period"),
            'group' => "pt.item as group",
            'sales' => DB::raw("FORMAT(SUM(total_executed), 2)  AS sales"),
            'type' => 'c.type',
            'concept' => 'c.concept',
            'classification' => 'c.classification',
            'customer' => 'c.customer_id as customer',
            'administrator' => 'c.administrator',
            'deliveryDate' => 'deliveryDate'
        ]);

        $this->parseCriteria($criteria);
        $this->addSortColumn('deliveryDate');

        $query = $this->service->getBaseQuerySalesByParam('concept', 'project_concepts');

        $this->query($query)
            ->where('total_executed', '>', '0')
            ->whereDate('c.deliveryDate', '>=', $startDate)
            ->whereDate('c.deliveryDate', '<=', $endDate);

        $this->applyCriteria($query, $criteria, ['startDate', 'endDate']);
        return $this->get($query, $criteria);
    }


    public function getSalesByClassification($criteria)
    {
        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate')->value ?? null;
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate')->value ?? now();

        $this->setColumns([
            'period' => DB::raw("CONCAT(YEAR(deliveryDate), '-', sp.item) as period"),
            'group' => "pt.item as group",
            'sales' => DB::raw("FORMAT(SUM(total_executed), 2)  AS sales"),
            'type' => 'c.type',
            'concept' => 'c.concept',
            'classification' => 'c.classification',
            'customer' => 'c.customer_id as customer',
            'administrator' => 'c.administrator',
            'deliveryDate' => 'deliveryDate'
        ]);

        $this->parseCriteria($criteria);
        $this->addSortColumn('deliveryDate');

        $query = $this->service->getBaseQuerySalesByParam('classification', 'project_classifications');

        $this->query($query)
            ->where('total_executed', '>', '0')
            ->whereDate('c.deliveryDate', '>=', $startDate)
            ->whereDate('c.deliveryDate', '<=', $endDate);

        $this->applyCriteria($query, $criteria, ['startDate', 'endDate']);
        return $this->get($query, $criteria);
    }


    public function getExperienciesByMonths($criteria)
    {
        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate')->value ?? null;
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate')->value ?? now();

        $this->setColumns([
            'period' => DB::raw("CONCAT(YEAR(date), '-', m.item) AS period"),
            'experience' => "sp.item as experience",
            'sales' => DB::raw("SUM(total) as sales"),
            'date' => 'date',
            'customer' => 'customer_id'
        ]);

        $this->parseCriteria($criteria);
        $this->addSortColumn('date');

        $query = $this->service->getBaseQueryExperienceByMonths($startDate, $endDate, null);
        $query = $this->query($query);

        $this->applyCriteria($query, $criteria, ['startDate', 'endDate']);
        return $this->get($query, $criteria);
    }


    public function amountBySatisfactionGrid($criteria)
    {
        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate')->value ?? null;
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate')->value ?? now();

        $this->setColumns([
            'period' => DB::raw("CONCAT(YEAR(date_register), '-', m.item) AS period"),
            'experience' => "cp.value as experience",
            'muy_malo' => DB::raw("COUNT(IF(r.answer_id = 1, 1, NULL)) AS muy_malo"),
            'malo' => DB::raw("COUNT(IF(r.answer_id = 2, 1, NULL)) AS malo"),
            'regular' => DB::raw("COUNT(IF(r.answer_id = 3, 1, NULL)) AS regular"),
            'bueno' => DB::raw("COUNT(IF(r.answer_id = 4, 1, NULL)) AS bueno"),
            'excelente' => DB::raw("COUNT(IF(r.answer_id = 5, 1, NULL)) AS excelente"),
            'date' => 'date_register as date',
            'customer' => 'r.customer_id'
        ]);

        $this->parseCriteria($criteria);
        $this->addSortColumn('date');

        $query = $this->service->getQueryAmountBySatisfactionGrid($startDate, $endDate);
        $query = $this->query($query);

        $this->applyCriteria($query, $criteria, ['startDate', 'endDate']);

        return $this->get($query, $criteria);
    }



    public function getRegisteredVsParticipants($criteria)
    {
        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate')->value ?? null;
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate')->value ?? now();
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customer')->value ?? null;

        $this->setColumns([
            'period' => 'period',
            'amountParticipants' => DB::raw("SUM(CASE WHEN label = 'Participantes' THEN total ELSE 0 END) AS amountParticipants"),
            'amountSurveyed' => DB::raw("SUM(CASE WHEN label = 'Encuestados' THEN total ELSE 0 END) AS amountSurveyed"),
            'date' => 'date',
            'customer' => 'customer_id'
        ]);

        $this->parseCriteria($criteria);
        $this->addSortColumn('date');

        $query = $this->service->getQueryRegisterVsParticipants($startDate, $endDate, $customerId);
        $query = $this->query($query);

        $this->applyCriteria($query, $criteria, ['startDate', 'endDate', 'customer']);
        return $this->get($query, $criteria);
    }


    public function getPerformanceByConsultant($criteria)
    {
        $startDate = CriteriaHelper::getMandatoryFilter($criteria, 'startDate')->value ?? null;
        $endDate = CriteriaHelper::getMandatoryFilter($criteria, 'endDate')->value ?? now();

        $this->setColumns([
            'consultant' => 'consultant',
            'assigned' => 'assigned',
            'executed' => "executed",
            'balance' => DB::raw("SUM(executed) - SUM(assigned) as balance"),
            'customer' => 'customer_id'
        ]);

        $this->parseCriteria($criteria);

        $query = $this->service->getQueryPerformanceByConsultantGrid($startDate, $endDate);
        $query = $this->query($query);

        $this->applyCriteria($query, $criteria, ['startDate', 'endDate']);
        return $this->get($query, $criteria);
    }


    public function getProgrammedVsExecutedSales($criteria)
    {
        $this->setColumns([
            'year' => 'label as year',
            'vrProgrammed' => DB::raw("FORMAT( SUM(CASE WHEN stack = 'Realidad virtual' THEN programmed ELSE 0 END) , 2) as vrProgrammed"),
            'vrExecute' => DB::raw("FORMAT( SUM(CASE WHEN stack = 'Realidad virtual' THEN executed ELSE 0 END) , 2) as vrExecute"),
            'vrBalance' => DB::raw("FORMAT( SUM(CASE WHEN stack = 'Realidad virtual' THEN balance  ELSE 0 END) , 2) as vrBalance"),
            'sstProgrammed' => DB::raw("FORMAT( SUM(CASE WHEN stack = 'SST' THEN programmed ELSE 0 END) , 2) as sstProgrammed"),
            'sstExecute' => DB::raw("FORMAT( SUM(CASE WHEN stack = 'SST' THEN executed ELSE 0 END) , 2) as sstExecute"),
            'sstBalance' => DB::raw("FORMAT( SUM(CASE WHEN stack = 'SST' THEN balance  ELSE 0 END) , 2) as sstBalance"),
            'sylProgrammed' => DB::raw("FORMAT( SUM(CASE WHEN stack = 'Sylogi' THEN programmed ELSE 0 END) , 2) as sylProgrammed"),
            'sylExecute' => DB::raw("FORMAT( SUM(CASE WHEN stack = 'Sylogi' THEN executed ELSE 0 END) , 2) as sylExecute"),
            'sylBalance' => DB::raw("FORMAT( SUM(CASE WHEN stack = 'Sylogi' THEN balance  ELSE 0 END) , 2) as sylBalance"),
        ]);

        $this->parseCriteria($criteria);

        $query = $this->service->getProgrammedVsExecutedSales();

        $query = $this->query(DB::table(DB::raw("({$query->toSql()}) as o ")))
            ->mergeBindings($query)
            ->groupBy('label');

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    // public function getChartBarSalesProgrammedVsExecutedByYear($startDate, $endDate)
    // {
    //     return $this->service->getChartBarSalesProgrammedVsExecutedByYear($startDate, $endDate);
    // }
}
