<?php


namespace AdeN\Api\Modules\PositivaFgn\Campus;

use AdeN\Api\Classes\BaseRepository;

use AdeN\Api\Modules\PositivaFgn\Professional\ProfessionalModel;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

use function GuzzleHttp\Promise\all;

class CampusRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CampusModel());
    }


    public static function getCustomFilters()
    {
        return [
            ["alias" => "Regional", "name" => "regional"],
            ["alias" => "Seccional", "name" => "sectional"],
            ["alias" => "Sede", "name" => "campus"],
            ["alias" => "Departamento", "name" => "department"],
            ["alias" => "Ciudad", "name" => "city"],
            ["alias" => "Dirección", "name" => "address"],
            ["alias" => "Profesional", "name" => "professional"]
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_campus.id",
            "regional" => "wg_positiva_fgn_regional.number AS regional",
            "sectional" => "wg_positiva_fgn_sectional.name AS sectional",
            "campus" => "wg_positiva_fgn_campus.campus",
            "department" => "rainlab_user_states.name AS department",
            "city" => "wg_towns.name AS city",
            "address" => "wg_positiva_fgn_campus.address",
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

		$query->join('wg_positiva_fgn_sectional', function ($join) {
            $join->on('wg_positiva_fgn_campus.sectional_id', '=', 'wg_positiva_fgn_sectional.id');
        })
        ->join('wg_positiva_fgn_regional', function ($join) {
            $join->on('wg_positiva_fgn_campus.regional_id', '=', 'wg_positiva_fgn_regional.id');
        })
        ->join('rainlab_user_states', function ($join) {
            $join->on('wg_positiva_fgn_campus.department_id', '=', 'rainlab_user_states.id');
        })
        ->join('wg_towns', function ($join) {
            $join->on('wg_positiva_fgn_campus.city_id', '=', 'wg_towns.id');
        });

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

        $entityModel->id = $entity->id;
        $entityModel->isActive = $entity->isActive == 1;
        $entityModel->regionalId = $entity->regional->value;
        $entityModel->sectionalId = $entity->sectional->value;
        $entityModel->campus = $entity->campus;
        $entityModel->departmentId = $entity->department->id;
        $entityModel->cityId = $entity->city->id;
        $entityModel->address = $entity->address;

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

    public function parseModelWithRelations(CampusModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->isActive = $model->isActive == 1;
            $entity->regional = $model->getRegional();
            $entity->sectional = $model->getSectional();
            $entity->campus = $model->campus;
            $entity->department = $model->getDepartment();
            $entity->city = $model->getCity();
            $entity->address = $model->address;

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
