<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandardItem;

use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Log;
use Carbon\Carbon;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use AdeN\Api\Modules\Customer\CustomerModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Wgroup\SystemParameter\SystemParameter;

class CustomerEvaluationMinimumStandardItemRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerEvaluationMinimumStandardItemModel());

        $this->service = new CustomerEvaluationMinimumStandardItemService();
    }

    public static function getCustomFilters()
    {
        return [

        ];
    }

    public function getMandatoryFilters()
    {
        return [
            array("field" => 'isActive', "operator" => 'eq', "value" => '1'),
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "category.id",
            "description" => "category.description",
            "items" => DB::raw("SUM(items) AS items"),
            "checked" => DB::raw("SUM(checked) AS checked"),
            "advance" => DB::raw("ROUND(IFNULL(SUM((checked / items) * 100), 0),2) AS advance"),
            "average" => DB::raw("ROUND(IFNULL(SUM(total), 0),2) AS average"),
            "total" => DB::raw("ROUND(IFNULL(SUM(total), 0),2) AS total"),
            "cycleId" => "category.cycle_id",
            "parentId" => "category.parent_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item');
        $qDetail->join('wg_config_minimum_standard_rate', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_item.rate_id', '=', 'wg_config_minimum_standard_rate.id');

        })->select('wg_customer_evaluation_minimum_standard_item.*', 'wg_config_minimum_standard_rate.text', 'wg_config_minimum_standard_rate.value', 'wg_config_minimum_standard_rate.code');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'evaluationMinimumStandardId') {
                        $qDetail->where(SqlHelper::getPreparedField('customer_evaluation_minimum_standard_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $qSub = DB::table('wg_config_minimum_standard_cycle');
        $qSub->join('wg_minimum_standard', function ($join) {
            $join->on('wg_config_minimum_standard_cycle.id', '=', 'wg_minimum_standard.cycle_id');

        })->join('wg_minimum_standard_item', function ($join) {
            $join->on('wg_minimum_standard.id', '=', 'wg_minimum_standard_item.minimum_standard_id');

        })->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
            $join->on('wg_minimum_standard_item.id', '=', 'detail.minimum_standard_item_id');

        })->select(
            'wg_minimum_standard.id',
            'wg_minimum_standard.description',
            'wg_minimum_standard.parent_id',
            'wg_config_minimum_standard_cycle.id AS cycle_id',
            DB::raw('COUNT(*) items'),
            DB::raw('SUM(CASE WHEN ISNULL(detail.id) THEN 0 ELSE 1 END) AS checked'),
            DB::raw('SUM(CASE WHEN detail.`code` = \'cp\' OR detail.`code` = \'nac\' THEN wg_minimum_standard_item.`value` ELSE 0 END) AS total')
        )
            ->whereRaw("wg_config_minimum_standard_cycle.`status` = 'activo'")
            ->whereRaw("wg_minimum_standard.`isActive` = '1'")
            ->whereRaw("wg_minimum_standard_item.`isActive` = '1'")
            ->groupBy('wg_minimum_standard.description', 'wg_minimum_standard.id')
            ->mergeBindings($qDetail);


        $query = $this->query(DB::table(DB::raw("({$qSub->toSql()}) as category")));

        $query->groupBy('category.id')->mergeBindings($qSub);


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'cycleId') {
                        $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        // $result["total"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns)->total() : (is_array($data = $query->get($this->columns)) ? count($data) : $data->count());
        // $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->take($criteria->take)->skip($criteria->skip)->get() : $query->get($this->columns));

        // return $result;

        return $this->get($query, $criteria);
    }

    public function allQuestion($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_evaluation_minimum_standard_item.id",
            "description" => "wg_minimum_standard_item.description",
            "numeral" => "wg_minimum_standard_item.numeral",
            "criterion" => "wg_minimum_standard_item.criterion",
            "rateId" => "wg_customer_evaluation_minimum_standard_item.rate_id",
            "rateCode" => "wg_config_minimum_standard_rate.code as rate_code",
            "rateText" => "wg_config_minimum_standard_rate.text as rate_text",
            "evaluationMinimumStandardId" => "wg_customer_evaluation_minimum_standard_item.customer_evaluation_minimum_standard_id",
            "itemId" => "wg_customer_evaluation_minimum_standard_item.minimum_standard_item_id",
            "minimumStandardId" => "wg_minimum_standard.id AS minimum_standard_id",
            "cycleId" => "wg_config_minimum_standard_cycle.id AS cycle_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $query = $this->query();

        /* Example relation*/
        $query->join('wg_customer_evaluation_minimum_standard', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard.id', '=', 'wg_customer_evaluation_minimum_standard_item.customer_evaluation_minimum_standard_id');

        })->join('wg_minimum_standard_item', function ($join) {
            $join->on('wg_minimum_standard_item.id', '=', 'wg_customer_evaluation_minimum_standard_item.minimum_standard_item_id');

        })->join('wg_minimum_standard', function ($join) {
            $join->on('wg_minimum_standard.id', '=', 'wg_minimum_standard_item.minimum_standard_id');

        })->join('wg_config_minimum_standard_cycle', function ($join) {
            $join->on('wg_config_minimum_standard_cycle.id', '=', 'wg_minimum_standard.cycle_id');

        })->leftjoin('wg_config_minimum_standard_rate', function ($join) {
            $join->on('wg_config_minimum_standard_rate.id', '=', 'wg_customer_evaluation_minimum_standard_item.rate_id');

        });

        $query->where('wg_config_minimum_standard_cycle.status', '=', 'activo')
            ->where('wg_minimum_standard.isActive', '=', '1')
            ->where('wg_minimum_standard_item.isActive', '=', '1');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["total"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : (is_array($data = $query->get()) ? count($data) : $data->count());


        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
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

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;

            return $entity;
        } else {
            return null;
        }
    }

    protected function parseModel($data)
    {
        if ($data instanceof Paginator || $data instanceof LengthAwarePaginator) {
            $models = $data->all();
        } else {
            $models = $data;
        }

        $modelClass = get_class($this->model);

        if (is_array($models) || $models instanceof Collection || $models instanceof \October\Rain\Support\Collection) {
            $parsed = array();
            foreach ($models as $model) {

                if (isset($model->rateId) && isset($model->rateCode) && isset($model->rateText)) {
                    $model->rate = ['id' => $model->rateId, 'code' => $model->rateCode, 'text' => $model->rateText];
                }

                if ($model instanceof $modelClass) {
                    $parsed[] = $model;
                } else {
                    $parsed[] = $model;
                }
            }

            return $parsed;
        } else if ($data instanceof $modelClass) {
            return $data;
        } else {
            return null;
        }
    }
}
