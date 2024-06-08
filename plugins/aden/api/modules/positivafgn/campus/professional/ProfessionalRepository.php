<?php


namespace AdeN\Api\Modules\PositivaFgn\Campus\Professional;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

use function GuzzleHttp\Promise\all;

class ProfessionalRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ProfessionalModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_campus_professional.id",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_positiva_fgn_campus_professional.document_number AS documentNumber",
            "fullName" => "wg_positiva_fgn_campus_professional.full_name AS fullName",
            "job" => "wg_positiva_fgn_campus_professional.job",
            "telephone" => "wg_positiva_fgn_campus_professional.telephone",
            "email" => "wg_positiva_fgn_campus_professional.email",
            "isActive" => DB::raw("IF(wg_positiva_fgn_campus_professional.is_active=1,'Activo','Inactivo') AS isActive"),
            "campusId" => "wg_positiva_fgn_campus_professional.campus_id as campusId"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

		$query->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('employee_document_type.value', '=', 'wg_positiva_fgn_campus_professional.document_type');
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function canSave($entity)
    {
        if($entity->isActive) {
            $valid = $this->query()
                    ->where("campus_id",$entity->campusId)
                    ->where("is_active",1)
                    ->first();
    
            if($valid && $valid->id != $entity->id){
                throw new \Exception('No es posible adicionar la información, ya existe un profesional activo para esta sede.');
            }
        }

        $valid = $this->query()
                    ->where("campus_id",$entity->campusId)
                    ->where("document_type", $entity->documentType->value)
                    ->where("document_number", $entity->documentNumber)
                    ->first();

        if($valid && $valid->id != $entity->id){
            throw new \Exception('No es posible adicionar la información, ya existe este profesional creado.');
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

        $entityModel->id = $entity->id;
        $entityModel->campusId = $entity->campusId;
        $entityModel->isActive = $entity->isActive == 1;
        $entityModel->documentType = $entity->documentType->value;
        $entityModel->documentNumber = $entity->documentNumber;
        $entityModel->fullName = $entity->fullName;
        $entityModel->job = $entity->job;
        $entityModel->telephone = $entity->telephone;
        $entityModel->email = $entity->email;

        if ($isNewRecord) {
            $entityModel->createdAt = Carbon::now();
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        }
        
        $entity->id = $entityModel->id;
        return $entity;
    }

    public function parseModelWithRelations(ProfessionalModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->campusId = $model->campusId;
            $entity->isActive = $model->isActive == 1;
            $entity->documentType = $model->getDocumentType();
            $entity->documentNumber = $model->documentNumber;
            $entity->fullName = $model->fullName;
            $entity->job = $model->job;
            $entity->telephone = $model->telephone;
            $entity->email = $model->email;

            return $entity;
        }
         else {
            return null;
        }
    }


}
