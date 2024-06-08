<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ManagementDetail;

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
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\ProgramManagement\QuestionResource\ProgramManagementQuestionResourceRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerManagementDetailRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerManagementDetailModel());

        $this->service = new CustomerManagementDetailService();
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
            "name" => "category.name",
            "questions" => DB::raw("SUM(questions) AS questions"),
            "answers" => DB::raw("SUM(answers) AS answers"),
            "advance" => DB::raw("ROUND(IFNULL(SUM((answers / questions) * 100), 0), 2) AS advance"),
            "average" => DB::raw("ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END), 0 ), 2 ) AS average"),
            "total" => DB::raw("ROUND(IFNULL(SUM(total), 0), 2) AS total"),
            "programId" => "category.program_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $qDetail = DB::table('wg_customer_management_detail');
        $qDetail->join('wg_rate', function ($join) {
            $join->on('wg_customer_management_detail.rate_id', '=', 'wg_rate.id');

        })->select('wg_customer_management_detail.*', 'wg_rate.text', 'wg_rate.value', 'wg_rate.code');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'managementId') {
                        $qDetail->where(SqlHelper::getPreparedField('management_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $qSub = DB::table('wg_customer_management_program');
        $qSub->join("wg_customer_management", function ($join) {
            $join->on('wg_customer_management.id', '=', 'wg_customer_management_program.management_id');

        })->join("wg_program_management_economic_sector", function ($join) {
            $join->on('wg_program_management_economic_sector.id', '=', 'wg_customer_management_program.program_economic_sector_id');

        })->join("wg_economic_sector", function ($join) {
            $join->on('wg_economic_sector.id', '=', 'wg_program_management_economic_sector.economic_sector_id');

        })->join('wg_program_management', function ($join) {
            $join->on('wg_program_management.id', '=', 'wg_program_management_economic_sector.program_id');

        })->join('wg_program_management_category', function ($join) {
            $join->on('wg_program_management_category.program_id', '=', 'wg_program_management.id');

        })->join('wg_program_management_question', function ($join) {
            $join->on('wg_program_management_category.id', '=', 'wg_program_management_question.category_id');

        })->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_management_program.customer_workplace_id');
            $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_management.customer_id');

        })->leftjoin(DB::raw("({$qDetail->toSql()}) as detail"), function ($join) {
            $join->on('wg_program_management_question.id', '=', 'detail.question_id');

        })->select(
            'wg_program_management_category.id',
            'wg_program_management_category.name',
            'wg_program_management.id AS program_id',
            'wg_program_management.isWeighted',
            DB::raw('COUNT(*) AS questions'),
            DB::raw('SUM(CASE WHEN ISNULL(detail.id) THEN 0 ELSE 1 END) AS answers'),
           // DB::raw('SUM(detail.`value`) total'),
            DB::raw("SUM( CASE WHEN wg_program_management.isWeighted AND detail.code IN ('cp', 'c') THEN wg_program_management_question.weightedValue ELSE  detail.value END ) total")
        )
            ->whereRaw("wg_customer_management_program.`active` = '1'")
            ->whereRaw("wg_program_management.`status` = 'activo'")
            ->whereRaw("wg_program_management_category.`status` = 'activo'")
            ->whereRaw("wg_program_management_question.`status` = 'activo'")
            ->groupBy('wg_program_management_category.name', 'wg_program_management_category.id')
            ->mergeBindings($qDetail);

            if ($criteria != null) {
                if ($criteria->mandatoryFilters != null) {
                    foreach ($criteria->mandatoryFilters as $item) {
                        if ($item->field == 'managementId') {
                            $qSub->where(SqlHelper::getPreparedField('wg_customer_management_program.management_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                        }
                    }
                }
            }


        $query = $this->query(DB::table(DB::raw("({$qSub->toSql()}) as category")));

        $query->groupBy('category.id')->mergeBindings($qSub);


        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'programId') {
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
            "id" => "wg_customer_management_detail.id",
            "description" => "wg_program_management_question.description",
            "article" => "wg_program_management_question.article",
            "observation" => "wg_customer_management_detail.observation",
            "rateId" => "wg_customer_management_detail.rate_id",
            "rateCode" => "wg_rate.code as rate_code",
            "rateText" => "wg_rate.text as rate_text",
            "managementId" => "wg_customer_management_detail.management_id",
            "questionId" => "wg_customer_management_detail.question_id",
            "categoryId" => "wg_program_management_category.id AS category_id",
            "programId" => "wg_program_management.id AS program_id",
            "hasResource" => DB::raw("0 as hasResource"),
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $query = $this->query();

        /* Example relation*/
        $query->join('wg_customer_management', function ($join) {
            $join->on('wg_customer_management.id', '=', 'wg_customer_management_detail.management_id');

        })->join('wg_program_management_question', function ($join) {
            $join->on('wg_program_management_question.id', '=', 'wg_customer_management_detail.question_id');

        })->join('wg_program_management_category', function ($join) {
            $join->on('wg_program_management_category.id', '=', 'wg_program_management_question.category_id');

        })->join('wg_customer_management_program', function ($join) {
            //$join->on('wg_customer_management_program.program_id', '=', 'wg_program_management_category.program_id');
            $join->on('wg_customer_management_program.management_id', '=', 'wg_customer_management.id');

        })->join("wg_program_management_economic_sector", function ($join) {
            $join->on('wg_program_management_economic_sector.id', '=', 'wg_customer_management_program.program_economic_sector_id');

        })->join("wg_economic_sector", function ($join) {
            $join->on('wg_economic_sector.id', '=', 'wg_program_management_economic_sector.economic_sector_id');

        })->join('wg_program_management', function ($join) {
            $join->on('wg_program_management.id', '=', 'wg_program_management_economic_sector.program_id');
            $join->on('wg_program_management.id', '=', 'wg_program_management_category.program_id');

        })->leftjoin('wg_rate', function ($join) {
            $join->on('wg_rate.id', '=', 'wg_customer_management_detail.rate_id');

        });

        $query->where('wg_customer_management_program.active', '=', '1')
            ->where('wg_program_management_category.status', '=', 'activo')
            ->where('wg_program_management_question.status', '=', 'activo');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == 'inRaw') {
                        $query->whereIn('wg_program_management_question_classification.customer_size', function ($query) use ($item) {
                            $query->select('size')
                                ->from('wg_customers')
                                ->join('wg_customer_management', function ($join) {
                                    $join->on('wg_customers.id', '=', 'wg_customer_management.customer_id');

                                })
                                ->where('wg_customer_management.id', '=', SqlHelper::getPreparedData($item));
                        });
                    } else {
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
            $model = (object) $model;
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

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'Programas_Empresariales_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Programas Empresariales', $data);
    }

    public function getResourceList($criteria)
    {
        return $this->service->getResourceList($criteria, ProgramManagementQuestionResourceRepository::getModelClass());
    }
}
