<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ContractDetail;

use DB;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Log;
use Carbon\Carbon;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\Customer\CustomerModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Wgroup\SystemParameter\SystemParameter;

class CustomerContractDetailRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerContractDetailModel());

        $this->service = new CustomerContractDetailService();
    }

    public static function getCustomFilters()
    {
        return [];
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
            "description" => "category.period as description",
            "items" => "category.items",
            "answers" => "category.answers",
            "advance" => DB::raw("ROUND(IFNULL((answers / items) * 100, 0),2) AS advance"),
            "average" => DB::raw("ROUND(IFNULL(total / items, 0),2) AS average"),
            "total" => DB::raw("ROUND(IFNULL(total, 0),2) AS total"),
            "startDate" => "category.startDate",
            "endDate" => "category.endDate",
            "year" => "category.year",
            "contractorId" => "category.contractor_id AS contractorId",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            //$this->addSortColumn('id');
        }

        $qSub = $this->query(DB::table('wg_customer_contract_detail'));

        $qSub->join('wg_customer_contractor', function ($join) {
            $join->on('wg_customer_contractor.id', '=', 'wg_customer_contract_detail.contractor_id');

        })->join(DB::raw(CustomerContractDetailModel::getPeriodicRequirementRelation('requirement')), function ($join) {
            $join->on('requirement.id', '=', 'wg_customer_contract_detail.periodic_requirement_id');
            $join->on('requirement.month', '=', 'wg_customer_contract_detail.month');

        })->leftjoin('wg_rate', function ($join) {
            $join->on('wg_rate.id', '=', 'wg_customer_contract_detail.rate_id');

        })->select(
            'wg_customer_contract_detail.id',
            'wg_customer_contract_detail.period',
            'wg_customer_contract_detail.year',
            'wg_customer_contract_detail.contractor_id',
            DB::raw('COUNT(*) items'),
            DB::raw('SUM(CASE WHEN ISNULL(wg_customer_contract_detail.rate_id) THEN 0 ELSE 1 END) AS answers'),
            DB::raw('SUM(wg_rate.`value`) AS total'),
            DB::raw('MIN(wg_customer_contract_detail.updated_at) startDate'),
            DB::raw('MAX(wg_customer_contract_detail.updated_at) endDate')
        )
            ->whereRaw("requirement.`isActive` = 1")
            ->whereRaw("requirement.`canShow` = 1")
            ->groupBy('wg_customer_contract_detail.period');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'contractorId') {
                        $qSub->where(SqlHelper::getPreparedField('wg_customer_contract_detail.contractor_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table(DB::raw("({$qSub->toSql()}) as category")));

        $query->mergeBindings($qSub);

        //dd($criteria->mandatoryFilters);

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'year') {
                        $query->where(SqlHelper::getPreparedField('category.year'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
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
            "id" => "wg_customer_contract_detail.id",
            "description" => "requirement.requirement as description",
            "rateId" => "wg_customer_contract_detail.rate_id AS rateId",
            "rateCode" => "wg_rate.code as rateCode",
            "rateText" => "wg_rate.text as rateText",
            "isActive" => DB::raw("IF(wg_customer_contract_detail.period = DATE_FORMAT(NOW(),'%Y%m'),1,0) AS isActive"),
            "contractorId" => "wg_customer_contract_detail.contractor_id AS contractorId",
            "periodicRequirementId" => "wg_customer_contract_detail.periodic_requirement_id AS periodicRequirementId",
            "period" => "wg_customer_contract_detail.period"
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            //$this->addSortColumn('id');
        }

        $query = $this->query();

        $query->join('wg_customer_contractor', function ($join) {
            $join->on('wg_customer_contractor.id', '=', 'wg_customer_contract_detail.contractor_id');

        })->join(DB::raw(CustomerContractDetailModel::getPeriodicRequirementRelation('requirement')), function ($join) {
            $join->on('requirement.id', '=', 'wg_customer_contract_detail.periodic_requirement_id');
            $join->on('requirement.month', '=', 'wg_customer_contract_detail.month');

        })->leftjoin('wg_rate', function ($join) {
            $join->on('wg_rate.id', '=', 'wg_customer_contract_detail.rate_id');

        });

        $query->whereRaw("requirement.`isActive` = 1")->whereRaw("requirement.`canShow` = 1");

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

        $entityModel->rate_id = $entity->rate ? $entity->rate->id : null;

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

    public function getChartBar($criteria)
    {
        return $this->service->getChartBar($criteria);
    }

    public function getChartPie($criteria)
    {
        return $this->service->getChartPie($criteria);
    }

    public function getStats($criteria)
    {
        return $this->service->getStats($criteria);
    }

    public function getYearList($contractorId)
    {
        return $this->service->getYearList($contractorId);
    }

    public function getPeriodList($contractorId)
    {
        return $this->service->getPeriodList($contractorId);
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
