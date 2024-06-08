<?php

namespace AdeN\Api\Modules\PositivaFgn\Professional;

use AdeN\Api\Classes\BaseRepository;
use Carbon\Carbon;
use DB;
use Wgroup\SystemParameter\SystemParameter;

class ProfessionalRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ProfessionalModel());
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Tipo Documento", "name" => "documentType"],
            ["alias" => "# Documento", "name" => "documentNumber"],
            ["alias" => "Nombre", "name" => "fullName"],
            ["alias" => "Cargo", "name" => "job"],
            ["alias" => "Teléfono", "name" => "telphone"],
            ["alias" => "Correo", "name" => "email"],
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_professionals.id",
            "documentType" => "documentType.item AS documentType",
            "documentNumber" => "wg_positiva_fgn_professionals.document_number AS documentNumber",
            "fullName" => "wg_positiva_fgn_professionals.full_name AS fullName",
            "job" => "wg_positiva_fgn_professionals.job",
            "telephone" => "wg_positiva_fgn_professionals.telephone",
            "email" => "wg_positiva_fgn_professionals.email",
            "isActive" => "wg_positiva_fgn_professionals.is_active AS isActive",
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

        $query->join(DB::raw(SystemParameter::getRelationTable('employee_document_type', 'documentType')), function ($join) {
            $join->on('documentType.value', '=', 'wg_positiva_fgn_professionals.document_type');
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function allSectional($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_sectional_professionals.id",
            "regional" => "wg_positiva_fgn_regional.number AS regional",
            "sectional" => "wg_positiva_fgn_sectional.name AS sectional",
            "isActive" => DB::raw("IF(wg_positiva_fgn_sectional.is_active=1,'Activo','Inactivo') AS isActive"),
            "professionalId" => "wg_positiva_fgn_sectional_professionals.professional_id",
        ]);

        $this->parseCriteria($criteria);
        $query = ProfessionalSectionalModel::join('wg_positiva_fgn_sectional', function ($join) {
            $join->on('wg_positiva_fgn_sectional_professionals.sectional_id', '=', 'wg_positiva_fgn_sectional.id');
        })->join('wg_positiva_fgn_regional', function ($join) {
            $join->on('wg_positiva_fgn_sectional.regional_id', '=', 'wg_positiva_fgn_regional.id');
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $entityModel = ProfessionalModel::findOrNew(isset($entity->id) ? $entity->id : $entity->professionalId);
        $entityModel->isActive = $entity->isActive == 1;
        $entityModel->documentType = $entity->documentType->value;
        $entityModel->documentNumber = $entity->documentNumber;
        $entityModel->fullName = $entity->fullName;
        $entityModel->job = $entity->job;
        $entityModel->telephone = $entity->telephone;
        $entityModel->email = $entity->email;

        $authUser = $this->getAuthUser();
        if (empty($entityModel->id)) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        }
        $entityModel->save();
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
        } else {
            return null;
        }
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();
    }

    /*---------------- Professional and Sectional ---------------------------*/
    public function canSave($entity)
    {
        $professionalIdVal = $entity->professionalId;
        $sectionalIdVal = isset($entity->sectionalId->value) ? $entity->sectionalId->value : $entity->sectionalId;
        $valid = ProfessionalSectionalModel::where("professional_id", $professionalIdVal)->where("sectional_id", $sectionalIdVal)->first();
        if ($valid && $valid->id != $entity->id) {
            throw new \Exception('El profesional ya está asignado en la seccional.');
        }
    }

    /*Función que inserta la información de la seccional enlazada al profesional*/
    public function insertOrUpdateSectional($entity)
    {
        $entityModel = ProfessionalSectionalModel::findOrNew($entity->id);
        $entityModel->professionalId = $entity->professionalId;
        $entityModel->sectionalId = isset($entity->sectionalId->value) ? $entity->sectionalId->value : $entity->sectionalId;
        $entityModel->isActive = 1;

        $authUser = $this->getAuthUser();
        if (!$entityModel->id) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        }
        $entityModel->save();
        return $entityModel;
    }

    /*Función que elimina la seccional seleccionada*/
    public function deleteSectional($id)
    {
        if (!($entityModel = ProfessionalSectionalModel::find($id))) {
            throw new Exception("Record not found to delete.");
        }
        $entityModel->delete();
    }
}
