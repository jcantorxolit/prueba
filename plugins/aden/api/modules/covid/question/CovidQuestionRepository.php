<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Covid\Question;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CovidQuestionRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CovidQuestionModel());

        $this->service = new CovidQuestionService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_covid_question.id",
            "name" => "wg_covid_question.name",
            "code" => "wg_covid_question.code",
            "riskLevelCode" => "wg_covid_question.risk_level_code",
            "sort" => "wg_covid_question.sort",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_covid_question.parent_id', '=', 'tableParent.id');
		}
		*/


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

        $entityModel->name = $entity->name;
        $entityModel->code = $entity->code ? $entity->code->value : null;
        $entityModel->riskLevelCode = $entity->riskLevelCode ? $entity->riskLevelCode->value : null;
        $entityModel->sort = $entity->sort;


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

    public function getList()
    {
        return $this->service->getList();
    }

    
    public function getGroupList()
    {
        return $this->service->getGroupList();
    }

    public function getRiskLevelList()
    {
        return $this->service->getRiskLevelList();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->name = $model->name;
            $entity->code = $model->getCode();
            $entity->riskLevelCode = $model->getRiskLevelCode();
            $entity->sort = $model->sort;


            return $entity;
        } else {
            return null;
        }
    }
}
