<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Covid\QuestionGroup;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CovidQuestionGroupRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CovidQuestionGroupModel());

        $this->service = new CovidQuestionGroupService();
    }

    public function all($criteria)
    {        
        $this->setColumns([
"id" => "wg_covid_question_group.id",
"questionCode" => "wg_covid_question_group.question_code",
"riskLevelCode" => "wg_covid_question_group.risk_level_code",
"groupName" => "wg_covid_question_group.group_name",
]);

        $this->parseCriteria($criteria);

        $query = $this->query();

		/* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_covid_question_group.parent_id', '=', 'tableParent.id');
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

        $entityModel->questionCode = $entity->questionCode ? $entity->questionCode->value : null;
$entityModel->riskLevelCode = $entity->riskLevelCode ? $entity->riskLevelCode->value : null;
$entityModel->groupName = $entity->groupName ? $entity->groupName->value : null;


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

            //Mapping fields
            $entity = new \stdClass();
  
			$entity->id = $model->id;
$entity->questionCode = $model->getQuestionCode();
$entity->riskLevelCode = $model->getRiskLevelCode();
$entity->groupName = $model->getGroupName();

  
            return $entity;
        } else {
            return null;
        }
    }
}