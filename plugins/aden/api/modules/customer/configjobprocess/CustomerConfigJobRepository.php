<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigJobProcess;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;

class CustomerConfigJobRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerConfigJobModel());

        $this->service = new CustomerConfigJobService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Número Identificación", "name" => "documentNumber"],
            ["alias" => "Nombre", "name" => "firstName"],
            ["alias" => "Apellidos", "name" => "lastName"],
            ["alias" => "Tipo Ausentismo", "name" => "category"],
            ["alias" => "Tipo Incapacidad", "name" => "typeText"],
            ["alias" => "Causa incapacidad", "name" => "causeItem"],
            ["alias" => "Fecha Inicial", "name" => "start"],
            ["alias" => "Fecha Final", "name" => "end"],
        ];
    }

    public function getMandatoryFilters()
    {
        return [
            array("field" => 'isActive', "operator" => 'eq', "value" => '1'),
        ];
    }

    public function all($criteria)
    {
        $urlImages = "uploads/public";

        $this->setColumns([
            "id" => "wg_customer_config_job.id",
            "workPlace" => "wg_customer_config_workplace.name as work_place",
            "macroProcess" => "wg_customer_config_macro_process.name as macro_process",
            "process" => "wg_customer_config_process.name as process",
            "job" => "wg_customer_config_job_data.name as job",
            "customerId" => "wg_customer_config_job.customer_id",
            "workPlaceId" => "wg_customer_config_job.workplace_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_customer_config_job_data", function ($join) {
            $join->on('wg_customer_config_job.job_id', '=', 'wg_customer_config_job_data.id');

        })->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_job.workplace_id', '=', 'wg_customer_config_workplace.id');

        })->join("wg_customer_config_macro_process", function ($join) {
            $join->on('wg_customer_config_job.macro_process_id', '=', 'wg_customer_config_macro_process.id');

        })->join("wg_customer_config_process", function ($join) {
            $join->on('wg_customer_config_job.process_id', '=', 'wg_customer_config_process.id');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_config_job.createdBy', '=', 'users.id');

        });

        $query->where('wg_customer_config_job.status', '=', 'Activo')
            ->where('wg_customer_config_workplace.status', '=', 'Activo')
            ->where('wg_customer_config_macro_process.status', '=', 'Activo')
            ->where('wg_customer_config_process.status', '=', 'Activo');

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                $query->where(function ($query) use ($filter) {
                    foreach ($filter->filters as $key => $item) {
                        try {
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                        } catch (Exception $ex) {
                        }
                    }
                });
            }
        }

        $result["data"] = $this->parseModel(($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns));
        $result["recordsTotal"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["recordsFiltered"] = ($this->pageSize > 0) ? $query->paginate($this->pageSize)->total() : $query->get()->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customer_id = $entity->customerId;
        $entityModel->workplace_id = $entity->workplace ? $entity->workplace->id : null;
        $entityModel->macro_process_id = $entity->macroprocess ? $entity->macroprocess->id : null;
        $entityModel->process_id = $entity->process ? $entity->process->id : null;
        $entityModel->job_id = $entity->job ? $entity->job->id : null;
        $entityModel->status = $entity->status ? $entity->status->value : null;

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            //$entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        return $entityModel;
    }

    public function findOrCreate($model)
    {
        $entity = $this->model->where('workplace_id', $model->workplace->id)
            ->where('customer_id', $model->customerId)
            ->where('macro_process_id', $model->macroprocess->id)
            ->where('process_id', $model->process->id)
            ->where('job_id', $model->job->id)
            ->where('status', 'Activo')
            ->first();

        if ($entity == null) {
            $entity = new \stdClass();
            $entity->id = 0;
            $entity->customerId = $model->customerId;
            $entity->workplace = $model->workplace;
            $entity->macroprocess = $model->macroprocess;
            $entity->process = $model->process;
            $entity->job = $model->job;
            $entity->status = new \stdClass();
            $entity->status->value = 'Activo';
            $entity = $this->insertOrUpdate($entity);
        }

        return $entity;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->workplace = $model->getWorkplace();
            $entity->macroprocess = $model->getMacroprocess();
            $entity->process = $model->getProcess();
            $entity->job = $model->getJobData();
            $entity->jobId = $model->job_id;

            return $entity;
        } else {
            return null;
        }
    }
}
