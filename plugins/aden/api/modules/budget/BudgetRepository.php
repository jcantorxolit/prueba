<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Budget;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class BudgetRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new BudgetModel());

        $this->service = new BudgetService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_budget.id",
            "item" => "wg_budget.item",
            "classification" => "project_type.item AS classification",
            "description" => "wg_budget.description",
            "year" => "wg_budget_detail.year",
            "amount" => "wg_budget_detail.amount"            
        ]);

        $this->parseCriteria($criteria);

        $qDetail = DB::table('wg_budget_detail')
            ->select(
                'wg_budget_detail.budget_id',
                'wg_budget_detail.year',
                DB::raw("SUM(wg_budget_detail.amount) AS amount")
            )
            ->groupBy('wg_budget_detail.budget_id', 'wg_budget_detail.year');

        $query = $this->query();

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('project_type')), function ($join) {
            $join->on('project_type.value', '=', 'wg_budget.classification');

        })->leftjoin(DB::raw("({$qDetail->toSql()}) AS wg_budget_detail"), function ($join) {
            $join->on('wg_budget_detail.budget_id', '=', 'wg_budget.id');

        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (! ($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->item = $entity->item;
        $entityModel->description = $entity->description;
        $entityModel->classification = $entity->classification ? $entity->classification->value : null;
        $entityModel->createdby = $entity->createdby;
        $entityModel->updatedby = $entity->updatedby;


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
        if (! ($entityModel = $this->find($id))) {
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

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->item = $model->item;
            $entity->description = $model->description;
            $entity->classification = $model->getClassification();
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }
}
