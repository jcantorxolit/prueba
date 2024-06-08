<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312\CustomerEvaluationMinimumStandardTracking0312Repository;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Classes\SnappyPdfOptions;
use AdeN\Api\Helpers\CriteriaHelper;
use Wgroup\SystemParameter\SystemParameter;

class CustomerEvaluationMinimumStandard0312Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerEvaluationMinimumStandard0312Model());

        $this->service = new CustomerEvaluationMinimumStandard0312Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_evaluation_minimum_standard_0312.id",
            "unique" => "wg_customer_evaluation_minimum_standard_0312.id AS unique",
            "period" => "wg_customer_evaluation_minimum_standard_0312.period",
            "createdAt" => "wg_customer_evaluation_minimum_standard_0312.created_at",
            "status" => "wg_customer_em_0312_status.item AS status",
            "statusCode" => "wg_customer_evaluation_minimum_standard_0312.status as statusCode",
            "customerId" => "wg_customer_evaluation_minimum_standard_0312.customer_id",
            'canMigrate' => DB::raw("CASE WHEN (wg_customer_evaluation_minimum_standard_item_0312.answers IS NULL OR wg_customer_evaluation_minimum_standard_item_0312.answers = 0) AND `wg_customer_evaluation_minimum_standard_0312`.`status` = 'A' THEN 1 ELSE 0 END canMigrate")
        ]);

        $this->parseCriteria($criteria);

        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join('wg_config_minimum_standard_rate_0312', function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                DB::raw('COUNT(*) questions'),
                DB::raw('CASE WHEN wg_config_minimum_standard_rate_0312.`code` IS NOT NULL THEN 1 ELSE 0 END answers')
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->groupBy('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');

        $query = $this->query();

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_em_0312_status')), function ($join) {
            $join->on('wg_customer_em_0312_status.value', '=', 'wg_customer_evaluation_minimum_standard_0312.status');
        })->leftjoin(DB::raw("({$qDetail->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id', '=', 'wg_customer_evaluation_minimum_standard_0312.id');
        })->mergeBindings($qDetail);

        $this->applyCriteria($query, $criteria);

        $data = $this->get($query, $criteria);

        $data['canCreate'] = $this->service->canCreate($criteria);

        $count = count($data['data']);

        $result = array_map(function ($item) use ($count) {
            $item->canMigrate = $count > 1 ? $item->attributes['canMigrate']  : 0;
            if ($item->canMigrate == 1) {
                $item->migrateFromId = $this->service->getMigrateFromId($item->period, $item->customerId);
            }
            return $item;
        }, $data['data']);

        $data['data'] = $result;

        return $data;
    }

    public function allSummary($criteria)
    {
        $this->setColumns([
            "name" => "wg_customer_evaluation_minimum_standard_0312.name",
            "description" => "wg_customer_evaluation_minimum_standard_0312.description",
            "items" => "wg_customer_evaluation_minimum_standard_0312.items",
            "checked" => "wg_customer_evaluation_minimum_standard_0312.checked",
            "advance" => DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.checked / wg_customer_evaluation_minimum_standard_0312.items) * 100, 0) ,2) AS advance"),
            "total" => DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
            "id" => "wg_customer_evaluation_minimum_standard_0312.id",
            "abbreviation" => "wg_customer_evaluation_minimum_standard_0312.abbreviation",
            "average" => DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.total / wg_customer_evaluation_minimum_standard_0312.items), 0), 2) AS average"),
        ]);

        foreach ($criteria->sorts as $sort) {
            if (property_exists($sort, "column")) {
                if ($sort->column == 0) {
                    $sort->column = 6;
                    $sort->dir = "asc";
                }
            }
        }

        $this->parseCriteria($criteria);


        $q1 = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            })            
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )            
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);


        $q2 = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo');


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerId") {
                        $q1->where(SqlHelper::getPreparedField('wg_customers.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');                        
                    } else if ($item->field == "customerEvaluationMinimumStandardId") {
                        $q2->where(SqlHelper::getPreparedField('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $sQuery = DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description'
            );

        $query = $this->query(DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_evaluation_minimum_standard_0312")))
            ->mergeBindings($sQuery);

        $this->applyCriteria($query, $criteria, ['customerEvaluationMinimumStandardId', 'customerId']);

        return $this->get($query, $criteria);
    }

    public function allSummaryClosed($criteria)
    {
        $this->setColumns([
            "name" => "wg_customer_evaluation_minimum_standard_0312.name",
            "description" => "wg_customer_evaluation_minimum_standard_0312.description",
            "items" => "wg_customer_evaluation_minimum_standard_0312.items",
            "checked" => "wg_customer_evaluation_minimum_standard_0312.checked",
            "advance" => DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.checked / wg_customer_evaluation_minimum_standard_0312.items) * 100, 0) ,2) AS advance"),
            "total" => DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
            "id" => "wg_customer_evaluation_minimum_standard_0312.id",
            "abbreviation" => "wg_customer_evaluation_minimum_standard_0312.abbreviation",
            "average" => DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.total / wg_customer_evaluation_minimum_standard_0312.items), 0), 2) AS average"),
        ]);

        foreach ($criteria->sorts as $sort) {
            if (property_exists($sort, "column")) {
                if ($sort->column == 0) {
                    $sort->column = 6;
                    $sort->dir = "asc";
                }
            }
        }

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })                    
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);


        $q2 = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->leftjoin("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.is_freezed', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo');


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerEvaluationMinimumStandardId") {
                        $q2->where(SqlHelper::getPreparedField('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $sQuery = DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->join(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.code) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description'
            );

        $query = $this->query(DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_evaluation_minimum_standard_0312")))
            ->mergeBindings($sQuery);

        $this->applyCriteria($query, $criteria, ['customerEvaluationMinimumStandardId', 'customerId']);

        return $this->get($query, $criteria);
    }

    public function getLastId($customerId)
    {
        try {
            if ($this->model->whereCustomerId($customerId)->count() == 0) {
                $model = $this->create($customerId);
                return $model ? $model->id : 0;
            } else {
                return $this->model->whereCustomerId($customerId)->whereStatus('iniciado')->max('id');
            }
        } catch (Exception $ex) {
            Log::error($ex);
            return 0;
        }
    }

    public function executeBulkOperations($id)
    {
        $model = $this->find($id);
        if ($model) {
            //Update the current configuration for the minimum standard just for status A
            if ($model->status == 'A') {
                $this->service->bulkInsertMinimumStandardItems($model);
                $this->service->bulkUpdateMinimumStandardItems($model);
                $this->service->bulkDeleteMinimumStandardItems($model);
            }
            CustomerEvaluationMinimumStandardTracking0312Repository::createMissingMonthlyReport($model->id);
        }
    }

    public static function executeChangePeriod()
    {
        DB::transaction(function () {
            $repository = new self;
            $repository->service->bulkCancelMinimunStandardImprovementPlanActionPlanTask();
            $repository->service->bulkInsertMinimunStandardImprovementPlanActionPlanComment();
            $repository->service->bulkCancelMinimunStandardImprovementPlanActionPlan();
            $repository->service->bulkInsertMinimunStandardImprovementPlanComment();
            $repository->service->bulkCancelMinimunStandardImprovementPlan();
            $repository->service->bulkCancelMinimunStandard();
            $repository->service->bulkInsertMinimumStandardChangePeriod();
            $repository->service->bulkInsertMinimumStandardItemsChangePeriod();
        });
    }

    public function create($customerId)
    {
        $entityModel = new \stdClass();

        $entityModel->id = 0;
        $entityModel->customerId = $customerId;
        $entityModel->startDate = null;
        $entityModel->endDate = null;
        $entityModel->status = "A";
        $entityModel->type = "EM";
        $entityModel->description = "Auto Evaluación";
        $entityModel->period = Carbon::now('America/Bogota')->year;

        return $this->insertOrUpdate($entityModel);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->startDate = $entity->startDate ? Carbon::parse($entity->startDate)->timezone('America/Bogota') : null;
        $entityModel->endDate = $entity->endDate ? Carbon::parse($entity->endDate)->timezone('America/Bogota') : null;
        $entityModel->status = $entity->status;
        $entityModel->type = $entity->type;
        $entityModel->description = $entity->description;
        $entityModel->period = $entity->period;


        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entityModel;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->isDeleted = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->startDate = $model->startDate;
            $entity->endDate = $model->endDate;
            $entity->status = $model->getStatus();
            $entity->type = $model->getType();
            $entity->description = $model->description;
            $entity->createdAt = $model->createdAt;
            $entity->createdBy = $model->createdBy;
            $entity->updatedAt = $model->updatedAt;
            $entity->updatedBy = $model->updatedBy;


            return $entity;
        } else {
            return null;
        }
    }


    public function getChartBar($criteria)
    {
        return $this->service->getChartBar($criteria);
    }

    public function getChartPie($criteria)
    {
        return $this->service->getChartPie($criteria);
    }

    public function getChartStatus($criteria)
    {
        return $this->service->getChartStatus($criteria);
    }

    public function getChartAverage($criteria)
    {
        return $this->service->getChartAverage($criteria);
    }

    public function getChartTotal($criteria)
    {
        return $this->service->getChartTotal($criteria);
    }

    public function getChartAdvance($criteria)
    {
        return $this->service->getChartAdvance($criteria);
    }

    public function getStats($criteria)
    {
        return $this->service->getStats($criteria);
    }

    public function getCycles($criteria)
    {
        return $this->service->getCycles($criteria);
    }

    public function getYears($criteria)
    {
        return $this->service->getYears($criteria);
    }

    public function getReport($criteria)
    {
        return $this->service->getReport($criteria);
    }

    public function getParent($criteria)
    {
        return $this->service->getParent($criteria);
    }

    public function getChildren($criteria)
    {
        return $this->service->getChildren($criteria);
    }

    public function getItems($criteria)
    {
        return $this->service->getItems($criteria);
    }

    public function exportSummaryExcel($criteria)
    {
        $entity = $this->find($criteria->customerEvaluationMinimumStandardId);
        if ($entity == null || $entity->status == 'A') {
            $data = $this->service->getExportSummaryData($criteria);
        } else {
            $data = $this->service->getExportSummaryDataClosed($criteria);
        }
        $filename = 'Resumen_EM_0312_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'EM 0312', $data);
    }

    public function exportPdf($criteria)
    {
        $data = $this->service->getExportPdfData($criteria);
        $filename = 'TABLA_VALORES_EVALUACIÓN_' . Carbon::now()->timestamp . '.pdf';
        $pdfOptions = (new SnappyPdfOptions('legal'))
            ->setJavascriptDelay(2500)
            ->setEnableJavascript(true)
            ->setEnableSmartShrinking(true)
            ->setNoStopSlowScripts(true);
        return ExportHelper::pdf("aden.pdf::html.minimum_standard_0312", $data, $filename, $pdfOptions);
    }

    public function migrateFrom($criteria)
    {
        $criteria->userId = ($authUser = $this->getAuthUser()) ? $authUser->id : 1;
        DB::transaction(function () use ($criteria) {
            $this->service->migrateFrom($criteria);
            CustomerEvaluationMinimumStandardTracking0312Repository::createMissingMonthlyReport($criteria->id);
            CustomerEvaluationMinimumStandardTracking0312Repository::insertMonthlyReport($criteria->id, $criteria->customerId);
        });
    }

    public function getTotalByCustomerAndYearChartLine($criteria)
    {
        return $this->service->getTotalByCustomerAndYearChartLine($criteria);
    }
}
