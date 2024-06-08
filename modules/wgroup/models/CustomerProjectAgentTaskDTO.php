<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerController;
use Wgroup\Models\Customer;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerProjectAgentTaskDTO
{

    function __construct($model = null)
    {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1")
    {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model: Modelo CustomerProjectAgentTask
     */
    private function getBasicInfo($model)
    {
        /*
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->type = $model->getTrackingType();
        $this->agent = $model->agent->name;
        $this->observation = $model->observation;
        $this->status = $model->getStatusType();
        $this->eventDateTime = Carbon::parse($model->eventDateTime)->format('d/m/Y H:i:s');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        */

        //Codigo
        $this->id = $model->id;
        $this->projectAgentId = $model->project_agent_id;
        $this->type = $model->typeTask;
        $this->task = $model->task;
        $this->observation = $model->observation;
        $this->startDateTime =  Carbon::parse($model->startDateTime);
        $this->endDateTime =  Carbon::parse($model->endDateTime);
        $this->status = $model->status;

        //$this->alerts = [];

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        //$this->tokensession = $this->getTokenSession(true);
    }


    public static function  fillAndSaveModel($object)
    {


        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerProjectAgentTask::find($object->id))) {
                // No existe
                $model = new CustomerProjectAgentTask();
                $isEdit = false;
            }
        } else {
            $model = new CustomerProjectAgentTask();
            $isEdit = false;
        }




        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->project_agent_id = $object->projectAgentId;
        $model->type = $object->type == null ? null : $object->type->code;;
        $model->task = $object->task;
        $model->observation = $object->observation;

        $model->startDateTime = Carbon::parse($object->startDateTime)->timezone('America/Bogota');
        $model->endDateTime = Carbon::parse($object->endDateTime)->timezone('America/Bogota');
        $model->duration = $object->duration ?? 0;

        $model->status = $object->status;

        self::canSave($model);

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        if (!empty($object->tracking->action)) {
            $modelTracking = new CustomerProjectAgentTaskTracking();

            $modelTracking->project_agent_task_id = $model->id;
            $modelTracking->type = $object->tracking->action;
            $modelTracking->observation = $object->tracking->description;
            $modelTracking->createdBy = $userAdmn->id;
            $modelTracking->updatedBy = $userAdmn->id;

            // Guarda
            $modelTracking->save();
        }

        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        // Datos de contacto


        return CustomerProjectAgentTask::find($model->id);
    }

    public static function canSave($model)
    {
        $parameAvailableHour = $model->availableHours();

        if ($parameAvailableHour) {
            $availableHour = (int)$parameAvailableHour->item;
            $year = $model->startDateTime->year;
            $month = $model->startDateTime->month;

            $projectAgentEntity = DB::table('wg_customer_project_agent')->where('id', $model->project_agent_id)->first();

            $subqueryTasks = DB::table('wg_customer_project_agent_task')
                //->whereRaw("status = 'inactivo' ")
                ->whereRaw("MONTH(startDateTime) = $month")
                ->whereRaw("YEAR(startDateTime) = $year")
                ->whereRaw("status <> 'cancelador'")
                ->select('project_agent_id', DB::raw('sum(duration) as duration'))
                ->groupBy('project_agent_id');


            $getTotalAmountCosts = DB::table('wg_customer_project_agent as pa')
                ->join('wg_customer_project as pr', 'pr.id', '=', 'pa.project_id')
                ->join('wg_customer_project_agent as pa_all', 'pa_all.project_id', '=', 'pr.id')
                ->Join(DB::raw("({$subqueryTasks->toSql()}) as pat"), function ($join) {
                    $join->on('pat.project_agent_id', 'pa_all.id');
                })
                ->when($projectAgentEntity == null, function ($query) use ($model) {
                    $query->where('pa.id', $model->project_agent_id);
                })
                ->when($projectAgentEntity, function ($query) use ($projectAgentEntity) {
                    $query->where('pa.agent_id', $projectAgentEntity->agent_id);
                })
                ->select(
                    'pa.project_id as projectId',
                    'pa_all.project_id as projectId2',
                    DB::raw('SUM(pat.duration) as duration')
                )
                ->first();

            if (($getTotalAmountCosts && ($getTotalAmountCosts->duration + $model->duration) > $availableHour) || ($getTotalAmountCosts == null && + $model->duration > $availableHour)) {
                $totalAmountCosts = $getTotalAmountCosts ? $getTotalAmountCosts->duration : 0;
                throw new \Exception("No es posible programar la tarea, ya que sobrepasa las $availableHour horas permitidas. Este mes lleva {$totalAmountCosts} horas programadas");
            }
        }
    }

    public static function  fillAndUpdateModel($object)
    {
        ////Log::info($object->startDateTime->setTimezone('America/Bogota'));
        ////Log::info(json_encode($object->startDateTime->setTimezone('America/Bogota')));
        ////Log::info($object);

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerProjectAgentTask::find($object->id))) {
                // No existe
                $model = new CustomerProjectAgentTask();
                $isEdit = false;
            }
        } else {
            $model = new CustomerProjectAgentTask();
            $isEdit = false;
        }

        $model->status = $object->status;


        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();
        }

        if (!empty($object->tracking->action)) {
            $modelTracking = new CustomerProjectAgentTaskTracking();

            $modelTracking->project_agent_task_id = $model->id;
            $modelTracking->type = $object->tracking->action;
            $modelTracking->observation = $object->tracking->description;
            $modelTracking->createdBy = $userAdmn->id;
            $modelTracking->updatedBy = $userAdmn->id;

            // Guarda
            $modelTracking->save();
        }


        self::updateCostStatus($model->project_agent_id);


        /** :: ASIGNO DETALLES (ENTIDADES RELACIONADAS) ::  **/

        // Datos de contacto


        return CustomerProjectAgentTask::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {

        // parse model
        if ($model) {
            $this->setInfo($model, $fmt_response);
        }

        return $this;
    }

    public static function parse($info, $fmt_response = "1")
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerProjectAgentTask) {
                    $parsed[] = (new CustomerProjectAgentTaskDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerProjectAgentTask) {
            return (new CustomerProjectAgentTaskDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerProjectAgentTaskDTO();
            }
        }
    }

    private function getUserSsession()
    {
        if (!Auth::check())
            return null;

        return Auth::getUser();
    }

    private function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }


    private static function updateCostStatus(int $projectAgentId)
    {
        $subqueryTasks = DB::table('wg_customer_project_agent_task')
            ->whereRaw("status = 'inactivo' ")
            ->groupBy('project_agent_id')
            ->select('project_agent_id', DB::raw('sum(duration) as duration'));

        $getTotalAmountCosts = DB::table('wg_customer_project_agent as pa')
            ->join('wg_customer_project as pr', 'pr.id', '=', 'pa.project_id')
            ->join('wg_customer_project_agent as pa_all', 'pa_all.project_id', '=', 'pr.id')
            ->leftJoin(DB::raw("({$subqueryTasks->toSql()}) as pat"), function ($join) {
                $join->on('pat.project_agent_id', 'pa_all.id');
            })
            ->where('pa.id', $projectAgentId)
            ->select(
                'pa.project_id as projectId',
                'pa_all.project_id as projectId2',
                DB::raw('sum(pat.duration) as duration'),
                DB::raw('sum(pa_all.estimatedHours) as estimatedHours'),
                DB::raw("sum(pat.duration) >= sum(pa_all.estimatedHours) as totalProgrammed")
            )
            ->first();

        if ($getTotalAmountCosts && $getTotalAmountCosts->totalProgrammed == 1) {
            DB::table('wg_customer_project_costs')
                ->where('project_id', $getTotalAmountCosts->projectId)
                ->update([
                    'status' => CustomerProjectCost::STATUS_EXECUTED
                ]);
        }
    }
}
