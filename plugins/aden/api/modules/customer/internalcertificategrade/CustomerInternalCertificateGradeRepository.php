<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\InternalCertificateGrade;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class CustomerInternalCertificateGradeRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerInternalCertificateGradeModel());

        $this->service = new CustomerInternalCertificateGradeService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_internal_certificate_grade.id",
            "name" => "wg_customer_internal_certificate_grade.name",
            "program" => "wg_customer_internal_certificate_program.name AS program",
            "category" => "certificate_program_category.item AS category",
            "capacity" => "wg_customer_internal_certificate_program.capacity",
            "registered" => DB::raw("IFNULL(wg_customer_internal_certificate_grade_participant.qty, 0) AS registered"),
            "quota" => DB::raw("wg_customer_internal_certificate_program.capacity - IFNULL(wg_customer_internal_certificate_grade_participant.qty, 0) AS quota"),
            "status" => "certificate_grade_status.item AS status",

            "location" => "wg_customer_internal_certificate_grade.location",
            "statusCode" => "wg_customer_internal_certificate_grade.status AS statusCode",
            "programId" => "wg_customer_internal_certificate_program.id AS programId",

            "customerId" => "wg_customer_internal_certificate_program.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $qParticipant = $this->service->getParticipantQuery();
        $qCalendar = $this->service->getCalendarQuery($criteria);
        $qAgent = $this->service->getAgentQuery($criteria);

        $query = $this->query();

        /* Example relation*/
        $query->leftjoin("wg_customer_internal_certificate_program", function ($join) {
            $join->on('wg_customer_internal_certificate_program.id', '=', 'wg_customer_internal_certificate_grade.customer_internal_certificate_program_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('certificate_program_category')), function ($join) {
            $join->on('certificate_program_category.value', '=', 'wg_customer_internal_certificate_program.category');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('certificate_grade_location')), function ($join) {
            $join->on('certificate_grade_location.value', '=', 'wg_customer_internal_certificate_grade.location');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('certificate_grade_status')), function ($join) {
            $join->on('certificate_grade_status.value', '=', 'wg_customer_internal_certificate_grade.status');
        })->leftjoin(DB::raw("({$qParticipant->toSql()}) AS wg_customer_internal_certificate_grade_participant"), function ($join) {
            $join->on('wg_customer_internal_certificate_grade_participant.customer_internal_certificate_grade_id', '=', 'wg_customer_internal_certificate_grade.id');
        })->mergeBindings($qParticipant);

        if ($qCalendar) {
            $query->join(DB::raw("({$qCalendar->toSql()}) AS wg_customer_internal_certificate_grade_calendar"), function ($join) {
                $join->on('wg_customer_internal_certificate_grade_calendar.customer_internal_certificate_grade_id', '=', 'wg_customer_internal_certificate_grade.id');
            })
                ->mergeBindings($qCalendar);
        }

        if ($qAgent) {
            $query->join(DB::raw("({$qAgent->toSql()}) AS wg_customer_internal_certificate_grade_agent"), function ($join) {
                $join->on('wg_customer_internal_certificate_grade_agent.customer_internal_certificate_grade_id', '=', 'wg_customer_internal_certificate_grade.id');
            })
                ->mergeBindings($qAgent);
        }

        $this->applyCriteria($query, $criteria, ['agentId', 'startDate', 'endDate']);

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

        $entityModel->customerInternalCertificateProgramId = $entity->customerInternalCertificateProgramId ? $entity->customerInternalCertificateProgramId->id : null;
        $entityModel->code = $entity->code;
        $entityModel->name = $entity->name;
        $entityModel->location = $entity->location ? $entity->location->value : null;
        $entityModel->description = $entity->description;
        $entityModel->status = $entity->status;
        $entityModel->createdby = $entity->createdby;
        $entityModel->updatedby = $entity->updatedby;
        $entityModel->isdeleted = $entity->isdeleted == 1;


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
            $entity->customerInternalCertificateProgramId = $model->customerInternalCertificateProgramId;
            $entity->code = $model->code;
            $entity->name = $model->name;
            $entity->location = $model->getLocation();
            $entity->description = $model->description;
            $entity->status = $model->status;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;
            $entity->isdeleted = $model->isdeleted;


            return $entity;
        } else {
            return null;
        }
    }

    public function getProgramList($criteria)
    {
        return $this->service->getProgramList($criteria);
    }
}
