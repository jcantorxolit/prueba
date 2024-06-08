<?php


namespace AdeN\Api\Modules\PositivaFgn\Vendor\Contract;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;

use function GuzzleHttp\Promise\all;

class ContractRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ContractModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "id AS id",
            "contract_number" => "contract_number AS contractNumber",
            "start_date" => "start_date AS startDate",
            "end_date" => "end_date AS endDate",
            "contract_value" => "contract_value AS contractValue",
            "isActive" => DB::raw("IF(is_active=1,'Activo','Inactivo') AS isActive"),
            "vendorId" => "vendor_id AS vendorId"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();
        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function canSave($entity)
    {
        $valid = $this->query()
                    ->where("contract_number", $entity->contractNumber)
                    ->first();

        if($valid && $valid->id != $entity->id){
            throw new \Exception('No es posible adicionar la información, ya existe este contrato creado.');
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
        $entityModel->contractNumber = $entity->contractNumber;
        $entityModel->startDate = $entity->startDate ? Carbon::createFromFormat("d/m/Y",$entity->startDate)->timezone('America/Bogota') : null;
        $entityModel->endDate = $entity->endDate ? Carbon::createFromFormat("d/m/Y",$entity->endDate)->timezone('America/Bogota') : null;
        $entityModel->contractValue = $entity->contractValue;
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
        
        return $entity;
    }

    public function parseModelWithRelations(ContractModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->vendorId = $model->vendorId;
            $entity->contractNumber = $model->contractNumber;
            $entity->startDate = Carbon::parse($model->startDate)->format("d/m/Y");
            $entity->endDate = $model->endDate ? Carbon::parse($model->endDate)->format("d/m/Y") : null;
            $entity->contractValue = $model->contractValue;
            $entity->isActive = $model->isActive;

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
