<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Covid\DailyPersonInTouch;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use AdeN\Api\Modules\Customer\Covid\Daily\CustomerCovidDailyRepository;

class CustomerCovidDailyPersonInTouchRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerCovidDailyPersonInTouchModel());

        $this->service = new CustomerCovidDailyPersonInTouchService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_covid_person_in_touch.id",
            "place" => "wg_customer_covid_person_in_touch.place",
            "person" => "wg_customer_covid_person_in_touch.person",            
            "registrationDate" => "wg_customer_covid_person_in_touch.registration_date",
            "customerCovidId" => "wg_customer_covid_person_in_touch.customer_covid_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

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

        $ccp = new CustomerCovidDailyRepository();
        $ccp = $ccp->find($entity->customerCovidId);

        $entityModel->customerCovidId = $entity->customerCovidId;
        $entityModel->place = $entity->place;
        $entityModel->person = $entity->person;
        $entityModel->registrationDate = $ccp->registrationDate;


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
            $entity->place = $model->place;
            $entity->person = $model->person;
            $entity->registrationDate = $model->registrationDate ? Carbon::parse($model->registrationDate) : null;

            return $entity;
        } else {
            return null;
        }
    }
}
