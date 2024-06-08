<?php


namespace AdeN\Api\Modules\PositivaFgn\Vendor\Coverage;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

use function GuzzleHttp\Promise\all;

class CoverageRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CoverageModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_vendor_coverage.id",
            "regional" => "wg_positiva_fgn_regional.number AS regional",
            "sectional" => "wg_positiva_fgn_sectional.name AS sectional",
            "department" => "rainlab_user_states.name AS department",
            "town" => "wg_towns.name AS town",
            "vendorId" => "wg_positiva_fgn_vendor_coverage.vendor_id AS vendorId"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

		$query->join('wg_positiva_fgn_sectional', function ($join) {
            $join->on('wg_positiva_fgn_vendor_coverage.sectional_id', '=', 'wg_positiva_fgn_sectional.id');
        })
        ->join('wg_positiva_fgn_regional', function ($join) {
            $join->on('wg_positiva_fgn_vendor_coverage.regional_id', '=', 'wg_positiva_fgn_regional.id');
        })
        ->join('rainlab_user_states', function ($join) {
            $join->on('wg_positiva_fgn_vendor_coverage.department_id', '=', 'rainlab_user_states.id');
        })
        ->join('wg_towns', function ($join) {
            $join->on('wg_positiva_fgn_vendor_coverage.town_id', '=', 'wg_towns.id');
        });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function canSave($entity)
    {
        $valid = $this->query()
                    ->where("department_id", $entity->department->id)
                    ->where("town_id", $entity->town->id)
                    ->where("regional_id", $entity->regional->value)
                    ->where("sectional_id", $entity->sectional->value)
                    ->first();

        if($valid && $valid->id != $entity->id){
            throw new \Exception('No es posible adicionar la información, ya existe esta cobertura creada.');
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
        $entityModel->vendorId = $entity->vendorId;
        $entityModel->regionalId = $entity->regional->value;
        $entityModel->sectionalId = $entity->sectional->value;
        $entityModel->departmentId = $entity->department->id;
        $entityModel->townId = $entity->town->id;

        if ($isNewRecord) {
            $entityModel->createdAt = Carbon::now();
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        }
        
        return $entity;
    }

    public function parseModelWithRelations(CoverageModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->vendorId = $model->vendorId;
            $entity->regional = $model->getRegional();
            $entity->sectional = $model->getSectional();
            $entity->department = $model->getDepartment();
            $entity->town = $model->getTown();

            return $entity;
        }
         else {
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
