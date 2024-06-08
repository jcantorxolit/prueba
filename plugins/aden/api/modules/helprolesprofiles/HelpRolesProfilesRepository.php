<?php


namespace AdeN\Api\Modules\HelpRolesProfiles;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\Document\CustomerDocumentModel;
use Illuminate\Pagination\Paginator;

class HelpRolesProfilesRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new HelpRolesProfilesModel());

        $this->service = new HelpRolesProfilesService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_help_roles_profiles.id",
            "typeName" => "configuration_help_roles_profile_type.item as typeName",
            "descriptionName" => DB::raw("IF(wg_help_roles_profiles.type = 1, customer_user_role.item, wg_customer_user_profile.item) as descriptionName"),
            "text" => "wg_help_roles_profiles.text"
        ]);

        $this->parseCriteria($criteria);
        $query = $this->query();

		$query->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_user_profile')), function ($join) {
            $join->on('wg_customer_user_profile.value', '=', 'wg_help_roles_profiles.description');
            $join->where("wg_help_roles_profiles.type","=",2);
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_user_role')), function ($join) {
            $join->on('customer_user_role.value', '=', 'wg_help_roles_profiles.description');
            $join->where("wg_help_roles_profiles.type","=",1);
        })
        ->leftjoin(DB::raw(SystemParameter::getRelationTable('configuration_help_roles_profile_type')), function ($join) {
            $join->on('configuration_help_roles_profile_type.value', '=', 'wg_help_roles_profiles.type');
        });

        $data = ($this->pageSize > 0) ? $query->paginate($this->pageSize, $this->columns) : $query->get($this->columns);

        $result["data"] = $this->parseModelWithDocument($data, HelpRolesProfilesModel::class);
        $result["recordsTotal"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["recordsFiltered"] = $data instanceof Paginator || $data instanceof \Illuminate\Pagination\LengthAwarePaginator ? $data->total() : $data->count();
        $result["draw"] = $criteria ? $criteria->draw : 1;

        return $result;
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;
        $authUser = $this->getAuthUser();
        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->type = $entity->type->value;
        $entityModel->description = $entity->description->value;
        $entityModel->text = $entity->text;

        if ($isNewRecord) {
            $entityModel->createdAt = Carbon::now();
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        }

        $result = $entityModel;
        return $result;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }
        $entityModel->delete();
        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);
        if ($model instanceof $modelClass) {
            //Mapping fields
            $entity = new \stdClass();
            $entity->id = $model->id;
            $entity->type = $model->getType();
            $entity->description = $model->getDescription();
            $entity->text = $model->text;
            return $entity;
        }
         else {
            return null;
        }
    }

    public function getText($id,$type)
    {
        $text = null;
        if($type == "profile") {
            $text = HelpRolesProfilesModel::whereType(2)->whereDescription($id)->first();
        } elseif($type == "role") {
            $text = HelpRolesProfilesModel::whereType(1)->whereDescription($id)->first();
            if($text){
                $getDocument = $this->parseModelWithDocument([$text], HelpRolesProfilesModel::class);
                $text = $getDocument[0];
            }
        }
        return $text;
    }

}
