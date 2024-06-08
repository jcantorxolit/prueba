<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\MinimumStandard0312\MinimumStandardItemQuestion0312;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class MinimumStandardItemQuestion0312Repository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new MinimumStandardItemQuestion0312Model());

        $this->service = new MinimumStandardItemQuestion0312Service();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "programPreventionQuestionId" => "wg_minimum_standard_item_question_0312.program_prevention_question_id",            
            "program" => "wg_progam_prevention.name AS program",
            "category" => "wg_progam_prevention_category.name AS category",
            "question" => "wg_progam_prevention_question.description AS question",
            "article" => "wg_progam_prevention_question.article",
            "guide" => DB::raw("CASE WHEN wg_progam_prevention_question.guide IS NOT NULL AND wg_progam_prevention_question.guide <> '' THEN 'Tiene' ELSE 'No Tiene' END AS guide"),
            
            "id" => "wg_minimum_standard_item_question_0312.id",
            "minimumStandardItemId" => "wg_minimum_standard_item_question_0312.minimum_standard_item_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_progam_prevention_question", function ($join) {
            $join->on('wg_progam_prevention_question.id', '=', 'wg_minimum_standard_item_question_0312.program_prevention_question_id');
        })->join("wg_progam_prevention_category", function ($join) {
            $join->on('wg_progam_prevention_category.id', '=', 'wg_progam_prevention_question.category_id');
        })->join("wg_progam_prevention", function ($join) {
            $join->on('wg_progam_prevention.id', '=', 'wg_progam_prevention_category.program_id');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allAvailable($criteria)
    {
        $this->setColumns([
            "programPreventionQuestionId" => "wg_progam_prevention_question.id AS programPreventionQuestionId",
            "program" => "wg_progam_prevention.name AS program",
            "category" => "wg_progam_prevention_category.name AS category",
            "question" => "wg_progam_prevention_question.description AS question",
            "article" => "wg_progam_prevention_question.article",
            "guide" => DB::raw("CASE WHEN wg_progam_prevention_question.guide IS NOT NULL AND wg_progam_prevention_question.guide <> '' THEN 'Tiene' ELSE 'No Tiene' END AS guide"),
            
            "id" => "wg_minimum_standard_item_question_0312.id",
            "minimumStandardItemId" => "wg_minimum_standard_item_question_0312.minimum_standard_item_id AS minimumStandardItemId",
        ]);

        $this->parseCriteria($criteria);

        $qItem = DB::table('wg_minimum_standard_item_question_0312')
            ->select(
                'wg_minimum_standard_item_question_0312.id',
                'wg_minimum_standard_item_question_0312.minimum_standard_item_id',
                'wg_minimum_standard_item_question_0312.program_prevention_question_id'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'minimumStandardItemId') {
                        $qItem->where(SqlHelper::getPreparedField('wg_minimum_standard_item_question_0312.minimum_standard_item_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = $this->query(DB::table('wg_progam_prevention'))
            ->mergeBindings($qItem);

        $query->join("wg_progam_prevention_category", function ($join) {
            $join->on('wg_progam_prevention_category.program_id', '=', 'wg_progam_prevention.id');
        })->join("wg_progam_prevention_question", function ($join) {
            $join->on('wg_progam_prevention_question.category_id', '=', 'wg_progam_prevention_category.id');
        })->leftjoin(DB::raw("({$qItem->toSql()}) as wg_minimum_standard_item_question_0312"), function ($join) {
            $join->on('wg_minimum_standard_item_question_0312.program_prevention_question_id', '=', 'wg_progam_prevention_question.id');
        })
            ->where('wg_progam_prevention.status', 'activo')
            ->where('wg_progam_prevention_category.status', 'activo')
            ->where('wg_progam_prevention_question.status', 'activo')
            ->whereNull('wg_minimum_standard_item_question_0312.id');

        $this->applyCriteria($query, $criteria, ['minimumStandardItemId']);

        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
        return $this->model->where('minimum_standard_item_id', $entity->minimumStandardItemId)
            ->where('program_prevention_question_id', $entity->programPreventionQuestionId)
            ->count() == 0;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->minimumStandardItemId = $entity->minimumStandardItemId;
        $entityModel->programPreventionQuestionId = $entity->programPreventionQuestionId;

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

    public function bulkInsertOrUpdate($entity)
    {
        foreach ($entity->questions as $record) {
            $record->id = 0;
            $record->minimumStandardItemId = $entity->minimumStandardItemId;
            if ($this->canInsert($record)) {
                $this->insertOrUpdate($record);
            }
        }

        return true;
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

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->minimumStandardItemId = $model->minimumStandardItemId;
            $entity->programPreventionQuestionId = $model->programPreventionQuestionId;
            $entity->createdAt = $model->createdAt;
            $entity->createdBy = $model->createdBy;
            $entity->updatedAt = $model->updatedAt;
            $entity->updatedBy = $model->updatedBy;


            return $entity;
        } else {
            return null;
        }
    }
}
