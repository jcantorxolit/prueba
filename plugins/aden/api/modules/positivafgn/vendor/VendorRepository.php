<?php


namespace AdeN\Api\Modules\PositivaFgn\Vendor;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\Document\CustomerDocumentModel;
use AdeN\Api\Modules\PositivaFgn\Vendor\Contract\ContractModel;
use AdeN\Api\Modules\PositivaFgn\Vendor\Coverage\CoverageModel;
use AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact\InfoModel;
use AdeN\Api\Modules\PositivaFgn\Vendor\Maincontact\MainContactModel;
use Illuminate\Pagination\Paginator;

use function GuzzleHttp\Promise\all;

class VendorRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new VendorModel());

        $this->service = new VendorService();
    }
    
    public static function getCustomFilters()
    {
        return [
            ["alias" => "Nombre / Razón Social", "name" => "name"],
            ["alias" => "Tipo Identificación", "name" => "documentType"],
            ["alias" => "# Identificación", "name" => "documentNumber"],
            ["alias" => "Departamento", "name" => "department"],
            ["alias" => "Municipio", "name" => "town"],
            ["alias" => "Estrategia", "name" => "strategy"],
            ["alias" => "Estado", "name" => "isActive"]
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_vendor.id",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_positiva_fgn_vendor.document_number",
            "name" => "wg_positiva_fgn_vendor.name",
            "department" => "rainlab_user_states.name AS department",
            "town" => "wg_towns.name AS town",
            "strategy" => "strategys.strategy",
            "isActive" => DB::raw("IF(wg_positiva_fgn_vendor.is_active=1,'Activo','Inactivo') AS isActive")
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

        $strategy = DB::table("wg_positiva_fgn_vendor_strategy")
                        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                            $join->on('wg_positiva_fgn_vendor_strategy.strategy', '=', 'positiva_fgn_consultant_strategy.value');
                        })
                        ->select(
                            DB::raw("GROUP_CONCAT(' ',positiva_fgn_consultant_strategy.item) as strategy"),
                            "vendor_id"
                        )
                        ->where("is_active",1)
                        ->groupBy("vendor_id");
                        
		$query->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('employee_document_type.value', '=', 'wg_positiva_fgn_vendor.document_type');
        })
        ->join('rainlab_user_states', function ($join) {
            $join->on('wg_positiva_fgn_vendor.department_id', '=', 'rainlab_user_states.id');
        })
        ->join('wg_towns', function ($join) {
            $join->on('wg_positiva_fgn_vendor.town_id', '=', 'wg_towns.id');
        })
        ->leftjoin(DB::raw("({$strategy->toSql()}) as strategys"), function($join) {
            $join->on("wg_positiva_fgn_vendor.id","=","strategys.vendor_id");
        })
        ->mergeBindings($strategy);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function canSave($entity)
    {
        $valid = $this->query()
                    ->where("document_type", $entity->documentType->value)
                    ->where("document_number", $entity->documentNumber)
                    ->first();

        if($valid && $valid->id != $entity->id){
            throw new \Exception('No es posible adicionar la información, ya existe este proveedor creado.');
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
        $entityModel->isActive = $entity->isActive == 1;
        $entityModel->documentType = $entity->documentType->value;
        $entityModel->documentNumber = $entity->documentNumber;
        $entityModel->name = $entity->name;
        $entityModel->legalRepresentative = $entity->legalRepresentative;
        $entityModel->departmentId = $entity->department->id;
        $entityModel->townId = $entity->town->id;
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
        
        $entity = $this->saveDetails($entity, $entityModel);
        $entity->id = $entityModel->id;
        return $entity;
    }

    private function saveDetails($entity, $entityModel)
    {

        if($entity->details->contact) {
            foreach ($entity->details->contact as $key => $contact) {
                $ContactInformationModel = ContactInformationModel::findOrNew($contact->id);
                $ContactInformationModel->id = $contact->id;
                $ContactInformationModel->vendorId = $entityModel->id;
                $ContactInformationModel->type = $contact->type->value;
                $ContactInformationModel->value = $contact->value;
                $ContactInformationModel->save();
                $contact->id = $ContactInformationModel->id;
                $entity->details->contact[$key] = $contact;
            }
        }

        if($entity->details->strategy) {
            foreach ($entity->details->strategy as $key => $strategy) {
                $StrategyModel = StrategyModel::findOrNew($strategy->id);
                $StrategyModel->id = $strategy->id;
                $StrategyModel->vendorId = $entityModel->id;
                $StrategyModel->strategy = $strategy->strategy->value;
                $StrategyModel->isActive = $strategy->isActive == 1;
                $StrategyModel->save();
                $strategy->id = $StrategyModel->id;
                $entity->details->strategy[$key] = $strategy;
            }
        }

        return $entity;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();
        ContactInformationModel::whereVendorId($id)->delete();
        StrategyModel::whereVendorId($id)->delete();
        MainContactModel::whereVendorId($id)->get()->each(function($model) {
            $model->info()->delete();
            $model->delete();
        });
        CoverageModel::whereVendorId($id)->delete();
        ContractModel::whereVendorId($id)->delete();
    }

    public function deleteDetail($id, $detail)
    {
        switch($detail) {
            case "contact":
                    $entityModel = ContactInformationModel::find($id);
                break;
            case "strategy":
                    $entityModel = StrategyModel::find($id);
                break;
        }
        $entityModel->delete();
    }

    public function parseModelWithRelations(VendorModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->isActive = $model->isActive == 1;
            $entity->documentType = $model->getDocumentType();
            $entity->documentNumber = $model->documentNumber;
            $entity->name = $model->name;
            $entity->legalRepresentative = $model->legalRepresentative;
            $entity->department = $model->getDepartment();
            $entity->town = $model->getTown();
            $entity->telephone = $model->telephone;
            $entity->email = $model->email;
            $entity->details = [];
            $entity->details["contact"] = $model->contacts->each(function($value){
                $value->type = $value->getType();
            });
            $entity->details["strategy"] = $model->strategys->each(function($value){
                $value->strategy = $value->getStrategy();
                $value->isActive = $value->isActive == 1;
            });

            return $entity;
        }
         else {
            return null;
        }
    }


}
