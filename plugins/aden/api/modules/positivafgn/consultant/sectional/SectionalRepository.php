<?php


namespace AdeN\Api\Modules\PositivaFgn\Consultant\Sectional;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\Document\CustomerDocumentModel;
use Illuminate\Pagination\Paginator;

use function GuzzleHttp\Promise\all;

class SectionalRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new SectionalModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_consultant_sectional.id",
            "regional" => "wg_positiva_fgn_regional.number AS regional",
            "sectional" => "wg_positiva_fgn_sectional.name AS sectional",
            "type" => "positiva_fgn_consultant_sectional_type.item AS type",
            "isActive" => DB::raw("IF(wg_positiva_fgn_consultant_sectional.is_active=1,'Activo','Inactivo') AS isActive"),
            "consultantId" => "wg_positiva_fgn_consultant_sectional.consultant_id AS consultantId"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();
		$query->join('wg_positiva_fgn_sectional', function ($join) {
            $join->on('wg_positiva_fgn_consultant_sectional.sectional_id', '=', 'wg_positiva_fgn_sectional.id');
        })
        ->join('wg_positiva_fgn_regional', function ($join) {
            $join->on('wg_positiva_fgn_consultant_sectional.regional_id', '=', 'wg_positiva_fgn_regional.id');
        })
        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_sectional_type')), function ($join) {
            $join->on('positiva_fgn_consultant_sectional_type.value', '=', 'wg_positiva_fgn_consultant_sectional.type');
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function canSave($entity)
    {
        $valid = $this->query()
                    ->where("consultant_id", $entity->consultantId)
                    ->where("sectional_id", $entity->sectional->value)
                    ->where("regional_id", $entity->regional->value)
                    ->first();

        if($valid && $valid->id != $entity->id) {
            throw new \Exception('No es posible adicionar la información, solo puede haber una sola configuración para este tipo.');
        }

        $valid = $this->query()
            ->where('type', 'ST001')
            ->where("consultant_id", $entity->consultantId)
            ->where('id', '<>', $entity->id)
            ->exists();

        if ($valid && $entity->type->value == 'ST001') {
            throw new Exception('Sólo puede existir una seccional base.');
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
        $entityModel->consultantId = $entity->consultantId;
        $entityModel->sectionalId = $entity->sectional->value;
        $entityModel->regionalId = $entity->regional->value;
        $entityModel->type = $entity->type->value;
        $entityModel->isActive = $entity->isActive == 1;

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

    public function parseModelWithRelations(SectionalModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->consultantId = $model->consultantId;
            $entity->type = $model->getType();
            $entity->regional = $model->getRegional();
            $entity->sectional = $model->getSectional();
            $entity->isActive = $model->isActive == 1;

            return $entity;
        }
         else {
            return null;
        }
    }


}
