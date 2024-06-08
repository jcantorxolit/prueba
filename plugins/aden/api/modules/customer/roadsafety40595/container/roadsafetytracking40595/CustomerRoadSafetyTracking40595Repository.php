<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Classes\Criteria;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use AdeN\Api\Helpers\ExportHelper;

class CustomerRoadSafetyTracking40595Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerRoadSafetyTracking40595Model());

        $this->service = new CustomerRoadSafetyTracking40595Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_road_safety_tracking_40595.id",
            "customerRoadSafetyId" => "wg_customer_road_safety_tracking_40595.customer_road_safety_id",
            "roadSafetyCycle" => "wg_customer_road_safety_tracking_40595.road_safety_cycle",
            "roadSafetyParentId" => "wg_customer_road_safety_tracking_40595.road_safety_parent_id",
            "items" => "wg_customer_road_safety_tracking_40595.items",
            "checked" => "wg_customer_road_safety_tracking_40595.checked",
            "avgProgress" => "wg_customer_road_safety_tracking_40595.avg_progress",
            "avgTotal" => "wg_customer_road_safety_tracking_40595.avg_total",
            "total" => "wg_customer_road_safety_tracking_40595.total",
            "accomplish" => "wg_customer_road_safety_tracking_40595.accomplish",
            "noAccomplish" => "wg_customer_road_safety_tracking_40595.no_accomplish",
            "noApplyWithJustification" => "wg_customer_road_safety_tracking_40595.no_apply_with_justification",
            "noApplyWithoutJustification" => "wg_customer_road_safety_tracking_40595.no_apply_without_justification",
            "noChecked" => "wg_customer_road_safety_tracking_40595.no_checked",
            "year" => "wg_customer_road_safety_tracking_40595.year",
            "month" => "wg_customer_road_safety_tracking_40595.month",
            "createdAt" => "wg_customer_road_safety_tracking_40595.created_at",
            "createdBy" => "wg_customer_road_safety_tracking_40595.created_by",
            "updatedAt" => "wg_customer_road_safety_tracking_40595.updated_at",
            "updatedBy" => "wg_customer_road_safety_tracking_40595.updated_by",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_road_safety_tracking_40595.parent_id', '=', 'tableParent.id');
		}
		*/


        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allSummaryCycle($criteria)
    {
        $this->setColumns([
            "abbreviation" => "wg_customer_road_safety_tracking_40595.abbreviation",
            "name" => "wg_customer_road_safety_tracking_40595.name",
            "jan" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.JAN, 2) AS 'JAN'"),
            "feb" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.FEB, 2) AS 'FEB'"),
            "mar" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.MAR, 2) AS 'MAR'"),
            "apr" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.APR, 2) AS 'APR'"),
            "may" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.MAY, 2) AS 'MAY'"),
            "jun" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.JUN, 2) AS 'JUN'"),
            "jul" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.JUL, 2) AS 'JUL'"),
            "aug" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.AUG, 2) AS 'AUG'"),
            "sep" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.SEP, 2) AS 'SEP'"),
            "oct" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.OCT, 2) AS 'OCT'"),
            "nov" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.NOV, 2) AS 'NOV'"),
            "dec" => DB::raw("ROUND(wg_customer_road_safety_tracking_40595.DEC, 2) AS 'DEC'"),
            "id" => "wg_customer_road_safety_tracking_40595.id",
            "year" => "wg_customer_road_safety_tracking_40595.year",
            "customerRoadSafetyId" => "wg_customer_road_safety_tracking_40595.customer_road_safety_id",
            "cycle" => "wg_customer_road_safety_tracking_40595.id AS cycle",
        ]);

        if (count($criteria->sorts) > 0) {
            foreach ($criteria->sorts as $sort) {
                if (property_exists($sort, "column")) {
                    if ($sort->column == 0 || $sort->column == 1) {
                        $sort->column = 14;
                        $sort->dir = "asc";
                    }
                }
            }
        } else {
            $defaulSort = new \stdClass();
            $defaulSort->column = 14;
            $defaulSort->dir = 'asc';
            $criteria->sorts = [$defaulSort];
        }


        $this->parseCriteria($criteria);

        $customerRoadSafetyId = CriteriaHelper::getMandatoryFilter($criteria, 'customerRoadSafetyId');
        $year = CriteriaHelper::getMandatoryFilter($criteria, 'year');

        $q1 = DB::table('wg_customer_road_safety_tracking_40595')
            ->join("wg_road_safety_cycle_40595", function ($join) {
                $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_customer_road_safety_tracking_40595.road_safety_cycle');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id',
                'wg_customer_road_safety_tracking_40595.year',
                DB::raw("SUM(CASE WHEN month = 1 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JAN'"),
                DB::raw("SUM(CASE WHEN month = 2 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'FEB'"),
                DB::raw("SUM(CASE WHEN month = 3 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'MAR'"),
                DB::raw("SUM(CASE WHEN month = 4 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'APR'"),
                DB::raw("SUM(CASE WHEN month = 5 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'MAY'"),
                DB::raw("SUM(CASE WHEN month = 6 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JUN'"),
                DB::raw("SUM(CASE WHEN month = 7 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JUL'"),
                DB::raw("SUM(CASE WHEN month = 8 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'AUG'"),
                DB::raw("SUM(CASE WHEN month = 9 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'SEP'"),
                DB::raw("SUM(CASE WHEN month = 10 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'OCT'"),
                DB::raw("SUM(CASE WHEN month = 11 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'NOV'"),
                DB::raw("SUM(CASE WHEN month = 12 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'DEC'")
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $customerRoadSafetyId->value)
            ->where("wg_customer_road_safety_tracking_40595.year", $year->value)
            ->groupBy(
                'wg_road_safety_cycle_40595.id',
                'wg_customer_road_safety_tracking_40595.year',
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id'
            );

        $q2 = DB::table('wg_customer_road_safety_tracking_40595')
            ->select(
                DB::raw("5 AS id"),
                DB::raw("'TOTAL' AS name"),
                DB::raw("'PUNTAJE' AS abbreviation"),
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id',
                'wg_customer_road_safety_tracking_40595.year',
                DB::raw("SUM(CASE WHEN month = 1 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JAN'"),
                DB::raw("SUM(CASE WHEN month = 2 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'FEB'"),
                DB::raw("SUM(CASE WHEN month = 3 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'MAR'"),
                DB::raw("SUM(CASE WHEN month = 4 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'APR'"),
                DB::raw("SUM(CASE WHEN month = 5 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'MAY'"),
                DB::raw("SUM(CASE WHEN month = 6 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JUN'"),
                DB::raw("SUM(CASE WHEN month = 7 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JUL'"),
                DB::raw("SUM(CASE WHEN month = 8 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'AUG'"),
                DB::raw("SUM(CASE WHEN month = 9 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'SEP'"),
                DB::raw("SUM(CASE WHEN month = 10 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'OCT'"),
                DB::raw("SUM(CASE WHEN month = 11 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'NOV'"),
                DB::raw("SUM(CASE WHEN month = 12 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'DEC'")
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $customerRoadSafetyId->value)
            ->where("wg_customer_road_safety_tracking_40595.year", $year->value)
            ->groupBy(
                'wg_customer_road_safety_tracking_40595.year',
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id'
            );

        $q1->union($q2)->mergeBindings($q2);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customer_road_safety_tracking_40595")))
            ->mergeBindings($q1);

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allSummaryCycleDetail($criteria)
    {
        $this->setColumns([
            "indicator" => "wg_customer_road_safety_tracking_40595.indicator",
            "jan" => DB::raw("MAX(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'JAN'"),
            "feb" => DB::raw("MAX(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'FEB'"),
            "mar" => DB::raw("MAX(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'MAR'"),
            "apr" => DB::raw("MAX(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'APR'"),
            "may" => DB::raw("MAX(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'MAY'"),
            "jun" => DB::raw("MAX(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'JUN'"),
            "jul" => DB::raw("MAX(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'JUL'"),
            "aug" => DB::raw("MAX(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'AUG'"),
            "sep" => DB::raw("MAX(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'SEP'"),
            "oct" => DB::raw("MAX(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'OCT'"),
            "nov" => DB::raw("MAX(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'NOV'"),
            "dec" => DB::raw("MAX(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'DEC'"),
            "year" => "wg_customer_road_safety_tracking_40595.year",
            "customerRoadSafetyId" => "wg_customer_road_safety_tracking_40595.customer_road_safety_id",
            "cycle" => "wg_customer_road_safety_tracking_40595.road_safety_cycle",
        ]);

        $this->parseCriteria($criteria);

        $q1 = $this->service->prepareSubQueryDetail(3, 'Cumple', 'accomplish');
        $q2 = $this->service->prepareSubQueryDetail(5, 'No Cumple', 'no_accomplish');
        $q3 = $this->service->prepareSubQueryDetail(6, 'No Aplica', 'no_apply_with_justification');

        $q1->union($q2)->union($q3);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customer_road_safety_tracking_40595")))
            ->mergeBindings($q1)
            ->groupBy("wg_customer_road_safety_tracking_40595.indicator");

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allSummaryIndicator($criteria)
    {
        $this->setColumns([
            "indicator" => "wg_customer_road_safety_tracking_40595.indicator",
            "jan" => DB::raw("MAX(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'JAN'"),
            "feb" => DB::raw("MAX(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'FEB'"),
            "mar" => DB::raw("MAX(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'MAR'"),
            "apr" => DB::raw("MAX(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'APR'"),
            "may" => DB::raw("MAX(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'MAY'"),
            "jun" => DB::raw("MAX(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'JUN'"),
            "jul" => DB::raw("MAX(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'JUL'"),
            "aug" => DB::raw("MAX(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'AUG'"),
            "sep" => DB::raw("MAX(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'SEP'"),
            "oct" => DB::raw("MAX(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'OCT'"),
            "nov" => DB::raw("MAX(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'NOV'"),
            "dec" => DB::raw("MAX(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) AS 'DEC'"),
            "year" => "wg_customer_road_safety_tracking_40595.year",
            "customerRoadSafetyId" => "wg_customer_road_safety_tracking_40595.customer_road_safety_id",
        ]);

        $this->parseCriteria($criteria);

        $customerRoadSafetyId = CriteriaHelper::getMandatoryFilter($criteria, 'customerRoadSafetyId');
        $year = CriteriaHelper::getMandatoryFilter($criteria, 'year');

        $q1 = $this->service->prepareSubQuery(1, 'Preguntas', 'items');
        $q2 = $this->service->prepareSubQuery(2, 'Respuestas', 'checked');
        $q3 = $this->service->prepareSubQuery(3, 'Cumple', 'accomplish');
        //$q4 = $this->service->prepareSubQuery(4, 'No Aplica sin Justificacion', 'no_apply_without_justification');
        $q5 = $this->service->prepareSubQuery(5, 'No Cumple', 'no_accomplish');
        $q6 = $this->service->prepareSubQuery(6, 'No Aplica', 'no_apply_with_justification');
        $q7 = $this->service->prepareSubQuery(7, 'Sin Respuesta', 'no_checked');

        $q1->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $customerRoadSafetyId->value)
            ->where("wg_customer_road_safety_tracking_40595.year", $year->value);

        $q2->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $customerRoadSafetyId->value)
            ->where("wg_customer_road_safety_tracking_40595.year", $year->value);

        $q3->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $customerRoadSafetyId->value)
            ->where("wg_customer_road_safety_tracking_40595.year", $year->value);

        $q5->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $customerRoadSafetyId->value)
            ->where("wg_customer_road_safety_tracking_40595.year", $year->value);

        $q7->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $customerRoadSafetyId->value)
            ->where("wg_customer_road_safety_tracking_40595.year", $year->value);

        $q1->union($q2)
            ->mergeBindings($q2)
            ->union($q3)
            ->mergeBindings($q3)
            ->union($q5)
            ->mergeBindings($q5)
            ->union($q7)
            ->mergeBindings($q7);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_customer_road_safety_tracking_40595")))
            ->mergeBindings($q1)
            ->groupBy("wg_customer_road_safety_tracking_40595.indicator");

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerRoadSafetyId = $entity->customerRoadSafetyId ? $entity->customerRoadSafetyId->id : null;
        $entityModel->roadSafetyCycle = $entity->roadSafetyCycle;
        $entityModel->roadSafetyParentId = $entity->roadSafetyParentId ? $entity->roadSafetyParentId->id : null;
        $entityModel->items = $entity->items;
        $entityModel->checked = $entity->checked;
        $entityModel->avgProgress = $entity->avgProgress;
        $entityModel->avgTotal = $entity->avgTotal;
        $entityModel->total = $entity->total;
        $entityModel->accomplish = $entity->accomplish;
        $entityModel->noAccomplish = $entity->noAccomplish;
        $entityModel->noApplyWithJustification = $entity->noApplyWithJustification;
        $entityModel->noApplyWithoutJustification = $entity->noApplyWithoutJustification;
        $entityModel->noChecked = $entity->noChecked;
        $entityModel->year = $entity->year;
        $entityModel->month = $entity->month;


        if ($isNewRecord) {
            $entityModel->isDeleted = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
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
            $entity->customerRoadSafetyId = $model->customerRoadSafetyId;
            $entity->roadSafetyCycle = $model->roadSafetyCycle;
            $entity->roadSafetyParentId = $model->roadSafetyParentId;
            $entity->items = $model->items;
            $entity->checked = $model->checked;
            $entity->avgProgress = $model->avgProgress;
            $entity->avgTotal = $model->avgTotal;
            $entity->total = $model->total;
            $entity->accomplish = $model->accomplish;
            $entity->noAccomplish = $model->noAccomplish;
            $entity->noApplyWithJustification = $model->noApplyWithJustification;
            $entity->noApplyWithoutJustification = $model->noApplyWithoutJustification;
            $entity->noChecked = $model->noChecked;
            $entity->year = $model->year;
            $entity->month = $model->month;
            $entity->createdAt = $model->createdAt;
            $entity->createdBy = $model->createdBy;
            $entity->updatedAt = $model->updatedAt;
            $entity->updatedBy = $model->updatedBy;

            return $entity;
        } else {
            return null;
        }
    }

    public function exportSummaryCycleExcel($criteria)
    {
        $data = $this->service->getExportSummaryCycleData($criteria);
        $filename = 'Resumen_Ciclos_PESV_40595_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'PESV 40595', $data);
    }

    public function exportSummaryIndicadorExcel($criteria)
    {
        $data = $this->service->getExportSummaryIndicatorData($criteria);
        $filename = 'Resumen_Indicadores_PESV_40595_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'PESV 40595', $data);
    }

    public static function createMissingMonthlyReport($customerRoadSafetyId)
    {
        $self = new self;
        $self->service->createMissingMonthlyReport($self->getCriteria($customerRoadSafetyId));
    }

    public static function migratePreviousMonthlyReport($criteria)
    {
        $self = new self;
        $self->service->migratePreviousMonthlyReport($criteria);
    }

    public static function insertMonthlyReport($customerRoadSafetyId, $customerId)
    {
        try {
            $self = new self;
            $self->service->insertMonthlyReport($self->getCriteria($customerRoadSafetyId, $customerId));
        } catch (\Exception $ex) {
            Log::error($ex);
        }
    }

    public static function updateMonthlyReport($customerRoadSafetyId, $customerId)
    {
        $self = new self;
        $self->service->updateMonthlyReport($self->getCriteria($customerRoadSafetyId, $customerId));
    }

    private function getCriteria($customerRoadSafetyId, $customerId = null)
    {
        $authUser = $this->getAuthUser();
        $criteria = new \stdClass();
        $criteria->customerRoadSafetyId = $customerRoadSafetyId;
        $criteria->customerId = $customerId;
        $criteria->createdBy = $authUser ? $authUser->id : 1;
        $criteria->updatedBy = $authUser ? $authUser->id : 1;
        $criteria->currentYear = Carbon::now()->year;
        $criteria->currentMonth = Carbon::now()->month;

        return $criteria;
    }
}
