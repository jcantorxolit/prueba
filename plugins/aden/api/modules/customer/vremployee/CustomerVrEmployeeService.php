<?php

namespace AdeN\Api\Modules\Customer\VrEmployee;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Classes\Criteria;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Config\SignatureIndicatorVr\SignatureIndicatorVrRepository;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Modules\Customer\VrEmployee\Experience\ExperienceModel;
use AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\ExperienceAnswerService;
use Carbon\Carbon;
use DB;
use Exception;
use stdClass;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\SystemParameter\SystemParameter;

class CustomerVrEmployeeService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }


    public function getExportData($criteria)
    {
        $query = DB::table("wg_customer_vr_employee_answer_scene");
        $query->leftJoin("wg_customer_vr_employee_question_scene", function ($join) {
            $join->on('wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id', '=', 'wg_customer_vr_employee_question_scene.id');
        })->leftJoin("wg_customer_vr_employee_experience", function ($join) {
            $join->on('wg_customer_vr_employee_question_scene.experience_scene_code', '=', 'wg_customer_vr_employee_experience.experience_scene_code');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience.experience_code', '=', 'experience_vr.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene')), function ($join) {
            $join->on('wg_customer_vr_employee_experience.experience_scene_code', '=', 'experience_scene.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene_application')), function ($join) {
            $join->on('wg_customer_vr_employee_answer_scene.value', '=', 'experience_scene_application.value');
        })->join("wg_customer_vr_employee_answer_experience", function ($join) {
            $join->on('wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id', '=', 'wg_customer_vr_employee_answer_experience.id');
            $join->on('wg_customer_vr_employee_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee_answer_experience.customer_vr_employee_id');
        })->join("wg_customer_vr_employee", function ($join) {
            $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee.id');
        })->join("wg_customer_employee", function ($join) {
            $join->on('wg_customer_employee.id', '=', 'wg_customer_vr_employee.customer_employee_id');
        })->join("wg_employee", function ($join) {
            $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
        })->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
            $join->on('employee_document_type.value', '=', 'wg_customer_vr_employee.document_type');
        });

        $query->select(
            DB::raw("DATE_SUB(wg_customer_vr_employee.created_at, INTERVAL 5 HOUR) AS registrationDate"),
            "wg_customer_vr_employee_answer_experience.registration_date as dateRealization",
            "employee_document_type.item AS documentType",
            "wg_employee.documentNumber",
            "wg_employee.fullName",
            "experience_vr.item AS experience",
            "experience_scene.item AS scene",
            "wg_customer_vr_employee_question_scene.description AS indicator",
            "experience_scene_application.item AS value",
            "wg_customer_vr_employee_answer_scene.observation",
            "wg_customer_vr_employee.customer_employee_id AS customerEmployeeId",
            "wg_customer_vr_employee.customer_id AS customerId",
            DB::raw("DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS selectedYear"),
            "wg_customer_vr_employee.average",
            DB::raw("IF(wg_customer_vr_employee.is_active=1,'En Progreso',IF(wg_customer_vr_employee.is_active=0,'Anulado','Finalizado')) as isActive")
        );

        $query = $this->prepareQuery($query->toSql())
            ->mergeBindings($query);

        $this->applyWhere($query, $criteria);

        $heading = [
            "Fecha registro" => "registrationDate",
            "Fecha Realización" => "dateRealization",
            "Tipo de identificación" => "documentType",
            "Número de identificación" => "documentNumber",
            "Nombre completo" => "fullName",
            "Experiencia" => "experience",
            "Escena" => "scene",
            "Indicador" => "indicator",
            "Valoración" => "value",
            "Justificación" => "observation"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function exportExcelIndicators($criteria)
    {

        $query = DB::table('wg_customer_vr_employee_experience_indicators');
        $query->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.experience_code', '=', 'experience_vr.value');
        })->join(DB::raw(SystemParameter::getRelationTable('experience_scene')), function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.experience_scene_code', '=', 'experience_scene.value');
        })->join("wg_customer_vr_employee_question_scene", function ($join) {
            $join->on('wg_customer_vr_employee_experience_indicators.question_id', '=', 'wg_customer_vr_employee_question_scene.id');
            $join->on('wg_customer_vr_employee_experience_indicators.experience_scene_code', '=', 'wg_customer_vr_employee_question_scene.experience_scene_code');
        })
            ->groupBy("customer_id", "period", "experience_vr.item", "experience_scene.item", "wg_customer_vr_employee_question_scene.description");

        $query->select(
            "experience_vr.item AS experience",
            "experience_scene.item AS scene",
            "wg_customer_vr_employee_question_scene.description as metric",
            DB::raw("ROUND((SUM(wg_customer_vr_employee_experience_indicators.positiveAnswers) / SUM(wg_customer_vr_employee_experience_indicators.questions))*100,0) as percentage")
        );


        $query->where("wg_customer_vr_employee_experience_indicators.customer_id", $criteria->customerId)
            ->when(!empty($criteria->selectedExperience), function ($query) use ($criteria) {
                $query->where('wg_customer_vr_employee_experience_indicators.experience_code', $criteria->selectedExperience->value);
            })
            ->when(!empty($criteria->selectedRangeDates), function ($query) use ($criteria) {
                $query->whereBetween('wg_customer_vr_employee_experience_indicators.registrationDate', [$criteria->selectedRangeDates->startDate, $criteria->selectedRangeDates->endDate]);
            });

        if (!empty($criteria->selectedYear)) {
            $query->where("period", $criteria->selectedYear);
        } else {
            return ExportHelper::headings([], []);
        }

        $heading = [
            "Experiencia" => "experience",
            "Escena" => "scene",
            "Métrica" => "metric",
            "Porcentaje de respuestas SI" => "percentage",
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function makeExperienceList($customerId)
    {
        $data = [];
        $experienceClient = CustomerParameter::whereCustomerId($customerId)
            ->join("system_parameters", function ($join) {
                $join->on("wg_customer_parameter.item", "=", "system_parameters.id");
            })
            ->where("wg_customer_parameter.namespace", "wgroup")
            ->where("wg_customer_parameter.group", "experienceVR")
            ->select("system_parameters.value")
            ->get();

        if (count($experienceClient)) {

            $query = DB::table(DB::raw(SystemParameter::getRelationTable('experience_vr')))
                ->join(DB::raw(SystemParameter::getRelationTable('experience_scene')), function ($join) {
                    $join->on('experience_vr.value', '=', 'experience_scene.code');
                })
                ->join("wg_customer_vr_employee_question_scene", function ($join) {
                    $join->on('experience_scene.value', '=', 'wg_customer_vr_employee_question_scene.experience_scene_code');
                })
                ->whereIn("experience_vr.value", $experienceClient->pluck("value")->all())
                ->select(
                    'experience_vr.item as experience',
                    'experience_scene.item as scene',
                    'wg_customer_vr_employee_question_scene.description as question'
                )
                ->orderBy("experience_vr.value")
                ->get();

            foreach ($query as $row) {
                $data["experience"][] = $row->experience;
                $data["scene"][] = $row->scene;
                $data["question"][] = $row->question;
            }

            if (!empty($data["experience"])) {
                $data["experience"] = array_unique($data["experience"]);
            }
            if (!empty($data["scene"])) {
                $data["scene"] = array_unique($data["scene"]);
            }
            if (!empty($data["question"])) {
                $data["question"] = array_unique($data["question"]);
            }
        }

        return $data;
    }


    public function consolidate($customerId)
    {

        $currentPeriod = Carbon::parse("2023-02-13");
        //Current year
        //$currentPeriod = Carbon::now();

        DB::table("wg_customer_vr_employee_experience_indicators")
            ->where("customer_id", $customerId)
            ->where("period", $currentPeriod->year)
            ->delete();

        for ($i = 0; $i <= 11; $i++) {
            $date = $currentPeriod->firstOfYear();
            if ($i > 0) {
                $date = $date->addMonths($i);
            }


            DB::statement(
                " INSERT INTO wg_customer_vr_employee_experience_indicators (
                customer_id, experience_code, experience_scene_code, question_id, questions, answers, positiveAnswers,  participants, `period`, monthNumber, registrationDate)

                SELECT
                    wg_customer_vr_employee.customer_id,
                    wg_customer_vr_employee_experience.experience_code,
                    wg_customer_vr_employee_experience.experience_scene_code,
                    wg_customer_vr_employee_question_scene.id AS question_id,
                    COUNT(wg_customer_vr_employee_answer_scene.id) AS questions,
                    COUNT(wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id) AS answers,
                    SUM(IF(wg_customer_vr_employee_answer_scene.value = 'SI', 1, 0)) AS positiveAnswers,
                    participants.participants,
                    DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y') AS `period`,
                    DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%m') AS monthNumber,
                    wg_customer_vr_employee_answer_experience.registration_date
                FROM
                    wg_customer_vr_employee
                INNER JOIN wg_customer_vr_employee_experience ON
                    wg_customer_vr_employee.id = wg_customer_vr_employee_experience.customer_vr_employee_id
                INNER JOIN wg_customer_vr_employee_question_scene ON
                    wg_customer_vr_employee_experience.experience_scene_code = wg_customer_vr_employee_question_scene.experience_scene_code
                INNER JOIN wg_customer_vr_employee_answer_experience ON
                    wg_customer_vr_employee.id = wg_customer_vr_employee_answer_experience.customer_vr_employee_id
                LEFT JOIN wg_customer_vr_employee_answer_scene ON
                    wg_customer_vr_employee_question_scene.id = wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id
                    AND wg_customer_vr_employee_answer_experience.id = wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id
                LEFT JOIN (
                    SELECT p.experience_code, COUNT(*) AS participants
                    FROM
                    (
                        SELECT
                            wg_customer_vr_employee.customer_id,
                            wg_customer_vr_employee_experience.experience_code,
                            COUNT(wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id) AS answers,
                            wg_customer_vr_employee.id,
                            wg_customer_vr_employee.customer_employee_id
                        FROM
                            wg_customer_vr_employee
                        INNER JOIN wg_customer_vr_employee_experience ON
                            wg_customer_vr_employee.id = wg_customer_vr_employee_experience.customer_vr_employee_id
                        INNER JOIN wg_customer_vr_employee_question_scene ON
                            wg_customer_vr_employee_experience.experience_scene_code = wg_customer_vr_employee_question_scene.experience_scene_code
                        INNER JOIN wg_customer_vr_employee_answer_experience ON
                            wg_customer_vr_employee.id = wg_customer_vr_employee_answer_experience.customer_vr_employee_id
                        join wg_customer_vr_employee_answer_scene ON
                            wg_customer_vr_employee_question_scene.id = wg_customer_vr_employee_answer_scene.customer_vr_employee_question_scene_id
                            AND wg_customer_vr_employee_answer_experience.id = wg_customer_vr_employee_answer_scene.customer_vr_employ_answer_experience_id
                        WHERE
                            wg_customer_vr_employee.customer_id = ?
                            AND wg_customer_vr_employee_answer_experience.registration_date between ? AND ?
                        GROUP BY
                            wg_customer_vr_employee.customer_employee_id,
                            wg_customer_vr_employee_experience.experience_code,
                            wg_customer_vr_employee_answer_experience.registration_date
                    ) p
                    GROUP BY p.experience_code
                ) participants ON wg_customer_vr_employee_experience.experience_code = participants.experience_code

                WHERE
                    wg_customer_vr_employee.customer_id = ?
                    AND wg_customer_vr_employee_answer_experience.registration_date between ? AND ?
                GROUP BY
                    wg_customer_vr_employee_experience.experience_code,
                    wg_customer_vr_employee_experience.experience_scene_code,
                    wg_customer_vr_employee_question_scene.id,
                    DATE_FORMAT(wg_customer_vr_employee_answer_experience.registration_date, '%Y%m')
                HAVING COUNT(wg_customer_vr_employee_answer_scene.id) > 0 ;",
                [
                    $customerId,
                    $date->firstOfMonth()->toDateString(),
                    $date->lastOfMonth()->toDateString(),
                    $customerId,
                    $date->firstOfMonth()->toDateString(),
                    $date->lastOfMonth()->toDateString()
                ]
            );
        }

        $genres = DB::select("SELECT
            p.gender, COUNT(*) AS total
            FROM
            (
                SELECT
                    wg_customer_vr_employee.customer_id, wg_employee.gender
                FROM
                    wg_customer_vr_employee
                INNER JOIN wg_customer_vr_employee_answer_experience ON
                    wg_customer_vr_employee.id = wg_customer_vr_employee_answer_experience.customer_vr_employee_id
                INNER JOIN wg_customer_employee ON
                    wg_customer_employee.id = wg_customer_vr_employee.customer_employee_id
                INNER JOIN wg_employee ON
                    wg_employee.id = wg_customer_employee.employee_id
                WHERE
                    wg_customer_vr_employee.customer_id = ?
                    AND wg_customer_vr_employee_answer_experience.registration_date between ? AND ?
                GROUP BY
                    wg_customer_vr_employee.customer_employee_id , wg_employee.gender
            ) p
            GROUP BY p.gender
            ORDER BY p.gender;
        ", [$customerId, $currentPeriod->firstOfYear()->toDateString(), $currentPeriod->lastOfYear()->toDateString()]);

        if ($genres) {
            foreach ($genres as $genre) {
                switch ($genre->gender) {
                    case 'F':
                        DB::table("wg_customer_vr_employee_experience_indicators")
                            ->where("customer_id", $customerId)
                            ->where("period", $currentPeriod->year)
                            ->update(["female" => $genre->total]);
                        break;
                    case 'M':
                        DB::table("wg_customer_vr_employee_experience_indicators")
                            ->where("customer_id", $customerId)
                            ->where("period", $currentPeriod->year)
                            ->update(["male" => $genre->total]);
                        break;
                }
            }
        }
    }

    public function getExportDataCertificate($criteria)
    {
        $storagePath = str_replace("\\", "/", CmsHelper::getStorageDirectory(''));

        $customerId = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        $period = CriteriaHelper::getMandatoryFilter($criteria, 'period');

        $newCriteria = new stdClass;
        $newCriteria->customerId = $customerId ? $customerId->value : null;
        $newCriteria->period = $period ? $period->value : null;

        $uids = $this->getUidsVrEmployeeCertificate($newCriteria);

        if (count($uids) > 0) {
            $uniqueId = str_replace('-', '_', $this->guidv4());
            $temporaryTableName = "vr_certificates_{$uniqueId}";
            DB::statement("DROP TEMPORARY TABLE IF EXISTS {$temporaryTableName}");
            DB::statement("CREATE TEMPORARY TABLE IF NOT EXISTS {$temporaryTableName} (id INT NOT NULL,PRIMARY KEY (`id`))");
            $temporaryTablevalues = array_map(function ($id) {
                return "({$id})";
            }, $uids);
            $temporaryTableData = implode(',', $temporaryTablevalues);
            DB::statement("INSERT INTO {$temporaryTableName} VALUES {$temporaryTableData}");

            $className = CustomerVrEmployeeModel::class;

            $query = DB::table("wg_customer_vr_employee")
                ->join("wg_customer_employee", function ($join) {
                    $join->on('wg_customer_employee.id', '=', 'wg_customer_vr_employee.customer_employee_id');
                })
                ->join("wg_employee", function ($join) {
                    $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
                })
                ->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                    $join->on('employee_document_type.value', '=', 'wg_customer_vr_employee.document_type');
                })
                ->join($temporaryTableName, function ($join) use ($temporaryTableName) {
                    $join->on("$temporaryTableName.id", '=', 'wg_customer_vr_employee.id');
                })
                ->join('system_files', function ($join) use ($className) {
                    $join->on('wg_customer_vr_employee.id', '=', 'system_files.attachment_id');
                    $join->where("system_files.attachment_type", '=', $className);
                    $join->whereRaw("system_files.field = 'document'");
                })
                ->select(
                    "wg_customer_vr_employee.id",
                    "employee_document_type.item AS documentType",
                    "wg_employee.documentNumber",
                    "wg_employee.fullName",
                    DB::raw("IF (
                    system_files.disk_name IS NOT NULL,
                    CONCAT_WS(\"/\",'{$storagePath}',
                            SUBSTR(system_files.disk_name, 1, 3),
                            SUBSTR(system_files.disk_name, 4, 3),
                            SUBSTR(system_files.disk_name, 7, 3),
                            system_files.disk_name
                        ),
                        NULL
                    ) as fullPath"),
                    DB::raw("IF (
                            system_files.disk_name IS NOT NULL,
                            CONCAT_WS(\"/\", wg_employee.documentNumber, SUBSTR(system_files.disk_name, 7, 4), system_files.file_name),
                            NULL
                        ) as filename"),
                    'wg_customer_vr_employee.customer_id AS customerId'
                );


            $query = $this->prepareQuery($query->toSql())
                ->mergeBindings($query);

            $this->applyWhere($query, $criteria, ['period']);

            $data = $query->get();

            $heading = [
                "TIPO IDENTIFICACIÓN" => "documentType",
                "NUMERO IDENTIFICACIÓN" => "documentNumber",
                "NOMBRE COMPLETO" => "fullName",
                "UBICACIÓN / NOMBRE CERTIFICADO" => "filename",
            ];

            $zipContent = [];

            $documents = \System\Models\File::join($temporaryTableName, "$temporaryTableName.id", "=", "system_files.attachment_id")
                ->where('attachment_type', $className)
                ->where('field', 'document')
                ->get();

            if ($documents != null && $documents->count() > 0) {
                foreach ($data as $value) {
                    if (($document = $documents->firstWhere('id', $value->id))) {
                        $zipContent[] = [
                            'filename' => $value->filename,
                            'fileContents' => $document
                        ];
                    }
                }
            }

            return [
                'excel' => ExportHelper::headings($data, $heading),
                'zip' => $zipContent,
                'uids' => $uids
            ];
        } else {
            return null;
        }
    }

    public function getUidsVrEmployeeCertificate($crirteria)
    {
        $query = DB::table("wg_customer_vr_employee");
        $query
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_vr_employee.customer_employee_id');
            })
            ->join("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('employee_document_type')), function ($join) {
                $join->on('employee_document_type.value', '=', 'wg_customer_vr_employee.document_type');
            })
            ->join("wg_customer_vr_employee_answer_experience", function ($join) {
                $join->on('wg_customer_vr_employee.id', '=', 'wg_customer_vr_employee_answer_experience.customer_vr_employee_id');
            })
            ->where('wg_customer_vr_employee.customer_id', $crirteria->customerId)
            ->whereYear('wg_customer_vr_employee_answer_experience.registration_date', $crirteria->period)
            ->where('wg_customer_vr_employee.is_active', 2)
            ->select(
                "wg_customer_vr_employee.id"
            );

        return $query->get()->map(function ($item) {
            return $item->id;
        })->toArray();
    }

    private function guidv4($data = null)
    {
        // Generate 16 bytes (128 bits) of random data or use the data passed into the function.
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        // Set version to 0100
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Set bits 6-7 to 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        // Output the 36 character UUID.
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    public function getReportPdfData($criteria)
    {
        $customer = $this->getCustomerPdfReportData($criteria);
        $participantChart = $this->getPieChartParticipant($criteria);
        $participantTable = $this->getTableParticipant($criteria);
        $experiencesWithScenes = $this->getAllExperiencesWithScenes($criteria);
        $employeeExperiences = $this->getAllEmployeeExperiences($criteria);
        $chartBarQuestionVsResponses = $this->getChartBarQuestionVsResponses($criteria);
        $generalObservations = $this->getAllGeneralObservations($criteria);

        $report['date'] = Carbon::now('America/Bogota')->format('d/m/Y');
        $report['period'] = $criteria->selectedYear;
        $report['customer'] = $customer;
        $report['participantTable'] = $participantTable;
        $report['experiencesWithScenes'] = $experiencesWithScenes;
        $report['chartBarQuestionVsResponses'] = $chartBarQuestionVsResponses;
        $report['employeeExperiences'] = $employeeExperiences;
        $report['generalObservations'] = $generalObservations;
        $report['themeUrl'] = CmsHelper::getThemeUrl();
        $report['themePath'] = CmsHelper::getThemePath();
        $report['footerInfo'] = (new SignatureIndicatorVrRepository)->parseModelWithRelations() ;

        $data = array_merge($report, $participantChart);

        return $data;
    }

    private function getPieChartParticipant($criteria)
    {
        $query = DB::table('wg_customer_vr_employee_experience_indicators')
            ->where("customer_id", $criteria->customerId)
            ->where("period", $criteria->selectedYear)
            // ->when(!empty($criteria->selectedExperience), function ($query) use ($criteria) {
            //     $query->where('wg_customer_vr_employee_experience_indicators.experience_code', $criteria->selectedExperience->value);
            // })
            // ->when(!empty($criteria->selectedRangeDates), function ($query) use ($criteria) {
            //     $query->whereBetween('wg_customer_vr_employee_experience_indicators.registrationDate', [$criteria->selectedRangeDates->startDate, $criteria->selectedRangeDates->endDate]);
            // })
            ;

        $data = $query->select("female", "male")->first();
        $female = $data ? (float)$data->female : 0;
        $male = $data ? (float)$data->male : 0;
        $total = $female + $male;

        $chartData = [
            ["Mujeres ($female)",  $female],
            ["Hombres ($male)",  $male],
        ];

        array_unshift($chartData, ['Label', 'Value']);

        return [
            "participantChart" => [
                "data" => json_encode($chartData),
                "total" => $total
            ]
        ];
    }

    private function getChartBarQuestionVsResponses($criteria)
    {
        $period = Carbon::create($criteria->selectedYear, 1, 1);

        $periodStart = $period->firstOfYear()->toDateString();
        $periodEnd = $period->lastOfYear()->toDateString();

        // if (empty($criteria->selectedExperience)) {
        //     $periodStart = $period->firstOfYear()->toDateString();
        //     $periodEnd = $period->lastOfYear()->toDateString();
        // } else {
        //     $periodStart = $criteria->selectedExperience->startDate;
        //     $periodEnd = $criteria->selectedExperience->endDate;
        // }


        $subQuery = DB::table('wg_customer_vr_satisfactions_responses as r')
            ->leftJoin(DB::raw(CustomerParameter::getRelationTable('experienceVR', 'cp')), function ($join) {
                $join->on('cp.customer_id', 'r.customer_id');
                $join->on('cp.item', '=', 'r.experience');
            })
            ->join('wg_customer_vr_satisfactions_questions as q', 'q.id', '=', 'r.question_id')
            ->join('wg_customer_vr_satisfactions_answers_types as ans', 'ans.code', '=', 'q.answer_type')
            ->where('r.customer_id', $criteria->customerId)
            ->whereBetween("r.date_register", [$periodStart, $periodEnd])
            // ->when(!empty($criteria->selectedExperience), function ($query) use ($criteria) {
            //     $query->where('cp.value', $criteria->selectedExperience);
            // })
            ->whereRaw("ans.isActive = 1")
            ->groupBy('r.experience', 'q.label', 'ans.id')
            ->orderBy('ans.order')
            ->select(
                'cp.value as experience',
                "q.label",
                'ans.answer',
                DB::raw("COUNT(IF(r.answer_id = ans.id, 1, null)) AS count"),
                'ans.color',
                'q.chart_type as chartType'
            );

        $data = $subQuery->get();

        $groupedData = $data->groupBy(['experience', 'label'])
            ->each(function ($experiences) {

                // estructurar la data para pasarla al chart, convirtiendo los registros en columnas
                foreach ($experiences as $experience) {
                    $experience->valueColumns = [];
                    $experience->charts = new \stdClass();

                    $experience->transformedData = new \stdClass();
                    $columns = [];

                    foreach ($experience as $question) {
                        if (!empty($columns[$question->label]) && in_array($question->answer, $columns[$question->label])) {
                            continue;
                        }
                        $columns[$question->label][] = $question->answer;
                        $experience->valueColumns[] = [
                            'label' => $question->answer,
                            'field' => $question->answer,
                            'color' => $question->color,
                            'value' => $question->count
                        ];


                        $column = $question->answer;

                        $temp = $experience->transformedData;
                        $temp->label = $question->label;
                        $temp->$column = $question->count;
                        $experience->transformedData = $temp;
                        $experience->chartType = $question->chartType;
                    }

                    if ($experience->chartType == 'line') {
                        $config = [
                            "labelColumn" => 'label',
                            "valueColumns" => $experience->valueColumns
                        ];
                        $experience->charts->label = $experience->transformedData->label;

                        $chart = array_map(function ($item) {
                            return [
                                $item['label'], $item['value'], $item['color']
                            ];
                        }, $experience->valueColumns);

                        array_unshift($chart, ['Label', 'Value', ['role' => 'style']]);

                        $temp = new \stdClass();
                        $temp->label = $experience->transformedData->label;
                        $temp->data =  json_encode($chart);
                        $experience->charts->line = $temp;
                    } else if ($experience->chartType == 'pie') {
                        $chart = [
                            [
                                "SI ({$experience->transformedData->SI})",
                                $experience->transformedData->SI
                            ],
                            [
                                "NO ({$experience->transformedData->NO})",
                                $experience->transformedData->NO
                            ]
                        ];

                        array_unshift($chart, ['Label', 'Value']);

                        $experience->charts->label = $experience->transformedData->label;

                        $temp = new \stdClass();
                        $temp->label = $experience->transformedData->label;
                        $temp->data =  json_encode($chart);
                        $experience->charts->pie = $temp;
                    }
                }
            });

        $result = [];
        foreach ($groupedData as $index => $groupedDatum) {
            $temp = new \stdClass();
            $temp->experience = $index;
            $temp->charts = new \stdClass();

            foreach ($groupedDatum as $question) {
                if (!empty($question->charts->line)) {
                    $temp->charts->line[] = $question->charts->line;
                }

                if (!empty($question->charts->pie)) {
                    $temp->charts->pie[] = $question->charts->pie;
                }
            }

            if (!isset($temp->charts->pie)) {
                $temp->charts->pie = [];
            }

            if (!isset($temp->charts->line)) {
                $temp->charts->line = [];
            }

            $result[] = $temp;
        }

        return $result;
    }

    private function getAllEmployeeExperiences($criteria)
    {
        $query = DB::table('wg_customer_vr_employee_experiences_progress_log as p')
            ->join('wg_customer_vr_employee as vr', function ($join) {
                $join->on('vr.id', 'p.customer_vr_employee_id');
            })
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', 'vr.customer_employee_id');
            })
            ->join('wg_employee', function ($join) {
                $join->on('wg_employee.id', 'wg_customer_employee.employee_id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('experience_vr')), function ($join) {
                $join->on('p.experience_code', '=', 'experience_vr.value');
            })
            ->select(
                "p.experience_code as experienceCode",
                "experience_vr.item AS experience",
                DB::raw("DATE_FORMAT(p.created_at, '%Y-%m-%d') AS date"),
                "p.percent",
                "vr.customer_employee_id AS customerEmployeeId",
                "wg_employee.fullName",
                DB::raw("YEAR(p.created_at) as year")
            )
            ->where('wg_customer_employee.customer_id', $criteria->customerId)
            ->whereYear('p.created_at', $criteria->selectedYear)
            // ->when(!empty($criteria->selectedExperience), function ($query) use ($criteria) {
            //     $query->where('p.experience_code', $criteria->selectedExperience);
            // })
            // ->when(!empty($criteria->selectedDateRange), function ($query) use ($criteria) {
            //     $query->whereBetween('p.created_at', [$criteria->selectedDateRange->startDate, $criteria->selectedDateRange->endDate]);
            // })
            ->distinct()
            ->orderBy('experience_vr.item')
            ->orderBy('wg_employee.fullName');

        $experiences = $query->get()->groupBy('experienceCode');

        $result = $experiences->map(function ($items, $key) {
            $experience = new \stdClass();
            $experience->title = $items->first()->experience;

            $data = $items->filter(function ($item) {
                return $item->experienceCode != null;
            })->map(function ($item, $key) {
                $row = new \stdClass();
                $row->employee = $item->fullName;
                $row->percent = $item->percent . '%';
                return $row;
            });

            $experience->data = collect($data)->unique()->all();

            return $experience;
        });

        return $result;
    }

    private function getAllExperiencesWithScenes($criteria)
    {
        $newCriteria = new stdClass;
        $newCriteria->customerId = $criteria->customerId;
        $newCriteria->selectedYear = new stdClass;
        $newCriteria->selectedYear->value = $criteria->selectedYear;
        $newCriteria->selectedDateRange = $criteria->selectedDateRange;
        $newCriteria->selectedExperience = new stdClass;
        $newCriteria->selectedExperience->value = $criteria->selectedExperience;

        return (new ExperienceAnswerService)->getAllExperiencesWithScenes($newCriteria);
    }

    private function getTableParticipant($criteria)
    {
        $period = Carbon::create($criteria->selectedYear, 1, 1);

        $periodStart = $period->firstOfYear()->toDateString();
        $periodEnd = $period->lastOfYear()->toDateString();

        // if (empty($criteria->selectedExperience)) {
        //     $periodStart = $period->firstOfYear()->toDateString();
        //     $periodEnd = $period->lastOfYear()->toDateString();
        // } else {
        //     $periodStart = $criteria->selectedExperience->startDate;
        //     $periodEnd = $criteria->selectedExperience->endDate;
        // }

        $query = DB::table('wg_customer_vr_employee')
            ->join('wg_customer_vr_employee_answer_experience', function ($join) {
                $join->on('wg_customer_vr_employee_answer_experience.customer_vr_employee_id', '=', 'wg_customer_vr_employee.id');
            })
            ->join('wg_customer_employee', function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_vr_employee.customer_employee_id');
            })
            ->select(
                DB::raw("COUNT(*) AS qty"),
                "wg_customer_vr_employee.customer_id",
                "wg_customer_vr_employee_answer_experience.registration_date"
            )
            ->where("wg_customer_vr_employee.customer_id", $criteria->customerId)
            ->whereBetween("wg_customer_vr_employee_answer_experience.registration_date", [$periodStart, $periodEnd])
            // ->when(!empty($criteria->selectedExperience), function ($query) use ($criteria) {
            //     $query->where('wg_customer_vr_employee_answer_experience.experience_code', $criteria->selectedExperience);
            // })
            ->groupBy(
                'wg_customer_vr_employee.customer_employee_id',
                'wg_customer_vr_employee_answer_experience.registration_date'
            );

        return DB::table(DB::raw("({$query->toSql()}) as consolidate"))
            ->mergeBindings($query)
            ->select(
                DB::raw("SUM(consolidate.qty) AS total"),
                "consolidate.registration_date"
            )
            ->orderBy('consolidate.registration_date')
            ->groupBy('consolidate.registration_date')
            ->get();
    }

    private function getCustomerPdfReportData($criteria)
    {
        $customer = DB::table('wg_customers')
            ->leftjoin(DB::raw(CustomerModel::getRelationInfoDetail('customer_info_detail')), function ($join) {
                $join->on('customer_info_detail.entityId', '=', 'wg_customers.id');
            })
            ->leftjoin('wg_contact', function ($join) {
                $join->on('wg_contact.customer_id', '=', 'wg_customers.id');
                $join->where('wg_contact.role', '=', 'rsg');
            })
            ->leftjoin('rainlab_user_countries', function ($join) {
                $join->on('rainlab_user_countries.id', '=', 'wg_customers.country_id');
            })
            ->leftjoin('rainlab_user_states', function ($join) {
                $join->on('rainlab_user_states.id', '=', 'wg_customers.state_id');
            })
            ->leftjoin('wg_towns', function ($join) {
                $join->on('wg_towns.id', '=', 'wg_customers.city_id');
            })
            ->select(
                'wg_customers.businessName',
                'wg_customers.documentNumber',
                'rainlab_user_countries.name AS country',
                'rainlab_user_states.name AS state',
                'wg_towns.name AS city',
                'customer_info_detail.address',
                'customer_info_detail.telephone AS phone',
                DB::raw("CONCAT(wg_contact.name, ' ', wg_contact.firstName, ' ', wg_contact.lastName) AS sgsst")
            )
            ->where('wg_customers.id', $criteria->customerId)
            ->groupBy('wg_customers.id')
            ->first();

        return $customer;
    }

    private function getAllGeneralObservations($criteria)
    {
        return DB::table('wg_customer_vr_general_observation')
            ->leftjoin("users", function ($join) {
                $join->on('users.id', '=', 'wg_customer_vr_general_observation.created_by');
            })
            ->select(
                "wg_customer_vr_general_observation.id",
                DB::raw("DATE(wg_customer_vr_general_observation.registration_date) AS registration_date"),
                "wg_customer_vr_general_observation.observation",
                DB::raw("CONCAT(users.name, ' ', users.surname) AS createdByUser"),
                "wg_customer_vr_general_observation.customer_id"
            )
            ->where('wg_customer_vr_general_observation.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_vr_general_observation.registration_date', $criteria->selectedYear)
            // ->when(!empty($criteria->selectedDateRange), function ($query) use ($criteria) {
            //     $query->whereBetween('wg_customer_vr_general_observation.registration_date', [$criteria->selectedDateRange->startDate, $criteria->selectedDateRange->endDate]);
            // })
            ->get();
    }
}
