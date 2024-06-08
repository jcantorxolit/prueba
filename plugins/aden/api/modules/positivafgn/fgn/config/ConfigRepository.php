<?php


namespace AdeN\Api\Modules\PositivaFgn\Fgn\Config;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

class ConfigRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ConfigModel());
    }


    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_config.id",
            "period" => "wg_positiva_fgn_config.period AS period",
            "startDate" => "wg_positiva_fgn_config.start_date AS startDate",
            "endDate" => "wg_positiva_fgn_config.end_date AS endDate",
            "isActive" => DB::raw("IF(wg_positiva_fgn_config.is_active=1,'Activo','Inactivo') AS isActive")
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

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

        $valide = ConfigModel::wherePeriod($entity->period)->first();
        if($valide && $valide->id != $entity->id){
            throw new \Exception('No es posible adicionar la información, ya existe este periodo.');
        }

        $entityModel->id = $entity->id;
        $entityModel->isActive = $entity->isActive == 1;
        $entityModel->startDate = $entity->startDate ? Carbon::createFromFormat("d/m/Y",$entity->startDate)->timezone('America/Bogota') : null;
        $entityModel->endDate = $entity->endDate ? Carbon::createFromFormat("d/m/Y",$entity->endDate)->timezone('America/Bogota') : null;
        $entityModel->period = $entity->period;

        if ($isNewRecord) {
            $entityModel->createdAt = Carbon::now();
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        }

        if($entity->id == 0){
            // ConfigModel::where("id","!=",$entityModel->id)->update(["is_active" => 0]);
        }
        
        $entity->id = $entityModel->id;
        return $entity;
    }

    public function parseModelWithRelations(ConfigModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->isActive = $model->isActive == 1;
            $entity->startDate = Carbon::parse($model->startDate)->format("d/m/Y");
            $entity->endDate = Carbon::parse($model->endDate)->format("d/m/Y");
            $entity->period = $model->period;

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
