<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Covid\DailyPersonNear;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\ExportHelper;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerCovidDailyPersonNearRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerCovidDailyPersonNearModel());
        $this->service = new CustomerCovidDailyPersonNearService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_covid_person_near.id",
            "manacleId" => "wg_customer_manacle.number as manacle_id",
            "distance" => "distance",
            "name" => "wg_employee.firstName as name",            
            "lastName" => "wg_employee.lastName as last_name",
            "workplace" => "wg_customer_config_workplace.name as workplace",
            "customerCovidId" => "wg_customer_covid.id AS customerCovidId"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

        $query->join("wg_customer_covid", function($join){
            $join->on("wg_customer_covid_person_near.customer_covid_id","=","wg_customer_covid.id");
        })
        ->join("wg_customer_covid_head", function ($join) {
            $join->on('wg_customer_covid_head.id', '=', 'wg_customer_covid.id');
        })
        ->join("wg_customer_manacle_employee", function ($join) {
            $join->on('wg_customer_covid_person_near.manacle_employee_id', '=', 'wg_customer_manacle_employee.id');
        })
        ->join("wg_customer_manacle", function ($join) {
            $join->on('wg_customer_manacle_employee.manacle_id', '=', 'wg_customer_manacle.id');
        })
        ->leftjoin("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_manacle_employee.customer_employee_id');
        })
        ->leftjoin("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
        })
        ->leftjoin("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_employee.workPlace');
        });

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
        $entityModel->manacleEmployeeId = $entity->manacleEmployeeId;
        $entityModel->distance = $entity->distance;

        if ($isNewRecord) {            
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entityModel;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        return $entityModel->delete();        
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportData($criteria);
        $filename = 'Historico_Covid_Personas_Cercanas_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'Registros', $data);
    }
}
