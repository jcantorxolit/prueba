<?php

namespace AdeN\Api\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\HttpHelper;

use AdeN\Api\Modules\Customer\AbsenteeismDisability\CustomerAbsenteeismDisabilityRepository;
use AdeN\Api\Modules\Customer\AbsenteeismIndicator\CustomerAbsenteeismIndicatorRepository;
use AdeN\Api\Modules\Customer\ConfigActivityHazard\CustomerConfigActivityHazardRepository;
use AdeN\Api\Modules\Customer\ConfigQuestionExpress\CustomerConfigQuestionExpressRepository;
use AdeN\Api\Modules\Customer\ContractDetail\CustomerContractDetailRepository;
use AdeN\Api\Modules\Customer\Covid\CustomerCovidRepository;
use AdeN\Api\Modules\Customer\Covid\DailyTemperature\CustomerCovidDailyTemperatureRepository;
use AdeN\Api\Modules\Customer\CustomerRepository;
use AdeN\Api\Modules\Customer\Diagnostic\CustomerDiagnosticRepository;
use AdeN\Api\Modules\Customer\Employee\Indicators\CustomerEmployeeDocumentIndicatorService;
use AdeN\Api\Modules\Customer\Employee\Indicators\CustomerEmployeeIndicatorRepository;
use AdeN\Api\Modules\Customer\Employee\Indicators\CustomerEmployeeIndicatorService;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\CustomerEvaluationMinimumStandard0312Repository;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandard\CustomerEvaluationMinimumStandardRepository;
use AdeN\Api\Modules\Customer\ImprovementPlan\CustomerImprovementPlanRepository;
use AdeN\Api\Modules\Customer\JobConditions\Indicator\IndicatorRepository;
use AdeN\Api\Modules\Customer\Management\CustomerManagementRepository;
use AdeN\Api\Modules\Customer\OccupationalInvestigationAl\CustomerOccupationalInvestigationRepository;
use AdeN\Api\Modules\Customer\OccupationalReportAl\CustomerOccupationalReportRepository;
use AdeN\Api\Modules\Customer\OccupationalReportIncident\CustomerOccupationalReportIncidentRepository;
use AdeN\Api\Modules\Customer\RoadSafety40595\CustomerRoadSafety40595Repository;
use AdeN\Api\Modules\Customer\RoadSafety\CustomerRoadSafetyRepository;
use AdeN\Api\Modules\Customer\UnsafeAct\CustomerUnsafeActRepository;
use AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\ExperienceAnswerRepository;
use AdeN\Api\Modules\Customer\VrEmployee\Satisfactionindicators\SatisfactionIndicatorRepository;
use AdeN\Api\Modules\Dashboard\Commercial\CommercialDashboardRepository;
use AdeN\Api\Modules\Dashboard\TopManagement\TopManagementRepository;
use AdeN\Api\Modules\Project\CustomerProjectRepository;

use Exception;
use Log;
use Response;
use Carbon\Carbon;
use Wgroup\SystemParameter\SystemParameter;
use DB;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package Presupuesto\api
 * @author David Blandon
 */
class ChartController extends BaseController
{
    private $repository;

    public function __construct()
    {
        $this->request = app('Input');
        parent::__construct();
    }

    public function index()
    {
        $input = $this->request->get("data", "");
        $base64 = $this->request->get("base64", "1");

        $base64 = $base64 == '1' ? true : false;

        try {
            $entities = HttpHelper::parse($input, $base64);
            if ($entities != null) {
                foreach ($entities as $entity) {
                    switch ($entity->name) {

                        case "chart_bar_options":
                            $result['chartBarOptions'] = $this->getChartBarOptions();
                            break;

                        case "chart_bar_with_scales_options":
                            $result['chartBarOptionsWithScales'] = $this->getChartBarOptionsWithScales();
                            break;

                        case "chart_line_options":
                            $result['chartLineOptions'] = $this->getChartLineOptions();
                            break;

                        case "chart_doughnut_options":
                            $result['chartDoughnutOptions'] = $this->getChartDoughnutOptions();
                            break;

                        case "chart_pie_options":
                            $result['chartPieOptions'] = $this->getChartPieOptions();
                            break;

                        case "chart_radar_options":
                            $result['chartRadarOptions'] = $this->getChartRadarOptions();
                            break;

                        case 'customer_diagnostic':
                            $repository = new CustomerDiagnosticRepository();
                            $result['customerDiagnosticProgram'] = $repository->getChartBar($entity->criteria);
                            $result['customerDiagnosticProgress'] = $repository->getChartPie($entity->criteria);
                            $result['customerDiagnosticAverage'] = ($stats = $repository->getStats($entity->criteria)) ? floatval($stats->average) : 0;
                            break;

                        case 'customer_management':
                            $repository = new CustomerManagementRepository();
                            $result['customerManagementProgram'] = $repository->getChartBar($entity->criteria);
                            $result['customerManagementProgress'] = $repository->getChartPie($entity->criteria);
                            $result['customerManagementAverage'] = ($stats = $repository->getStats($entity->criteria)) ? floatval($stats->average) : 0;
                            break;

                        case 'customer_management_indicator':
                            $repository = new CustomerManagementRepository();

                            $result['customerManagementAverageProgram'] = $repository->getAvegareProgramChartBar($entity->criteria);
                            $result['customerManagemenImprovementPlanStatus'] = $repository->getImprovementPlanStatusChartBar($entity->criteria);
                            $result['customerManagemenValoration'] = $repository->getValorationChartBar($entity->criteria);

                            break;

                        case 'customer_project_summary':
                            $repository = new CustomerProjectRepository();
                            $result['projectSummary'] = $repository->getSummaryChartPie($entity->criteria);
                            break;

                        case 'customer_evaluation_minimum_standard':
                            $repository = new CustomerEvaluationMinimumStandardRepository();
                            $entity->criteria->evaluationMinimumStandardId = $entity->criteria->evaluationMinimumStandardId ?
                                $entity->criteria->evaluationMinimumStandardId :
                                $repository->getLastid($entity->criteria->customerId);

                            $result['customerEvaluationMinimumStandardCycle'] = $repository->getChartBar($entity->criteria);
                            $result['customerEvaluationMinimumStandardProgress'] = $repository->getChartPie($entity->criteria);
                            $result['customerEvaluationMinimumStandardAverage'] = ($stats = $repository->getStats($entity->criteria)) ? floatval($stats->total) : 0;
                            $result['customerEvaluationMinimumStandardId'] = $entity->criteria->evaluationMinimumStandardId;
                            break;

                            // DASHBOARD

                        case 'customer_evaluation_minimum_standard_dashboard':
                            $repository = new CustomerEvaluationMinimumStandardRepository();
                            $entity->criteria->evaluationMinimumStandardId = $entity->criteria->evaluationMinimumStandardId ?
                                $entity->criteria->evaluationMinimumStandardId :
                                $repository->getLastid($entity->criteria->customerId);

                            $result['customerEvaluationMinimumStandardCycle'] = $repository->getChartBar($entity->criteria);
                            $result['customerEvaluationMinimumStandardProgress'] = $repository->getChartPie($entity->criteria);
                            $result['customerEvaluationMinimumStandardAverage'] = ($stats = $repository->getStats($entity->criteria)) ? floatval($stats->total) : 0;
                            $result['customerEvaluationMinimumStandardId'] = $entity->criteria->evaluationMinimumStandardId;

                            $result['customerEvaluationMinimumStandardDashboardFirst'] = $repository->getChartFirst($entity->criteria);
                            $result['customerEvaluationMinimumStandardDashboardSecond'] = $repository->getChartSecond($entity->criteria);

                            break;

                            // END DASHBOARD

                        case 'customer_evaluation_minimum_standard_monthly':
                            $repository = new CustomerEvaluationMinimumStandardRepository();
                            $entity->criteria->evaluationMinimumStandardId = $entity->criteria->evaluationMinimumStandardId ?
                                $entity->criteria->evaluationMinimumStandardId :
                                $repository->getLastid($entity->criteria->customerId);

                            $result['customerEvaluationMinimumStandardMonthlyStatus'] = $repository->getChartStatus($entity->criteria);
                            $result['customerEvaluationMinimumStandardMonthlyAverage'] = $repository->getChartAverage($entity->criteria);
                            $result['customerEvaluationMinimumStandardMonthlyTotal'] = $repository->getChartTotal($entity->criteria);
                            $result['customerEvaluationMinimumStandardMonthlyAdvance'] = $repository->getChartAdvance($entity->criteria);
                            $result['customerEvaluationMinimumStandardId'] = $entity->criteria->evaluationMinimumStandardId;
                            break;

                        case 'customer_evaluation_minimum_standard_0312':
                            $repository = new CustomerEvaluationMinimumStandard0312Repository();
                            $entity->criteria->customerEvaluationMinimumStandardId = $entity->criteria->customerEvaluationMinimumStandardId ?
                                $entity->criteria->customerEvaluationMinimumStandardId :
                                $repository->getLastid($entity->criteria->customerId);

                            $result['customerEvaluationMinimumStandardProgress'] = $repository->getChartPie($entity->criteria);
                            $result['customerEvaluationMinimumStandardCycle'] = $repository->getChartBar($entity->criteria);
                            $result['customerEvaluationMinimumStandardAverage'] = ($stats = $repository->getStats($entity->criteria)) ? floatval($stats->total) : 0;
                            //$result['customerEvaluationMinimumStandardPdf'] = $repository->exportPdf($entity->criteria);
                            $result['customerEvaluationMinimumStandardId'] = $entity->criteria->customerEvaluationMinimumStandardId;
                            break;

                        case 'customer_road_safety_40595':
                            $repository = new CustomerRoadSafety40595Repository();
                            $entity->criteria->customerRoadSafetyId = $entity->criteria->customerRoadSafetyId ?
                                $entity->criteria->customerRoadSafetyId :
                                $repository->getLastid($entity->criteria->customerId);

                            $result['customerRoadSafetyProgress'] = $repository->getChartPie($entity->criteria);
                            $result['customerRoadSafetyCycle'] = $repository->getChartBar($entity->criteria);
                            $result['customerRoadSafetyAverage'] = ($stats = $repository->getStats($entity->criteria)) ? floatval($stats->total) : 0;
                            $result['customerRoadSafetyId'] = $entity->criteria->customerRoadSafetyId;
                            break;

                        case 'customer_road_safety_monthly_40595':
                            $repository = new CustomerRoadSafety40595Repository();
                            $entity->criteria->customerRoadSafetyId = $entity->criteria->customerRoadSafetyId ?
                                $entity->criteria->customerRoadSafetyId :
                                $repository->getLastid($entity->criteria->customerId);

                            //$result['customerRoadSafetyMonthlyStatus'] = $repository->getChartStatus($entity->criteria);
                            $result['customerRoadSafetyMonthlyAverage'] = $repository->getChartAverage($entity->criteria);
                            $result['customerRoadSafetyMonthlyTotal'] = $repository->getChartTotal($entity->criteria);
                            //$result['customerRoadSafetyMonthlyAdvance'] = $repository->getChartAdvance($entity->criteria);
                            $result['customerRoadSafetyId'] = $entity->criteria->customerRoadSafetyId;
                            break;

                        case 'customer_evaluation_minimum_standard_monthly_0312':
                            $repository = new CustomerEvaluationMinimumStandard0312Repository();
                            $entity->criteria->customerEvaluationMinimumStandardId = $entity->criteria->customerEvaluationMinimumStandardId ?
                                $entity->criteria->customerEvaluationMinimumStandardId :
                                $repository->getLastid($entity->criteria->customerId);

                            $result['customerEvaluationMinimumStandardMonthlyStatus'] = $repository->getChartStatus($entity->criteria);
                            $result['customerEvaluationMinimumStandardMonthlyAverage'] = $repository->getChartAverage($entity->criteria);
                            $result['customerEvaluationMinimumStandardMonthlyTotal'] = $repository->getChartTotal($entity->criteria);
                            $result['customerEvaluationMinimumStandardMonthlyAdvance'] = $repository->getChartAdvance($entity->criteria);
                            $result['customerEvaluationMinimumStandardId'] = $entity->criteria->customerEvaluationMinimumStandardId;
                            break;

                        case 'customer_road_safety':
                            $repository = new CustomerRoadSafetyRepository();
                            $entity->criteria->customerRoadSafetyId = $entity->criteria->customerRoadSafetyId ?
                                $entity->criteria->customerRoadSafetyId :
                                $repository->getLastid($entity->criteria->customerId);

                            $result['customerRoadSafetyCycle'] = $repository->getChartBar($entity->criteria);
                            $result['customerRoadSafetyProgress'] = $repository->getChartPie($entity->criteria);
                            $result['customerRoadSafetyAverage'] = ($stats = $repository->getStats($entity->criteria)) ? floatval($stats->total) : 0;
                            $result['customerRoadSafetyId'] = $entity->criteria->customerRoadSafetyId;
                            break;

                        case 'customer_contract':
                            $repository = new CustomerContractDetailRepository();
                            $result['customerContractPeriod'] = $repository->getChartBar($entity->criteria);
                            $result['customerContractProgress'] = $repository->getChartPie($entity->criteria);
                            $result['customerContractAverage'] = ($stats = $repository->getStats($entity->criteria)) ? floatval($stats->average) : 0;
                            break;

                        case "customer_absenteeism_disability":
                            $repository = new CustomerAbsenteeismDisabilityRepository();
                            $result['customerAbsenteeismDisabilitySummary'] = $repository->getChartBar($entity->criteria);
                            break;

                        case "customer_absenteeism_disability_general_event":
                            $repository = new CustomerAbsenteeismDisabilityRepository();
                            $result['customerAbsenteeismDisabilityGeneralEvent'] = $repository->getChartLineDisabilityGeneralEvent($entity->criteria);
                            break;

                        case "customer_absenteeism_indicator":
                            $repository = new CustomerAbsenteeismIndicatorRepository();
                            $result['customerAbsenteeismIndicatorEventNumber'] = $repository->getChartEventNumber($entity->criteria);
                            $result['customerAbsenteeismIndicatorDisabilityDays'] = $repository->getCharttDisabilityDays($entity->criteria);
                            $result['customerAbsenteeismIndicatorIF'] = $repository->getCharttIF($entity->criteria);
                            $result['customerAbsenteeismIndicatorIS'] = $repository->getCharttIS($entity->criteria);
                            $result['customerAbsenteeismIndicatorILI'] = $repository->getCharttILI($entity->criteria);
                            break;

                        case "customer_absenteeism_indicator_0312":
                            $repository = new CustomerAbsenteeismIndicatorRepository();
                            $result['customerAbsenteeismIndicatorFrequencyAccidentality'] = $repository->getChartFrequencyAccidentality($entity->criteria);
                            $result['customerAbsenteeismIndicatorSeverityAccidentality'] = $repository->getChartSeverityAccidentality($entity->criteria);
                            $result['customerAbsenteeismIndicatorMortalProportionAccidentality'] = $repository->getChartMortalProportionAccidentality($entity->criteria);
                            $result['customerAbsenteeismIndicatorAbsenteeismMedicalCause'] = $repository->getChartAbsenteeismMedicalCause($entity->criteria);
                            $result['customerAbsenteeismIndicatorOccupationalDiseaseFatalityRate'] = $repository->getChartOccupationalDiseaseFatalityRate($entity->criteria);
                            $result['customerAbsenteeismIndicatorOccupationalDiseasePrevalence'] = $repository->getChartOccupationalDiseasePrevalence($entity->criteria);
                            $result['customerAbsenteeismIndicatorOccupationalDiseaseIncidence'] = $repository->getChartOccupationalDiseaseIncidence($entity->criteria);

                            break;

                        case "customer_occupational_report":
                            $repository = new CustomerOccupationalReportRepository();
                            $result['customerOccupationalReportAccidentType'] = $repository->getChartAccidentType($entity->criteria);
                            $result['customerOccupationalReportDeathCause'] = $repository->getChartDeathCause($entity->criteria);
                            $result['customerOccupationalReportLocation'] = $repository->getChartLocation($entity->criteria);
                            $result['customerOccupationalReportLink'] = $repository->getChartLink($entity->criteria);
                            $result['customerOccupationalReportWorkTime'] = $repository->getChartWorkTime($entity->criteria);
                            $result['customerOccupationalReportWeekDay'] = $repository->getChartWeekDay($entity->criteria);
                            $result['customerOccupationalReportPlace'] = $repository->getChartPlace($entity->criteria);
                            $result['customerOccupationalReportInjury'] = $repository->getChartInjury($entity->criteria);
                            $result['customerOccupationalReportBody'] = $repository->getChartBody($entity->criteria);
                            $result['customerOccupationalReportFactor'] = $repository->getChartFactor($entity->criteria);
                            break;

                        case "customer_occupational_report_incident":
                            $repository = new CustomerOccupationalReportIncidentRepository();
                            $result['customerOccupationalReportAccidentType'] = $repository->getChartAccidentType($entity->criteria);
                            $result['customerOccupationalReportDeathCause'] = $repository->getChartDeathCause($entity->criteria);
                            $result['customerOccupationalReportLocation'] = $repository->getChartLocation($entity->criteria);
                            $result['customerOccupationalReportLink'] = $repository->getChartLink($entity->criteria);
                            $result['customerOccupationalReportWorkTime'] = $repository->getChartWorkTime($entity->criteria);
                            $result['customerOccupationalReportWeekDay'] = $repository->getChartWeekDay($entity->criteria);
                            $result['customerOccupationalReportPlace'] = $repository->getChartPlace($entity->criteria);
                            $result['customerOccupationalReportInjury'] = $repository->getChartInjury($entity->criteria);
                            $result['customerOccupationalReportBody'] = $repository->getChartBody($entity->criteria);
                            $result['customerOccupationalReportFactor'] = $repository->getChartFactor($entity->criteria);
                            $result['customerOccupationalReportStatus'] = $repository->getChartStatus($entity->criteria);
                            break;

                        case 'customer_config_activity_hazard_characterization':
                            $repository = new CustomerConfigActivityHazardRepository();

                            $result['matrixCharacterizationClassification'] = $repository->getChartBarClassification($entity->criteria);
                            $result['matrixCharacterizationAcceptability'] = $repository->getChartPieAcceptability($entity->criteria);
                            $result['matrixCharacterizationAcceptabilityClassification'] = $repository->getChartBarAcceptabilityClassification($entity->criteria);
                            $result['matrixCharacterizationAcceptabilityType'] = $repository->getChartBarAcceptabilityType($entity->criteria);
                            $result['matrixCharacterizationIntervention'] = $repository->getChartIntervention($entity->criteria);
                            $result['matrixCharacterizationImprovement'] = $repository->getChartPieImprovementPlan($entity->criteria);

                            break;

                        case 'customer_express_matrix_hazard_intervention_stats':
                            $repository = new CustomerConfigQuestionExpressRepository();

                            $result['customerExpressMatrixHazardInterventionStats'] = $repository->getChartPieHazardInterventionStats($entity->criteria);
                            break;

                        case 'customer_unsafe_act':
                            $repository = new CustomerUnsafeActRepository();
                            $result['customerUnsafeActWorkplace'] = $repository->getChartWorkplace($entity->criteria);
                            $result['customerUnsafeActHazard'] = $repository->getChartHazard($entity->criteria);
                            $result['customerUnsafeActPeriod'] = $repository->getChartPeriod($entity->criteria);
                            $result['customerUnsafeActStatus'] = $repository->getChartStatus($entity->criteria);
                            break;

                        case "customer_covid_indicators":
                            $repository = new CustomerCovidRepository();
                            $result['covidGenre'] = $repository->getGenreCharPie($entity->criteria);
                            $result['covidPregnant'] = $repository->getPregnantCharPie($entity->criteria);
                            $result['covidFever'] = $repository->getFeverCharBar($entity->criteria);
                            $result['covidEmployee'] = $repository->getEmployeeCharBar($entity->criteria);
                            $result['covidEmployeeWorkPlace'] = $repository->getEmployeeWorkplaceCharBar($entity->criteria);
                            $result['covidRiskLevel'] = $repository->getRiskLevelCharBar($entity->criteria);
                            $result['covidOximetria'] = $repository->getOximetriaCharBar($entity->criteria);
                            $result['covidPulsometria'] = $repository->getPulsometriaCharBar($entity->criteria);
                            break;

                        case "customer_covid_daily_form":
                            $result['chartDailyForm'] = (new CustomerCovidDailyTemperatureRepository)->getTemperatureOfMonth($entity->criteria);
                            break;

                        case "customer_vr_employee_observations_charts":
                            $genreStats = ExperienceAnswerRepository::getGenreChart($entity->criteria);
                            $result['genre'] = $genreStats[0];
                            $result['genreTotal'] = $genreStats[1];
                            $result['obsTypes'] = ExperienceAnswerRepository::getObsTypesChart($entity->criteria);
                            break;

                        case "customer_vr_employee_indicators_charts":
                            $genreStats = ExperienceAnswerRepository::getGenreIndicatorChart($entity->criteria);
                            $result['genre'] = $genreStats[0];
                            $result['genreTotal'] = $genreStats[1];
                            $result['competitorExperience'] = ExperienceAnswerRepository::getCompetitorExperienceChart($entity->criteria);
                            break;

                        case "customer_vr_employee_indicators_period_chart":
                            $result['periodChart'] = ExperienceAnswerRepository::getPeriodChart($entity->criteria);
                            break;

                        case "customer_job_condition_indicators":
                            $customerId = $entity->criteria->customerId;
                            $year = $entity->criteria->year;
                            $years = $entity->criteria->years;
                            $location = $entity->criteria->location;

                            $repository = new IndicatorRepository();
                            $result['customerJobConditionIndicatorsInterventions'] = $repository->getChartPieJobConditionsInterventionStats($customerId, $year, $location);
                            $result['customerJobConditionIndicatorsComplianceByPeriod'] = $repository->getDataComplianceByPeriod($customerId, $years, $location);
                            $result['customerJobConditionIndicatorsLevelRiskByMonth'] = $repository->getDataLevelRiskByMonth($customerId, $year, $location);
                            break;

                        case "customer_projects_arl_contributions":
                            $repository = new CustomerProjectRepository();
                            $result['customerProjectsArlContributions'] = $repository->getContributationsVsExecutionsChartPie($entity->criteria);
                            $result['contributionsVsExec'] = $repository->getContributationsVsExecutionsChartLineByMonth($entity->criteria);
                            break;

                        case "customer_vr_satisfaction_general":
                            $repository = new SatisfactionIndicatorRepository();
                            $result['registeredVsParticipants'] = $repository->getChartLineRegisteredVsParticipants($entity->criteria->customerId);
                            $result['amountBySatisfaction'] = $repository->getChartBarAmountBySatisfaction($entity->criteria->customerId);
                            break;

                        case "customer_vr_satisfaction_by_responses":
                            $repository = new SatisfactionIndicatorRepository();
                            $result['customerVrSatisfactionByResponses'] = $repository->getChartBarQuestionVsResponses($entity->criteria->customerId, $entity->criteria->date);
                            break;

                        case "dashboard_top_management_cost_historical":
                            $repository = new TopManagementRepository();
                            $result['dashboardTopManagementCostHistorical'] = $repository->getChartLineCostHistorical($entity->criteria);
                            $result['dashboardTopManagementCostTotalCurrentYear'] = $repository->getKPITotalCosts($entity->criteria);
                            $result['dashboardTopManagementCostTypesByStates'] = $repository->getChartBarStackedTypeSalesByStates($entity->criteria);
                            break;

                        case "dashboard_top_management_cost_summary":
                            $repository = new TopManagementRepository();
                            $startDate = empty($entity->criteria->startDate) ? null : Carbon::createFromFormat('d/m/Y', $entity->criteria->startDate)->startOfDay();
                            $endDate = empty($entity->criteria->endDate) ? null : Carbon::createFromFormat('d/m/Y', $entity->criteria->endDate)->endOfDay();

                            $type = $entity->criteria->type->value ?? null;
                            $concept = $entity->criteria->concept->value ?? null;
                            $classification = $entity->criteria->classification->value ?? null;

                            $customerId = $entity->criteria->customer->value ?? null;
                            $administrator = $entity->criteria->administrator->value ?? null;

                            $result['dashboardTopManagementCostByMonths'] = $repository->getChartBarCostByMonths($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator);
                            $result['dashboardTopManagementCostByType'] = $repository->getChartBarCostByType($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator);
                            $result['dashboardTopManagementCostByConcept'] = $repository->getChartBarCostByConcept($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator);
                            $result['dashboardTopManagementCostByClassification'] = $repository->getChartBarCostByClassification($startDate, $endDate, $type, $concept, $classification, $customerId, $administrator);
                            $result['dashboardTopManagementExperiencesByMoths'] = $repository->getChartBarExperiencesByMonths($startDate, $endDate, $customerId);

                            $result['dashboardTopManagementPerformanceByConcultant'] = $repository->getChartLinePerformanceByConsultant($startDate, $endDate, $customerId);


                            $satisfactionRepository = new SatisfactionIndicatorRepository();
                            $result['dashboardTopManagementSatisfactionByExperience'] = $satisfactionRepository->getChartBarAmountBySatisfaction($customerId, $startDate, $endDate);
                            $result['registeredVsParticipantsAllClientsAndPeriods'] = $satisfactionRepository->getChartPieRegisteredVsParticipantsAllClientsAndPeriods($startDate, $endDate, $customerId);
                            $result['registeredVsParticipantsByMonths'] = $satisfactionRepository->getChartLineRegisteredVsParticipantsByMonths($startDate, $endDate, $customerId);
                            break;

                        case 'customer_employee_indicators_summary':
                            $service = new CustomerEmployeeIndicatorService();
                            $service->setCustomerId($entity->criteria->customerId);
                            $service->setYear($entity->criteria->year);
                            $service->setWorkplace($entity->criteria->workplace ?? null);

                            $result['customerEmployeeIndicatorsSummaryTotal'] = $service->getTotalEmployees();
                            $result['customerEmployeeIndicatorsSummaryActive'] = $service->getActiveAndInactiveEmployeeChartPie();
                            $result['customerEmployeeIndicatorsSummaryAuthorized'] = $service->getAuthorizedEmployeeChartPie();

                            $result['customerEmployeeIndicatorsEmployeesByWorkplaces'] = $service->getCharLineEmployeesByWorkplaces();
                            $result['customerEmployeeIndicatorsActiveEmployees'] = $service->getChartBarAmountActiveEmployees();
                            $result['customerEmployeeIndicatorsAutorizedEmployees'] = $service->getChartBarAmountAutorizedEmployees();

                            $result['customerEmployeeIndicatorsAmountEmployeesByPeriod'] = $service->getChartLineAmountEmployeesByPeriod();
                            $result['customerEmployeeIndicatorsAmountEmployeesVsActiveVsInactiveByPeriod'] = $service->getChartLineAmountEmployeesVsActiveVsInactiveByPeriod();
                            $result['customerEmployeeIndicatorsAmountamountActiveVsAutorizedVsUnautorizedEmployeesByPeriodByPeriod'] = $service->getChartLineAmountActiveVsAutorizedVsUnautorizedByPeriod();
                            break;

                        case 'customer_employee_demographic_indicators':
                            $repository = new CustomerEmployeeIndicatorRepository();

                            $repository->setCustomerId($entity->criteria->customerId);
                            $repository->setWorkplace($entity->criteria->workplace ?? null);

                            $result['customerEmployeeDemographicIndicatorsTypeHousing'] = $repository->getTypeHousingChartPie();
                            $result['customerEmployeeDemographicIndicatorsAntiquityCompany'] = $repository->getAntiquityCompanyChartPie();
                            $result['customerEmployeeDemographicIndicatorsAntiquityJob'] = $repository->getAntiquityJobChartPie();

                            $result['customerEmployeeDemographicIndicatorsHasChildren'] = $repository->getHasChildrenChartPie();
                            $result['customerEmployeeDemographicIndicatorsStratum'] = $repository->getCharBarStratum();
                            $result['customerEmployeeDemographicIndicatorsCivilStatus'] = $repository->getCharBarCivilStatus();

                            $result['customerEmployeeDemographicIndicatorsGender'] = $repository->getGenderChartPie();
                            $result['customerEmployeeDemographicIndicatorsScholarship'] = $repository->getCharBarScholarship();
                            $result['customerEmployeeDemographicIndicatorsAge'] = $repository->getCharBarAge();

                            $result['customerEmployeeDemographicIndicatorsPracticeSports'] = $repository->getCharBarPracticeSports();
                            $result['customerEmployeeDemographicIndicatorsDrinkAlcoholic'] = $repository->getCharBarDrinkAlcoholic();
                            $result['customerEmployeeDemographicIndicatorsSmokes'] = $repository->getCharBarSmokes();

                            $result['customerEmployeeDemographicIndicatorsDiagnosedDisease'] = $repository->getCharBarDiagnosedDisease();
                            $result['customerEmployeeDemographicIndicatorsWorkArea'] = $repository->getCharBarWorkArea();
                            $result['customerEmployeeDemographicIndicatorsWorkShift'] = $repository->getCharBarWorkShift();

                            break;


                        case 'customer_employee_documents_indicators':
                            $service = new CustomerEmployeeDocumentIndicatorService();

                            $service->setCustomerId($entity->criteria->customerId);
                            $service->setYear($entity->criteria->year);
                            $service->setWorkplace($entity->criteria->workplace ?? null);

                            $result['customerEmployeeIndicatorsDocumentsTotal'] = $service->getTotalDocuments();
                            $result['customerEmployeeIndicatorsDocumentsByStatus'] = $service->getDocumentsByStatusChartPie();
                            $result['customerEmployeeIndicatorsDocumentsAuthorized'] = $service->getAuthorizedDocumentsChartPie();

                            $result['customerEmployeeIndicatorsDocumentsByWorkplaces'] = $service->getCharLineDocumentsByWorkplaces();
                            $result['customerEmployeeIndicatorsStatusDocumentsByWorkplaces'] = $service->getChartBarStatusByDocuments();
                            $result['customerEmployeeIndicatorsAuthorizedByWorkplaces'] = $service->getChartBarAmountAuthorizedDocuments();

                            $result['customerEmployeeIndicatorsDocumentsByPeriod'] = $service->getChartLineAmountDocumentsByPeriod();
                            $result['customerEmployeeIndicatorsDocumentsByStatusByPeriod'] = $service->getChartLineDocumentsComparativeByStateAndPeriod();
                            $result['customerEmployeeIndicatorsAuthorizedDocumentsByPeriod'] = $service->getChartLineAutorizedVsUnautorizedByPeriod();
                            break;

                        case 'dashboard_commercial_summary':
                            $repository = new CommercialDashboardRepository();
                            $result['amountLicensesByYearsHistorical'] = $repository->getChartLineLicensesByYearsHistorical();
                            $result['amountLicensesByTypeAndYearsHistorical'] = $repository->getChartLineLicensesByTypeAndYearsHistorical();
                            $result['amountActiveLicensesByType'] = $repository->getChartPieActiveLicensesByType();
                            $result['amountActiveLicensesByState'] = $repository->getChartPieActiveLicensesByState();
                            break;

                        case 'customer_vr_experience_employee_indicators':
                            $customerEmployeeId = $entity->criteria->customerEmployeeId;
                            $year = $entity->criteria->year;

                            $repository = new ExperienceAnswerRepository();
                            $result['customerVrExperienceEmployeeIndicators'] = $repository->getExperienceByEmployeeIndicators($customerEmployeeId, $year);
                            break;

                        case 'dashboard_minimal_standard_progress':
                            $repository = new CustomerEvaluationMinimumStandardRepository();
                            $result['dashboardMinimalStandardProgress'] = $repository->getMinimalStandardProgress($entity->criteria);
                            $result['dashboardStats'] = $repository->getStatsBoard($entity->criteria);
                            break;

                        case 'dashboard_minimal_standard_compare':
                            $repository = new CustomerEvaluationMinimumStandard0312Repository();
                            $result['dashboardMinimalStandardCompareChartLine'] = $repository->getTotalByCustomerAndYearChartLine($entity->criteria);
                            break;

                        case 'dashboard_hazard_matrix':
                            $repository = new CustomerConfigActivityHazardRepository();
                            $result['dashboardHazardMatrixChartBar'] = $repository->getChartBarClassification($entity->criteria);
                            $result['dashboardHazardMatrixAmountRecords'] = $repository->getAmountRecords($entity->criteria);
                            break;

                        case 'dashboard_hazard_matrix_acceptability':
                            $repository = new CustomerConfigActivityHazardRepository();
                            $result['dashboardHazardMatrixAcceptabilityChartBar'] = $repository->getChartBarAcceptability($entity->criteria);
                            break;

                        case 'dashboard_road_safety':
                            $repository = new CustomerRoadSafetyRepository();
                            $result['dashboardRoadSafetyChartBar'] = $repository->getRoadSafetyChartBar($entity->criteria);
                            $result['customerRoadSafetyAverage'] = ($stats = $repository->getStatsPercentByCustomer($entity->criteria)) ? floatval($stats->total) : 0;
                            break;

                        case 'dashboard_sst_progress':
                            $repository = new CustomerDiagnosticRepository();
                            $result['customerDiagnosticProgress'] = $repository->getDiagnosticProgress($entity->criteria);
                            $result['rates'] = $this->getRates();
                            break;

                        case 'dashboard_sst_compare':
                            $repository = new CustomerDiagnosticRepository();
                            $result['customerDiagnosticProgressCompareChartLine'] = $repository->getTotalByCustomerAndYearChartLine($entity->criteria);
                            break;

                        case 'dashboard_unsafe_act_hazard':
                            $repository = new CustomerUnsafeActRepository();
                            $result['customerUnsafeActHazard'] = $repository->getChartHazard($entity->criteria);
                            $result['customerCountUnsafeActHazard'] = $repository->getCountUnsafeConditions($entity->criteria);
                            break;

                        case 'dashboard_unsafe_act_status':
                            $repository = new CustomerUnsafeActRepository();
                            $result['customerUnsafeActStatus'] = $repository->getChartStatus($entity->criteria);
                            $result['customerCountUnsafeActHazard'] = $repository->getCountUnsafeConditions($entity->criteria);
                            break;

                        case 'dashboard_improvement_plan':
                            $repository = new CustomerImprovementPlanRepository();
                            $result['dashboardImprovementPlan'] = $repository->getChartStackedBarPlanByStatus($entity->criteria);
                            break;

                        case "dashboard_job_condition_indicators":
                            $customerId = $entity->criteria->customerId;
                            $year = $entity->criteria->year;
                            $location = $entity->criteria->location;

                            $repository = new IndicatorRepository();
                            $result['customerJobConditionIndicatorsLevelRiskByMonth'] = $repository->getDataLevelRiskByMonth($customerId, $year, $location);
                            break;

                        case 'dashboard_customer_employees_amounts':
                            $customerId = $entity->criteria->customerId;

                            $repository = new CustomerRepository();
                            $result['dashboardCustomerEmployeesAmounts'] = $repository->getAmountEmployeesAll($customerId);
                            $result['dashboardCustomerEmployeesAmountsChartStackedBar'] = $repository->getAmountEmployeesChartStackedBar($customerId);
                            break;

                        case 'dashboard_accidents':
                            $customerId = $entity->criteria->customerId;
                            $period = $entity->criteria->period;

                            $repository = new CustomerOccupationalInvestigationRepository();
                            $result['dashboardAccidents'] = $repository->getInfoToDashboard($customerId, $period);
                            $result['chartBarBody'] = $repository->chartBarBody($customerId, $period);
                            $result['chartBarFactor'] = $repository->chartBarFactor($customerId, $period);
                            break;

                        case 'dashboard_occupational_medicine':
                            $customerId = $entity->criteria->customerId;
                            $period = $entity->criteria->period;
                            $workplaceId = $entity->criteria->workplaceId;

                            $repository = new CustomerOccupationalInvestigationRepository();
                            $result['getChartStackedBarAusentismVsInvestigationAT'] = $repository->getChartStackedBarAusentismVsInvestigationAT($customerId, $period, $workplaceId);
                            $result['getKpiOccupationalMedicineDashboard'] = $repository->getKpiOccupationalMedicineDashboard($customerId, $period, $workplaceId);
                            $result['chartPieAbsenteeisByCause'] = $repository->getChartPieAbsenteeisByCause($customerId, $period, $workplaceId);
                            break;

                        case 'dashboard_occupational_investigation_data':
                            $customerId = $entity->criteria->customerId;
                            $period = $entity->criteria->period;
                            $workplaceId = $entity->criteria->workplaceId;

                            $repository = new CustomerManagementRepository();
                            $result['dashboardOccupationalInvestigationData'] = $repository->getProgramsWithRateAndPercent($customerId, $period, $workplaceId);
                            $result['dashboardOccupationalInvestigationRates'] = \DB::table('wg_rate')->get();
                            break;

                        default:
                            $repository = new SystemParameter();
                            $result = [];
                            break;
                    }
                }
            }

            $this->response->setData($result);
            $this->response->setRecordsTotal(0);
            $this->response->setRecordsFiltered(0);
        } catch (Exception $exc) {
            // error on server
            Log::error($exc);
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getTraceAsString());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    private function getRates()
    {
        return DB::table('wg_rate')
            ->select(
                'wg_rate.id',
                'wg_rate.code as value',
                'wg_rate.text as item'
            )
            ->get();
    }

    private function getChartBarOptions()
    {
        return [
            'legend' => ['position' => 'right'],
            'responsive' => true,
            'maintainAspectRatio' => true,
            'scaleBeginAtZero' => true,
            'scaleShowGridLines' => true,
            'scaleGridLineColor' => 'rgba(0,0,0,.05)',
            'scaleGridLineWidth' => 1,
            'barShowStroke' => true,
            'barStrokeWidth' => 2,
            'barValueSpacing' => 5,
            'barDatasetSpacing' => 1,
            'scales' => [
                'yAxes' => [[
                    'ticks' => [
                        "beginAtZero" => true,
                    ],
                ]],
                'xAxes' => [[
                    'ticks' => [
                        "beginAtZero" => true
                    ],
                ]],
            ],
            'legendTemplate' => '<ul class="tc-chart-js-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].fillColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
        ];
    }

    /**
     * En lugar de colocar varias barras agrupadas por tipo, coloca una sola barra diferenciando los tipos por color
     * en forma de escala
     */
    private function getChartBarOptionsWithScales()
    {
        $options = $this->getChartBarOptions();
        $options['scales'] = [
            'xAxes' => [[
                'stacked' => true,
                'ticks' => [
                    "beginAtZero" => true,
                ],
            ]],
            'yAxes' => [[
                'stacked' => true,
                'ticks' => [
                    "beginAtZero" => true,
                ],
            ]]
        ];

        return $options;
    }

    private function getChartLineOptions()
    {
        return [
            'legend' => ['position' => 'right'],
            'responsive' => true,
            'maintainAspectRatio' => true,
            'scaleShowGridLines' => true,
            'scaleGridLineColor' => 'rgba(0,0,0,.05)',
            'scaleGridLineWidth' => 1,
            'bezierCurve' => true,
            'bezierCurveTension' => 0.4,
            'pointDot' => true,
            'pointDotRadius' => 4,
            'pointDotStrokeWidth' => 1,
            'pointHitDetectionRadius' => 20,
            'datasetStroke' => true,
            'datasetStrokeWidth' => 2,
            'datasetFill' => true,
            'scales' => [
                'yAxes' => [[
                    'ticks' => [
                        "beginAtZero" => true,
                    ],
                ]],
            ],
            'legendTemplate' => '<ul class="tc-chart-js-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].strokeColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
        ];
    }

    private function getChartDoughnutOptions()
    {
        return [
            'legend' => ['position' => 'right'],
            'responsive' => true,
            'maintainAspectRatio' => true,
            'segmentShowStroke' => true,
            'segmentStrokeColor' => '#fff',
            'segmentStrokeWidth' => 2,
            'percentageInnerCutout' => 50,
            'cutoutPercentage' => 50,
            'animationSteps' => 100,
            'animationEasing' => 'easeOutBounce',
            'animateRotate' => true,
            'animateScale' => false,
            'legendTemplate' => '<ul class="tc-chart-js-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>',
        ];
    }

    private function getChartPieOptions()
    {
        return [
            'legend' => ['position' => 'right'],
            'responsive' => true,
            'maintainAspectRatio' => true,
            'segmentShowStroke' => true,
            'segmentStrokeColor' => '#fff',
            'segmentStrokeWidth' => 2,
            'percentageInnerCutout' => 50,
            'animationSteps' => 100,
            'animationEasing' => 'easeOutBounce',
            'animateRotate' => true,
            'animateScale' => false,
            'legendTemplate' => '<ul class="tc-chart-js-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>',
        ];
    }

    private function getChartRadarOptions()
    {
        return [
            'legend' => ['position' => 'top'],
            'responsive' => true,
            'scaleBeginAtZero' => true,
            'scaleShowGridLines' => true,
            'scaleGridLineColor' => 'rgba(0,0,0,.05)',
            'scaleGridLineWidth' => 1,
            'barShowStroke' => true,
            'barStrokeWidth' => 2,
            'barValueSpacing' => 5,
            'barDatasetSpacing' => 1,
            'legendTemplate' => '<ul class="tc-chart-js-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].fillColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
        ];
    }
}
