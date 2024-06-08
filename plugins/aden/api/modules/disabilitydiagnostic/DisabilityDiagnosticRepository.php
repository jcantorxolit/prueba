<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\DisabilityDiagnostic;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class DisabilityDiagnosticRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new DisabilityDiagnosticModel());

        $this->service = new DisabilityDiagnosticService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_disability_diagnostic.id",
            "code" => "wg_disability_diagnostic.code",
            "description" => "wg_disability_diagnostic.description",
            "status" => "estado.item AS status",
            "isActive" => "wg_disability_diagnostic.isActive",           
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        
		$query->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_disability_diagnostic.isActive', '=', 'estado.value');

        });
        
        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allEmployee($criteria)
    {
        $this->setColumns([
            "id" => "wg_disability_diagnostic.id",
            "code" => "wg_disability_diagnostic.code",
            "description" => "wg_disability_diagnostic.description",
            "status" => "estado.item AS status",
            "isActive" => "wg_disability_diagnostic.isActive",     
            "customerEmployeeId" => "wg_customer_absenteeism_disability.customer_employee_id"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        
		$query->join("wg_customer_absenteeism_disability", function ($join) {
            $join->on('wg_customer_absenteeism_disability.diagnostic_id', '=', 'wg_disability_diagnostic.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_disability_diagnostic.isActive', '=', 'estado.value');

        })->groupBy('wg_disability_diagnostic.code');
        
        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allSourceEmployee($criteria)
    {
        $this->setColumns([
            "id" => "wg_disability_diagnostic.id",
            "code" => "wg_disability_diagnostic.code",
            "description" => "wg_disability_diagnostic.description",
            "status" => "estado.item AS status",
            "isActive" => "wg_disability_diagnostic.isActive",    
            "customerEmployeeId" => "wg_customer_health_damage_diagnostic_source.customer_employee_id"
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        
		$query->join("wg_customer_health_damage_diagnostic_source_detail", function ($join) {
            $join->on('wg_customer_health_damage_diagnostic_source_detail.codeCIE10', '=', 'wg_disability_diagnostic.id');

        })->join("wg_customer_health_damage_diagnostic_source", function ($join) {
            $join->on('wg_customer_health_damage_diagnostic_source.id', '=', 'wg_customer_health_damage_diagnostic_source_detail.customer_health_damage_diagnostic_source_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('estado')), function ($join) {
            $join->on('wg_disability_diagnostic.isActive', '=', 'estado.value');

        })->groupBy('wg_disability_diagnostic.code');
        
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

        $entityModel->code = $entity->code;
        $entityModel->description = $entity->description;
        $entityModel->isActive = $entity->isActive == 1;

        if ($isNewRecord) {       
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;            
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

        return true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->code = $model->code;
            $entity->description = $model->description;
            $entity->isActive = $model->isActive == 1;            

            return $entity;
        } else {
            return null;
        }
    }
}
