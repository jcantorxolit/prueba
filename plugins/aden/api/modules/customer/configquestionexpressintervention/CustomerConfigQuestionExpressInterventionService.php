<?php

namespace AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use Carbon\Carbon;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;

class CustomerConfigQuestionExpressInterventionService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $query = DB::table('wg_customer_config_question_express_intervention')
            ->join('wg_customer_config_question_express', function ($join) {
                $join->on('wg_customer_config_question_express.id', '=', 'wg_customer_config_question_express_intervention.customer_question_express_id');
            })
            ->join(DB::raw("({$qAgentUser->toSql()}) as wg_custoemr_agent_user"), function ($join) {
                $join->on('wg_custoemr_agent_user.type', '=', 'wg_customer_config_question_express_intervention.responsible_type');
                $join->on('wg_custoemr_agent_user.id', '=', 'wg_customer_config_question_express_intervention.responsible_id');
            })
            ->mergeBindings($qAgentUser)
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->select(
                'wg_customer_config_question_express_intervention.id',
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id',
                'wg_customer_config_question_express_intervention.name',
                'wg_customer_config_question_express_intervention.description',
                'wg_custoemr_agent_user.id AS responsibleId',
                'wg_custoemr_agent_user.type AS responsibleType',
                'wg_custoemr_agent_user.name AS responsibleName',
                'wg_custoemr_agent_user.email AS responsibleEmail',
                'wg_customer_config_question_express_intervention.amount',
                'wg_customer_config_question_express_intervention.execution_date',
                'wg_customer_config_question_express_intervention.files_info',
                'wg_customer_config_question_express_intervention.is_closed',
                'wg_customer_config_question_express_intervention.is_historical',
                'wg_config_classification_express.parent_id',
                'wg_config_classification_express.parent_name',
                'wg_config_classification_express.id AS child_id',
                'wg_config_classification_express.name AS child_name',
                'wg_config_question_express.description AS question'
            )
            ->where('wg_customer_config_question_express_intervention.is_historical', $criteria->isHistorical)
            ->where('wg_customer_config_question_express_intervention.is_deleted', 0)
            ->where('wg_customer_config_question_express_intervention.customer_id', $criteria->customerId);

        if (isset($criteria->id)) {
            $query->where('wg_customer_config_question_express_intervention.customer_question_express_id', $criteria->id);
        }

        if (isset($criteria->isClosed)) {
            $query->where('wg_customer_config_question_express_intervention.is_closed', $criteria->isClosed);
        }

        if (isset($criteria->workplaceId)) {
            $query->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId);
        }

        return array_map(function ($item) {
            return [
                'id' => $item->id,
                'customerId' => $item->customer_id,
                'customerQuestionExpressId' => $item->customer_question_express_id,
                'name' => $item->name,
                'description' => $item->description,
                'responsible' => [
                    'id' => $item->responsibleId,
                    'type' => $item->responsibleType,
                    'name' => $item->responsibleName,
                    'email' => $item->responsibleEmail
                ],
                'amount' => $item->amount,
                'executionDate' => $item->execution_date ? Carbon::parse($item->execution_date) : null,
                'files' => $this->getFiles($item->files_info),
                'isClosed' => $item->is_closed == 1,
                'status' => $item->is_closed == 1,
                'isHistorical' => $item->is_historical == 1,
                'factor' => $item->parent_name,
                'subfactor' => $item->child_name,
                'hazard' => $item->question
            ];
        }, $query->get()->toArray());
    }

    public function getFiles($filesInfo)
    {
        $result = [];

        if ($filesInfo != null && $filesInfo != "") {
            $result = ($files = json_decode($filesInfo)) ? $files : [];
        }

        return $result ? $result : [];
    }

    public function canUpdateQuestionStatus($customerQuestionExpressId)
    {
        $data = DB::table('wg_customer_config_question_express_intervention')
            ->join('wg_customer_config_question_express', function ($join) {
                $join->on('wg_customer_config_question_express.id', '=', 'wg_customer_config_question_express_intervention.customer_question_express_id');
            })
            ->select(
                DB::raw('COUNT(*) AS qty'),
                DB::raw('SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 1 THEN 1 ELSE 0 END) AS isClosed')
            )
            ->where('wg_customer_config_question_express_intervention.is_historical', 0)
            ->where('wg_customer_config_question_express_intervention.is_deleted', 0)
            ->where('wg_customer_config_question_express_intervention.customer_question_express_id', $customerQuestionExpressId)
            ->groupBy('wg_customer_config_question_express_intervention.customer_question_express_id')
            ->first();

        return $data->qty == $data->isClosed;
    }

    private function prepareUnionHazardQuery()
    {
        $q1 = DB::table('wg_config_classification_express')
            ->select(
                'wg_config_classification_express.id',
                'wg_config_classification_express.name',
                'wg_config_classification_express.id AS parent_id',
                'wg_config_classification_express.name AS parent_name',
                'wg_config_classification_express.sort AS parent_sort',
                'wg_config_classification_express.sort',
                'wg_config_classification_express.type'
            )
            ->where('type', 'F')
            ->where('is_active', 1);

        $q2 = DB::table('wg_config_classification_express')
            ->join(DB::raw('wg_config_classification_express AS wg_config_classification_express_child'), function ($join) {
                $join->on('wg_config_classification_express_child.parent_id', '=', 'wg_config_classification_express.id');
            })
            ->select(
                'wg_config_classification_express_child.id',
                'wg_config_classification_express_child.name',
                'wg_config_classification_express.id AS parent_id',
                'wg_config_classification_express.name AS parent_name',
                'wg_config_classification_express.sort AS parent_sort',
                'wg_config_classification_express_child.sort',
                'wg_config_classification_express_child.type'
            )
            ->where('wg_config_classification_express.type', 'F')
            ->where('wg_config_classification_express.is_active', 1)
            ->where('wg_config_classification_express_child.is_active', 1);

        return $q1->union($q2)->mergeBindings($q2);
    }

    public function getExportExcelData($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $query = DB::table('wg_customer_config_question_express_intervention')
            ->join('wg_customer_config_question_express', function ($join) {
                $join->on('wg_customer_config_question_express.id', '=', 'wg_customer_config_question_express_intervention.customer_question_express_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_config_question_express.customer_id');
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_question_express.customer_workplace_id');
            })
            ->join(DB::raw("({$qAgentUser->toSql()}) as wg_custoemr_agent_user"), function ($join) {
                $join->on('wg_custoemr_agent_user.type', '=', 'wg_customer_config_question_express_intervention.responsible_type');
                $join->on('wg_custoemr_agent_user.id', '=', 'wg_customer_config_question_express_intervention.responsible_id');
            })
            ->mergeBindings($qAgentUser)
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->select(
                'wg_customer_config_question_express_intervention.id',
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id',
                'wg_customer_config_question_express_intervention.name',
                'wg_customer_config_question_express_intervention.description',
                'wg_customer_config_question_express_intervention.amount',
                'wg_customer_config_question_express_intervention.execution_date',
                'wg_customer_config_question_express_intervention.files_name',
                DB::raw("CASE WHEN wg_customer_config_question_express_intervention.is_closed = 0 THEN 'Abierto' ELSE 'Cerrado' END AS status"),
                'wg_custoemr_agent_user.id AS responsibleId',
                'wg_custoemr_agent_user.type AS responsibleType',
                'wg_custoemr_agent_user.name AS responsibleName',
                'wg_custoemr_agent_user.email AS responsibleEmail',
                'wg_customer_config_workplace.name AS workplace_name',
                'wg_config_classification_express.parent_id',
                'wg_config_classification_express.parent_name',
                'wg_config_classification_express.id AS child_id',
                'wg_config_classification_express.name AS child_name',
                'wg_config_question_express.description AS question'
            )
            ->where('wg_customer_config_question_express_intervention.is_historical', $criteria->isHistorical)
            ->where('wg_customer_config_question_express_intervention.is_deleted', 0)
            ->where('wg_customer_config_question_express_intervention.customer_id', $criteria->customerId);

        if (isset($criteria->id)) {
            $query->where('wg_config_classification_express.parent_id', $criteria->id);
        }

        if (isset($criteria->isClosed)) {
            $query->where('wg_customer_config_question_express_intervention.is_closed', $criteria->isClosed);
        }

        if (isset($criteria->workplaceId)) {
            $query->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId);
        }

        $heading = [
            "CENTRO DE TRABAJO" => "workplace_name",
            "FACTOR" => "parent_name",
            "SUBFACTOR" => "child_name",
            "PREGUNTA" => "question",
            "NOMBRE PLAN INTERVENCIÓN" => "name",
            "DESCRIPCIÓN PLAN INTERVENCIÓN" => "description",
            "RESPONSABLE PLAN INTERVENCIÓN" => "responsibleName",
            "PRESUPUESTO PLAN INTERVENCIÓN" => "amount",
            "FECHA EJECUCIÓN PLAN INTERVENCIÓN" => "execution_date",
            "ESTADO PLAN INTERVENCIÓN" => "status",
            "ARCHIVOS ADJUNTOS" => "files_name"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportExcelGeneralData($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $query = DB::table('wg_customer_config_question_express')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_config_question_express.customer_id');
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_question_express.customer_workplace_id');
            })
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('matrix_express_question_rate')), function ($join) {
                $join->on('matrix_express_question_rate.value', '=', 'wg_customer_config_question_express.rate');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->select(
                'wg_customer_config_workplace.name AS workplace_name',
                'wg_config_classification_express.parent_id',
                'wg_config_classification_express.parent_name',
                'wg_config_classification_express.id AS child_id',
                'wg_config_classification_express.name AS child_name',
                'wg_config_question_express.description AS question',
                'wg_customer_config_question_express.rate AS rate',
                'matrix_express_question_rate.item AS rate_text',
                'wg_config_question_express.priority'
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->where('wg_config_question_express.is_master', 0)
            ->whereNotNull('wg_customer_config_question_express.rate')
            ->orderBy('wg_customer_config_workplace.name')
            ->orderBy('wg_config_classification_express.parent_sort')
            ->orderBy('wg_config_classification_express.sort')
            ->orderBy('wg_config_classification_express.name')
            ->orderBy('wg_config_question_express.sort');

        if (isset($criteria->workplaceId)) {
            $query->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId);
        }

        \Log::info($query->toSql());

        $heading = [
            "CENTRO DE TRABAJO" => "workplace_name",
            "FACTOR" => "parent_name",
            "SUBFACTOR" => "child_name",
            "PREGUNTA" => "question",
            "RESPUESTA" => "rate_text",
            "NIVEL" => "priority"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportExcelGeneralInterventionData($criteria)
    {
        $qHazard = $this->prepareUnionHazardQuery();

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $query = DB::table('wg_customer_config_question_express_intervention')
            ->join('wg_customer_config_question_express', function ($join) {
                $join->on('wg_customer_config_question_express.id', '=', 'wg_customer_config_question_express_intervention.customer_question_express_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.customer_id', '=', 'wg_customer_config_question_express.customer_id');
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_question_express.customer_workplace_id');
            })
            ->join(DB::raw("({$qAgentUser->toSql()}) as wg_custoemr_agent_user"), function ($join) {
                $join->on('wg_custoemr_agent_user.type', '=', 'wg_customer_config_question_express_intervention.responsible_type');
                $join->on('wg_custoemr_agent_user.id', '=', 'wg_customer_config_question_express_intervention.responsible_id');
            })
            ->mergeBindings($qAgentUser)
            ->join("wg_config_question_express", function ($join) {
                $join->on('wg_config_question_express.id', '=', 'wg_customer_config_question_express.question_express_id');
            })
            ->join(DB::raw("({$qHazard->toSql()}) as wg_config_classification_express"), function ($join) {
                $join->on('wg_config_classification_express.id', '=', 'wg_config_question_express.classification_express_id');
            })
            ->mergeBindings($qHazard)
            ->select(
                'wg_customer_config_question_express_intervention.id',
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.customer_question_express_id',
                'wg_customer_config_question_express_intervention.name',
                'wg_customer_config_question_express_intervention.description',
                'wg_customer_config_question_express_intervention.amount',
                'wg_customer_config_question_express_intervention.execution_date',
                'wg_customer_config_question_express_intervention.files_name',
                DB::raw("CASE WHEN wg_customer_config_question_express_intervention.is_closed = 0 THEN 'Abierto' ELSE 'Cerrado' END AS status"),
                'wg_custoemr_agent_user.id AS responsibleId',
                'wg_custoemr_agent_user.type AS responsibleType',
                'wg_custoemr_agent_user.name AS responsibleName',
                'wg_custoemr_agent_user.email AS responsibleEmail',
                'wg_customer_config_workplace.name AS workplace_name',
                'wg_config_classification_express.parent_id',
                'wg_config_classification_express.parent_name',
                'wg_config_classification_express.id AS child_id',
                'wg_config_classification_express.name AS child_name',
                'wg_config_question_express.description AS question'
            )
            ->where('wg_customer_config_question_express_intervention.is_historical', $criteria->isHistorical)
            ->where('wg_customer_config_question_express_intervention.is_deleted', 0)
            ->where('wg_customer_config_question_express_intervention.customer_id', $criteria->customerId);


        if (isset($criteria->workplaceId)) {
            $query->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId);
        }

        $heading = [
            "CENTRO DE TRABAJO" => "workplace_name",
            "FACTOR" => "parent_name",
            "SUBFACTOR" => "child_name",
            "PREGUNTA" => "question",
            "PLAN INTERVENCIÓN" => "name",
            "RESPONSABLE PLAN INTERVENCIÓN" => "responsibleName",
            "PRESUPUESTO PLAN INTERVENCIÓN" => "amount",
            "FECHA EJECUCIÓN PLAN INTERVENCIÓN" => "execution_date",
            "ESTADO PLAN INTERVENCIÓN" => "status"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportExcelGeneralResponsibleData($criteria)
    {
        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $query = DB::table('wg_customer_config_question_express_intervention')

            ->join('wg_customer_config_question_express', function ($join) {
                $join->on('wg_customer_config_question_express.id', '=', 'wg_customer_config_question_express_intervention.customer_question_express_id');
            })
            ->join(DB::raw("({$qAgentUser->toSql()}) as wg_custoemr_agent_user"), function ($join) {
                $join->on('wg_custoemr_agent_user.type', '=', 'wg_customer_config_question_express_intervention.responsible_type');
                $join->on('wg_custoemr_agent_user.id', '=', 'wg_customer_config_question_express_intervention.responsible_id');
            })
            ->select(
                'wg_custoemr_agent_user.name AS responsibleName',
                'wg_custoemr_agent_user.email AS responsibleEmail',
                DB::raw('COUNT(*) AS total'),
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 0 THEN 1 ELSE 0 END) AS open"),
                DB::raw("SUM(CASE WHEN wg_customer_config_question_express_intervention.is_closed = 1 THEN 1 ELSE 0 END) AS closed"),
                'wg_customer_config_question_express.customer_workplace_id',
                'wg_customer_config_question_express_intervention.customer_id'
            )
            ->mergeBindings($qAgentUser)
            ->where('wg_customer_config_question_express_intervention.is_historical', $criteria->isHistorical)
            ->where('wg_customer_config_question_express_intervention.is_deleted', 0)
            ->where('wg_customer_config_question_express_intervention.customer_id', $criteria->customerId)
            ->groupBy(
                'wg_customer_config_question_express_intervention.customer_id',
                'wg_customer_config_question_express_intervention.responsible_type',
                'wg_customer_config_question_express_intervention.responsible_id'
            );


        if (isset($criteria->workplaceId)) {
            $query->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId);
        }

        $heading = [
            "NOMBRE" => "responsibleName",
            "EMAIL" => "responsibleEmail",
            "PLANES INTERVENCIÓN ABIERTOS" => "open",
            "PLANES INTERVENCIÓN CERRADOS" => "closed",
            "TOTAL" => "total"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getYearList($criteria)
    {
        $query = DB::table('wg_customer_config_question_express_intervention')
            ->join("wg_customer_config_question_express", function ($join) {
                $join->on('wg_customer_config_question_express.id', '=', 'wg_customer_config_question_express_intervention.customer_question_express_id');
                $join->on('wg_customer_config_question_express.customer_id', '=', 'wg_customer_config_question_express_intervention.customer_id');
            })
            ->select(
                DB::raw('YEAR(wg_customer_config_question_express_intervention.execution_date) item'),
                DB::raw('YEAR(wg_customer_config_question_express_intervention.execution_date) value')
            )
            ->where('wg_customer_config_question_express.customer_id', $criteria->customerId)
            ->groupBy(
                'wg_customer_config_question_express_intervention.customer_id',
                DB::raw('YEAR(wg_customer_config_question_express_intervention.execution_date)')
            )->orderBy(DB::raw('YEAR(wg_customer_config_question_express_intervention.execution_date)'), "DESC");

        if (isset($criteria->workplaceId)) {
            $query->where('wg_customer_config_question_express.customer_workplace_id', $criteria->workplaceId);
            $query->groupBy('wg_customer_config_question_express.customer_workplace_id');
        }

        return $query->get();
    }
}
