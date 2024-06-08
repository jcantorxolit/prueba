<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Certificate\GradeParticipant;

use AdeN\Api\Classes\BaseRepository;
use Carbon\Carbon;
use DB;
use Exception;
use Wgroup\SystemParameter\SystemParameter;

class CertificateGradeParticipantRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CertificateGradeParticipantModel());

        $this->service = new CertificateGradeParticipantService();
    }

    public static function getSearchCustomFilters()
    {
        return [
            ["alias" => "Tipo de Identificación", "name" => "documentType"],
            ["alias" => "Identificación", "name" => "documentNumber"],
            ["alias" => "Nombres", "name" => "name"],
            ["alias" => "Apellidos", "name" => "lastName"],
            ["alias" => "Empresa", "name" => "customer"],
            ["alias" => "Curso", "name" => "grade"],
            ["alias" => "Fecha", "name" => "certificateCreatedAt"],
            ["alias" => "Fecha Vencimiento", "name" => "certificateExpirationAt"],
            ["alias" => "Origen", "name" => "origin"],
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_certificate_grade_participant.id",
            "certificateGradeId" => "wg_certificate_grade_participant.certificate_grade_id",
            "customerId" => "wg_certificate_grade_participant.customer_id",
            "documenttype" => "wg_certificate_grade_participant.documentType",
            "identificationnumber" => "wg_certificate_grade_participant.identificationNumber",
            "name" => "wg_certificate_grade_participant.name",
            "lastname" => "wg_certificate_grade_participant.lastName",
            "workcenter" => "wg_certificate_grade_participant.workCenter",
            "amount" => "wg_certificate_grade_participant.amount",
            "channel" => "wg_certificate_grade_participant.channel",
            "countryOriginId" => "wg_certificate_grade_participant.country_origin_id",
            "countryResidenceId" => "wg_certificate_grade_participant.country_residence_id",
            "isapproved" => "wg_certificate_grade_participant.isApproved",
            "hascertificate" => "wg_certificate_grade_participant.hasCertificate",
            "countdownloads" => "wg_certificate_grade_participant.countDownloads",
            "validatecodecertificate" => "wg_certificate_grade_participant.validateCodeCertificate",
            "certificatecreatedat" => "wg_certificate_grade_participant.certificateCreatedAt",
            "generatedby" => "wg_certificate_grade_participant.generatedBy",
            "createdby" => "wg_certificate_grade_participant.createdBy",
            "updatedby" => "wg_certificate_grade_participant.updatedBy",
            "createdAt" => "wg_certificate_grade_participant.created_at",
            "updatedAt" => "wg_certificate_grade_participant.updated_at",
            "isdeleted" => "wg_certificate_grade_participant.isDeleted",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
        $query->leftjoin("tableParent", function ($join) {
        $join->on('wg_certificate_grade_participant.parent_id', '=', 'tableParent.id');
        }
         */

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allSearch($criteria)
    {
        $this->setColumns([
            "id" => "wg_certificate_grade_participant.id",
            "documentType" => "wg_certificate_grade_participant.documentType",
            "documentNumber" => "wg_certificate_grade_participant.identificationNumber",
            "name" => "wg_certificate_grade_participant.name",
            "lastName" => "wg_certificate_grade_participant.lastName",
            "customer" => "wg_certificate_grade_participant.customer",
            "grade" => "wg_certificate_grade_participant.grade",
            "certificateCreatedAt" => "wg_certificate_grade_participant.certificateCreatedAt",
            "certificateExpirationAt" => "wg_certificate_grade_participant.certificateExpirationAt",
            "origin" => "wg_certificate_grade_participant.origin"
        ]);

        $this->parseCriteria($criteria);

        $q1 = DB::table('wg_certificate_grade_participant')
            ->join("wg_certificate_grade", function ($join) {
                $join->on('wg_certificate_grade.id', '=', 'wg_certificate_grade_participant.certificate_grade_id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_certificate_grade_participant.customer_id');
            })
            ->join("wg_certificate_program", function ($join) {
                $join->on('wg_certificate_program.id', '=', 'wg_certificate_grade.certificate_program_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
                $join->on('wg_certificate_grade_participant.documentType', '=', 'tipodoc.value');
            })
            ->select(
                "wg_certificate_grade_participant.id",
                "wg_certificate_grade_participant.identificationNumber",
                "tipodoc.item AS documentType",
                "wg_certificate_grade_participant.name",
                "wg_certificate_grade_participant.lastName",
                "wg_customers.businessName AS customer",
                "wg_certificate_grade.name AS grade",
                "wg_certificate_grade_participant.certificateCreatedAt",
                "wg_certificate_grade_participant.certificateExpirationAt",
                DB::raw("'Waygroup' AS origin")
            )
            ->where('wg_certificate_grade_participant.hasCertificate', 1);

        $q2 = DB::table('wg_certificate_external')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
                $join->on('wg_certificate_external.documentType', '=', 'tipodoc.value');
            })
            ->select(
                "wg_certificate_external.id",
                "wg_certificate_external.identificationNumber",
                "tipodoc.item AS documentType",
                "wg_certificate_external.name",
                "wg_certificate_external.lastName",
                DB::raw("wg_certificate_external.company COLLATE utf8_general_ci AS customer"),
                "wg_certificate_external.grade",
                "wg_certificate_external.expeditionDate AS certificateCreatedAt",
                "wg_certificate_external.expeditionDate",
                DB::raw("'Externo' AS origin")
            );

        $q1->union($q2);

        $query = $this->query(DB::table(DB::raw("({$q1->toSql()}) as wg_certificate_grade_participant")))
            ->mergeBindings($q1);

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function allExpiration($criteria)
    {
        $this->setColumns([
            "id" => "wg_certificate_grade_participant.id",
            "documentType" => "tipodoc.item AS documentType",
            "documentNumber" => "wg_certificate_grade_participant.identificationNumber",
            "name" => "wg_certificate_grade_participant.name",
            "lastName" => "wg_certificate_grade_participant.lastName",
            "customer" => "wg_customers.businessName AS customer",
            "grade" => "wg_certificate_grade.name AS grade",
            "certificateCreatedAt" => "wg_certificate_grade_participant.certificateCreatedAt",
            "certificateExpirationAt" => 'wg_certificate_grade_participant.certificateExpirationAt',
            "year" =>  DB::raw('YEAR(wg_certificate_grade_participant.certificateExpirationAt) AS year'),
            "month" =>  DB::raw('MONTH(wg_certificate_grade_participant.certificateExpirationAt) AS month'),
            "customerId" => "wg_certificate_grade_participant.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $query->join("wg_certificate_grade", function ($join) {
            $join->on('wg_certificate_grade.id', '=', 'wg_certificate_grade_participant.certificate_grade_id');
        })->join("wg_customers", function ($join) {
            $join->on('wg_customers.id', '=', 'wg_certificate_grade_participant.customer_id');
        })->join("wg_certificate_program", function ($join) {
            $join->on('wg_certificate_program.id', '=', 'wg_certificate_grade.certificate_program_id');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tipodoc')), function ($join) {
            $join->on('wg_certificate_grade_participant.documentType', '=', 'tipodoc.value');
        })->where('wg_certificate_grade_participant.hasCertificate', 1);

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (! ($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->certificateGradeId = $entity->certificateGradeId ? $entity->certificateGradeId->id : null;
        $entityModel->customerId = $entity->customerId ? $entity->customerId->id : null;
        $entityModel->documenttype = $entity->documenttype ? $entity->documenttype->value : null;
        $entityModel->identificationnumber = $entity->identificationnumber;
        $entityModel->name = $entity->name;
        $entityModel->lastname = $entity->lastname;
        $entityModel->workcenter = $entity->workcenter;
        $entityModel->amount = $entity->amount;
        $entityModel->channel = $entity->channel ? $entity->channel->value : null;
        $entityModel->countryOriginId = $entity->countryOriginId ? $entity->countryOriginId->value : null;
        $entityModel->countryResidenceId = $entity->countryResidenceId ? $entity->countryResidenceId->value : null;
        $entityModel->isapproved = $entity->isapproved == 1;
        $entityModel->hascertificate = $entity->hascertificate == 1;
        $entityModel->countdownloads = $entity->countdownloads;
        $entityModel->validatecodecertificate = $entity->validatecodecertificate;
        $entityModel->certificatecreatedat = $entity->certificatecreatedat ? Carbon::parse($entity->certificatecreatedat)->timezone('America/Bogota') : null;
        $entityModel->generatedby = $entity->generatedby;
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
        if (! ($entityModel = $this->find($id))) {
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
            $entity->certificateGradeId = $model->certificateGradeId;
            $entity->customerId = $model->customerId;
            $entity->documenttype = $model->getDocumenttype();
            $entity->identificationnumber = $model->identificationnumber;
            $entity->name = $model->name;
            $entity->lastname = $model->lastname;
            $entity->workcenter = $model->workcenter;
            $entity->amount = $model->amount;
            $entity->channel = $model->getChannel();
            $entity->countryOriginId = $model->getCountryOriginId();
            $entity->countryResidenceId = $model->getCountryResidenceId();
            $entity->isapproved = $model->isapproved;
            $entity->hascertificate = $model->hascertificate;
            $entity->countdownloads = $model->countdownloads;
            $entity->validatecodecertificate = $model->validatecodecertificate;
            $entity->certificatecreatedat = $model->certificatecreatedat;
            $entity->generatedby = $model->generatedby;
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
}
