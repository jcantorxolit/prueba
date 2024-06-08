<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\Tracking;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use AdeN\Api\Modules\InformationDetail\InformationDetailRepository;
use DB;
use Exception;
use Log;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\CustomerModel;

class CustomerTrackingRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new CustomerTrackingModel());

        $this->service = new CustomerTrackingService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Tipo ID Empresa", "name" => "customerDocumentType"],
            ["alias" => "Número ID Empresa", "name" => "customerDocumentNumber"],
            ["alias" => "Empresa", "name" => "customerName"],
            ["alias" => "Tipo ID Empleado", "name" => "employeeDocumentType"],
            ["alias" => "Número ID Empleado", "name" => "employeeDocumentNumber"],
            ["alias" => "Empleado", "name" => "employeeName"],
            ["alias" => "Centro de Trabajo", "name" => "workPlace"],
            ["alias" => "Cargo", "name" => "job"],
        ];
    }

    public function getMandatoryFilters()
    {
        return [
            array("field" => 'isActive', "operator" => 'eq', "value" => '1'),
        ];
    }

    public function all($criteria)
    {
        $this->setColumns([
            "unique" => "wg_customer_tracking.id as unique",
            "id" => "wg_customer_tracking.id",
            "type" => "tracking_tiposeg.item AS type",
            "responsible" => "responsible.name as responsible",
            "eventDateTime" => "wg_customer_tracking.eventDateTime",
            "observation" => DB::raw("SUBSTRING(wg_customer_tracking.observation, 1, 100) as observation"),
            "createdBy" => "users.name AS createdBy",
            "status" => "tracking_status.item AS status",
            "countAttachment" => DB::raw("IFNULL(tracking_document_stats.qryAttachment, 0) AS countAttachment"),
           
            "customerId" => "wg_customer_tracking.customer_id",
            "isVisible" => "wg_customer_tracking.isVisible",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();        

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $query->leftjoin(DB::raw(SystemParameter::getRelationTable('tracking_tiposeg')), function ($join) {
            $join->on('wg_customer_tracking.type', '=', 'tracking_tiposeg.value');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('tracking_status')), function ($join) {
            $join->on('wg_customer_tracking.status', '=', 'tracking_status.value');

        })->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible"), function ($join) {
            $join->on('wg_customer_tracking.agent_id', '=', 'responsible.id');
            $join->on('wg_customer_tracking.userType', '=', 'responsible.type');
            $join->on('wg_customer_tracking.customer_id', '=', 'responsible.customer_id');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_tracking.createdBy', '=', 'users.id');

        })->leftjoin(DB::raw(CustomerTrackingModel::getRelationDocumentCount('tracking_document_stats')), function ($join) {
            $join->on('wg_customer_tracking.id', '=', 'tracking_document_stats.customer_tracking_id');
        })->mergeBindings($qAgentUser);;

        $this->applyCriteria($query, $criteria);
        
        return $this->get($query, $criteria);
    }

    public function allComment($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_tracking_comment.id",
            "comment" => "wg_customer_tracking_comment.comment",
            "createdBy" => "users.name AS createdBy",
            "createdAt" => "wg_customer_tracking_comment.created_at",
            "customerTrackingId" => "wg_customer_tracking_comment.customer_tracking_id",
        ]);

        $authUser = $this->getAuthUser();

        $this->parseCriteria($criteria);

        $query = $this->query();        

        /* Example relation*/
        $query->join('wg_customer_tracking_comment', function ($join) {
            $join->on('wg_customer_tracking.id', '=', 'wg_customer_tracking_comment.customer_tracking_id');

        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_tracking_comment.createdBy', '=', 'users.id');

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

            return $entity;
        } else {
            return null;
        }
    }
}