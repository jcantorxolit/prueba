<?php

namespace AdeN\Api\Modules\Customer\Employee\Indicators;

use Illuminate\Support\Facades\DB;

use AdeN\Api\Classes\BaseService;
use October\Rain\Database\Builder;

use Wgroup\SystemParameter\SystemParameter;

class CustomerEmployeeDemographicIndicatorService extends BaseService
{

    private $customerId;

    private $workplace;


    public function setCustomerId(int $customerId){
        $this->customerId = $customerId;
    }

    public function setWorkplace($workplace){
        $this->workplace = $workplace;
    }


    public function consolidateStatusEmployees(int $customerId) {

        DB::table('wg_customer_employee_status_consolidate')
            ->where('customer_id', $customerId)
            ->whereYear('period', now()->year)
            ->whereMonth('period', now()->month)
            ->delete();

        DB::statement("
            INSERT IGNORE INTO wg_customer_employee_status_consolidate
              (customer_id, workplace_id, period, total, count_actives, count_inactives, count_autorized, count_not_autorized)
            SELECT ce.customer_id,
                   IF(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, NULL, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                   NOW() AS period,
                   COUNT(*) total,
                   COUNT(IF(ce.isActive = 1, ce.isActive, NULL)) AS count_actives,
                   COUNT(IF(ce.isActive = 0, ce.isActive, NULL)) AS count_inactives,
                   COUNT(IF(ce.isActive = 1 AND ce.isAuthorized = 1, 1, NULL)) AS count_autorized,
                   COUNT(IF(ce.isActive = 1 AND ce.isAuthorized <> 1, 1, NULL)) AS count_not_autorized
            FROM wg_customer_employee ce
            JOIN wg_employee e ON e.id = ce.employee_id
            WHERE ce.customer_id = $customerId
            GROUP BY customer_id, IF(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, NULL, CAST(TRIM(ce.workPlace) AS UNSIGNED))
        ");
    }


    public function consolidateSupportDocuments(int $customerId) {

        DB::table('wg_customer_employee_status_documents_consolidate')
            ->where('customer_id', $customerId)
            ->whereYear('period', now()->year)
            ->whereMonth('period', now()->month)
            ->delete();

        DB::statement("
            INSERT IGNORE INTO wg_customer_employee_status_documents_consolidate (
              customer_id, workplace_id, period, total, countActive, countAnnuled, countExpired, countApproved, countDenied,
              count_active_approved, count_active_denied_expired)
            SELECT
              ce.customer_id,
              IF(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, NULL, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
              NOW() AS period,
              COUNT(*) total,
              COUNT(IF(d.status = 1, 1, NULL)) AS countActives,
              COUNT(IF(d.status = 2, 1, NULL)) AS countAnnuled,
              COUNT(IF(d.status = 3, 1, NULL)) AS countExpired,
              COUNT(IF(d.status <> 2, isApprove, NULL)) AS countApproved,
              COUNT(IF(d.status <> 2, isDenied, NULL)) AS countDenied,
              COUNT(IF(d.status = 1 AND isApprove = 1, 1, NULL)) AS count_active_approved,
              COUNT(IF(d.status = 1 AND isDenied = 1, 1, NULL)) + COUNT(IF(d.status = 3, 1, NULL)) AS count_active_denied_expired
            FROM wg_customer_employee_document d
            join wg_customer_employee ce ON ce.id = d.customer_employee_id
            WHERE ce.customer_id = $customerId
            GROUP BY customer_id, IF(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, NULL, CAST(TRIM(ce.workPlace) AS UNSIGNED))
        ");
    }


    public function consolidateDemographic(int $customerId) {

        DB::table("wg_customer_employee_demographic_consolidate")
            ->where('customer_id', $customerId)
            ->delete();

        DB::statement("INSERT IGNORE INTO wg_customer_employee_demographic_consolidate (customer_id, workplace_id, label, value, total)
            select e.customer_id, wp.id AS workplace_id, label, value, total
            FROM (
              SELECT ce.customer_id,
                  if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                  'typeHousing' AS label,
                  CASE typehousing when '' then null else typehousing END AS value,
                  COUNT(*) total
              FROM wg_customer_employee ce
              JOIN wg_employee e ON e.id = ce.employee_id
              WHERE ce.customer_id = $customerId
                  AND e.typeHousing IS NOT NULL
              GROUP BY customer_id, workPlace, CASE typehousing when '' then null else typehousing END

              UNION

              SELECT ce.customer_id,
                if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                'antiquityCompany' AS label,
                CASE antiquityCompany when '' then null else antiquityCompany END AS value,
                COUNT(*) total
              FROM wg_customer_employee ce
              JOIN wg_employee e ON e.id = ce.employee_id
              WHERE ce.customer_id = $customerId
                  AND e.typeHousing IS NOT NULL
              GROUP BY customer_id, workPlace, CASE antiquityCompany when '' then null else antiquityCompany END

              UNION

              SELECT ce.customer_id,
                if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                'antiquityJob' AS label,
                CASE antiquityJob when '' then null else antiquityJob END AS value,
                COUNT(*) total
              FROM wg_customer_employee ce
              JOIN wg_employee e ON e.id = ce.employee_id
              WHERE ce.customer_id = $customerId
                  AND e.typeHousing IS NOT NULL
              GROUP BY customer_id, workPlace, CASE antiquityJob when '' then null else antiquityJob END

              UNION

              SELECT ce.customer_id,
                if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                'hasChildren' AS label,
                hasChildren AS value,
                COUNT(*) total
              FROM wg_customer_employee ce
              JOIN wg_employee e ON e.id = ce.employee_id
              WHERE ce.customer_id = $customerId
                  AND e.typeHousing IS NOT NULL
              GROUP BY customer_id, workPlace, hasChildren

              UNION

              SELECT ce.customer_id,
                if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                'stratum' AS label,
                CASE stratum when '' then null else stratum END AS value,
                COUNT(*) total
              FROM wg_customer_employee ce
              JOIN wg_employee e ON e.id = ce.employee_id
              WHERE ce.customer_id = $customerId
                  AND e.typeHousing IS NOT NULL
              GROUP BY customer_id, workPlace, CASE stratum when '' then null else stratum END

              UNION

              SELECT ce.customer_id,
                if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                'civilStatus' AS label,
                CASE civilStatus when '' then null else civilStatus END AS value,
                COUNT(*) total
              FROM wg_customer_employee ce
              JOIN wg_employee e ON e.id = ce.employee_id
              WHERE ce.customer_id = $customerId
                  AND e.typeHousing IS NOT NULL
              GROUP BY customer_id, workPlace, CASE civilStatus when '' then null else civilStatus END

              UNION

              SELECT ce.customer_id,
                if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                'scholarship' AS label,
                CASE scholarship when '' THEN null WHEN '0' then null else scholarship END AS value,
                COUNT(*) total
              FROM wg_customer_employee ce
              JOIN wg_employee e ON e.id = ce.employee_id
              WHERE ce.customer_id = $customerId
                  AND e.typeHousing IS NOT NULL
              GROUP BY customer_id, workPlace, CASE scholarship when '' THEN null WHEN '0' then null else scholarship END

              UNION

              SELECT ce.customer_id,
                  if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                  'gender' AS label,
                  CASE
                    WHEN gender NOT IN ('F', 'M') THEN NULL
                    ELSE gender END AS value,
                  COUNT(*) total
              FROM wg_customer_employee ce
              JOIN wg_employee e ON e.id = ce.employee_id
              WHERE ce.customer_id = $customerId
                  AND e.typeHousing IS NOT NULL
              GROUP BY customer_id, workPlace,
                CASE
                  WHEN gender NOT IN ('F', 'M') THEN NULL
                  ELSE gender END

            UNION

            SELECT ce.customer_id,
              if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
              'practiceSports' AS label,
              isPracticeSports AS value,
              COUNT(*) total
            FROM wg_customer_employee ce
            JOIN wg_employee e ON e.id = ce.employee_id
            WHERE ce.customer_id = $customerId
                AND e.typeHousing IS NOT NULL
            GROUP BY customer_id, workPlace, isPracticeSports

            UNION

            SELECT ce.customer_id,
              if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
              'drinkAlcoholic' AS label,
              isDrinkAlcoholic AS value,
              COUNT(*) total
            FROM wg_customer_employee ce
            JOIN wg_employee e ON e.id = ce.employee_id
            WHERE ce.customer_id = $customerId
                AND e.typeHousing IS NOT NULL
            GROUP BY customer_id, workPlace, isDrinkAlcoholic

            UNION

            SELECT ce.customer_id,
              if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
              'smokes' AS label,
              isSmokes AS value,
              COUNT(*) total
            FROM wg_customer_employee ce
            JOIN wg_employee e ON e.id = ce.employee_id
            WHERE ce.customer_id = $customerId
                AND e.typeHousing IS NOT NULL
            GROUP BY customer_id, workPlace, isSmokes

            UNION

            SELECT ce.customer_id,
              if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
              'diagnosedDisease' AS label,
              isDiagnosedDisease AS value,
              COUNT(*) total
            FROM wg_customer_employee ce
            JOIN wg_employee e ON e.id = ce.employee_id
            WHERE ce.customer_id = $customerId
                AND e.typeHousing IS NOT NULL
            GROUP BY customer_id, workPlace, isDiagnosedDisease

            UNION

            SELECT ce.customer_id,
              if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
              'workArea' AS label,
              workArea AS value,
              COUNT(*) total
            FROM wg_customer_employee ce
            JOIN wg_employee e ON e.id = ce.employee_id
            WHERE ce.customer_id = $customerId
                AND e.typeHousing IS NOT NULL
            GROUP BY customer_id, workPlace, workArea

            UNION
              
            SELECT customer_id,
              if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
              'workShift' AS label,
              work_shift AS value,
              COUNT(*) total
            from wg_customer_employee ce
            JOIN wg_employee e ON e.id = ce.employee_id
            WHERE ce.customer_id = $customerId
                AND e.typeHousing IS NOT NULL
            GROUP BY customer_id, workPlace, work_shift
            
            UNION

            SELECT customer_id, workplace, label, value, count(*) AS total
            FROM (
                 SELECT ce.customer_id,
                    if(CAST(TRIM(ce.workPlace) AS UNSIGNED) = 0, null, CAST(TRIM(ce.workPlace) AS UNSIGNED)) AS workplace,
                    'age' AS label,
                    TIMESTAMPDIFF(YEAR, birthdate, NOW()) AS age,
                    CASE
                        WHEN TIMESTAMPDIFF(YEAR, birthdate, now()) BETWEEN 1 AND 17 THEN '1-17'
                        WHEN TIMESTAMPDIFF(YEAR, birthdate, NOW()) BETWEEN 18 AND 25 THEN '18-25'
                        WHEN TIMESTAMPDIFF(YEAR, birthdate, NOW()) BETWEEN 26 AND 35 THEN '26-35'
                        WHEN TIMESTAMPDIFF(YEAR, birthdate, NOW()) BETWEEN 36 AND 45 THEN '36-45'
                        WHEN TIMESTAMPDIFF(YEAR, birthdate, NOW()) BETWEEN 46 AND 55 THEN '46-55'
                        WHEN TIMESTAMPDIFF(YEAR, birthdate, NOW()) > 55 THEN 'MÁS DE 55'
                        ELSE 'No Responde'
                    END AS value
                 FROM wg_customer_employee ce
                 JOIN wg_employee e ON e.id = ce.employee_id
                 WHERE ce.customer_id = $customerId
                   AND e.typeHousing IS NOT NULL
             ) AS d
            GROUP BY customer_id, workplace, value

        ) AS e
        LEFT JOIN wg_customer_config_workplace wp ON wp.id = e.workPlace and wp.customer_id = e.customer_id
        ");

    }


    public function getTypeHousingChartPie()
    {
        return $this->getCharPieSettingsWithParam('typeHousing','type_housing');
    }


    public function getAntiquityCompanyChartPie()
    {
        return $this->getCharPieSettingsWithParam('antiquityCompany','antiquity');
    }


    public function getAntiquityJobChartPie()
    {
        return $this->getCharPieSettingsWithParam('antiquityJob','antiquity');
    }


    public function getHasChildrenChartPie()
    {
        $workplace = $this->workplace;

        $data = DB::table('wg_customer_employee_demographic_consolidate as c')
            ->where('c.customer_id', $this->customerId)
            ->where('c.label', 'hasChildren')
            ->whereNotNull('c.value')
            ->when($workplace, function($query) use ($workplace) {
                $query->where('c.workplace_id', $workplace);
            })
            ->select(
                DB::raw("sum(if(value=1, total, null)) AS SI"),
                DB::raw("sum(if(value=0, total, null)) AS NO"),
                DB::raw("coalesce(round( (sum(if(value=1, total, null)) / sum(total)) * 100 , 2), 0) AS percentSi"),
                DB::raw("coalesce(round( (sum(if(value=0, total, null)) / sum(total)) * 100 , 2), 0) AS percentNo")
            )
            ->first();

        $chart = [
            [
                "label" => "{$data->percentSi}% Si",
                "value" => $data->SI,
            ],
            [
                "label" => "{$data->percentNo}% No",
                "value" => $data->NO,
            ]
        ];

        return $this->chart->getChartPie(json_decode(json_encode($chart)));
    }


    public function getGenderChartPie($label)
    {
        $workplace = $this->workplace;

        $data = DB::table('wg_customer_employee_demographic_consolidate as c')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('gender')), function ($join) {
                $join->on('gender.value', '=', 'c.value');
            })
            ->where('c.customer_id', $this->customerId)
            ->where('c.label', $label)
            ->whereNotNull('c.value')
            ->when($workplace, function($query) use ($workplace) {
                $query->where('c.workplace_id', $workplace);
            })
            ->groupBy('c.value')
            ->select(
                'gender.item as label',
                DB::raw('sum(total) AS value')
            )
            ->get();


        $total = 0;
        $data->each(function($item) use (&$total) {
            $total += floatval($item->value);
        });

        foreach ($data as $index => $item) {
            if (empty($item->label)) {
                unset($data[$index]);
            }

            $percent = round(($item->value / $total) * 100);
            $item->label = "{$percent}% $item->label";
        }

        return $this->chart->getChartPie($data);
    }


    public function getCharBarStratum() {
        return $this->getCharBarSettingsWithParam('stratum', 'Estrato', 'stratum');
    }



    public function getCharBarCivilStatus() {
        return $this->getCharBarSettingsWithParam('civilStatus', 'Estado Civil', 'civil_status');
    }


    public function getCharBarScholarship() {
        return $this->getCharBarSettingsWithParam('scholarship', 'Grado escolaridad', 'scholarship');
    }


    public function getCharBarAge() {
        $data = $this->getBaseQuery('age')
            ->addSelect('c.value as label')
            ->get();

        return $this->getCharBarSettingsDefault($data, 'Edad');
    }


    public function getCharBarPracticeSports() {
        return $this->getCharBarSettings('practiceSports', 'Practica Deporte');
    }


    public function getCharBarDrinkAlcoholic() {
        return $this->getCharBarSettings('drinkAlcoholic', 'Consume Bebidas Alcohólicas');
    }


    public function getCharBarSmokes() {
        return $this->getCharBarSettings('smokes', 'Fuma');
    }


    public function getCharBarDiagnosedDisease() {
        return $this->getCharBarSettings('diagnosedDisease', 'Diagnóstico De Enfermedades');
    }


    public function getCharBarWorkArea() {
        return $this->getCharBarSettingsWithParam('workArea', 'Área de Trabajo', 'work_area');
    }


    public function getCharBarWorkShift() {
        return $this->getCharBarSettingsWithParam('workShift', 'Turno de Trabajo', 'work_shifts');
    }


    /**
     * @return Builder
     */
    private function getBaseQuery(string $label, $param = null) {
        $workplace = $this->workplace;

        return DB::table('wg_customer_employee_demographic_consolidate as c')
            ->when($param, function($query) use ($param) {
                $query->leftjoin(DB::raw(SystemParameter::getRelationTable($param, 'sp')), function ($join) {
                    $join->on('sp.value', '=', 'c.value');
                });
            })
            ->where('c.customer_id', $this->customerId)
            ->where('c.label', $label)
            ->when($workplace, function($query) use ($workplace) {
                $query->where('c.workplace_id', $workplace);
            })
            ->groupBy('c.value')
            ->select(
                DB::raw('sum(total) AS value')
            );
    }


    private function getCharBarSettings($label, $title) {
        $data = $this->getBaseQuery($label)
            ->addSelect( DB::raw("CASE `c`.`value` WHEN '1' THEN 'SI' WHEN '0' THEN 'NO' ELSE 'No Responde' END AS `label`") )
            ->get();

        return $this->getCharBarSettingsDefault($data, $title);
    }


    private function getCharBarSettingsWithParam($label, $title, $param) {
        $data = $this->getBaseQuery($label, $param)
            ->addSelect( DB::raw("CASE WHEN c.value IS NULL THEN 'No Responde' ELSE sp.item END as label") )
            ->get();

        return $this->getCharBarSettingsDefault($data, $title);
    }


    private function getCharPieSettingsWithParam(string $label, $param)
    {
        $data = $this->getBaseQuery($label, $param)
            ->whereNotNull('c.value')
            ->select(
                DB::raw("sp.item as label"),
                DB::raw('sum(total) AS value')
            )
            ->get();

        $this->addPercentToLabel($data);
        return $this->chart->getChartPie($data);
    }


    private function getCharBarSettingsDefault($data, $title) {
        $this->addPercentToLabel($data);

        $config = array(
            "labelColumn" => [$title],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

}
