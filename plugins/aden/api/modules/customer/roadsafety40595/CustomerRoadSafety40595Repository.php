<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\RoadSafety40595;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Classes\SnappyPdfOptions;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595\CustomerRoadSafetyTracking40595Repository;
use Wgroup\SystemParameter\SystemParameter;

class CustomerRoadSafety40595Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerRoadSafety40595Model());

        $this->service = new CustomerRoadSafety40595Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_road_safety_40595.id",
            "unique" => "wg_customer_road_safety_40595.id AS unique",
            "period" => "wg_customer_road_safety_40595.period",
            "createdAt" => "wg_customer_road_safety_40595.created_at",
            "status" => "wg_customer_em_40595_status.item AS status",
            "statusCode" => "wg_customer_road_safety_40595.status as statusCode",
            "customerId" => "wg_customer_road_safety_40595.customer_id",
            'canMigrate' => DB::raw("CASE WHEN (wg_customer_road_safety_item_40595.answers IS NULL OR wg_customer_road_safety_item_40595.answers = 0) AND `wg_customer_road_safety_40595`.`status` = 'A' THEN 1 ELSE 0 END canMigrate")
        ]);

        $this->parseCriteria($criteria);

        $qDetail = DB::table('wg_customer_road_safety_item_40595')
            ->join('wg_road_safety_rate_40595', function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                DB::raw('COUNT(*) questions'),
                DB::raw('CASE WHEN wg_road_safety_rate_40595.`code` IS NOT NULL THEN 1 ELSE 0 END answers')
            )
            ->where('wg_customer_road_safety_item_40595.status', 'activo')
            ->groupBy('wg_customer_road_safety_item_40595.customer_road_safety_id');

        $query = $this->query();

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_em_40595_status')), function ($join) {
            $join->on('wg_customer_em_40595_status.value', '=', 'wg_customer_road_safety_40595.status');
        })->leftjoin(DB::raw("({$qDetail->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
            $join->on('wg_customer_road_safety_item_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_40595.id');
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
            "name" => "wg_customer_road_safety_40595.name",
            "description" => "wg_customer_road_safety_40595.description",
            "items" => "wg_customer_road_safety_40595.items",
            "checked" => "wg_customer_road_safety_40595.checked",
            "advance" => DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.checked / wg_customer_road_safety_40595.items) * 100, 0) ,2) AS advance"),
            "total" => DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
            "id" => "wg_customer_road_safety_40595.id",
            "abbreviation" => "wg_customer_road_safety_40595.abbreviation",
            "average" => DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.total / wg_customer_road_safety_40595.items), 0), 2) AS average"),
        ]);

        foreach ($criteria->sorts as $sort) {
            if (property_exists($sort, "column")) {
                if ($sort->column == 0) {
                    $sort->column = 6;
                    $sort->dir = "asc";
                }
            }
        }

        $customerRoadSafetyIdField = CriteriaHelper::getMandatoryFilter($criteria, "customerRoadSafetyId");

        $this->parseCriteria($criteria);


        $q1 = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            // ->join("wg_road_safety_item_criterion_40595", function ($join) {
            //     $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            // })
            ->join("wg_customer_road_safety_40595", function ($join) use ($customerRoadSafetyIdField) {
                $join->where('wg_customer_road_safety_40595.id', '=', $customerRoadSafetyIdField->value);
                $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_cycle_40595.description',
                'wg_road_safety_40595.id AS road_safety_id',
                //'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1);


        $q2 = DB::table('wg_customer_road_safety_item_40595')
            ->join("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->where('wg_customer_road_safety_item_40595.status', 'activo');


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerId") {
                        //$q1->where(SqlHelper::getPreparedField('wg_customers.id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == "customerRoadSafetyId") {
                        $q2->where(SqlHelper::getPreparedField('wg_customer_road_safety_item_40595.customer_road_safety_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $sQuery = DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description',
                'wg_road_safety_item_40595.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                //'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description'
            );

        $query = $this->query(DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_road_safety_40595")))
            ->mergeBindings($sQuery);

        $this->applyCriteria($query, $criteria, ['customerRoadSafetyId', 'customerId']);

        Log::info($query->toSql());

        return $this->get($query, $criteria);
    }

    public function allSummaryClosed($criteria)
    {
        $this->setColumns([
            "name" => "wg_customer_road_safety_40595.name",
            "description" => "wg_customer_road_safety_40595.description",
            "items" => "wg_customer_road_safety_40595.items",
            "checked" => "wg_customer_road_safety_40595.checked",
            "advance" => DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.checked / wg_customer_road_safety_40595.items) * 100, 0) ,2) AS advance"),
            "total" => DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
            "id" => "wg_customer_road_safety_40595.id",
            "abbreviation" => "wg_customer_road_safety_40595.abbreviation",
            "average" => DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.total / wg_customer_road_safety_40595.items), 0), 2) AS average"),
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

        $q1 = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
                $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_parent_40595.id AS road_safety_id',
                'wg_road_safety_parent_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1);


        $q2 = DB::table('wg_customer_road_safety_item_40595')
            ->leftjoin("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->where('wg_customer_road_safety_item_40595.is_freezed', 1)
            ->where('wg_customer_road_safety_item_40595.status', 'activo');


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == "customerRoadSafetyId") {
                        $q2->where(SqlHelper::getPreparedField('wg_customer_road_safety_item_40595.customer_road_safety_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $sQuery = DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->join(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description',
                'wg_road_safety_item_40595.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.code) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description'
            );

        $query = $this->query(DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_road_safety_40595")))
            ->mergeBindings($sQuery);

        $this->applyCriteria($query, $criteria, ['customerRoadSafetyId', 'customerId']);

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
                $this->service->bulkInsertRoadSafetyItems($model);
                $this->service->bulkUpdateRoadSafetyItems($model);
                $this->service->bulkDeleteRoadSafetyItems($model);
            }
            //CustomerRoadSafetyTracking40595Repository::createMissingMonthlyReport($model->id);
        }
    }

    public function updateTracking($id)
    {
        CustomerRoadSafetyTracking40595Repository::createMissingMonthlyReport($id);
        CustomerRoadSafetyTracking40595Repository::insertMonthlyReport($id, null);
    }

    public static function executeChangePeriod()
    {
        DB::transaction(function () {
            $repository = new self;
            $repository->service->bulkCancelRoadSafetyImprovementPlanActionPlanTask();
            $repository->service->bulkInsertRoadSafetyImprovementPlanActionPlanComment();
            $repository->service->bulkCancelRoadSafetyImprovementPlanActionPlan();
            $repository->service->bulkInsertRoadSafetyImprovementPlanComment();
            $repository->service->bulkCancelRoadSafetyImprovementPlan();
            $repository->service->bulkCancelRoadSafety();
            $repository->service->bulkInsertRoadSafetyChangePeriod();
            $repository->service->bulkInsertRoadSafetyItemsChangePeriod();
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
        $entityModel->startDate = !empty($entity->startDate) ? Carbon::parse($entity->startDate)->timezone('America/Bogota') : null;
        $entityModel->endDate = !empty($entity->endDate) ? Carbon::parse($entity->endDate)->timezone('America/Bogota') : null;
        $entityModel->status = !empty($entity->status) ? $entity->status : 'A';
        $entityModel->misionallity = $entity->misionallity;
        $entityModel->size = $entity->size ? $entity->size->value : null;


        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $this->parseModelWithRelations($entityModel);
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

    public function findByCustomerId($id)
    {
        return $this->model->where('customer_id', $id)->first();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model && $model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->startDate = $model->startDate;
            $entity->endDate = $model->endDate;
            $entity->status = $model->status;
            $entity->misionallity = $model->misionallity;
            $entity->size = $model->getSize();
            $entity->description = $model->description;

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
        $entity = $this->find($criteria->customerRoadSafetyId);
        if ($entity == null || $entity->status == 'A') {
            $data = $this->service->getExportSummaryData($criteria);
        } else {
            $data = $this->service->getExportSummaryDataClosed($criteria);
        }
        $filename = 'Resumen_PESV_40595_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'PESV 40595', $data);
    }

    public function exportPdf($criteria)
    {
        $data = $this->service->getExportPdfData($criteria);
        $filename = 'TABLA_VALORES_PESV_40595_' . Carbon::now()->timestamp . '.pdf';
        $pdfOptions = (new SnappyPdfOptions('legal'))
            ->setJavascriptDelay(2500)
            ->setEnableJavascript(true)
            ->setEnableSmartShrinking(true)
            ->setNoStopSlowScripts(true)
            ->setMarginTop(20);
        return ExportHelper::pdf("aden.pdf::html.road_safety_40595", $data, $filename, $pdfOptions);
    }

    public function migrateFrom($criteria)
    {
        $criteria->userId = ($authUser = $this->getAuthUser()) ? $authUser->id : 1;
        DB::transaction(function () use ($criteria) {
            $this->service->migrateFrom($criteria);
            CustomerRoadSafetyTracking40595Repository::createMissingMonthlyReport($criteria->id);
            CustomerRoadSafetyTracking40595Repository::insertMonthlyReport($criteria->id, $criteria->customerId);
        });
    }

    public function getTotalByCustomerAndYearChartLine($criteria)
    {
        return $this->service->getTotalByCustomerAndYearChartLine($criteria);
    }

    public function getRateList()
    {
        return $this->service->getRateList();
    }

    public function getRealRateList()
    {
        return $this->service->getRealRateList();
    }
}
