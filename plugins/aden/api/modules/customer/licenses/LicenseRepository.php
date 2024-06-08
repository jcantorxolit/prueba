<?php

namespace AdeN\Api\Modules\Customer\Licenses;

use DB;
use Log;
use Carbon\Carbon;
use AdeN\Api\Classes\BaseRepository;
use Wgroup\SystemParameter\SystemParameter;

class LicenseRepository extends BaseRepository
{
    public function __construct() {
        parent::__construct(new LicenseModel());
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_licenses.id",
            "license" => "license.item as license",
            "startDate" => DB::raw("DATE_FORMAT(start_date, '%Y-%m-%d') as startDate"),
            "endDate" => DB::raw("DATE_FORMAT(end_date, '%Y-%m-%d') as endDate"),
            "agent" => "a.name as agent",
            "value" => DB::raw("round(wg_customer_licenses.value, 0) as value"),
            "state" => "state.item as state",
            "customerId" => "wg_customer_licenses.customer_id as customerId",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query()
            ->join('wg_agent as a', 'a.id', '=', 'wg_customer_licenses.agent_id')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_types', 'license')), function ($join) {
                $join->on('wg_customer_licenses.license', '=', 'license.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_states', 'state')), function ($join) {
                $join->on('wg_customer_licenses.state', '=', 'state.value');
            });

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function insertOrUpdate($entity)
    {
        $this->canSave($entity);

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
        }

        $authUser = $this->getAuthUser();
        $userId = $authUser ? $authUser->id : 1;

        $entityModel->customerId = $entity->customerId;
        $entityModel->license = $entity->license->value ?? null;
        $entityModel->agentId = $entity->agentId->id;
        $entityModel->startDate = Carbon::createFromFormat("d/m/Y", $entity->startDate);
        $entityModel->endDate   = Carbon::createFromFormat("d/m/Y", $entity->endDate);
        $entityModel->value = $entity->value;
        $entityModel->state = $entity->state->value;

        if (empty($entityModel->id)) {
            $entityModel->createdBy = $userId;
            $entityModel->createdAt = Carbon::now('America/Bogota');
        } else {
            $entityModel->updatedBy =  $userId;
            $entityModel->updatedAt = Carbon::now('America/Bogota');
            $entityModel->reason = $entity->reason;
        }

        $entityModel->save();

        $entity->id = $entityModel->id;
        return $entityModel;
    }


    public function canSave($entity){
        if (!empty($entity->id)) {
            $this->canUpdate($entity);
        }
    }


    private function canUpdate($entity) {
        if (empty($entity->reason)) {
            throw new \Exception('Es necesario especificar un motivo.');
        }
    }


    public function parseModelWithRelations($model)
    {
        if (empty($model)) {
            return $model;
        }

        $entity = new \stdClass();
        $entity->id = $model->id;
        $entity->customerId = $model->customerId;
        $entity->license = $model->getLicense();
        $entity->startDate = Carbon::parse($model->startDate)->format('d/m/Y');
        $entity->endDate = Carbon::parse($model->endDate)->format('d/m/Y');
        $entity->value = $model->value;
        $entity->state = $model->getState();
        $entity->agentId = $model->agentId;
        $entity->reason = $model->reason;
        return $entity;
    }


    public function getComercialAgents(int $customerId) {
        return DB::table('wg_customer_agent as ca')
            ->join('wg_agent as a', 'a.id', '=', 'ca.agent_id')
            ->where('ca.customer_id', $customerId)
            ->where('ca.type', 'gcom')
            ->where('a.active', true)
            ->orderBy('a.name')
            ->select('a.id', 'a.name as item')
            ->get();
    }


    public function finish($entity)
    {
        $entityModel = $this->find($entity->id);
        $userId = $this->getAuthUser()->id ?? 1;

        $entityModel->state = LicenseModel::STATE_FINISH;
        $entityModel->finishDate = Carbon::createFromFormat("d/m/Y", $entity->finishDate);
        $entityModel->finishBy =  $userId;
        $entityModel->reason = $entity->reason;
        $entityModel->updatedBy =  $userId;
        $entityModel->save();

        $entity->id = $entityModel->id;
        return $entityModel;
    }


    public function getCurrentLicense(int $customerId) {
        return LicenseModel::whereCustomerId($customerId)
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_types', 'license')), function ($join) {
                $join->on('wg_customer_licenses.license', '=', 'license.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_states', 'state')), function ($join) {
                $join->on('wg_customer_licenses.state', '=', 'state.value');
            })
            ->orderBy('wg_customer_licenses.id', 'desc')
            ->select(
                'license.item as license',
                DB::raw("DATE_FORMAT(wg_customer_licenses.start_date, '%Y-%m-%d') as startDate"),
                DB::raw("DATE_FORMAT(wg_customer_licenses.end_date, '%Y-%m-%d') as endDate"),
                'state.item as state'
            )
            ->first();
    }


    public function getLogs($criteria) {
        $this->setColumns([
            "date" => DB::raw("DATE_FORMAT(l.created_at, '%Y-%m-%d') as date"),
            "field" => "l.field",
            "beforeValue" => DB::raw("CASE WHEN l.field = 'Comercial Asignado' THEN ag_be.name
                                           WHEN l.field = 'Estado' THEN state_be.item COLLATE utf8_general_ci
                                           WHEN l.field = 'Licencia' THEN license_be.item COLLATE utf8_general_ci
                                       ELSE l.before_value
                                      END as beforeValue"),
            "afterValue" => DB::raw("CASE WHEN l.field = 'Comercial Asignado' THEN ag_af.name
                                        WHEN l.field = 'Estado' THEN state_af.item COLLATE utf8_general_ci
                                        WHEN l.field = 'Licencia' THEN license_af.item COLLATE utf8_general_ci
                                        ELSE l.after_value
                                      END as afterValue"),
            "user" => DB::raw("COALESCE(u.name, 'Sistema') as user"),
            "reason" => "l.reason",
            "licenseId" => "l.license_id as licenseId",
            "id" => 'l.id'
        ]);

        $this->parseCriteria($criteria);

        $query = DB::table('wg_customer_licenses_logs as l')
            ->leftjoin('users as u', 'u.id', '=', 'l.user_id')
            ->leftjoin('wg_agent as ag_be', function($join) {
                $join->where('l.field', 'Comercial Asignado');
                $join->on('ag_be.id', 'l.before_value');
            })
            ->leftjoin('wg_agent as ag_af', function($join) {
                $join->where('l.field', 'Comercial Asignado');
                $join->on('ag_af.id', 'l.after_value');
            })

            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_types', 'license_be')), function ($join) {
                $join->where('l.field', 'Licencia');
                $join->on('l.before_value', '=', 'license_be.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_types', 'license_af')), function ($join) {
                $join->where('l.field', 'Licencia');
                $join->on('l.after_value', '=', 'license_af.value');
            })

            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_states', 'state_be')), function ($join) {
                $join->where('l.field', 'Estado');
                $join->on('l.before_value', '=', 'state_be.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_licenses_states', 'state_af')), function ($join) {
                $join->where('l.field', 'Estado');
                $join->on('l.after_value', '=', 'state_af.value');
            });

        $this->addSortColumn('id', 'DESC');

        $this->query($query);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }


    public function validateLicense(int $customerId) {
        $license = LicenseModel::query()
            ->whereCustomerId($customerId)
            ->where('state', '<>', 'LS003')
            ->select('end_date')
            ->first();

        $param = SystemParameter::whereNamespace('wgroup')
            ->whereGroup('customer_licenses_alert_x_days_to_finish')
            ->first();

        $response = new \stdClass();
        $response->closeExpire = false;

        if ($param && $license) {
            $today = Carbon::now()->startOfDay();

            $days = explode(',', $param->value);
            foreach ($days as $day) {
                $dateToCompare = Carbon::parse($license->end_date)->startOfDay();
                if ($today->diffInDays($dateToCompare) == $day) {
                    $response->closeExpire = true;
                    break;
                }
            }

        }

        return $response;
    }

}