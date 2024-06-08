<?php

namespace AdeN\Api\Modules\Customer\Covid\Daily;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Classes\Criteria;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeModel;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;

class CustomerCovidDailyService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getWorkplaceList($customerId)
    {
        return DB::table('wg_customer_config_workplace')
            ->select(
                DB::raw("TRIM(UPPER(wg_customer_config_workplace.name)) AS name")
            )
            ->where('wg_customer_config_workplace.customer_id', $customerId)
            ->where('wg_customer_config_workplace.status', 'Activo')
            ->orderBy('wg_customer_config_workplace.name')
            ->get();
    }

    public function getPeriodList($criteria)
    {
        $query = DB::table('wg_customer_covid')
            ->select(
                DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') AS item"),
                DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') AS value")
            )
            ->where('wg_customer_covid.customer_id', $criteria->customerId)
            ->groupBy(
                'wg_customer_covid.customer_id',
                DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m')")
            );

        if(!empty($criteria->isEmployee)) {
            $query->whereNotNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",0);
        }
        if(!empty($criteria->isExternal)) {
            $query->whereNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",1);
        }

        return $query->get();
    }

    public function getDateList($criteria)
    {
        $query = DB::table('wg_customer_covid')
            ->select(
                DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%d/%m/%Y') AS item"),
                DB::raw("wg_customer_covid.registration_date AS value")
            )
            ->where('wg_customer_covid.customer_id', $criteria->customerId)
            ->groupBy(
                "wg_customer_covid.registration_date"
            );
        
        if(!empty($criteria->isEmployee)) {
            $query->whereNotNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",0);
        }
        if(!empty($criteria->isExternal)) {
            $query->whereNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",1);
        }

        return $query->get();
    }

    public function getExportData($criteria)
    {
        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $customerCovidHeadId = CriteriaHelper::getMandatoryFilter($criteria, 'customerCovidHeadId');
        $period = CriteriaHelper::getMandatoryFilter($criteria, 'period');

        $qQuestion = $this->prepareQuestionQuery($customerCovidHeadId);
        $qAnswer =   $this->prepareAnswerQuery($customerCovidHeadId);
        $qTemperature = $this->prepareTemperature($customerCovidHeadId);

        $query = DB::table('wg_customer_covid');
        $query->join("wg_customer_covid_head", function ($join) {
                $join->on('wg_customer_covid_head.id', '=', 'wg_customer_covid.customer_covid_head_id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_covid_head.customer_id');
            })
            ->leftjoin("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid_head.customer_employee_id');
            })
            ->leftjoin("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('employee_document_type.value', '=', 'wg_employee.documentType');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_document_type', 'external_document_type')), function ($join) {
                $join->on('external_document_type.value', '=', 'wg_customer_covid_head.document_type');
            })
            ->leftjoin("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_covid_head.customer_workplace_id');
            })
            ->leftjoin("wg_customer_config_job", function ($join) {
                $join->on('wg_customer_config_job.id', '=', 'wg_customer_employee.job');
            })
            ->leftjoin("wg_customer_config_job_data", function ($join) {
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('employee_contract_type')), function ($join) {
                $join->on('employee_contract_type.value', '=', 'wg_customer_employee.contractType');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_covid_external_type')), function ($join) {
                $join->on('customer_covid_external_type.value', '=', 'wg_customer_covid_head.external_type');
            })
            ->leftjoin(DB::raw("wg_config_general AS risk_level"), function ($join) {
                $join->on('risk_level.value', '=', 'wg_customer_covid.risk_level');
                $join->where('risk_level.type', '=', 'RISK_LEVEL_COVID_19');
            })
            ->leftjoin(DB::raw("({$qQuestion->toSql()}) AS question"), function ($join) {
                $join->on('question.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->mergeBindings($qQuestion)
            ->leftjoin(DB::raw("({$qAnswer->toSql()}) AS answer"), function ($join) {
                $join->on('answer.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->mergeBindings($qAnswer)
            ->leftjoin(DB::raw("({$qTemperature->toSql()}) AS temperature"), function ($join) {
                $join->on('temperature.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->mergeBindings($qTemperature)
            ->leftjoin(DB::raw(CustomerEmployeeModel::getRelationInfoDetailByCustomer('employee_info_detail', $customerId ? $customerId->value : null)), function ($join) {
                $join->on('wg_employee.id', '=', 'employee_info_detail.entityId');
            })->leftjoin(DB::raw("wg_customers AS contractor"), function ($join) {
                $join->on('contractor.id', '=', 'wg_customer_covid_head.contractor_id');
            })
            ->select(
                DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%d/%m/%Y  %T') AS registration_date"),
                DB::raw("CASE WHEN wg_customer_covid_head.is_external = 1 THEN 'Externo' ELSE 'Empleado' END AS isExternal"),
                'customer_covid_external_type.item AS externalType',
                'contractor.businessName as contractorName',
                DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN wg_customer_covid_head.document_number ELSE wg_employee.documentNumber END AS documentNumber"),
                DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN external_document_type.item ELSE employee_document_type.item END AS documentType"),
                DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN wg_customer_covid_head.first_name ELSE wg_employee.firstName END AS firstName"),
                DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN wg_customer_covid_head.last_name ELSE wg_employee.lastName END AS lastName"),
                "employee_contract_type.item AS contractType",
                "wg_customer_config_workplace.name as customerWorkplace",
                DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN wg_customer_covid_head.job ELSE wg_customer_config_job_data.name END AS job"),
                DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN wg_customer_covid_head.telephone ELSE employee_info_detail.telephone END AS telephone"),
                DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN wg_customer_covid_head.mobile ELSE employee_info_detail.mobile END AS mobile"),
                DB::raw("CASE WHEN wg_customer_covid_head.customer_employee_id IS NULL THEN wg_customer_covid_head.address ELSE employee_info_detail.address END AS address"),
                DB::raw("CASE WHEN answer.answer_1 = 1 THEN 'Si' ELSE 'No' END AS answer_1"),
                DB::raw("CASE WHEN answer.answer_2 = 1 THEN 'Si' ELSE 'No' END AS answer_2"),
                DB::raw("CASE WHEN answer.answer_3 = 1 THEN 'Si' ELSE 'No' END AS answer_3"),
                DB::raw("CASE WHEN answer.answer_4 = 1 THEN 'Si' ELSE 'No' END AS answer_4"),
                DB::raw("CASE WHEN answer.answer_5 = 1 THEN 'Si' ELSE 'No' END AS answer_5"),
                DB::raw("CASE WHEN answer.answer_6 = 1 THEN 'Si' ELSE 'No' END AS answer_6"),
                DB::raw("CASE WHEN answer.answer_7 = 1 THEN 'Si' ELSE 'No' END AS answer_7"),
                DB::raw("CASE WHEN answer.answer_8 = 1 THEN 'Si' ELSE 'No' END AS answer_8"),
                DB::raw("CASE WHEN answer.answer_9 = 1 THEN 'Si' ELSE 'No' END AS answer_9"),
                DB::raw("CASE WHEN answer.answer_10 = 1 THEN 'Si' ELSE 'No' END AS answer_10"),
                DB::raw("CASE WHEN answer.answer_11 = 1 THEN 'Si' ELSE 'No' END AS answer_11"),
                DB::raw("CASE WHEN answer.answer_12 = 1 THEN 'Si' ELSE 'No' END AS answer_12"),
                DB::raw("CASE WHEN answer.answer_13 = 1 THEN 'Si' ELSE 'No' END AS answer_13"),
                DB::raw("CASE WHEN answer.answer_14 = 1 THEN 'Si' ELSE 'No' END AS answer_14"),
                DB::raw("CASE WHEN answer.answer_15 = 1 THEN 'Si' ELSE 'No' END AS answer_15"),
                DB::raw("CASE WHEN answer.answer_16 = 1 THEN 'Si' ELSE 'No' END AS answer_16"),
                DB::raw("CASE WHEN answer.answer_17 = 1 THEN 'Si' ELSE 'No' END AS answer_17"),
                DB::raw("CASE WHEN answer.answer_18 = 1 THEN 'Si' ELSE 'No' END AS answer_18"),
                DB::raw("DATE_FORMAT(temperature.date_1, ' %T') AS hour_1"),
                'temperature.temperature_1',
                'temperature.observation_1',
                "temperature.pulse_1",
                "temperature.oximetria_1",
                "temperature.address_1",
                "temperature.origin_1",
                DB::raw("DATE_FORMAT(temperature.date_2, ' %T') AS hour_2"),
                'temperature.temperature_2',
                'temperature.observation_2',
                "temperature.pulse_2",
                "temperature.oximetria_2",
                "temperature.address_2",
                "temperature.origin_2",
                DB::raw("DATE_FORMAT(temperature.date_3, ' %T') AS hour_3"),
                'temperature.temperature_3',
                'temperature.observation_3',
                "temperature.pulse_3",
                "temperature.oximetria_3",
                "temperature.address_3",
                "temperature.origin_3",
                DB::raw("DATE_FORMAT(temperature.date_4, ' %T') AS hour_4"),
                'temperature.temperature_4',
                'temperature.observation_4',
                "temperature.pulse_4",
                "temperature.oximetria_4",
                "temperature.address_4",
                "temperature.origin_4",
                "risk_level.name AS riskLevelText",
                "question.questions",
                'wg_customer_covid.customer_covid_head_id AS customerCovidHeadId',
                'wg_customer_covid_head.customer_id AS customerId',
                'wg_customer_covid_head.created_by AS createdBy',
                DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') AS period")
            );

            // personas contacto
            $personInTouch = DB::table('wg_customer_covid_person_in_touch')
                ->groupBy("customer_covid_id")
                ->select(DB::raw("GROUP_CONCAT(CONCAT('  ',person)) AS persons, GROUP_CONCAT(CONCAT('  ',place)) AS places"), "customer_covid_id");

            $query->leftjoin(DB::raw("({$personInTouch->toSql()}) as persons"), function ($join) {
                $join->on('persons.customer_covid_id', '=', 'wg_customer_covid.id');
            })->mergeBindings($personInTouch);

            $query->addSelect("persons.persons AS person_touch");
            $query->addSelect("persons.places AS person_place");
            
            $query->groupBy(
                'wg_customer_covid_head.customer_id',
                'wg_customer_covid_head.document_number',
                "wg_customer_covid_head.document_type",
                "wg_customer_covid.registration_date",
                "wg_customer_covid_head.customer_employee_id"
            )
            ->orderBy("wg_customer_covid.registration_date");

        if(!empty($period)){
            $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ? ", [$period->value]);
        }

        if(!empty($customerCovidHeadId)){
            $query->where("wg_customer_covid_head.id","=",$customerCovidHeadId->value);
        }

        if(!empty($customerId)){
            $query->where("wg_customer_covid_head.customer_id","=",$customerId->value);
        }

        $query = $this->prepareQuery($query->toSql())
            ->mergeBindings($query);

        $this->applyWhere($query, $criteria);

        return ExportHelper::headings($query->get(), $this->getHeader());
    }

    public function getHeader()
    {
        return  [
            "FECHA REGISTRO" => "registration_date",
            "TIPO PERSONAL" => "isExternal",
            "TIPO DE EXTERNO" => "externalType",
            "EMPRESA CONTRATISTA" => "contractorName",
            "NÚMERO IDENTIFICACIÓN" => "documentNumber",
            "TIPO IDENTIFICACIÓN" => "documentType",
            "NOMBRE(S)" => "firstName",
            "APELLIDOS" => "lastName",
            "TIPO CONTRATO" => "contractType",
            "CENTRO DE TRABAJO" => "customerWorkplace",
            "CARGO" => "job",
            "TELÉFONO" => "telephone",
            "CELULAR" => "mobile",
            "DIRECCIÓN" => "address",
            "PREGUNTA 1" => "answer_1",
            "PREGUNTA 2" => "answer_2",
            "PREGUNTA 3" => "answer_3",
            "PREGUNTA 4" => "answer_4",
            "PREGUNTA 5" => "answer_5",
            "PREGUNTA 6" => "answer_6",
            "PREGUNTA 7" => "answer_7",
            "PREGUNTA 8" => "answer_8",
            "PREGUNTA 9" => "answer_9",
            "PREGUNTA 10" => "answer_10",
            "PREGUNTA 11" => "answer_11",
            "PREGUNTA 12" => "answer_12",
            "PREGUNTA 13" => "answer_13",
            "PREGUNTA 14" => "answer_14",
            "PREGUNTA 15" => "answer_15",
            "PREGUNTA 16" => "answer_16",
            "PREGUNTA 17" => "answer_17",
            "PREGUNTA 18" => "answer_18",
            "SÍNTOMAS" => "questions",
            "TEMPERATURA 1" => "temperature_1",
            "HORA 1" => "hour_1",
            "OBSEVACION 1" => "observation_1",
            "PULSO 1" => "pulse_1",
            "OXIMETRIA 1" => "oximetria_1",
            "DIRECCION 1" => "address_1",
            "ORIGEN 1" => "origin_1",
            "TEMPERATURA 2" => "temperature_2",
            "HORA 2" => "hour_2",
            "OBSEVACION 2" => "observation_2",
            "PULSO 2" => "pulse_2",
            "OXIMETRIA 2" => "oximetria_2",
            "DIRECCION 2" => "address_2",
            "ORIGEN 2" => "origin_2",
            "TEMPERATURA 3" => "temperature_3",
            "HORA 3" => "hour_3",
            "OBSEVACION 3" => "observation_3",
            "PULSO 3" => "pulse_3",
            "OXIMETRIA 3" => "oximetria_3",
            "DIRECCION 3" => "address_3",
            "ORIGEN 3" => "origin_3",
            "TEMPERATURA 4" => "temperature_4",
            "HORA 4" => "hour_4",
            "OBSEVACION 4" => "observation_4",
            "PULSO 4" => "pulse_4",
            "OXIMETRIA 4" => "oximetria_4",
            "DIRECCION 4" => "address_4",
            "ORIGEN 4" => "origin_4",
            "NIVEL DE RIESGO" => "riskLevelText",
            "LUGAR CONTACTO" => "person_touch",
            "PERSONA CON LA QUE TUVO CONTACTO " => "person_place"
        ];
    }
        
        private function prepareQuestionQuery($customerCovidHeadId)
        {
            return DB::table('wg_customer_covid_question')
            ->join(DB::raw("wg_covid_question"), function ($join) {
                $join->on('wg_covid_question.code', '=', 'wg_customer_covid_question.covid_question_code');
            })
            ->join(DB::raw("wg_customer_covid"), function ($join) {
                $join->on('wg_customer_covid_question.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->where('wg_customer_covid_question.is_active', 1)
            ->where('wg_customer_covid.customer_covid_head_id', $customerCovidHeadId->value)
            ->select(
                'wg_customer_covid_question.customer_covid_id',
                DB::raw("GROUP_CONCAT(CONCAT_WS(': ', wg_covid_question.name, wg_customer_covid_question.observation) SEPARATOR ' / ') AS questions")
            )
            ->groupBy('wg_customer_covid_question.customer_covid_id');
    }

    private function prepareAnswerQuery($customerCovidHeadId)
    {
        return DB::table('wg_customer_covid_question')
                ->join(DB::raw("wg_customer_covid"), function ($join) {
                    $join->on('wg_customer_covid_question.customer_covid_id', '=', 'wg_customer_covid.id');
                })
                ->select(
                    'wg_customer_covid_question.customer_covid_id',
                    DB::raw("MAX(CASE WHEN covid_question_code = '001' THEN is_active END) AS answer_1"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '002' THEN is_active END) AS answer_2"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '003' THEN is_active END) AS answer_3"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '004' THEN is_active END) AS answer_4"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '005' THEN is_active END) AS answer_5"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '006' THEN is_active END) AS answer_6"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '007' THEN is_active END) AS answer_7"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '008' THEN is_active END) AS answer_8"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '009' THEN is_active END) AS answer_9"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '010' THEN is_active END) AS answer_10"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '011' THEN is_active END) AS answer_11"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '012' THEN is_active END) AS answer_12"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '013' THEN is_active END) AS answer_13"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '014' THEN is_active END) AS answer_14"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '015' THEN is_active END) AS answer_15"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '016' THEN is_active END) AS answer_16"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '017' THEN is_active END) AS answer_17"),
                    DB::raw("MAX(CASE WHEN covid_question_code = '018' THEN is_active END) AS answer_18")
                )
                ->where('wg_customer_covid.customer_covid_head_id', $customerCovidHeadId->value)
                ->groupBy('wg_customer_covid_question.customer_covid_id');
    }

    private function prepareTemperature($customerCovidHeadId)
    {
        $qInner = DB::table(DB::raw("(SELECT @row_num := 1) x, (SELECT @prev_value := '') y, wg_customer_covid_temperature"))
            ->select(
                'wg_customer_covid_temperature.customer_covid_id',
                'wg_customer_covid_temperature.temperature',
                'wg_customer_covid_temperature.observation',
                'wg_customer_covid_temperature.pulse',
                'wg_customer_covid_temperature.oximetria',
                'wg_customer_covid_temperature.address',
                'wg_customer_covid_temperature.origin',
                'wg_customer_covid_temperature.registration_date',
                DB::raw("@row_num := CASE WHEN @prev_value = wg_customer_covid_temperature.customer_covid_id THEN @row_num + 1 ELSE 1 END AS sortorder"),
                DB::raw("@prev_value := wg_customer_covid_temperature.customer_covid_id AS current_group")
            )
            ->join(DB::raw("wg_customer_covid"), function ($join) {
                $join->on('wg_customer_covid_temperature.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->where('wg_customer_covid.customer_covid_head_id', $customerCovidHeadId->value)
            ->orderBy("wg_customer_covid_temperature.customer_covid_id");

        return DB::table(DB::raw("({$qInner->toSql()}) AS temperature_sort"))
            ->mergeBindings($qInner)
            ->select(
                'temperature_sort.customer_covid_id',
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 1 THEN temperature_sort.temperature END) AS temperature_1"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 1 THEN temperature_sort.observation END) AS observation_1"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 1 THEN temperature_sort.pulse END) AS pulse_1"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 1 THEN temperature_sort.oximetria END) AS oximetria_1"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 1 THEN temperature_sort.address END) AS address_1"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 1 THEN temperature_sort.origin END) AS origin_1"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 1 THEN temperature_sort.registration_date END) AS date_1"),

                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 2 THEN temperature_sort.temperature END) AS temperature_2"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 2 THEN temperature_sort.observation END) AS observation_2"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 2 THEN temperature_sort.pulse END) AS pulse_2"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 2 THEN temperature_sort.oximetria END) AS oximetria_2"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 2 THEN temperature_sort.address END) AS address_2"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 2 THEN temperature_sort.origin END) AS origin_2"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 2 THEN temperature_sort.registration_date END) AS date_2"),

                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 3 THEN temperature_sort.temperature END) AS temperature_3"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 3 THEN temperature_sort.observation END) AS observation_3"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 3 THEN temperature_sort.pulse END) AS pulse_3"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 3 THEN temperature_sort.oximetria END) AS oximetria_3"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 3 THEN temperature_sort.address END) AS address_3"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 3 THEN temperature_sort.origin END) AS origin_3"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 3 THEN temperature_sort.registration_date END) AS date_3"),

                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 4 THEN temperature_sort.temperature END) AS temperature_4"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 4 THEN temperature_sort.observation END) AS observation_4"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 4 THEN temperature_sort.pulse END) AS pulse_4"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 4 THEN temperature_sort.oximetria END) AS oximetria_4"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 4 THEN temperature_sort.address END) AS address_4"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 4 THEN temperature_sort.origin END) AS origin_4"),
                DB::raw("MAX(CASE WHEN temperature_sort.sortorder = 4 THEN temperature_sort.registration_date END) AS date_4")
            )
            ->whereRaw('temperature_sort.sortorder BETWEEN 1 and 4')
            ->groupBy('temperature_sort.customer_covid_id');
    }

    public function getGenreCharPie($criteria)
    {
        $query = DB::table('wg_customer_covid')
            ->leftJoin('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid.customer_id');
            })
            ->leftJoin('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('customer_covid_external_type')), function ($join) {
                $join->on('customer_covid_external_type.value', '=', 'wg_customer_covid.external_type');
            })
            ->select(
                "wg_employee.gender AS label",
                "customer_covid_external_type.item as labelExternal",
                DB::raw("COUNT(*) AS value")
            )
            ->where('wg_customer_covid.customer_id', $criteria->customerId)
            ->groupBy(
                'wg_customer_covid.customer_id',
                'wg_employee.gender',
                'customer_covid_external_type.item'
            );

        if(!empty($criteria->workplaceId)) {
            $query->where('wg_customer_covid.customer_workplace_id', $criteria->workplaceId);
        }
        if(!empty($criteria->contractorId)) {
            $query->where('wg_customer_covid.contractor_id', $criteria->contractorId);
        }
        if(!empty($criteria->isEmployee)) {
            $query->whereNotNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",0);
        }
        if(!empty($criteria->isExternal)) {
            $query->whereNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",1);
        }

        if ($criteria->type == 1) {
            $query->whereRaw("wg_customer_covid.registration_date = ?", [$criteria->day]);
            $query->groupBy("wg_customer_covid.registration_date");
        } else if ($criteria->type == 0) {
            $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ?", [$criteria->period]);
            $query->groupBy(DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m')"));
        }

        $data = $query->get();

        $total = array_reduce($data, function ($value, $item) {
            $value += floatval($item->value);
            return $value;
        }, 0);

        $data = array_map(function ($item) use ($total,$criteria) {
            $value = $item->value;
            $item->label = !empty($criteria->isEmployee) ? ($item->label == 'F' ? 'Femenino' : 'Masculino') : $item->labelExternal;
            $item->value = round((floatval($item->value) / $total) * 100, 2);
            $item->label = "({$value}) {$item->label}: ({$item->value} %)";
            return $item;
        }, $data);

        return $this->chart->getChartPie($data);
    }

    public function getPregnantCharPie($criteria)
    {
        $qBase = DB::table('wg_customer_covid')
            ->leftJoin('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid.customer_id');
            })
            ->leftJoin('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->join('wg_customer_covid_question', function ($join) {
                $join->on('wg_customer_covid_question.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->select(
                DB::raw("COUNT(*) AS value")
            )
            ->where('wg_customer_covid.customer_id', $criteria->customerId)
            ->where('wg_customer_covid_question.covid_question_code', '002')
            ->where('wg_customer_covid_question.is_active', 1);

        if(!empty($criteria->workplaceId)) {
            $qBase->where('wg_customer_covid.customer_workplace_id', $criteria->workplaceId);
        }
        if(!empty($criteria->contractorId)) {
            $qBase->where('wg_customer_covid.contractor_id', $criteria->contractorId);
        }
        if(!empty($criteria->isEmployee)) {
            $qBase->whereNotNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",0);
        }
        if(!empty($criteria->isExternal)) {
            $qBase->whereNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",1);
        }
        
        if ($criteria->type == 1) {
            $qBase->whereRaw("wg_customer_covid.registration_date = ?", [$criteria->day]);
            $qBase->groupBy("wg_customer_covid.registration_date");
            $query = $qBase;
        } else if ($criteria->type == 0) {
            if(!empty($criteria->isEmployee)) {
                $qBase->addSelect(DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') AS period"));
                $qBase->addSelect('wg_customer_covid.customer_id');
                $qBase->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ?", [$criteria->period]);
                $qBase->groupBy(
                    DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m')"),
                    'wg_customer_covid.customer_employee_id'
                );
    
                $query = DB::table(DB::raw("({$qBase->toSql()}) AS indicator"))
                    ->mergeBindings($qBase)
                    ->select(
                        DB::raw("COUNT(*) AS value")
                    )
                    ->groupBy('customer_id', 'period');
            } else {
                $qBase->addSelect(DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') AS period"));
                $qBase->addSelect('wg_customer_covid.document_number');
                $qBase->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ?", [$criteria->period]);
                $qBase->groupBy(
                    DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m')"),
                    'wg_customer_covid.document_number'
                );
    
                $query = DB::table(DB::raw("({$qBase->toSql()}) AS indicator"))
                    ->mergeBindings($qBase)
                    ->select(
                        DB::raw("COUNT(*) AS value")
                    )
                    ->groupBy('period');

            }
        }

        return (($item = $query->first())) ? $item->value : 0;
    }

    public function getFeverCharBar($criteria)
    {
        $qTemperature = DB::table('wg_customer_covid_temperature')
            ->select(
                'customer_covid_id',
                DB::raw("MAX(temperature) AS temperature")
            )
            ->whereRaw("temperature >= 37.3")
            ->groupBy(
                'customer_covid_id'
            );

        $query = DB::table('wg_customer_covid')
            ->leftJoin('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid.customer_id');
            })
            ->leftJoin('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->join(DB::raw("({$qTemperature->toSql()}) as wg_customer_covid_temperature"), function ($join) {
                $join->on('wg_customer_covid_temperature.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->mergeBindings($qTemperature)
            ->select(
                "wg_customer_covid_temperature.temperature AS label",
                DB::raw("COUNT(*) AS value")
            )
            ->where('wg_customer_covid.customer_id', $criteria->customerId)
            ->groupBy(
                'wg_customer_covid.customer_id',
                'wg_customer_covid_temperature.temperature'
            );

        if(!empty($criteria->workplaceId)) {
            $query->where('wg_customer_covid.customer_workplace_id', $criteria->workplaceId);
        }
        if(!empty($criteria->contractorId)) {
            $query->where('wg_customer_covid.contractor_id', $criteria->contractorId);
        }
        if(!empty($criteria->isEmployee)) {
            $query->whereNotNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",0);
        }
        if(!empty($criteria->isExternal)) {
            $query->whereNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",1);
        }

        if ($criteria->type == 1) {
            $query->whereRaw("wg_customer_covid.registration_date = ?", [$criteria->day]);
            $query->groupBy("wg_customer_covid.registration_date");
        } else if ($criteria->type == 0) {
            $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ?", [$criteria->period]);
            $query->groupBy(DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m')"));
        }

        $data = $query->get();

        $config = array(
            "labelColumn" => ['Temperatura'],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getEmployeeCharBar($criteria)
    {
        $query = DB::table('wg_customer_covid')
            ->leftJoin('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid.customer_id');
            })
            ->leftJoin('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->select(
                DB::raw("wg_customer_covid.registration_date AS label"),
                DB::raw("COUNT(*) AS value")
            )
            ->where('wg_customer_covid.customer_id', $criteria->customerId)
            ->groupBy(
                'wg_customer_covid.customer_id',
                "wg_customer_covid.registration_date"
            );

        if(!empty($criteria->workplaceId)) $query->where('wg_customer_covid.customer_workplace_id', $criteria->workplaceId);
        if(!empty($criteria->contractorId)) $query->where('wg_customer_covid.contractor_id', $criteria->contractorId);
        if(!empty($criteria->isEmployee)) $query->whereNotNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",0);
        if(!empty($criteria->isExternal)) $query->whereNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",1);

        if ($criteria->type == 1) {
            $query->whereRaw("wg_customer_covid.registration_date = ?", [$criteria->day]);
        } else if ($criteria->type == 0) {
            $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ?", [$criteria->period]);
        }

        $data = $query->get();

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => !empty($criteria->isExternal) ? 'Externos' :'Empleados', 'field' => 'value', 'color' => '#EEA236']
            ]
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getEmployeeWorkplaceCharBar($criteria)
    {
        $query = DB::table('wg_customer_covid')
            ->leftJoin('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_covid.customer_workplace_id');
            })
            ->select(
                DB::raw("wg_customer_config_workplace.name AS label"),
                DB::raw("COUNT(*) AS value")
            )
            ->where('wg_customer_covid.customer_id', $criteria->customerId)
            ->groupBy("wg_customer_config_workplace.name");

        if(!empty($criteria->workplaceId)) {
            $query->where('wg_customer_covid.customer_workplace_id', $criteria->workplaceId);
        }
        if(!empty($criteria->contractorId)) {
            $query->where('wg_customer_covid.contractor_id', $criteria->contractorId);
        }
        if(!empty($criteria->isEmployee)) {
            $query->whereNotNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",0);
        }
        if(!empty($criteria->isExternal)) {
            $query->whereNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",1);
        }

        if ($criteria->type == 1) {
            $query->whereRaw("wg_customer_covid.registration_date = ?", [$criteria->day]);
        } else if ($criteria->type == 0) {
            $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ?", [$criteria->period]);
        }

        $data = $query->get();

        $config = array(
            "labelColumn" => ['Externos'],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getRiskLevelCharBar($criteria)
    {
        $query = DB::table('wg_customer_covid')
            ->leftJoin('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_covid.customer_employee_id');
                $join->on('wg_customer_employee.customer_id', '=', 'wg_customer_covid.customer_id');
            })
            ->leftJoin('wg_employee', function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->select(
                DB::raw("'Nivel de riesgo' AS label"),
                DB::raw("COUNT(*) AS value"),
                DB::raw("SUM(CASE WHEN wg_customer_covid.risk_level = 'B' THEN 1 ELSE 0 END) AS low"),
                DB::raw("SUM(CASE WHEN wg_customer_covid.risk_level = 'M' THEN 1 ELSE 0 END) AS medium"),
                DB::raw("SUM(CASE WHEN wg_customer_covid.risk_level = 'A' THEN 1 ELSE 0 END) AS high")
            )
            ->where('wg_customer_covid.customer_id', $criteria->customerId)
            ->groupBy(
                'wg_customer_covid.customer_id'
            );

        if(!empty($criteria->workplaceId)) {
            $query->where('wg_customer_covid.customer_workplace_id', $criteria->workplaceId);
        }
        if(!empty($criteria->contractorId)) {
            $query->where('wg_customer_covid.contractor_id', $criteria->contractorId);
        }
        if(!empty($criteria->isEmployee)) {
            $query->whereNotNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",0);
        }
        if(!empty($criteria->isExternal)) {
            $query->whereNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",1);
        }

        if ($criteria->type == 1) {
            $query->whereRaw("wg_customer_covid.registration_date = ?", [$criteria->day]);
            $query->groupBy("wg_customer_covid.registration_date");
        } else if ($criteria->type == 0) {
            $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ?", [$criteria->period]);
            $query->groupBy(DB::raw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m')"));
        }

        $data = $query->get();

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Bajo', 'field' => 'low', 'color' => '#5CB85C'],
                ['label' => 'Medio', 'field' => 'medium', 'color' => '#EEA236'],
                ['label' => 'Alto', 'field' => 'high', 'color' => '#D43F3A']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getCovidWorkplaceList($criteria)
    {

        $query = DB::table('wg_customer_covid')
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_covid.customer_workplace_id', '=', 'wg_customer_config_workplace.id');
            })
            ->select('wg_customer_config_workplace.id','wg_customer_config_workplace.name')
            ->where('wg_customer_config_workplace.customer_id', $criteria->customerId)
            ->orderBy('wg_customer_config_workplace.name')
            ->groupBy("wg_customer_config_workplace.id");
            
            if(!empty($criteria->isEmployee)) {
                $query->whereNotNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",0);
            }
            if(!empty($criteria->isExternal)) {
                $query->whereNull('wg_customer_covid.customer_employee_id')->where("wg_customer_covid.is_external",1);
            }
            
            if (!empty($criteria->day)) {
                $query->whereRaw("wg_customer_covid.registration_date = ?", [$criteria->day]);
            }
            if (!empty($criteria->period)) {
                $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ?", [$criteria->period]);
            }

        return $query->get();
    }

    public function getCovidContractorList($criteria)
    {

        $query = DB::table('wg_customers')
            ->join('wg_customer_covid', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_covid.contractor_id');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.id AS value',
                'wg_customers.businessName AS item'
            )
            ->where('wg_customer_covid.customer_id', $criteria->customerId)
            ->groupBy("wg_customers.id")
            ->orderBy('wg_customers.businessName');

        if (!empty($criteria->day)) {
            $query->whereRaw("wg_customer_covid.registration_date = ?", [$criteria->day]);
        }
        if (!empty($criteria->period)) {
            $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = ?", [$criteria->period]);
        }

        return $query->get();
    }

    public function mergeInfo()
    {

        $covidList = DB::table('wg_customer_covid')
            ->select("*")      
            ->get();
        
        DB::beginTransaction();

        try {

            foreach ($covidList as $covid) {
                $covidTemperatureList = DB::table('wg_customer_covid_temperature')
                    ->select("id","customer_covid_id",
                        DB::raw("DATE_FORMAT(registration_date , '%Y-%m-%d') as registration_date")
                    )
                    ->where("customer_covid_id", $covid->id)
                    ->get();

                foreach ($covidTemperatureList as $temperature) {
                    if($temperature->registration_date != $covid->registration_date) {
                        $covidWithDate = DB::table('wg_customer_covid')
                            ->select("*")   
                            ->where("registration_Date",$temperature->registration_date)
                            ->where("document_number",$covid->document_number)
                            ->first();

                            $moveTo = 0;
                            if(!isset($covidWithDate->id)) {
                                unset($covid->id);
                                $covid->registration_date = $temperature->registration_date;
                                $newCovid = DB::table('wg_customer_covid')->insertGetId(((array)$covid));
                                $moveTo = $newCovid;
                            } else {
                                $moveTo = $covidWithDate->id;
                            }

                            DB::table('wg_customer_covid_temperature')
                                    ->where('id', $temperature->id)
                                    ->update(['customer_covid_id' => $moveTo]);
                    }
                };
            }

            DB::commit();
            
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    public function getRelationQuestion($customerCovidHeadId)
    {
        return DB::table("wg_customer_covid_question")
            ->select(
                DB::raw("GROUP_CONCAT(CONCAT('  ',wg_covid_question.name)) AS symptoms"),
                "customer_covid_id"
            )
            ->join("wg_covid_question", function ($join) {
                $join->on('wg_customer_covid_question.covid_question_code', '=', 'wg_covid_question.code');
            })
            ->join("wg_customer_covid", function ($join) {
                $join->on('wg_customer_covid_question.customer_covid_id', '=', 'wg_customer_covid.id');
            })
            ->where("is_active",1)
            ->where("customer_covid_head_id", $customerCovidHeadId->value)
            ->groupBy("customer_covid_id");
    }

    public function getLastDate($covidId)
    {
        return DB::table("wg_customer_covid")
            ->select('id')
            ->where("customer_covid_head_id", $covidId)
            ->orderBy("registration_date","desc")
            ->first();
    }

    public function setFever($parentId)
    {
        DB::table("wg_customer_covid_question")
            ->where("customer_covid_id", $parentId)
            ->where("covid_question_code", "004")
            ->update(['is_active' => 1]);
    }

    public function quitFever($parentId)
    {
        DB::table("wg_customer_covid_question")
            ->where("customer_covid_id", $parentId)
            ->where("covid_question_code", "004")
            ->update(['is_active' => 0]);
    }


}
