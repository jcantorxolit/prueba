<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\DiagnosticPrevention;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;

use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerDiagnosticPreventionRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerDiagnosticPreventionModel());

        $this->service = new CustomerDiagnosticPreventionService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Tipo ID Empresa", "name" => "customerDocumentType"],
            ["alias" => "Número ID Empresa", "name" => "customerDocumentNumber"],
            ["alias" => "Empresa", "name" => "customerName"],
            ["alias" => "Tipo ID Empleado", "name" => "employeeDocumentType"],
            ["alias" => "Número ID Empleado", "name" => "employeeDocumentNumber"],
            ["alias" => "Empleado", "name" => "employeeName"],
            ["alias" => "Centro de Trabajo", "name" => "workPlace"],
            ["alias" => "Cargo", "name" => "job"],
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
            "average" => DB::raw("ROUND(IFNULL(SUM(total	/ questions), 0), 2) AS average"),
            "total" => DB::raw("ROUND(IFNULL(SUM(total), 0), 2) AS total"),
            "programId" => "category.program_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $qPrevention = DB::table('wg_customer_diagnostic_prevention');
        $qPrevention->join('wg_rate', function ($join) {
            $join->on('wg_customer_diagnostic_prevention.rate_id', '=', 'wg_rate.id');

        })->select('wg_customer_diagnostic_prevention.*', 'wg_rate.text', 'wg_rate.value');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'diagnosticId') {
                        $qPrevention->where(SqlHelper::getPreparedField('diagnostic_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $qSub = DB::table('wg_progam_prevention');
        $qSub->join('wg_progam_prevention_category', function ($join) {
            $join->on('wg_progam_prevention.id', '=', 'wg_progam_prevention_category.program_id');

        })->join('wg_progam_prevention_question', function ($join) {
            $join->on('wg_progam_prevention_category.id', '=', 'wg_progam_prevention_question.category_id');

        })->join('wg_progam_prevention_question_classification', function ($join) {
            $join->on('wg_progam_prevention_question.id', '=', 'wg_progam_prevention_question_classification.program_prevention_question_id');

        })->leftjoin(DB::raw("({$qPrevention->toSql()}) as prevention"), function ($join) {
            $join->on('wg_progam_prevention_question.id', '=', 'prevention.question_id');

        })->select(
            'wg_progam_prevention_category.id',
            'wg_progam_prevention_category.name',
            'wg_progam_prevention.id AS program_id',
            DB::raw('COUNT(*) AS questions'),
            DB::raw('SUM(CASE WHEN ISNULL(prevention.id) THEN 0 ELSE 1 END) AS answers'),
            DB::raw('SUM(prevention.`value`) total')
        )
            ->whereRaw("wg_progam_prevention.`status` = 'activo'")
            ->whereRaw("wg_progam_prevention_category.`status` = 'activo'")
            ->whereRaw("wg_progam_prevention_question.`status` = 'activo'")
            ->where(function ($query) use ($criteria) {
                if ($criteria != null && $criteria->mandatoryFilters != null) {
                    foreach ($criteria->mandatoryFilters as $item) {
                        if ($item->operator == 'inRaw') {
                            $query->whereIn('wg_progam_prevention_question_classification.customer_size', function ($query) use ($item) {
                                $query->select('size')
                                    ->from('wg_customers')
                                    ->join('wg_customer_diagnostic', function ($join) {
                                        $join->on('wg_customers.id', '=', 'wg_customer_diagnostic.customer_id');

                                    })
                                    ->where('wg_customer_diagnostic.id', '=', SqlHelper::getPreparedData($item));
                            });
                        }
                    }
                }
            })
            ->groupBy('wg_progam_prevention_category.name', 'wg_progam_prevention_category.id')
            ->mergeBindings($qPrevention);


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
            "id" => "wg_customer_diagnostic_prevention.id",
            "description" => "wg_progam_prevention_question.description",
            "article" => "wg_progam_prevention_question.article",
            "observation" => "wg_customer_diagnostic_prevention.observation",
            "rateId" => "wg_customer_diagnostic_prevention.rate_id",
            "rateCode" => "wg_rate.code as rate_code",
            "rateText" => "wg_rate.text as rate_text",
            "diagnosticId" => "wg_customer_diagnostic_prevention.diagnostic_id",
            "questionId" => "wg_customer_diagnostic_prevention.question_id",
            "categoryId" => "wg_progam_prevention_category.id AS category_id",
            "programId" => "wg_progam_prevention.id AS program_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        if (!count($criteria->sorts)) {
            $this->addSortColumn('id');
        }

        $query = $this->query();

        /* Example relation*/
        $query->join('wg_customer_diagnostic', function ($join) {
            $join->on('wg_customer_diagnostic.id', '=', 'wg_customer_diagnostic_prevention.diagnostic_id');

        })->join('wg_progam_prevention_question', function ($join) {
            $join->on('wg_progam_prevention_question.id', '=', 'wg_customer_diagnostic_prevention.question_id');

        })->join('wg_progam_prevention_question_classification', function ($join) {
            $join->on('wg_progam_prevention_question_classification.program_prevention_question_id', '=', 'wg_progam_prevention_question.id');

        })->join('wg_progam_prevention_category', function ($join) {
            $join->on('wg_progam_prevention_category.id', '=', 'wg_progam_prevention_question.category_id');

        })->join('wg_progam_prevention', function ($join) {
            $join->on('wg_progam_prevention.id', '=', 'wg_progam_prevention_category.program_id');

        })->leftjoin('wg_rate', function ($join) {
            $join->on('wg_rate.id', '=', 'wg_customer_diagnostic_prevention.rate_id');

        });

        $query->where('wg_progam_prevention.status', '=', 'activo')
            ->where('wg_progam_prevention_category.status', '=', 'activo')
            ->where('wg_progam_prevention_question.status', '=', 'activo');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->operator == 'inRaw') {
                        $query->whereIn('wg_progam_prevention_question_classification.customer_size', function ($query) use ($item) {
                            $query->select('size')
                                ->from('wg_customers')
                                ->join('wg_customer_diagnostic', function ($join) {
                                    $join->on('wg_customers.id', '=', 'wg_customer_diagnostic.customer_id');

                                })
                                ->where('wg_customer_diagnostic.id', '=', SqlHelper::getPreparedData($item));
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

        $this->pageSize = 0;

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
