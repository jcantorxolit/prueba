<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\CovidQuestion;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerCovidQuestionRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerCovidQuestionModel());

        $this->service = new CustomerCovidQuestionService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_covid_question.id",
            "customerCovidId" => "wg_customer_covid_question.customer_covid_id",
            "covidQuestionCode" => "wg_customer_covid_question.covid_question_code",
            "isActive" => "wg_customer_covid_question.is_active",
            "observation" => "wg_customer_covid_question.observation",
            "registrationDate" => "wg_customer_covid_question.registration_date",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_covid_question.parent_id', '=', 'tableParent.id');
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

        $entityModel->customerCovidId = $entity->customerCovidId;
        $entityModel->covidQuestionCode = $entity->covidQuestionCode;
        $entityModel->isActive = $entity->isActive;
        $entityModel->observation = $entity->observation;
        $entityModel->registrationDate = Carbon::now('America/Bogota');

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $this->parseModelWithRelations($entityModel);
    }

    public static function bulkInsertOrUpdate($questionList, $parentId)
    {
        $reposity = new self;
        foreach ($questionList as $question) {
            $question->customerCovidId = $parentId;
            $reposity->insertOrUpdate($question);
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }
        
        return $entityModel->delete();
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerCovidId = $model->customerCovidId;
            $entity->covidQuestionId = $model->covidQuestionId;
            $entity->isActive = $model->isActive == 1;
            $entity->observation = $model->observation;
            $entity->registrationDate = $model->registrationDate ? Carbon::parse($model->registrationDate) : null;

            return $entity;
        } else {
            return null;
        }
    }
}
