<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Budget\Detail;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class BudgetDetailRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new BudgetDetailModel());

        $this->service = new BudgetDetailService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_budget_detail.id",
            "period" => "wg_budget_detail.period",            
            "amount" => "wg_budget_detail.amount",
            "budgetId" => "wg_budget_detail.budget_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
        $year = $entity->year ? $entity->year->value : null;
        $month = $entity->month ? $entity->month->value : null;
        
        if (!$entity->id) {
            return !$this->model->where('year', $year)
                ->where('month', $month)                
                ->where('budget_id', $entity->budgetId)                
                ->count() > 0;
        }

        return true;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (! ($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->budgetId = $entity->budgetId;
        $entityModel->year = $entity->year ? $entity->year->value : null;
        $entityModel->month = $entity->month ? $entity->month->value : null;
        $entityModel->period = $entityModel->year . str_pad($entityModel->month, 2, "0", STR_PAD_LEFT);
        $entityModel->amount = $entity->amount;


        if ($isNewRecord) {            
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

        $entityModel->delete();

        return $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->budgetId = $model->budgetId;
            $entity->period = $model->period;
            $entity->year = $model->getYear();
            $entity->month = $model->getMonth();
            $entity->amount = $model->amount;            

            return $entity;
        } else {
            return null;
        }
    }
}
