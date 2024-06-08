<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\User\Skill;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerUserSkillRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerUserSkillModel());

        $this->service = new CustomerUserSkillService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_user_skill.id",
            "customerUserId" => "wg_customer_user_skill.customer_user_id",
            "skill" => "wg_customer_user_skill.skill",
            "createdby" => "wg_customer_user_skill.createdBy",
            "updatedby" => "wg_customer_user_skill.updatedBy",
            "createdAt" => "wg_customer_user_skill.created_at",
            "updatedAt" => "wg_customer_user_skill.updated_at",
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

        $entityModel->customer_user_id = $entity->customerUserId;
        $entityModel->skill = $entity->skill ? $entity->skill->id : null;

        if ($isNewRecord) {            
            $entityModel->createdBy = $authUser ? $authUser->id : 1;            
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
    }

    public function bulkInsertOrUpdate($records, $parentId)
    {
        foreach ($records as $entity) {
            $entity->customerUserId = $parentId;
            $this->insertOrUpdate($entity);
        }
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
            $entity->customerUserId = $model->customerUserId;
            $entity->skill = $model->skill;

            return $entity;
        } else {
            return null;
        }
    }

    public static function getSkills($customerUserId)
    {
        return (new self)->service->getSkills($customerUserId);
    }
}
