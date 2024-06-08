<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Covid\DailyTemperature;

use AdeN\Api\Classes\BaseRepository;

use Exception;
use Log;
use Carbon\Carbon;
use AdeN\Api\Modules\Customer\Covid\Daily\CustomerCovidDailyRepository;
use DB;

class CustomerCovidDailyTemperatureRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerCovidDailyTemperatureModel());

        $this->service = new CustomerCovidDailyTemperatureService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_covid_temperature.id",
            "hour" => DB::raw("DATE_FORMAT(wg_customer_covid_temperature.registration_date, '%H:%i') as hour"),
            "temperature" => "wg_customer_covid_temperature.temperature",
            "pulse" => "wg_customer_covid_temperature.pulse",
            "oximetria" => "wg_customer_covid_temperature.oximetria",
            "observation" => "wg_customer_covid_temperature.observation",
            "customerCovidId" => "wg_customer_covid_temperature.customer_covid_id",
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

        $repoDaily = new CustomerCovidDailyRepository();
        $valueRepoDaily = $repoDaily->find($entity->customerCovidId);

        $reload = false;
        if($entity->id) {
            $maxTemperature = $this->service->getMaxTemperature($entityModel->customerCovidId);
            if(!is_null($maxTemperature) && $maxTemperature->id == $entity->id){
                (new CustomerCovidDailyRepository)->quitFever($entityModel->customerCovidId);
                $reload = true;
            }

            if( !empty($entityModel->oximetria) && (float)$entityModel->oximetria < 95 ||
                 !empty($entityModel->pulse) && (float)$entityModel->pulse > 100 ||
                 !empty($entityModel->pulse) && (float)$entityModel->pulse < 60) {
                    $reload = true;
            }

        }

        if((float)$entity->temperature >= 37.3) {
            $repoDaily->setFever($entity->customerCovidId);
            $reload = true;
        }

        if(!empty($entity->oximetria) && (float)$entity->oximetria < 95 ||
            !empty($entity->pulse) && (float)$entity->pulse > 100 ||
            !empty($entity->pulse) && (float)$entity->pulse < 60) {
                $reload = true;
        }

        $entityModel->customerCovidId = $entity->customerCovidId;
        $entityModel->temperature = $entity->temperature;
        $entityModel->pulse = $entity->pulse;
        $entityModel->oximetria = $entity->oximetria;
        $entityModel->observation = $entity->observation;
        $entityModel->origin = $entity->origin;
        $entityModel->manacleId = !empty($entity->manacleId) ? $entity->manacleId : null;
        $entityModel->latitude = !empty($entity->latitude) ? $entity->latitude : null;
        $entityModel->longitude = !empty($entity->longitude) ? $entity->longitude : null;
        $entityModel->address = !empty($entity->address) ? $entity->address : null;
        $hour = Carbon::parse($valueRepoDaily->registrationDate)->toDateString() ." ". Carbon::parse($entity->registrationDate)->toTimeString();
        $entityModel->registrationDate = Carbon::parse($hour)->timezone('America/Bogota');

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $entityModel->reload = $reload;
        return $entityModel;
    }

    public function parseModelWithRelations(CustomerCovidDailyTemperatureModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->customerCovidId = $model->customerCovidId;
            $entity->temperature = (float)(str_replace(",",".", $model->temperature));
            $entity->pulse = (float)(str_replace(",",".", $model->pulse));
            $entity->oximetria = (float)(str_replace(",",".", $model->oximetria));
            $entity->origin = $model->origin;
            $entity->observation = $model->observation;
            $entity->address = $model->address;
            $entity->registrationDate = Carbon::parse($model->registrationDate);
            return $entity;
        } else {
            return null;
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $reload = false;
        $maxTemperature = $this->service->getMaxTemperature($entityModel->customerCovidId);
        if(!is_null($maxTemperature) && $maxTemperature->id == $id){
            (new CustomerCovidDailyRepository)->quitFever($entityModel->customerCovidId);
            $reload = true;
        }

        if((float)$entityModel->oximetria < 95 ||
            (float)$entityModel->pulse > 100 ||
            (float)$entityModel->pulse < 60) {
                $reload = true;
        }

        $entityModel->delete();
        return $reload;
    }

    public function getTemperatureOfMonth($criteria)
    {
        return $this->service->getTemperatureOfMonth($criteria);
    }

}