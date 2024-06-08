<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Management;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

use Wgroup\SystemParameter\SystemParameter;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Modules\Customer\ManagementProgram\CustomerManagementProgramRepository;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Wgroup\Classes\ServiceCustomerManagement;

class CustomerManagementRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerManagementModel());

        $this->service = new CustomerManagementService();
    }

    public static function getCustomFilters()
    {
        return [];
    }

    public function getMandatoryFilters()
    {
        return [];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_management.id",
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "economicSector" => "wg_economic_sector.name AS economicSector",
            "program" => "wg_program_management.name AS program",
            "createdAt" => "wg_customer_management.created_at as createdAt",
            //"endDate" => "wg_customer_management.endDate",
            "createdBy" => "users.name AS createdBy",
            "status" => "management_status.item AS status",
            "abbreviation" => "wg_program_management.abbreviation",
            "statusCode" => "wg_customer_management.status AS statusCode",
            "customerId" => "wg_customer_management.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_customer_management_program", function ($join) {
            $join->on('wg_customer_management_program.management_id', '=', 'wg_customer_management.id');
        })->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_management_program.customer_workplace_id');
            $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_management.customer_id');
        })->join("wg_program_management_economic_sector", function ($join) {
            $join->on('wg_program_management_economic_sector.id', '=', 'wg_customer_management_program.program_economic_sector_id');
        })->join("wg_program_management", function ($join) {
            $join->on('wg_program_management.id', '=', 'wg_program_management_economic_sector.program_id');
        })->join("wg_economic_sector", function ($join) {
            $join->on('wg_economic_sector.id', '=', 'wg_program_management_economic_sector.economic_sector_id');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_management.createdBy', '=', 'users.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('management_status')), function ($join) {
            $join->on('wg_customer_management.status', '=', 'management_status.value');
        })
            ->where('wg_customer_management_program.active', '1')
            ->where('wg_program_management.status', 'activo');

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allSummary($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_management.id",
            "workplace" => "wg_customer_config_workplace.name AS workplace",
            "economicSector" => "wg_economic_sector.name AS economicSector",
            "program" => "wg_program_management.name AS program",
            "createdAt" => "wg_customer_management.created_at",
            "endDate" => "wg_customer_management.endDate",
            "createdBy" => "users.name AS createdBy",
            "status" => "management_status.item AS status",
            "abbreviation" => "wg_program_management.abbreviation",
            "customerId" => "wg_customer_management.customer_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_customer_management_program", function ($join) {
            $join->on('wg_customer_management_program.management_id', '=', 'wg_customer_management.id');
        })->join("wg_customer_config_workplace", function ($join) {
            $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_management_program.customer_workplace_id');
            $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_management.customer_id');
        })->join("wg_program_management_economic_sector", function ($join) {
            $join->on('wg_program_management_economic_sector.id', '=', 'wg_customer_management_program.program_economic_sector_id');
        })->join("wg_program_management", function ($join) {
            $join->on('wg_program_management.id', '=', 'wg_program_management_economic_sector.program_id');
        })->join("wg_economic_sector", function ($join) {
            $join->on('wg_economic_sector.id', '=', 'wg_program_management_economic_sector.economic_sector_id');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_management.createdBy', '=', 'users.id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('management_status')), function ($join) {
            $join->on('wg_customer_management.status', '=', 'management_status.value');
        });

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allSummaryIndicator($criteria)
    {
        $this->setColumns([
            "program" => "indicator.name",
            "abbreviation" => "indicator.abbreviation",
            "workplace" => "indicator.workplace",
            "questions" => "indicator.questions",
            "answers" => "indicator.answers",
            "advance" => DB::raw("ROUND((indicator.answers / indicator.questions) * 100, 2) AS advance"),
            "average" => DB::raw("ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END ), 0 ), 2 ) AS average"),
            "total" => "indicator.total",
            "id" => "indicator.id",
            "customerId" => "indicator.customerId",
            "programId" => "indicator.programId",
            "workplaceId" => "indicator.workplaceId",
            "year" => "indicator.year"
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $qBase = $this->service->prepareSubQueryBase($criteria);

        $query = $this->query(DB::table(DB::raw("({$qBase->toSql()}) as indicator")))
            ->mergeBindings($qBase)
            ->where('indicator.status', '<>', 'cancelado');

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allSummaryResponsible($criteria)
    {
        $this->setColumns([
            "workplace" => "summary.workplace",
            "responsible" => "summary.responsible",
            "qty" => "summary.qty",
            "customerId" => "summary.customer_id",
            "programId" => "summary.programId",
            "workplaceId" => "summary.workplaceId",
            "year" =>  "summary.year",
            'status' => 'summary.status'
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $query = $this->service->prepareQueryBase($criteria);

        $query->join('wg_customer_management_detail', function ($join) {
            $join->on('wg_customer_management_detail.management_id', '=', 'wg_customer_management.id');
            $join->on('wg_customer_management_detail.question_id', '=', 'wg_program_management_question.id');
        })->join('wg_customer_improvement_plan', function ($join) {
            $join->on('wg_customer_improvement_plan.customer_id', '=', 'wg_customer_management.customer_id');
            $join->on('wg_customer_improvement_plan.entityId', '=', 'wg_customer_management_detail.id');
            $join->where('wg_customer_improvement_plan.entityName', '=', 'PE');
        })->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible"), function ($join) {
            $join->on('wg_customer_improvement_plan.responsible', '=', 'responsible.id');
            $join->on('wg_customer_improvement_plan.responsibleType', '=', 'responsible.type');
            $join->on('wg_customer_improvement_plan.customer_id', '=', 'responsible.customer_id');
        })
            ->select(
                "wg_customer_config_workplace.name AS workplace",
                "responsible.name AS responsible",
                DB::raw("COUNT(*) AS qty"),
                "wg_customer_management.customer_id",
                "wg_program_management_economic_sector.program_id AS programId",
                "wg_customer_config_workplace.id AS workplaceId",
                DB::raw("YEAR(wg_customer_management.created_at) AS year"),
                'wg_customer_improvement_plan.status'
            )
            ->mergeBindings($qAgentUser)
            ->where('wg_customer_improvement_plan.status', 'AB')
            ->where('wg_customer_management.status', '<>', 'cancelado')
            ->groupBy(
                'wg_customer_improvement_plan.responsible',
                'wg_customer_improvement_plan.responsibleType',
                'wg_customer_improvement_plan.customer_id'
            );

        $query = DB::table(DB::raw("({$query->toSql()}) as summary"))
            ->mergeBindings($query);


        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allComment($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_management_comment.id",
            "comment" => "wg_customer_management_comment.comment",
            "createdBy" => "users.name AS createdBy",
            "createdAt" => "wg_customer_management_comment.created_at",
            "customerDiagnosticId" => "wg_customer_management_comment.customer_tracking_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->join('wg_customer_management_comment', function ($join) {
            $join->on('wg_customer_management.id', '=', 'wg_customer_management_comment.customer_tracking_id');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_management_comment.createdBy', '=', 'users.id');
        });

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
                            $query->where(SqlHelper::getPreparedField($this->filterColumns[$item->field]), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
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

    public function allAvailableEconomicSector($criteria)
    {
        $this->setColumns([
            "id" => "wg_program_management_economic_sector.id",
            "abbreviation" => "wg_program_management.abbreviation",
            "program" => "wg_program_management.name",
            "programId" => "wg_program_management.id AS programId",
            "economicSectorId" => "wg_economic_sector.id AS economicSectorId",
            "isActive" => DB::raw("IFNULL(wg_customer_management.customerManagementId, 0) as isActive"),
            "customerManagementId" => DB::raw("IFNULL(wg_customer_management.customerManagementId, 0) AS customerManagementId"),
            "customerManagementProgramId" => DB::raw("IFNULL(wg_customer_management.customerManagementProgramId, 0) AS customerManagementProgramId"),
            "workplaceId" => DB::raw("IFNULL(wg_customer_management.workplaceId, 0) AS workplaceId"),
            "status" => "wg_customer_management.status",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $workplaceId = CriteriaHelper::getMandatoryFilter($criteria, 'workplaceId');

        $qCustomerProgram = DB::table('wg_customer_management')
            ->join("wg_customer_management_program", function ($join) {
                $join->on('wg_customer_management_program.management_id', '=', 'wg_customer_management.id');
            })
            ->select(
                'wg_customer_management.id AS customerManagementId',
                'wg_customer_management_program.id AS customerManagementProgramId',
                'wg_customer_management_program.program_id AS programId',
                'wg_customer_management_program.program_economic_sector_id AS programEconomicSectorId',
                'wg_customer_management_program.customer_workplace_id AS workplaceId',
                'wg_customer_management.status'
            )
            ->where('customer_id', $customerId->value)
            ->where('wg_customer_management.status', 'iniciado');

        if ($workplaceId) {
            $qCustomerProgram->where('wg_customer_management_program.customer_workplace_id', $workplaceId->value);
        }

        $query = $this->query(DB::table('wg_program_management_economic_sector'));

        /* Example relation*/
        $query->join("wg_program_management", function ($join) {
            $join->on('wg_program_management.id', '=', 'wg_program_management_economic_sector.program_id');
        })->join("wg_economic_sector", function ($join) {
            $join->on('wg_economic_sector.id', '=', 'wg_program_management_economic_sector.economic_sector_id');
        })->leftjoin(DB::raw("({$qCustomerProgram->toSql()}) AS wg_customer_management"), function ($join) {
            $join->on('wg_customer_management.programEconomicSectorId', '=', 'wg_program_management_economic_sector.id');
        })->mergeBindings($qCustomerProgram)
            ->where('wg_program_management.status', 'activo');

        $this->applyCriteria($query, $criteria, ['customerId', 'workplaceId']);

        return $this->get($query, $criteria);
    }

    public function canInsert($entity)
    {
        $criteria = CmsHelper::parseToStdClass([
            'programId' => $entity->programId,
            'programEconomicSectorId' => $entity->programEconomicSector->id,
            'customerWorkplaceId' => $entity->customerWorkplace->id,
            'customerId' => $entity->customerId,
            'isActive' => true,
        ]);

        if ($entity->id == 0) {
            // if ($this->service->isWorkplaceInOpenManagement($criteria)) {
            //     throw new BadRequestHttpException("No es posible guardar el registro. Ya existe una evaluación en proceso para el centro de trabajo.");
            // }
            $criteria->id = $entity->id;
            return $this->service->find($criteria) == null;
        } else {
            // if ($this->service->isWorkplaceInOpenManagement($criteria)) {
            //     throw new BadRequestHttpException("No es posible guardar el registro. Ya existe una evaluación en proceso para el centro de trabajo.");
            // }
            $entityToCompare = $this->service->find($criteria);
            return $entityToCompare->id != $entity->id;
        }
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

        if ($isNewRecord) {
            $entityModel->status = 'iniciado';
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        CustomerManagementProgramRepository::create($entity, $entityModel->id);

        (new ServiceCustomerManagement)->saveManagementQuestion($entityModel);

        return $entityModel;
    }

    public function bulkInsertOrUpdate($records, $customerId, $type, $origin)
    {
        foreach ($records as $record) {

            $this->insertOrUpdate($record);
        }

        return true;
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
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;

            return $entity;
        } else {
            return null;
        }
    }

    public function getChartBar($criteria)
    {
        return $this->service->getChartBar($criteria);
    }

    public function getChartPie($criteria)
    {
        return $this->service->getChartPie($criteria);
    }

    public function getStats($criteria)
    {
        return $this->service->getStats($criteria);
    }

    public function getPrograms($diagnosticId)
    {
        return $this->service->getPrograms($diagnosticId);
    }

    public function getYearList($criteria)
    {
        return $this->service->getYearList($criteria);
    }

    public function getProgramList($criteria)
    {
        return $this->service->getProgramList($criteria);
    }

    public function getWorkplaceList($criteria)
    {
        return $this->service->getWorkplaceList($criteria);
    }

    public function getWorkplaceByYears($criteria)
    {
        return $this->service->getWorkplaceByYears($criteria);
    }

    public function getCategoryList($criteria)
    {
        return $this->service->getCategoryList($criteria);
    }

    public function getQuestionList($criteria)
    {
        return $this->service->getQuestionList($criteria);
    }

    public function getAvegareProgramChartBar($criteria)
    {
        return $this->service->getAvegareProgramChartBar($criteria);
    }

    public function getImprovementPlanStatusChartBar($criteria)
    {
        return $this->service->getImprovementPlanStatusChartBar($criteria);
    }

    public function getValorationChartBar($criteria)
    {
        return $this->service->getValorationChartBar($criteria);
    }

    public function getProgramsWithRateAndPercent(int $customerId, $period, $workplaceId)
    {
        return $this->service->getProgramsWithRateAndPercent($customerId, $period, $workplaceId);
    }
}
