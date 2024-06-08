<?php

namespace AdeN\Api\Modules\PositivaFgn\Sectional;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Modules\PositivaFgn\Professional\ProfessionalModel;
use AdeN\Api\Modules\PositivaFgn\Professional\ProfessionalSectionalModel;
use DB;
use Exception;
use Wgroup\SystemParameter\SystemParameter;

class SectionalRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new SectionalModel());
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Regional", "name" => "regional"],
            ["alias" => "Nit", "name" => "nit"],
            ["alias" => "Seccional", "name" => "name"],
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_sectional.id",
            "regional" => "r.number AS regional",
            "nit" => "wg_positiva_fgn_sectional.nit as nit",
            "name" => "wg_positiva_fgn_sectional.name as name",
            "isActive" => "wg_positiva_fgn_sectional.is_active AS isActive",
            "idRegional" => "r.id AS idRegional",
            "idSeccional" => "wg_positiva_fgn_sectional.id AS idSeccional",
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

        $query->join('wg_positiva_fgn_regional AS r', 'r.id', '=', 'wg_positiva_fgn_sectional.regional_id');

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function allSectionalXProfessional($criteria)
    {
        $this->setColumns([
            "sectionalId" => "sectional.id",
            "id" => "wg_positiva_fgn_sectional_professionals.id",
            "full_name" => "professional.full_name",
            "document_number" => "professional.document_number",
            "document_type" => "documentType.item AS documentType",
            "job" => "professional.job",
            "email" => "professional.email",
            "isActive" => DB::raw("IF(professional.is_active=1,'Activo','Inactivo') AS isActive"),
        ]);

        $query = ProfessionalSectionalModel::query();
        $query->join('wg_positiva_fgn_sectional AS sectional', 'sectional.id', '=', 'wg_positiva_fgn_sectional_professionals.sectional_id')
            ->join('wg_positiva_fgn_regional AS regional', 'regional.id', '=', 'sectional.regional_id')
            ->join('wg_positiva_fgn_professionals AS professional', 'professional.id', '=', 'wg_positiva_fgn_sectional_professionals.professional_id');
        $query->join(DB::raw(SystemParameter::getRelationTable('employee_document_type', 'documentType')), function ($join) {
            $join->on('documentType.value', '=', 'professional.document_type');
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function allProfessional($criteria, $filters)
    {
        $sectionalId = CriteriaHelper::getMandatoryFilter($filters, "sectionalId");
        $this->setColumns([
            "id" => "wg_positiva_fgn_professionals.id",
            "documentType" => "documentType.item AS documentType",
            "documentNumber" => "wg_positiva_fgn_professionals.document_number AS documentNumber",
            "fullName" => "wg_positiva_fgn_professionals.full_name AS fullName",
            "job" => "wg_positiva_fgn_professionals.job",
            "isActive" => DB::raw("IF(wg_positiva_fgn_professionals.is_active=1,'Activo','Inactivo') AS isActive"),
        ]);

        $this->parseCriteria($criteria);
        $query = ProfessionalModel::query();

        $query->join(DB::raw(SystemParameter::getRelationTable('employee_document_type', 'documentType')), function ($join) {
            $join->on('documentType.value', '=', 'wg_positiva_fgn_professionals.document_type');
        })->where('wg_positiva_fgn_professionals.is_active', '1')
            ->whereRaw('wg_positiva_fgn_professionals.id NOT IN (SELECT professional_id FROM wg_positiva_fgn_sectional_professionals WHERE sectional_id = ?)', $sectionalId->value);
        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $authUser = $this->getAuthUser();

        $entityModel = SectionalModel::findOrNew($entity->id);
        $entityModel->regionalId = $entity->regional->value;
        $entityModel->nit = $entity->nit;
        $entityModel->name = $entity->name;
        $entityModel->isActive = $entity->isActive;

        if (empty($entity->id)) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        }

        $entityModel->save();
        $entity->id = $entityModel->id;
        return $entity;
    }

    public function parseModelWithRelations(SectionalModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->regional = $model->regional();
            $entity->nit = $model->nit;
            $entity->name = $model->name;
            $entity->isActive = $model->isActive == 1;

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
}
