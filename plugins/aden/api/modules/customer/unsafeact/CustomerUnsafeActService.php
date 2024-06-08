<?php

namespace AdeN\Api\Modules\Customer\UnsafeAct;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;
use System\Models\File;

class CustomerUnsafeActService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getExportExcelData($criteria)
    {
        $qAgentUser = CustomerModel::getRelatedUnsafeActAgentAndUserRaw($criteria);

        $sQuery = DB::table('wg_customer_unsafe_act')
            ->leftjoin("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_unsafe_act.work_place');
            })
            ->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
                $join->on('wg_config_job_activity_hazard_classification.id', '=', 'wg_customer_unsafe_act.risk_type');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_unsafe_act_status')), function ($join) {
                $join->on('wg_customer_unsafe_act.status', '=', 'customer_unsafe_act_status.value');
            })
            ->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible_unsafe_act"), function ($join) {
                $join->on('wg_customer_unsafe_act.responsible_id', '=', 'responsible_unsafe_act.id');
                $join->on('wg_customer_unsafe_act.responsible_type', '=', 'responsible_unsafe_act.type');
                $join->on('wg_customer_unsafe_act.customer_id', '=', 'responsible_unsafe_act.customer_id');
            })
            ->mergeBindings($qAgentUser)
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_unsafe_act.createdBy');
            })
            ->select(
                "wg_customer_unsafe_act.id",
                "wg_customer_unsafe_act.dateOf",
                "wg_customer_config_workplace.name AS work_place",
                "wg_config_job_activity_hazard_classification.name AS risk_type",
                "wg_customer_unsafe_act.place",
                "wg_customer_unsafe_act.description",
                "responsible_unsafe_act.name AS assignedTo",
                DB::raw("CONCAT_WS(' ', users.name, IFNULL(users.surname, '')) AS reportedBy"),
                "customer_unsafe_act_status.item AS status",
                "wg_customer_unsafe_act.status AS statusCode",
                "wg_customer_unsafe_act.customer_id",
                "responsible_unsafe_act.user_id AS assignedToId",
                "users.id AS reportedById"
            )
            ->where('wg_customer_unsafe_act.customer_id', $criteria->customerId);

        if (isset($criteria->assignedToId)) {
            $sQuery->where('responsible_unsafe_act.user_id', $criteria->assignedToId);
        }

        if (isset($criteria->reportedById)) {
            $sQuery->where('users.id', $criteria->reportedById);
        }

        $query = DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_unsafe_act"))
            ->mergeBindings($sQuery)
            ->select(
                "wg_customer_unsafe_act.dateOf",
                "wg_customer_unsafe_act.work_place",
                "wg_customer_unsafe_act.risk_type",
                "wg_customer_unsafe_act.place",
                "wg_customer_unsafe_act.description",
                "wg_customer_unsafe_act.assignedTo",
                "wg_customer_unsafe_act.reportedBy",
                "wg_customer_unsafe_act.status"
            );

        //$this->applyWhere($query, $criteria);

        $heading = [
            "FECHA" => "dateOf",
            "CENTRO DE TRABAJO" => "work_place",
            "TIPO DE PELIGRO" => "risk_type",
            "LUGAR" => "place",
            "DESC DE LA CONDICIÓN INSEGURA" => "description",
            "ASIGNADO A" => "assignedTo",
            "REPORTADO POR" => "reportedBy",
            "ESTADO" => "status"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportReportData($criteria)
    {
        $qAgentUser = CustomerModel::getRelatedUnsafeActAgentAndUserRaw($criteria);

        $sQuery = DB::table('wg_customer_unsafe_act')
            ->leftjoin("wg_customer_unsafe_act_observation", function ($join) {
                $join->on('wg_customer_unsafe_act_observation.customer_unsafe_act_id', '=', 'wg_customer_unsafe_act.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_unsafe_act_status', 'customer_unsafe_act_observation_status')), function ($join) {
                $join->on('wg_customer_unsafe_act_observation.status', '=', 'customer_unsafe_act_observation_status.value');
            })
            ->leftjoin("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_unsafe_act.work_place');
            })
            ->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
                $join->on('wg_config_job_activity_hazard_classification.id', '=', 'wg_customer_unsafe_act.risk_type');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_unsafe_act_status')), function ($join) {
                $join->on('wg_customer_unsafe_act.status', '=', 'customer_unsafe_act_status.value');
            })
            ->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible_unsafe_act"), function ($join) {
                $join->on('wg_customer_unsafe_act.responsible_id', '=', 'responsible_unsafe_act.id');
                $join->on('wg_customer_unsafe_act.responsible_type', '=', 'responsible_unsafe_act.type');
                $join->on('wg_customer_unsafe_act.customer_id', '=', 'responsible_unsafe_act.customer_id');
            })
            ->mergeBindings($qAgentUser)
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_unsafe_act.createdBy');
            })
            ->select(
                "wg_customer_unsafe_act.id",
                "wg_customer_unsafe_act.dateOf",
                "wg_customer_config_workplace.name AS work_place",
                "wg_config_job_activity_hazard_classification.name AS risk_type",
                "wg_customer_unsafe_act.place",
                "wg_customer_unsafe_act.description",
                "responsible_unsafe_act.name AS assignedTo",
                DB::raw("CONCAT_WS(' ', users.name, IFNULL(users.surname, '')) AS reportedBy"),
                "wg_customer_unsafe_act_observation.dateOf AS observationDateOf",
                "wg_customer_unsafe_act_observation.description AS observation",
                DB::raw("CASE WHEN customer_unsafe_act_observation_status.item IS NOT NULL THEN customer_unsafe_act_observation_status.item ELSE customer_unsafe_act_status.item END AS observationStatus"),
                "customer_unsafe_act_status.item AS status",
                "wg_customer_unsafe_act.status AS statusCode",
                "wg_customer_unsafe_act.customer_id",
                "responsible_unsafe_act.user_id AS assignedToId",
                "users.id AS reportedById"
            )
            ->where('wg_customer_unsafe_act.customer_id', $criteria->customerId)
            ->orderBy('wg_customer_unsafe_act.id', 'DESC');

        if (isset($criteria->assignedToId)) {
            $sQuery->where('responsible_unsafe_act.user_id', $criteria->assignedToId);
        }

        if (isset($criteria->reportedById)) {
            $sQuery->where('users.id', $criteria->reportedById);
        }

        $query = DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_unsafe_act"))
            ->mergeBindings($sQuery)
            ->select(
                "wg_customer_unsafe_act.dateOf",
                "wg_customer_unsafe_act.reportedBy",
                "wg_customer_unsafe_act.work_place",
                "wg_customer_unsafe_act.risk_type",
                "wg_customer_unsafe_act.description",
                "wg_customer_unsafe_act.place",
                "wg_customer_unsafe_act.observation",
                "wg_customer_unsafe_act.observationStatus",
                "wg_customer_unsafe_act.observationDateOf"
            );

        //$this->applyWhere($query, $criteria);

        $heading = [
            "FECHA" => "dateOf",
            "REPORTADO POR" => "reportedBy",
            "CENTRO DE TRABAJO" => "work_place",
            "TIPO DE PELIGRO" => "risk_type",
            "DESC DE LA CONDICIÓN INSEGURA" => "description",
            "LUGAR" => "place",
            "ESTADO" => "observationStatus",
            "OBSERVACIONES" => "observation",
            "FECHA OBSERVACIÓN" => "observationDateOf",
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportZipData($criteria)
    {
        $uids = [];

        foreach ($criteria->selectedItems as $key => $value) {
            if ($value->selected) {
                $uids[] = $key;
            }
        }

        $qAgentUser = CustomerModel::getRelatedUnsafeActAgentAndUserRaw($criteria);

        $sQuery = DB::table('wg_customer_unsafe_act')
            ->leftjoin("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_unsafe_act.work_place');
            })
            ->leftjoin("wg_config_job_activity_hazard_classification", function ($join) {
                $join->on('wg_config_job_activity_hazard_classification.id', '=', 'wg_customer_unsafe_act.risk_type');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_unsafe_act_status')), function ($join) {
                $join->on('wg_customer_unsafe_act.status', '=', 'customer_unsafe_act_status.value');
            })
            ->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible_unsafe_act"), function ($join) {
                $join->on('wg_customer_unsafe_act.responsible_id', '=', 'responsible_unsafe_act.id');
                $join->on('wg_customer_unsafe_act.responsible_type', '=', 'responsible_unsafe_act.type');
                $join->on('wg_customer_unsafe_act.customer_id', '=', 'responsible_unsafe_act.customer_id');
            })
            ->mergeBindings($qAgentUser)
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_unsafe_act.createdBy');
            })
            ->select(
                "wg_customer_unsafe_act.id",
                "wg_customer_unsafe_act.dateOf",
                "wg_customer_config_workplace.name AS work_place",
                "wg_config_job_activity_hazard_classification.name AS risk_type",
                "wg_customer_unsafe_act.place",
                "wg_customer_unsafe_act.address_formatted",
                "wg_customer_unsafe_act.description",
                "wg_customer_unsafe_act.imageUrl",
                "responsible_unsafe_act.name AS assignedTo",
                DB::raw("CONCAT_WS(' ', users.name, IFNULL(users.surname, '')) AS reportedBy"),
                "customer_unsafe_act_status.item AS status",
                "wg_customer_unsafe_act.status AS statusCode",
                "wg_customer_unsafe_act.customer_id",
                "responsible_unsafe_act.user_id AS assignedToId",
                "users.id AS reportedById"
            )
            ->whereIn('wg_customer_unsafe_act.id', $uids);

        $query = DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_unsafe_act"))
            ->mergeBindings($sQuery)
            ->select(
                "wg_customer_unsafe_act.id",
                "wg_customer_unsafe_act.status",
                "wg_customer_unsafe_act.dateOf",
                "wg_customer_unsafe_act.description",
                "wg_customer_unsafe_act.risk_type",
                "wg_customer_unsafe_act.assignedTo",
                "wg_customer_unsafe_act.reportedBy",
                "wg_customer_unsafe_act.work_place",
                "wg_customer_unsafe_act.place",
                "wg_customer_unsafe_act.address_formatted",
                "wg_customer_unsafe_act.imageUrl"
            );

        $heading = [
            "ESTADO",
            "FECHA",
            "DESC DE LA CONDICIÓN INSEGURA",
            "TIPO DE PELIGRO",
            "NOTIFICAR A",
            "REPORTADO POR",
            "CENTRO DE TRABAJO",
            "LUGAR",
            "DIRECCIÓN"
        ];

        return [
            "heading" => $heading,
            "data" => $query->get()
        ];
    }

    public function migrateFilesApi()
    {
        $entityFiles = File::whereAttachmentType("Wgroup\\CustomerUnsafeAct\\CustomerUnsafeAct")
                        ->get();

        \Wgroup\CustomerUnsafeAct\CustomerUnsafeAct::whereNotNull("imageUrl")
        ->get()
        ->each(function($unsafeact) use($entityFiles) {
            $fileRelation = $unsafeact->photos();
            $files = json_decode($unsafeact->imageUrl);
            foreach($files as $image) {
                try {
                    if(!empty($image->url) && is_string($image->url) && preg_match("/sylogi.co/", $image->url)) {
                        $file = new File();
                        $file->fromUrl($image->url);
                        $exists = $entityFiles->where("file_name", $file->file_name)->first();
                        if(!$exists){
                            $fileRelation->add($file);
                        }
                    }
                } catch (\Throwable $th) {
                    dd($th);
                }
            }
            // $unsafeact->imageUrl = null;
            // $unsafeact->save();
        });

        dd("Listo!!");

    }

    public function getYearList($criteria)
    {
        return DB::table('wg_customer_unsafe_act')
            ->select(
                DB::raw('YEAR(`dateOf`) as item'),
                DB::raw('YEAR(`dateOf`) as value')
            )
            ->where('wg_customer_unsafe_act.customer_id', [$criteria->customerId])
            ->groupBy(DB::raw('YEAR(`dateOf`)'), 'wg_customer_unsafe_act.customer_id')
            ->orderBy(DB::raw('YEAR(`dateOf`)'), 'DESC')
            ->get();
    }

    public function getWokplaceList($criteria)
    {
        return DB::table('wg_customer_unsafe_act')
            ->join("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_unsafe_act.work_place');
            })
            ->select(
                'wg_customer_config_workplace.id as value',
                'wg_customer_config_workplace.name as item'
            )
            ->where('wg_customer_unsafe_act.customer_id', [$criteria->customerId])
            ->when(isset($criteria->year), function($query) use ($criteria) {
                $query->where(DB::raw('YEAR(`dateOf`)'), $criteria->year);
            })
            ->groupBy('wg_customer_unsafe_act.work_place', 'wg_customer_unsafe_act.customer_id')
            ->orderBy('wg_customer_config_workplace.name', 'ASC')
            ->get();
    }

    public function getChartWorkplace($criteria)
    {
        $query = DB::table('wg_customer_unsafe_act')
            ->join("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_unsafe_act.work_place');
            })
            ->select(
                DB::raw("wg_customer_config_workplace.name AS label"),
                DB::raw("COUNT(*) AS value")
            )
            ->where('wg_customer_unsafe_act.customer_id', [$criteria->customerId])
            ->groupBy('wg_customer_config_workplace.id', 'wg_customer_unsafe_act.customer_id')
            ->orderBy('wg_customer_config_workplace.name', 'ASC');

        $query->where(DB::raw("YEAR(wg_customer_unsafe_act.dateOf)"), $criteria->year);

        if ($criteria->month) {
            $query->where(DB::raw("MONTH(wg_customer_unsafe_act.dateOf)"), $criteria->month);
        }

        if ($criteria->workplace) {
            $workPlaceId = is_object($criteria->workplace) ? $criteria->workplace->value : $criteria->workplace;
            $query->where("wg_customer_unsafe_act.work_place", $workPlaceId);
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Centro de Trabajo', 'field' => 'value']
            ]
        );
        return $this->chart->getChartBar($query->get(), $config);
    }

    public function getChartHazard($criteria)
    {
        $query = DB::table('wg_customer_unsafe_act')
            ->join("wg_config_job_activity_hazard_classification", function ($join) {
                $join->on('wg_config_job_activity_hazard_classification.id', '=', 'wg_customer_unsafe_act.risk_type');
            })
            ->select(
                DB::raw("wg_config_job_activity_hazard_classification.name AS label"),
                DB::raw("COUNT(*) AS value")
            )
            ->where('wg_customer_unsafe_act.customer_id', [$criteria->customerId])
            ->groupBy('wg_customer_unsafe_act.risk_type', 'wg_customer_unsafe_act.customer_id')
            ->orderBy('wg_config_job_activity_hazard_classification.name', 'ASC');

        $query->where(DB::raw("YEAR(wg_customer_unsafe_act.dateOf)"), $criteria->year);

        if ($criteria->month) {
            $query->where(DB::raw("MONTH(wg_customer_unsafe_act.dateOf)"), $criteria->month);
        }

        if ($criteria->workplace) {
            $workPlaceId = is_object($criteria->workplace) ? $criteria->workplace->value : $criteria->workplace;
            $query->where("wg_customer_unsafe_act.work_place", $workPlaceId);
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Tipo de Peligro', 'field' => 'value']
            ]
        );
        return $this->chart->getChartBar($query->get(), $config);
    }

    public function getChartPeriod($criteria)
    {
        $query = DB::table('wg_customer_unsafe_act')
            ->join("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_unsafe_act.work_place');
            })
            ->select(
                DB::raw("'Eventos' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_unsafe_act.dateOf) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_unsafe_act.customer_id', $criteria->customerId)
            ->groupBy(
                'wg_customer_unsafe_act.customer_id',
                DB::raw("YEAR(wg_customer_unsafe_act.dateOf)")
            )
            ->orderBy('wg_customer_config_workplace.name', 'ASC');

        $query->whereYear('wg_customer_unsafe_act.dateOf', '=', $criteria->year);

        if ($criteria->month) {
            $query->where(DB::raw("MONTH(wg_customer_unsafe_act.dateOf)"), $criteria->month);
        }

        if ($criteria->workplace) {
            $workPlaceId = is_object($criteria->workplace) ? $criteria->workplace->value : $criteria->workplace;
            $query->where("wg_customer_unsafe_act.work_place", $workPlaceId);
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Clasificación', 'field' => 'value']
            ]
        );

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartLine($query->get(), $config);
    }

    public function getChartStatus($criteria)
    {
        $query = DB::table('wg_customer_unsafe_act')
            ->join(DB::raw(SystemParameter::getRelationTable('customer_unsafe_act_status')), function ($join) {
                $join->on('wg_customer_unsafe_act.status', '=', 'customer_unsafe_act_status.value');
            })
            ->select(
                DB::raw("customer_unsafe_act_status.item AS label"),
                DB::raw("COUNT(*) AS value")
            )
            ->where('wg_customer_unsafe_act.customer_id', [$criteria->customerId])
            ->groupBy('wg_customer_unsafe_act.status', 'wg_customer_unsafe_act.customer_id')
            ->orderBy('customer_unsafe_act_status.item', 'ASC');

        $query->where(DB::raw("YEAR(wg_customer_unsafe_act.dateOf)"), $criteria->year);

        if ($criteria->month) {
            $query->where(DB::raw("MONTH(wg_customer_unsafe_act.dateOf)"), $criteria->month);
        }

        if ($criteria->workplace) {
            $workPlaceId = is_object($criteria->workplace) ? $criteria->workplace->value : $criteria->workplace;
            $query->where("wg_customer_unsafe_act.work_place", $workPlaceId);
        }

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Estado', 'field' => 'value']
            ]
        );
        return $this->chart->getChartBar($query->get(), $config);
    }


    public function getCountUnsafeConditions($criteria)
    {
        $workplace = $criteria->workplace ?? null;

        return DB::table('wg_customer_unsafe_act')
            ->join("wg_config_job_activity_hazard_classification", 'wg_config_job_activity_hazard_classification.id', '=', 'wg_customer_unsafe_act.risk_type')
            ->where('wg_customer_unsafe_act.customer_id', [$criteria->customerId])
            ->where(DB::raw("YEAR(wg_customer_unsafe_act.dateOf)"), $criteria->year)
            ->when($workplace, function($query) use ($workplace) {
                $query->where("wg_customer_unsafe_act.work_place", $workplace);
            })
            ->count();
    }

}
