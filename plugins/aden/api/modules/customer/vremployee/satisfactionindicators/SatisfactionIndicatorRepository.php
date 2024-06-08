<?php

namespace AdeN\Api\Modules\Customer\VrEmployee\Satisfactionindicators;

use DB;
use Log;

use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\ExportHelper;
use Maatwebsite\Excel\Facades\Excel;

use AdeN\Api\Classes\BaseRepository;
use System\Models\Parameters;
use Wgroup\CustomerParameter\CustomerParameter;

class SatisfactionIndicatorRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new SatisfactionIndicatorModel());
        $this->service = new SatisfactionIndicatorService();
    }


    public function all($criteria)
    {
        $this->setColumns([
            "customerId" => "customerId",
            "year" => "year",
            "date" => "date",
            "participants" => "participants",
        ]);

        $this->parseCriteria($criteria);

        $query = DB::table('wg_customer_vr_satisfactions_responses as r')
            ->groupBy(DB::raw("date_format(r.date_register, '%d/%m/%Y')"), 'r.customer_id')
            ->select(
                "r.customer_id as customerId",
                DB::raw("YEAR(r.date_register) as year"),
                DB::raw("DATE_FORMAT(r.date_register, '%Y-%m-%d') as date"),
                DB::raw("count(DISTINCT `group`) AS participants")
            );

        $query = DB::table(DB::raw("({$query->toSql()}) as d"))
            ->mergeBindings($query);

        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }



    public function valuationList($criteria)
    {
        $this->setColumns([
            "customerId" => "r.customer_id as customerId",
            "date" => DB::raw("DATE_FORMAT(r.date_register, '%Y-%m-%d') as date"),
            'experience' => 'cp.value as experience',
            'valuationAvailable' => DB::raw("if(count(r.answer_id) > 0, 1, 0) AS valuationAvailable"),
            "amount" => DB::raw("COUNT(IF(r.experience = cp.item, r.answer_id, null)) AS amount")
        ]);

        $query = DB::table('wg_customer_vr_satisfactions_responses as r')
            ->join('wg_customer_vr_satisfactions_questions as q', 'q.id', '=', 'r.question_id')
            ->join(DB::raw(CustomerParameter::getRelationTable('experienceVR', 'cp')), function ($join) {
                $join->on('cp.customer_id', 'r.customer_id');
            })
            ->groupBy('cp.value');

        $this->query($this->query());


        $this->applyCriteria($query, $criteria);
        return $this->get($query, $criteria);
    }

    public function downloadTemplate($customerId)
    {
        $instance = CmsHelper::getInstance();
        $file = "templates/$instance/PlantillaVRSatisfaccion.xlsx";

        $answerTypes = $this->service->getAnswerTypes($customerId);

        Excel::load(CmsHelper::getAppPath($file), function ($file) use ($answerTypes) {
            $sheet = $file->setActiveSheetIndex(1);
            $cells = [];

            $index = 0;
            foreach ($answerTypes as $key => $answerType) {
                $letter = self::getAlphabetic($index);
                $cellOptions = $letter.'2';
                $cellRange = $letter.'2:'. $letter . (count($answerType) + 1);
                $randomString = uniqid('VRS');

                $options = $answerTypes[$key]->map(function($row) {
                    return [ 'NOMBRE' => mb_strtoupper($row->NOMBRE) ];
                })->toArray();

                // write data
                $sheet->setCellValue($letter.'1', $key);
                $sheet->fromArray($options, null, $cellOptions, false);

                // configurar las columnas de la hoja de datos como rangos
                $file->addNamedRange(new \PHPExcel_NamedRange($randomString, $sheet, $cellRange));

                // definir las celdas que tendran las opciones activas en la hoja principal
                $cells[$cellOptions] = ['range' =>  $cellOptions .':'. $letter.'5000', 'formula' => $randomString];

                $index += 1;
            }

            $sheet = $file->setActiveSheetIndex(0);

            // assign title to the first sheet
            $sheet->fromArray($answerTypes->keys()->toArray(), null, 'A1', false);

            // assign the ranges
            ExportHelper::configSheetValidation($cells, $sheet);

        })->download('xlsx');
    }

    private static function getAlphabetic(int $index)
    {
        $lenghtCharts = 26;

        $alphabetic = range('A', 'Z');
        if ($index < $lenghtCharts) {
            return $alphabetic[$index];
        }

        $series = floor($index / $lenghtCharts);
        $as =  str_repeat('A', $series);
        $mod = $index % $lenghtCharts;

        return $as . $alphabetic[$mod];
    }


    public function getChartLineRegisteredVsParticipants($customerId) {
        return $this->service->getChartLineRegisteredVsParticipants($customerId);
    }

    public function getChartBarAmountBySatisfaction($customerId, $startDate = null, $endDate = null) {
        return $this->service->getChartBarAmountBySatisfaction($customerId, $startDate, $endDate);
    }

    public function getChartBarQuestionVsResponses($customerId, $date) {
        return $this->service->getChartBarQuestionVsResponses($customerId, $date);
    }

    public function getAllYears($customerId) {
        return $this->service->getAllYears($customerId);
    }

    public function getAllAnswerTypes() {
        return $this->service->getAllAnswerTypes();
    }

    public function getChartLineRegisteredVsParticipantsByMonths($startDate, $endDate, $customerId) {
        return $this->service->getChartLineRegisteredVsParticipantsByMonths($startDate, $endDate, $customerId);
    }

    public function getChartPieRegisteredVsParticipantsAllClientsAndPeriods($startDate, $endDate, $customerId) {
        return $this->service->getChartPieRegisteredVsParticipantsAllClientsAndPeriods($startDate, $endDate, $customerId);
    }

}
