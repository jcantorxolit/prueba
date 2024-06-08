<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Parameter;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerParameterRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerParameterModel());

        $this->service = new CustomerParameterService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_parameter.id",
            "customerId" => "wg_customer_parameter.customer_id",
            "namespace" => "wg_customer_parameter.namespace",
            "group" => "wg_customer_parameter.group",
            "item" => "wg_customer_parameter.item",
            "value" => "wg_customer_parameter.value",
            "data" => "wg_customer_parameter.data",
            "updatedAt" => "wg_customer_parameter.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_parameter.parent_id', '=', 'tableParent.id');
		}
		*/


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

        $entityModel->customerId = $entity->customerId ? $entity->customerId->id : null;
        $entityModel->namespace = $entity->namespace;
        $entityModel->group = $entity->group;
        $entityModel->item = $entity->item;
        $entityModel->value = $entity->value;
        $entityModel->data = $entity->data;


        if ($isNewRecord) {
            $entityModel->isActive = true;
            $entityModel->save();
        } else {
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

        if ($this->canDelete($entityModel)) {
            $entityModel->delete();
        } else {
            throw new Exception("No es posible eliminar el registro. Tiene relación con otros procesos");
        }
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->namespace = $model->namespace;
            $entity->group = $model->group;
            $entity->item = $model->item;
            $entity->value = $model->value;
            $entity->data = $model->data;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }

    private function canDelete($entity)
    {
        switch ($entity->group) {
            case 'officeTypeMatrixSpecial':
                $result = $this->service->getOfficeTypeMatrixSpecialCount($entity->id) == 0;
                break;

            case 'businessUnitMatrixSpecial':
                $result = $this->service->getBusinessUnitMatrixSpecialCount($entity->id) == 0;
                break;

            default:
                $result = true;
        }

        return $result;
    }

    public static function createCustoomerVrObservation($customerId, $observation)
    {
        $entity = new \stdClass;
        $entity->id = 0;
        $entity->customerId = new \stdClass;
        $entity->customerId->id = $customerId;
        $entity->namespace = "wgroup";
        $entity->group = "customer_vr_employee_observation";
        $entity->item = "observation";
        $entity->value = $observation;
        $entity->data = 0;

        (new self)->insertOrUpdate($entity);
    }

    public function getCustomerVrObservationList($customerId)
    {
        return DB::table('system_parameters')
            ->select(DB::raw("'system' as origin"), 'value as item', 'value')
            ->where('namespace', 'wgroup')
            ->where('group', 'customer_vr_employee_observation')
            // ->unionAll(function ($query) use ($customerId) {
            //     $query->select(DB::raw("'customer' as origin"), 'value as item', 'value')
            //         ->from('wg_customer_parameter')
            //         ->where('namespace', 'wgroup')
            //         ->where('group', 'customer_vr_employee_observation')
            //         ->where('is_active', 1)
            //         ->where('customer_id', $customerId);
            // })
            ->orderBy("value")
            ->get();
    }
}
