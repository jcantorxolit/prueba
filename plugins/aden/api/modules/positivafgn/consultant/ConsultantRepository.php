<?php


namespace AdeN\Api\Modules\PositivaFgn\Consultant;

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

class ConsultantRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new ConsultantModel());

        $this->service = new ConsultantService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Nombre", "name" => "fullName"],
            ["alias" => "Tipo Identificación", "name" => "documentType"],
            ["alias" => "# Identificación", "name" => "documentNumber"],
            ["alias" => "Cargo", "name" => "job"],
            ["alias" => "Regional", "name" => "regional"],
            ["alias" => "Seccional", "name" => "sectional"],
            ["alias" => "Estrategia", "name" => "strategy"],
            ["alias" => "Tipo", "name" => "type"],
            ["alias" => "Estado", "name" => "isActive"]
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_positiva_fgn_consultant.id",
            "fullName" => "wg_positiva_fgn_consultant.full_name",
            "documentType" => "employee_document_type.item AS documentType",
            "documentNumber" => "wg_positiva_fgn_consultant.document_number",
            "job" => "wg_positiva_fgn_consultant.job",
            "regional" => "sectional.regional",
            "sectional" => "sectional.sectional",
            "strategy" => "strategys.strategy",
            "type" => "positiva_fgn_consultant_type.item AS type",
            "isActive" => DB::raw("IF(wg_positiva_fgn_consultant.is_active=1,'Activo','Inactivo') AS isActive")
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

        $strategy = DB::table("wg_positiva_fgn_consultant_strategy")
                        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_strategy')), function ($join) {
                            $join->on('wg_positiva_fgn_consultant_strategy.strategy', '=', 'positiva_fgn_consultant_strategy.value');
                        })
                        ->select(
                            DB::raw("GROUP_CONCAT(' ',positiva_fgn_consultant_strategy.item) as strategy"),
                            "consultant_id"
                        )
                        ->where("is_active",1)
                        ->groupBy("consultant_id");

        $sectional = DB::table("wg_positiva_fgn_regional")
                        ->join("wg_positiva_fgn_sectional", function($join){
                            $join->on("wg_positiva_fgn_regional.id","=","wg_positiva_fgn_sectional.regional_id");
                        })
                        ->join("wg_positiva_fgn_consultant_sectional", function($join){
                            $join->on("wg_positiva_fgn_sectional.id","=","wg_positiva_fgn_consultant_sectional.sectional_id");
                        })
                        ->select("wg_positiva_fgn_regional.number AS regional","wg_positiva_fgn_sectional.name AS sectional","consultant_id")
                        ->where("wg_positiva_fgn_consultant_sectional.is_active",1)
                        ->where("wg_positiva_fgn_consultant_sectional.type","ST001")
                        ->groupBy("consultant_id");

		$query->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('employee_document_type.value', '=', 'wg_positiva_fgn_consultant.document_type');
        })
        ->join(DB::raw(SystemParameter::getRelationTable('positiva_fgn_consultant_type')), function ($join) {
            $join->on('positiva_fgn_consultant_type.value', '=', 'wg_positiva_fgn_consultant.type');
        })
        ->leftjoin(DB::raw("({$strategy->toSql()}) as strategys"), function($join) {
            $join->on("wg_positiva_fgn_consultant.id","=","strategys.consultant_id");
        })
        ->mergeBindings($strategy)
        ->leftjoin(DB::raw("({$sectional->toSql()}) as sectional"), function($join) {
            $join->on("wg_positiva_fgn_consultant.id","=","sectional.consultant_id");
        })
        ->mergeBindings($sectional);

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
            throw new \Exception('No es posible adicionar la información, ya existe este asesor creado.');
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
        $entityModel->type = $entity->type->value;
        $entityModel->isActive = $entity->isActive == 1;
        $entityModel->documentType = $entity->documentType->value;
        $entityModel->documentNumber = $entity->documentNumber;
        $entityModel->fullName = $entity->fullName;
        $entityModel->gender = $entity->gender->value;
        $entityModel->birthDate = $entity->birthDate ? Carbon::createFromFormat("d/m/Y",$entity->birthDate)->timezone('America/Bogota') : null;
        $entityModel->job = $entity->job;
        $entityModel->grade = $entity->grade->value;
        $entityModel->accountingAccount = $entity->accountingAccount->value;
        $entityModel->admissionDate = $entity->admissionDate ? Carbon::createFromFormat("d/m/Y",$entity->admissionDate)->timezone('America/Bogota') : null;
        $entityModel->withdrawalDate = $entity->withdrawalDate ? Carbon::createFromFormat("d/m/Y",$entity->withdrawalDate)->timezone('America/Bogota') : null;
        $entityModel->profession = $entity->profession;
        $entityModel->workingDay = $entity->workingDay->value;
        $entityModel->mainContact = $entity->mainContact;
        $entityModel->telephone = $entity->telephone;
        $entityModel->eps = $entity->eps->value;
        $entityModel->afp = $entity->afp->value;
        $entityModel->ccf = $entity->ccf->value;
        $entityModel->accountType = $entity->accountType ? $entity->accountType->value : null;
        $entityModel->bank = $entity->bank;
        $entityModel->accountNumber = $entity->accountNumber;

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
        if($entity->details->license) {
            foreach ($entity->details->license as $key => $license) {
                $LicenseModel = LicenseModel::findOrNew($license->id);
                $LicenseModel->id = $license->id;
                $LicenseModel->consultantId = $entityModel->id;
                $LicenseModel->license = $license->license;
                $LicenseModel->expeditionDate = Carbon::createFromFormat("d/m/Y",$license->expeditionDate)->timezone('America/Bogota');
                $LicenseModel->issuingEntity = $license->issuingEntity;
                $LicenseModel->save();
                $license->id = $LicenseModel->id;
                $entity->details->license[$key] = $license;
            }
        }

        if($entity->details->contact) {
            foreach ($entity->details->contact as $key => $contact) {
                $ContactInformationModel = ContactInformationModel::findOrNew($contact->id);
                $ContactInformationModel->id = $contact->id;
                $ContactInformationModel->consultantId = $entityModel->id;
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
                $StrategyModel->consultantId = $entityModel->id;
                $StrategyModel->strategy = $strategy->strategy->value;
                $StrategyModel->type = $strategy->type->value;
                $StrategyModel->isActive = $strategy->isActive == 1;
                $StrategyModel->save();
                $strategy->id = $StrategyModel->id;
                $entity->details->strategy[$key] = $strategy;
            }
        }

        return $entity;
    }

    public function delete($id, $detail)
    {
        switch($detail) {
            case "license":
                    $entityModel = LicenseModel::find($id);
                break;
            case "contact":
                    $entityModel = ContactInformationModel::find($id);
                break;
            case "strategy":
                    $entityModel = StrategyModel::find($id);
                break;
        }
        $entityModel->delete();
    }

    public function parseModelWithRelations(ConsultantModel $model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->type = $model->getType();
            $entity->isActive = $model->isActive == 1;
            $entity->documentType = $model->getDocumentType();
            $entity->documentNumber = $model->documentNumber;
            $entity->fullName = $model->fullName;
            $entity->gender = $model->getGender();
            $entity->birthDate = $model->birthDate ? Carbon::parse($model->birthDate)->format("d/m/Y") : null;
            $entity->job = $model->job;
            $entity->grade = $model->getGrade();
            $entity->accountingAccount = $model->getAccountingAccount();
            $entity->admissionDate = $model->admissionDate ? Carbon::parse($model->admissionDate)->format("d/m/Y") : null;
            $entity->withdrawalDate = $model->withdrawalDate ? Carbon::parse($model->withdrawalDate)->format("d/m/Y") : null;
            $entity->profession = $model->profession;
            $entity->workingDay = $model->getWorkingDay();
            $entity->mainContact = $model->mainContact;
            $entity->telephone = $model->telephone;
            $entity->eps = $model->getEps();
            $entity->afp = $model->getAfp();
            $entity->ccf = $model->getCcf();
            $entity->accountType = $model->getAccountType();
            $entity->bank = $model->bank;
            $entity->accountNumber = $model->accountNumber;
            $entity->details = [];
            $entity->details["license"] = $model->licenses->each(function($value) {
                $value->expeditionDate = Carbon::parse($value->expeditionDate)->format("d/m/Y");
            });
            $entity->details["contact"] = $model->contacts->each(function($value){
                $value->type = $value->getType();
            });
            $entity->details["strategy"] = $model->strategys->each(function($value){
                $value->strategy = $value->getStrategy();
                $value->type = $value->getStrategyType();
                $value->isActive = $value->isActive == 1;
            });

            return $entity;
        }
         else {
            return null;
        }
    }


    public static function getRegionalList(){
        return (new self)->service->getRegionalList();
    }

    public static function getSectionalList($criteria){
        return (new self)->service->getSectionalList($criteria);
    }

    public static function getAllSectionalList($criteria) {
        return (new self)->service->getAllSectionalList($criteria);
    }

    public static function getAllSectionalList2() {
        return (new self)->service->getAllSectionalList2();
    }


}
