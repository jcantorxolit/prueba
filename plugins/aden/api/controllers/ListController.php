<?php

namespace AdeN\Api\Controllers;

use AdeN\Api\Classes\BaseController;
use AdeN\Api\Helpers\HttpHelper;
use AdeN\Api\Modules\Certificate\GradeParticipant\CertificateGradeParticipantModel;
use AdeN\Api\Modules\Certificate\GradeParticipant\CertificateGradeParticipantRepository;
use AdeN\Api\Modules\Config\General\ConfigGeneralRepository;
use AdeN\Api\Modules\Config\JobActivityHazardClassification\ConfigJobActivityHazardClassificationRepository;
use AdeN\Api\Modules\Config\JobActivityHazardDescription\ConfigJobActivityHazardDescriptionRepository;
use AdeN\Api\Modules\Config\JobActivityHazardEffect\ConfigJobActivityHazardEffectRepository;
use AdeN\Api\Modules\Config\JobActivityHazardType\ConfigJobActivityHazardTypeRepository;
use AdeN\Api\Modules\CovidBolivar\Question\CovidBolivarQuestionRepository;
use AdeN\Api\Modules\Covid\Question\CovidQuestionRepository;
use AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged\CustomerAbsenteeismDisabilityDayChargedRepository;
use AdeN\Api\Modules\Customer\AbsenteeismDisability\CustomerAbsenteeismDisabilityRepository;
use AdeN\Api\Modules\Customer\AbsenteeismIndicator\CustomerAbsenteeismIndicatorRepository;
use AdeN\Api\Modules\Customer\ArlServiceCost\CustomerArlServiceCostRepository;
use AdeN\Api\Modules\Customer\ConfigActivityExpress\CustomerConfigActivityExpressRepository;
use AdeN\Api\Modules\Customer\ConfigActivityHazard\CustomerConfigActivityHazardRepository;
use AdeN\Api\Modules\Customer\ConfigActivitySpecial\CustomerConfigActivitySpecialRepository;
use AdeN\Api\Modules\Customer\ConfigAreaJobSpecialRelation\CustomerConfigAreaJobSpecialRelationRepository;
use AdeN\Api\Modules\Customer\ConfigAreaSpecial\CustomerConfigAreaSpecialRepository;
use AdeN\Api\Modules\Customer\ConfigBusinessUnitSpecialRelation\CustomerConfigBusinessUnitSpecialRelationRepository;
use AdeN\Api\Modules\Customer\ConfigHazardSpecialRelation\CustomerConfigHazardSpecialRelationRepository;
use AdeN\Api\Modules\Customer\ConfigJobActivity\CustomerConfigJobActivityRepository;
use AdeN\Api\Modules\Customer\ConfigJobExpress\CustomerConfigJobExpressRepository;
use AdeN\Api\Modules\Customer\ConfigJobSpecial\CustomerConfigJobSpecialRepository;
use AdeN\Api\Modules\Customer\ConfigOfficeSpecialExchangeControl\CustomerConfigOfficeSpecialExchangeControlRepository;
use AdeN\Api\Modules\Customer\ConfigOfficeSpecial\CustomerConfigOfficeSpecialRepository;
use AdeN\Api\Modules\Customer\ConfigProcessExpress\CustomerConfigProcessExpressRepository;
use AdeN\Api\Modules\Customer\ConfigProcessSpecial\CustomerConfigProcessSpecialRepository;
use AdeN\Api\Modules\Customer\ConfigQuestionExpressIntervention\CustomerConfigQuestionExpressInterventionRepository;
use AdeN\Api\Modules\Customer\ConfigQuestionExpress\CustomerConfigQuestionExpressRepository;
use AdeN\Api\Modules\Customer\ConfigWorkplace\CustomerConfigWorkplaceRepository;
use AdeN\Api\Modules\Customer\ContractDetail\CustomerContractDetailRepository;
use AdeN\Api\Modules\Customer\Contractor\CustomerContractorRepository;
use AdeN\Api\Modules\Customer\ContractSafetyInspection\CustomerContractSafetyInspectionRepository;
use AdeN\Api\Modules\Customer\Contributions\ContributionRepository;
use AdeN\Api\Modules\Customer\CovidBolivar\CustomerCovidBolivarRepository;
use AdeN\Api\Modules\Customer\CovidBolivar\Daily\CustomerCovidBolivarDailyRepository;
use AdeN\Api\Modules\Customer\Covid\CustomerCovidRepository;
use AdeN\Api\Modules\Customer\CustomerRepository;
use AdeN\Api\Modules\Customer\Diagnostic\CustomerDiagnosticRepository;
use AdeN\Api\Modules\Customer\Document\CustomerDocumentRepository;
use AdeN\Api\Modules\Customer\EconomicGroup\CustomerEconomicGroupRepository;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeRepository;
use AdeN\Api\Modules\Customer\Employee\Document\CustomerEmployeeDocumentRepository;
use AdeN\Api\Modules\Customer\Employee\Indicators\CustomerEmployeeIndicatorRepository;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\CustomerEvaluationMinimumStandard0312Repository;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandard\CustomerEvaluationMinimumStandardRepository;
use AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312\CustomerEvaluationMinimumStandard0312Service;
use AdeN\Api\Modules\Customer\ImprovementPlan\CustomerImprovementPlanRepository;
use AdeN\Api\Modules\Customer\ImprovementPlanDocument\CustomerImprovementPlanDocumentRepository;
use AdeN\Api\Modules\Customer\InternalCertificateGrade\CustomerInternalCertificateGradeRepository;
use AdeN\Api\Modules\Customer\InternalProject\CustomerInternalProjectRepository;
use AdeN\Api\Modules\Customer\InvestigationAl\CustomerInvestigationAlRepository;
use AdeN\Api\Modules\Customer\JobConditions\Jobcondition\JobConditionRepository;
use AdeN\Api\Modules\Customer\Licenses\LicenseRepository;
use AdeN\Api\Modules\Customer\Management\CustomerManagementRepository;
use AdeN\Api\Modules\Customer\OccupationalInvestigationAl\CustomerOccupationalInvestigationRepository;
use AdeN\Api\Modules\Customer\Parameter\CustomerParameterRepository;
use AdeN\Api\Modules\Customer\Parameter\CustomerParameterService;
use AdeN\Api\Modules\Customer\RoadSafety40595\CustomerRoadSafety40595Repository;
use AdeN\Api\Modules\Customer\RoadSafety\CustomerRoadSafetyRepository;
use AdeN\Api\Modules\Customer\RoadSafety\CustomerRoadSafetyService;
use AdeN\Api\Modules\Customer\SafetyInspection\CustomerSafetyInspectionRepository;
use AdeN\Api\Modules\Customer\TrackingDocument\CustomerTrackingDocumentRepository;
use AdeN\Api\Modules\Customer\UnsafeAct\CustomerUnsafeActRepository;
use AdeN\Api\Modules\Customer\User\CustomerUserRepository;
use AdeN\Api\Modules\Customer\VrEmployee\CustomerVrEmployeeRepository;
use AdeN\Api\Modules\Customer\VrEmployee\ExperienceAnswer\ExperienceAnswerRepository;
use AdeN\Api\Modules\Customer\VrEmployee\Experience\ExperienceRepository;
use AdeN\Api\Modules\Customer\VrEmployee\Satisfactionindicators\SatisfactionIndicatorRepository;
use AdeN\Api\Modules\EconomicSector\EconomicSectorRepository;
use AdeN\Api\Modules\MinimumStandard0312\MinimumStandard0312Repository;
use AdeN\Api\Modules\PositivaFgn\Campus\CampusRepository;
use AdeN\Api\Modules\PositivaFgn\Consultant\ConsultantRepository;
use AdeN\Api\Modules\PositivaFgn\Vendor\VendorRepository;
use AdeN\Api\Modules\Project\AgentTask\CustomerProjectAgentTaskRepository;
use AdeN\Api\Modules\Project\CustomerProjectRepository;
use AdeN\Api\Modules\ResourceLibrary\ResourceLibraryRepository;
use AdeN\Api\Modules\Dashboard\TopManagement\TopManagementRepository;
use AdeN\Api\Modules\TemplateManage\TemplateManageRepository;
use Auth;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Support\Collection;
use Log;
use RainLab\User\Models\Country;
use RainLab\User\Models\State;
use Response;
use Wgroup\CertificateProgram\CertificateProgram;
use Wgroup\CustomerParameter\CustomerParameter;
use Wgroup\Models\Customer;
use Wgroup\Models\Town;
use Wgroup\SystemParameter\SystemParameter;
use Wgroup\Traits\UserSecurity;

/**
 * The API controller class.
 * The controller finds and serves requested services.
 *
 * @package Presupuesto\api
 * @author David Blandon
 */
class ListController extends BaseController
{
    use UserSecurity;

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

                        case 'current_datetime':
                            $result['currentDateTime'] = Carbon::now('America/Bogota')->format('d/m/Y H:i:s');
                            break;

                        case 'current_date':
                            $result['currentDate'] = Carbon::now('America/Bogota')->format('Y/m/d');
                            break;

                        case 'current_year':
                            $result['currentYear'] = Carbon::now('America/Bogota')->year;
                            break;

                        case 'current_month':
                            $result['currentMonth'] = Carbon::now('America/Bogota')->month;
                            break;

                        case 'current_customer':
                            $currentUser = $entity->value ? DB::table('users')->where('company', $entity->value)->first() : Auth::getUser();
                            $result['currentCustomer'] = $this->getCustomer($currentUser);
                            break;

                        case 'redirect_url':
                            $result['redirectUrl'] = $this->getRedirectUrl();
                            break;

                        case 'export_url':
                            $result['exportUrl'] = SystemParameter::whereNamespace("config")->whereGroup($entity->name)
                                ->orderBy('id', 'ASC')
                                ->first();
                            break;

                        case "customer_admin_profile_remove":
                            $result['customerAdminProfileRemove'] = SystemParameter::whereNamespace("config")->whereGroup($entity->name)
                                ->orderBy('id', 'ASC')
                                ->first();
                            break;

                        case 'country':
                            $result['countryList'] = Country::where('is_enabled', true)->orderBy("name", "asc")->get();
                            break;

                        case 'state':
                            $result['stateList'] = State::whereCountryId($entity->value)->orderBy('name', 'ASC')->get();
                            break;

                        case 'state_full':
                            $result['stateList'] = State::orderBy('name', 'ASC')->get();
                            break;

                        case 'city':
                            $result['cityList'] = Town::whereStateId($entity->value)->orderBy('name', 'ASC')->get();
                            break;

                        case 'city_full':
                            $result['cityList'] = Town::orderBy('name', 'ASC')->get();
                            break;

                        case 'economic_activity':
                            $result['economicActivityList'] = DB::table('wg_investigation_economic_activity')
                                ->join('wg_economic_sector', function ($join) {
                                    $join->on('wg_economic_sector.id', '=', 'wg_investigation_economic_activity.economic_sector_id');
                                })
                                ->select(
                                    'wg_investigation_economic_activity.id',
                                    'wg_investigation_economic_activity.name',
                                    'wg_investigation_economic_activity.code',
                                    'wg_economic_sector.name as economicSector'
                                )
                                ->get();
                            break;

                        case 'config_hazard_classification':
                            $result["configHazardClassification"] = DB::table('wg_config_job_activity_hazard_classification')
                                ->select('id', 'name')
                                ->get();
                            break;

                        case 'config_hazard_type':
                            $result["configHazardType"] = DB::table('wg_config_job_activity_hazard_type')
                                ->select('id', DB::raw("CONCAT(`code`, ' ', REPLACE(`name`, `code`, '')) AS `name`"))
                                ->where('wg_config_job_activity_hazard_type.classification_id', $entity->value)
                                ->get();
                            break;

                        case 'config_hazard_description':
                            $result["configHazardDescription"] = DB::table('wg_config_job_activity_hazard_description')
                                ->select('id', DB::raw("TRIM(CONCAT(`code`, ' ', REPLACE(`name`, `code`, ''))) AS `name`"))
                                ->where('wg_config_job_activity_hazard_description.type_id', $entity->value)
                                ->get();
                            break;

                        case 'config_hazard_health_effect':
                            $result["configHazardHealthEffect"] = DB::table('wg_config_job_activity_hazard_effect')
                                ->select('id', DB::raw("CONCAT(`code`, ' ', REPLACE(`name`, `code`, '')) AS `name`"))
                                ->where('wg_config_job_activity_hazard_effect.type_id', $entity->value)
                                ->get();
                            break;

                        case 'economic_sector':
                            $result["economicSectorList"] = (new EconomicSectorRepository)->getList();
                            break;

                        case 'customer_custom_filter_field':
                            $result['customerFilterField'] = CustomerRepository::getCustomFilters();
                            break;

                        case 'customer_workplace':
                            $repository = new CustomerRepository();

                            $result["workplaceList"] = $repository->getWorkplaceList($entity->value);
                            $result["customer"] = $repository->parseModelWithRelations($repository->find($entity->value));
                            $result["weekday"] = ucfirst(Carbon::now()->formatLocalized('%A'));
                            break;

                        case 'customer_macroprocess':
                            $repository = new CustomerRepository();
                            $result["macroprocessList"] = $repository->getMacroprocessList($entity->criteria);
                            break;

                        case 'customer_process':
                            $repository = new CustomerRepository();
                            $result["processList"] = $repository->getProcessList($entity->criteria);
                            break;

                        case 'customer_job':
                            $repository = new CustomerRepository();
                            $result["jobList"] = $repository->getJobList($entity->value);
                            break;

                        case 'customer_activity':
                            $repository = new CustomerRepository();
                            $result["activityList"] = $repository->getActivityList($entity->value);
                            break;

                        case 'customer_has_economic_group':
                            $repository = new CustomerRepository();
                            $result["customerHasEconomicGroupList"] = $repository->getHasEconomicGroupList($entity->criteria);
                            break;

                        case 'customer_economic_group':
                            $repository = new CustomerEconomicGroupRepository();
                            $result["customerEconomicGroupList"] = $repository->getList($entity->criteria);
                            break;

                        case 'customer_employeer':
                            $repository = new CustomerRepository();
                            $result["customerEmployeerList"] = $repository->getEmployeerList($entity->criteria);
                            break;

                        case "customer_employee_type_rh":
                            $result["customer_employee_type_rh"] = SystemParameter::whereNamespace("wgroup")->whereGroup('wg_employee_type_rh')
                                ->orderBy('id', 'ASC')
                                ->get();
                            break;

                        case 'customer_related_agent_user':
                            $repository = new CustomerRepository();
                            $result["customerRelatedAgentAndUserList"] = $repository->getRelatedAgentAndUserList($entity->value);
                            break;

                        case 'customer_contractor':
                            $repository = new CustomerContractorRepository();
                            $result["customerContractorList"] = $repository->getList($entity->criteria);
                            break;

                        case 'customer_contractor_simple':
                            $repository = new CustomerContractorRepository();
                            $result["customerContractorList"] = $repository->getCustomerContractorList($entity->criteria);
                            break;

                        case 'customer_user_customer_list':
                            $repository = new CustomerUserRepository();
                            $result["customerList"] = $repository->getCustomerList($entity->criteria);
                            break;

                        case 'dashboard_year':
                            $data = [];
                            $currentYear = Carbon::now('America/Bogota')->year;

                            do {
                                $data[] = [
                                    'item' => $currentYear,
                                    'value' => $currentYear,
                                ];
                                $currentYear--;
                            } while ($currentYear >= 2014);

                            $result["yearList"] = $data;
                            break;

                        case 'customer_document_type':
                            $repository = new CustomerRepository();
                            $result['customerDocumentType'] = $repository->getDocumentTypeList($entity->value);
                            break;

                        case 'customer_employee_custom_filter_field':
                            $result['customerEmployeeCustomFilterField'] = CustomerEmployeeRepository::getCustomFilters();
                            break;

                        case 'customer_employee_document_expiration_custom_filter_field':
                            $result['customerEmployeeDocumentExpirationCustomFilterField'] = CustomerEmployeeDocumentRepository::getCustomExpirationFilters();
                            break;

                        case 'customer_employee_document_type':
                            $repository = new CustomerRepository();
                            $result['customerEmployeeDocumentType'] = $repository->getEmployeeDocumentTypeList($entity->value);
                            break;

                        case 'customer_diagnostic_prevention_program':
                            $repository = new CustomerDiagnosticRepository();
                            $result['customerDiagnosticPreventionProgram'] = $repository->getPrograms($entity->value);
                            break;

                        case 'customer_management_program':
                            $repository = new CustomerManagementRepository();
                            $result['customerManagementProgram'] = $repository->getPrograms($entity->value);
                            break;

                        case 'customer_management_category':
                            $repository = new CustomerManagementRepository();
                            $result['customerManagementCategoryList'] = $repository->getCategoryList($entity->criteria);
                            $result['customerManagementQuestionList'] = $repository->getQuestionList($entity->criteria);
                            break;

                        case 'management_program':
                            $result['managementProgramList'] = DB::table('wg_program_management')
                                ->where('status', 'Activo')
                                ->orderBy('name')
                                ->get();
                            break;

                        case 'customer_management_indicator_filters':
                            $repository = new CustomerManagementRepository();
                            $result['customer_management_indicator_years'] = $repository->getYearList($entity->criteria);
                            $result['customer_management_indicator_program_list'] = $repository->getProgramList($entity->criteria);
                            $result['customer_management_indicator_workplace_id'] = $repository->getWorkplaceList($entity->criteria);
                            break;

                        case 'customer_evaluation_minimum_stardard_cycle':
                            $repository = new CustomerEvaluationMinimumStandardRepository();
                            $result['customerEvaluationMinimumStandardCycle'] = $repository->getCycles($entity->value);
                            break;

                        case 'customer_evaluation_minimum_stardard_cycle_0312':
                            $repository = new CustomerEvaluationMinimumStandard0312Repository();
                            $result['customerEvaluationMinimumStandardCycle'] = $repository->getCycles($entity->criteria);
                            break;

                        case 'customer_evaluation_minimum_stardard_year_0312':
                            $repository = new CustomerEvaluationMinimumStandard0312Repository();
                            $result['customerEvaluationMinimumStandardYear'] = $repository->getYears($entity->criteria);
                            break;

                        case 'customer_evaluation_minimum_stardard_report_0312':
                            $repository = new CustomerEvaluationMinimumStandard0312Repository();
                            $result['customerEvaluationMinimumStandardReport'] = $repository->getReport($entity->criteria);
                            break;

                        case 'customer_evaluation_minimum_stardard_parent_0312':
                            $repository = new CustomerEvaluationMinimumStandard0312Repository();
                            $result['customerEvaluationMinimumStardardParent'] = $repository->getParent($entity->criteria);
                            break;

                        case 'customer_evaluation_minimum_stardard_0312':
                            $repository = new CustomerEvaluationMinimumStandard0312Repository();
                            $result['customerEvaluationMinimumStardard'] = $repository->getChildren($entity->criteria);
                            break;

                        case 'customer_evaluation_minimum_stardard_item_0312':
                            $repository = new CustomerEvaluationMinimumStandard0312Repository();
                            $result['customerEvaluationMinimumStardardItem'] = $repository->getItems($entity->criteria);
                            $result['customerEvaluationMinimumStardardItem'] = array_map(function ($row) {
                                $row->description = "{$row->numeral} - {$row->description}";
                                return $row;
                            }, $result['customerEvaluationMinimumStardardItem']);
                            break;

                        case 'minimum_stardard_rate_0312':
                            $repository = new MinimumStandard0312Repository();
                            $result['rate'] = $repository->getRateList();
                            $result['rateReal'] = $repository->getRealRateList();
                            break;

                        case 'customer_road_safety_cycle':
                            $repository = new CustomerRoadSafetyRepository();
                            $result['customerRoadSafetyCycle'] = $repository->getCycles($entity->value);
                            break;

                        case 'customer_contract_year':
                            $repository = new CustomerContractDetailRepository();
                            $result['customerContractYear'] = $repository->getYearList($entity->value);
                            break;

                        case 'customer_contract_period':
                            $repository = new CustomerContractDetailRepository();
                            $result['customerContractPeriod'] = $repository->getPeriodList($entity->value);
                            break;

                        case 'customer_contract_safety_inspection_period':
                            $repository = new CustomerContractSafetyInspectionRepository();
                            $result['customerContractSafetyInspectionPeriod'] = $repository->getPeriodList($entity->value);
                            break;

                        case 'customer_contract_safety_inspection_list':
                            $repository = new CustomerContractSafetyInspectionRepository();
                            $result['customerContractSafetyInspectionList'] = $repository->getPrepareList($entity->value);
                            break;

                        case 'customer_contract_safety_inspection_header_fields':
                            $repository = new CustomerContractSafetyInspectionRepository();
                            $result['customerContractSafetyInspectionHeaderFields'] = $repository->getHeaderFields($entity->value);
                            break;

                        case 'customer_safety_inspection_list':
                            $repository = new CustomerSafetyInspectionRepository();
                            $result['customerSafetyInspectionList'] = $repository->getPrepareList($entity->value);
                            break;

                        case 'customer_safety_inspection_header_fields':
                            $repository = new CustomerSafetyInspectionRepository();
                            $result['customerSafetyInspectionHeaderFields'] = $repository->getHeaderFields($entity->value);
                            break;

                        case 'road_safety-cycle-40595':
                            $result['road_safety_cycle_40595'] = DB::table('wg_road_safety_cycle_40595')->get();
                            break;

                        case 'customer_road_safety_cycle_40595':
                            $repository = new CustomerRoadSafety40595Repository();
                            $result['customerRoadSafetyCycle'] = $repository->getCycles($entity->criteria);
                            break;

                        case 'road_safety_rate_40595':
                            $repository = new CustomerRoadSafety40595Repository();
                            $result['rate'] = $repository->getRateList();
                            $result['rateReal'] = $repository->getRealRateList();
                            break;

                        case 'customer_road_safety_year_40595':
                            $repository = new CustomerRoadSafety40595Repository();
                            $result['customerRoadSafetyYear'] = $repository->getYears($entity->criteria);
                            break;

                        case "criteria_operators":
                            $result["criteriaOperatorList"] = $this->getCriteriaOperators();
                            break;

                        case "criteria_conditions":
                            $result["criteriaConditionList"] = $this->getCriteriaConditions();
                            break;

                        case "active_options":
                            $result["activeOptions"] = $this->getActiveOptions();
                            break;

                        case "yes_no_options":
                            $result["activeOptions"] = $this->getYesNoOptions();
                            break;

                        case "customer_document_verified":
                            $result["customer_document_verified"] = $this->getDocumentVerifiedOptions();
                            break;

                        case "customer_employee_document_management":
                            $result["customer_employee_document_management"] = $this->getDocumentManagementOptions();
                            break;

                        case "yes_no_letter_options":
                            $result["activeOptions"] = $this->getYesNoLetterOptions();
                            break;

                        case "month_options":
                            $result["monthOptions"] = $this->monthOptions();
                            break;

                        case 'investigation_tracking_filter_field':
                            $result['investigationTrackingFilterField'] = CustomerInvestigationAlRepository::getCustomFilters();
                            break;

                        case "shift_condition_list":
                            $result['shiftConditionList'] = DB::table('wg_covid_bolivar_question')
                                ->select(
                                    DB::raw('0 AS id'),
                                    'name',
                                    'code AS covidBolivarQuestionCode',
                                    'score',
                                    'is_master AS isMaster',
                                    DB::raw('0 AS isActive')
                                )
                                ->where('is_workplace_shift_condition', 1)
                                ->orderBy('shift_sort')
                                ->get();

                            foreach ($result['shiftConditionList'] as $item) {
                                $item->isMaster = $item->isMaster == 1;
                                switch ($item->covidBolivarQuestionCode) {
                                    case 'P001':
                                        $item->name = "ESTADO DE EMBARAZO";
                                        break;

                                    case 'F003':
                                        $item->name = "CONVIVENCIA CON MAYORES DE 60 AÑOS";
                                        break;

                                    case 'F005':
                                        $item->name = "CONVIVENCIA CON PERSONAL DE LA SALUD";
                                        break;
                                }
                            }

                            break;

                        case "customer_express_matrix_workplace_list":
                            $result["customerExpressMatrixWorkplaceList"] = (new CustomerConfigWorkplaceRepository())->getList($entity->criteria);
                            break;

                        case "customer_express_matrix_workplace_process_list":
                            $result["customerExpressMatrixWorkplaceList"] = (new CustomerConfigWorkplaceRepository())->getWithProcessList($entity->criteria);
                            break;

                        case "customer_express_matrix_process_list":
                            $result["customerExpressMatrixProcessList"] = (new CustomerConfigProcessExpressRepository())->getList($entity->criteria);
                            break;

                        case "customer_express_matrix_job_list":
                            $result["customerExpressMatrixJobList"] = (new CustomerConfigJobExpressRepository())->getList($entity->criteria);
                            break;

                        case "customer_express_matrix_activity_list":
                            $result["customerExpressMatrixActivityList"] = (new CustomerConfigActivityExpressRepository())->getList($entity->criteria);
                            break;

                        case "customer_express_matrix_workplace_stats":
                            $result["customerExpressMatrixWorkplaceStats"] = (new CustomerConfigQuestionExpressRepository())->getWorkplaceStats($entity->criteria);
                            break;

                        case "customer_express_matrix_workplace_with_qa":
                            $result["customerExpressMatrixWorkplaceList"] = (new CustomerConfigQuestionExpressRepository())->getWorkplaceList($entity->criteria);
                            break;

                        case "customer_express_matrix_hazard_stats_list":

                            $yearList = (new CustomerConfigQuestionExpressInterventionRepository())->getYearList($entity->criteria);

                            if (count($yearList) > 0 && !isset($entity->criteria->year)) {
                                $entity->criteria->year = $yearList[0]->value;
                            }

                            $result["customerExpressMatrixQuestionInterventionYearList"] = $yearList;
                            $result["customerExpressMatrixHazardStatsList"] = (new CustomerConfigQuestionExpressRepository())->getHazardStats($entity->criteria);
                            break;

                        case "customer_express_matrix_hazard_general_stats":

                            $yearList = (new CustomerConfigQuestionExpressInterventionRepository())->getYearList($entity->criteria);

                            if (count($yearList) > 0 && !isset($entity->criteria->year)) {
                                $entity->criteria->year = $yearList[0]->value;
                            }

                            $result["customerExpressMatrixQuestionInterventionYearList"] = $yearList;
                            $result["customerExpressMatrixHazardGeneralStats"] = (new CustomerConfigQuestionExpressRepository())->getHazardGeneralStats($entity->criteria);
                            break;

                        case "customer_express_matrix_hazard_list":
                            $result["customerExpressMatrixHazardList"] = (new CustomerConfigQuestionExpressRepository())->getHazardList($entity->criteria);
                            break;

                        case "customer_express_matrix_question_intervention_list":
                            $result["customerExpressMatrixQuestionInterventionList"] = (new CustomerConfigQuestionExpressInterventionRepository())->getList($entity->criteria);
                            break;

                        case "customer_express_matrix_question_intervention_year_list":
                            $result["customerExpressMatrixQuestionInterventionYearList"] = (new CustomerConfigQuestionExpressInterventionRepository())->getYearList($entity->criteria);
                            break;

                        case "customer_special_matrix_office_business_unit_list":
                            $result["customerSpecialMatrixOfficeList"] = (new CustomerConfigOfficeSpecialRepository())->getWithBusinessUnitList($entity->criteria);
                            break;

                        case "customer_special_matrix_office_list":
                            $result["customerSpecialMatrixOfficeList"] = (new CustomerConfigOfficeSpecialRepository())->getList($entity->criteria);
                            break;

                        case "customer_special_matrix_process_list":
                            $result["customerSpecialMatrixProcessList"] = (new CustomerConfigProcessSpecialRepository())->getList($entity->criteria);
                            break;

                        case "customer_special_matrix_business_unit_list":
                            $result["customerSpecialMatrixBusinessUnitList"] = (new CustomerConfigBusinessUnitSpecialRelationRepository())->getList($entity->criteria);
                            break;

                        case "customer_special_matrix_office_process_subprocess_list":
                            $result["customerSpecialMatrixOfficeProcessSubprocessList"] = (new CustomerConfigBusinessUnitSpecialRelationRepository())->getProcessSubprocessList($entity->criteria);
                            break;

                        case "customer_special_matrix_office_hazard_list":
                            $result["customerSpecialMatrixOfficeList"] = (new CustomerConfigHazardSpecialRelationRepository())->getOfficeSpecialListWithHazard($entity->criteria);
                            break;

                        case "customer_config_special_matrix_hazard_classification_list":
                            $result["customerSpecialMatrixHazardClassificationList"] = (new CustomerConfigHazardSpecialRelationRepository())->getClassificationList($entity->criteria);
                            break;

                        case "customer_special_matrix_area_job_list":
                            $cList = (new CustomerConfigAreaJobSpecialRelationRepository())->getList($entity->criteria);
                            if ($cList instanceof Collection) {
                                $cList = $cList->values();
                            }
                            $result["customerSpecialMatrixAreaJobList"] = $cList;
                            break;

                        case "customer_special_matrix_hazard_filter_field":
                            $result["customerSpecialMatrixHazardFilterField"] = CustomerConfigHazardSpecialRelationRepository::getCustomFilters();
                            break;

                        case "customer_special_matrix_area_available_list":
                            $result["customerSpecialMatrixAreaAvailableList"] = (new CustomerConfigAreaSpecialRepository)->getList($entity->criteria);
                            break;

                        case "customer_special_matrix_job_available_list":
                            $result["customerSpecialMatrixJobAvailableList"] = (new CustomerConfigJobSpecialRepository)->getList($entity->criteria);
                            break;

                        case "customer_special_matrix_activity_available_list":
                            $result["customerSpecialMatrixActivityAvailableList"] = (new CustomerConfigActivitySpecialRepository)->getList($entity->criteria);
                            break;

                        case "customer_config_job_activity_list":
                            $respository = new CustomerConfigJobActivityRepository();
                            $result["customerConfigJobActivityList"] = $respository->allList($entity->value);
                            break;

                        case "customer_config_acitivty_hazard_workplace_list":
                            $result["customerConfigAcitivtyHazardWorkplaceList"] = (new CustomerConfigActivityHazardRepository())->getWorkplaceList($entity->criteria);
                            break;

                        case 'absenteeism_disability_workplace_list':
                            $repository = new CustomerAbsenteeismDisabilityRepository();
                            $result["workplaceList"] = $repository->getCustomerWorkplaceList($entity->criteria->customerId, $entity->criteria->period);
                            break;

                        case "customer_config_acitivty_hazard_process_list":
                            $result["customerConfigAcitivtyHazardProcessList"] = (new CustomerConfigActivityHazardRepository())->getProcessList($entity->criteria);
                            break;

                        case "customer_config_acitivty_hazard_classification_list":
                            $result["customerConfigAcitivtyHazardClassificationList"] = (new CustomerConfigActivityHazardRepository())->getClassificationList($entity->criteria);
                            break;

                        case "customer_workplace_shift_list":
                            $result["customerMatrixWorkplaceShiftList"] = (new CustomerConfigWorkplaceRepository())->getWithShiftList($entity->criteria);
                            break;

                        case "customer_config_acitivty_hazard_filter_field":
                            $result["customerAbsenteeismDisabilityFilterField"] = CustomerConfigActivityHazardRepository::getCustomFilters();
                            break;

                        case "customer_absenteeism_disability_filter_field":
                            $result["customerAbsenteeismDisabilityFilterField"] = CustomerAbsenteeismDisabilityRepository::getCustomFilters();
                            break;

                        case "customer_absenteeism_disability_filter_years":
                            $result["customerAbsenteeismDisabilityFilterYears"] = CustomerAbsenteeismDisabilityRepository::getFilterYears($entity->value);
                            break;

                        case "customer_covid_filter_field":
                            $result["customerCovidFilterField"] = CustomerCovidRepository::getCustomFilters();
                            break;

                        case "customer_covid_bolivar_filter_field":
                            $result["customerCovidFilterField"] = CustomerCovidBolivarRepository::getCustomFilters();
                            break;

                        case "customer_unsafe_act_massive_filter_field":
                            $result["customerUnsafeActMassiveFilterField"] = CustomerUnsafeActRepository::getCustomMassiveFilters();
                            break;

                        case "absenteeism_disability_causes_full":
                            $causes = SystemParameter::whereNamespace("wgroup")->whereGroup('absenteeism_disability_causes')
                                ->orderBy('id', 'ASC')
                                ->get()
                                ->toArray();

                            $causesAdmin = SystemParameter::whereNamespace("wgroup")->whereGroup('absenteeism_disability_causes_admin')
                                ->orderBy('id', 'ASC')
                                ->get()
                                ->toArray();

                            $result["absenteeism_disability_causes_full"] = array_merge($causes, $causesAdmin);
                            break;

                        case "absenteeism_disability_causes_only":
                            $causes = SystemParameter::whereNamespace("wgroup")->whereGroup('absenteeism_disability_causes')
                                ->orderBy('id', 'ASC')
                                ->whereIn("value", ["EG", "AL", "LM", "LP", "ELC"])
                                ->get()
                                ->toArray();

                            $result["absenteeism_disability_causes_only"] = $causes;
                            break;

                        case "absenteeism_disability_causes_years":
                            $repository = new  CustomerAbsenteeismDisabilityRepository;
                            $result["absenteeism_disability_causes_years"] = $repository->getCustomerWorkplacePeriodList($entity->value);
                            break;

                        case "absenteeism_disability_years":
                            $repository = new  CustomerAbsenteeismDisabilityRepository;
                            $result["absenteeism_disability_causes_years"] = $repository->getCustomeAbsenteeismDisabilityPeriodList($entity->value);
                            break;

                        case "absenteeism_disability_indicator_years":
                            $result["absenteeism_disability_indicator_years"] = DB::table('wg_customer_absenteeism_indicator')
                                ->select(DB::raw('YEAR(`periodDate`) as item'), DB::raw('YEAR(`periodDate`) as value'))
                                ->whereRaw('customer_id = ?', [$entity->value])
                                ->groupBy(DB::raw('YEAR(`periodDate`)'), 'customer_id')
                                ->orderBy(DB::raw('YEAR(`periodDate`)'), 'DESC')
                                ->get();
                            break;

                        case "absenteeism_disability_indicator_workplaces":
                            $repository = new CustomerAbsenteeismIndicatorRepository();
                            $result["absenteeism_disability_indicator_workplaces"] = $repository->getWorkplaceList($entity->value);
                            break;

                        case "absenteeism_disability_day_charged_is_death":
                            $respository = new CustomerAbsenteeismDisabilityDayChargedRepository();
                            $result["absenteeismDisabilityDayChargedIsDeath"] = $respository->getDayChargedIsDeathValue();
                            break;

                        case "absenteeism_disability_current_minimum_daily":
                            $result["absenteeismDisabilityCurrentMinimumDaily"] = DB::table('system_parameters')
                                ->select(
                                    'item as value'
                                )
                                ->where('group', 'minimo_diario_vigente')
                                ->where('namespace', 'wgroup')
                                ->where('value', Carbon::now()->year)
                                ->first();
                            break;

                        case "config_day_charged_part":
                            $result["configDayChargedPart"] = DB::table('wg_config_day_charged_part')
                                ->select(
                                    'id',
                                    'name'
                                )
                                ->get();
                            break;

                        case "customer_unsafe_act_years":
                            $result["customerUnsafeActYears"] = (new CustomerUnsafeActRepository)->getYearList($entity->criteria);
                            break;

                        case "customer_unsafe_act_workplace":
                            $result["customerUnsafeActWorkplace"] = (new CustomerUnsafeActRepository)->getWokplaceList($entity->criteria);
                            break;

                        case "unsafe_act_risk_type":
                            $result["unsafe_act_risk_type"] = DB::table('wg_config_job_activity_hazard_classification')
                                ->select("id", 'name', 'code')
                                ->orderBy('name', 'DESC')
                                ->get();
                            break;

                        case "unsafe_act_classification":
                            $result["unsafe_act_classification"] = DB::table('wg_config_job_activity_hazard_type')
                                ->select("id", 'name', 'code')
                                ->where("classification_id", $entity->value)
                                ->orderBy('name', 'DESC')
                                ->get();
                            break;

                        case "work_medicine_complementary_test_result":
                            $repository = new SystemParameter();
                            $result["workMedicineComplementaryTestResult"] = $repository->whereNamespace("wgroup")
                                ->whereGroup($entity->name)
                                ->whereCode($entity->value)
                                ->orderBy('id', 'ASC')
                                ->get();
                            break;

                        case "certificate_program":
                            $entityModel = new CertificateProgram();
                            $result["certificateProgram"] = $entityModel->where("isActive", 1)
                                ->orderBy('id', 'ASC')
                                ->get();
                            break;

                        case "certificate_grade_participant_year":
                            $entityModel = new CertificateGradeParticipantModel();
                            $result["certificateGradeParticipantYear"] = $entityModel->where("hasCertificate", 1)
                                ->select(
                                    DB::raw('YEAR(certificateExpirationAt) AS item'),
                                    DB::raw('YEAR(certificateExpirationAt) AS value')
                                )
                                ->orderBy(DB::raw('YEAR(certificateExpirationAt)'), 'DESC')
                                ->groupBy(DB::raw('YEAR(certificateExpirationAt)'))
                                ->get();
                            break;

                        case "contractor_customer":
                            $contractorClassification = SystemParameter::where('group', 'wg_contractor_classification_dashboard')
                                ->where('code', 'child')
                                ->get()
                                ->map(function ($item) {
                                    return $item->value;
                                })
                                ->toArray();

                            $result["contractorCustomer"] = Customer::where("id", "<>", $entity->value)
                                ->whereIn("classification", $contractorClassification ? $contractorClassification : ["Contratista"])
                                ->select('id', 'businessName', 'documentNumber')
                                ->whereNotIn('id', function ($q) use ($entity) {
                                    $q->select('contractor_id')
                                        ->from('wg_customer_contractor')
                                        ->where("customer_id", $entity->value);
                                })->get();

                            break;

                        case "resource_library_custom_filter_field":
                            $result["resourceLibraryCustomFilterField"] = ResourceLibraryRepository::getCustomFilters();
                            break;


                        case "template_manage_custom_filter_field":
                            $result["templateManageCustomFilterField"] = TemplateManageRepository::getCustomFilters();
                            break;

                        case 'customer_project_year':
                            $repository = new CustomerProjectRepository();
                            $result['yearList'] = $repository->allYears();
                            break;

                        case 'customer_project_task_type':
                            $repository = new CustomerProjectRepository();
                            $result['projectTaskTypeList'] = $repository->allTaskType();
                            break;

                        case 'customer_project_summary':
                            $repository = new CustomerProjectRepository();
                            $result['projectSummary'] = $repository->getSummary($entity->criteria);
                            $result['chartProjectSummary'] = $repository->getSummaryChartPie($entity->criteria);
                            break;

                        case 'customer_project_list':
                            $repository = new CustomerProjectRepository();
                            $result['projectList'] = $repository->getList($entity->criteria);
                            break;

                        case 'customer_project_agent_task_timeline':
                            $repository = new CustomerProjectAgentTaskRepository();
                            $result['projectAgentTaskTimeLine'] = $repository->getListTimeLine($entity->criteria);
                            break;

                        case 'customer_internal_project_year':
                            $repository = new CustomerInternalProjectRepository();
                            $result['yearList'] = $repository->allYears();
                            break;

                        case 'customer_internal_certificate_program':
                            $repository = new CustomerInternalCertificateGradeRepository();
                            $result['customerInternalCertificateProgram'] = $repository->getProgramList($entity->criteria);
                            break;

                        case "certificate_search_custom_filter_field":
                            $result["certificateSearchCustomFilterField"] = CertificateGradeParticipantRepository::getSearchCustomFilters();
                            break;

                        case 'minimum-standard-cycle-0312':
                            $result['minimum_standard_cycle_0312'] = DB::table('wg_config_minimum_standard_cycle_0312')->get();
                            break;

                        case 'minimum-standard-parent-0312':
                            $repository = new MinimumStandard0312Repository();
                            $result['minimum_standard_parent_0312'] = $repository->getParentList();
                            break;

                        case 'minimum-standard-0312':
                            $repository = new MinimumStandard0312Repository();
                            $result['minimum_standard_0312'] = $repository->getChildList();
                            break;

                        case 'config_job_activity_hazard_classification_list':
                            $result['configJobActivityhazardClassificationList'] = (new ConfigJobActivityHazardClassificationRepository)->getList();
                            break;

                        case 'config_job_activity_hazard_type_list':
                            $result['configJobActivityhazardTypeList'] = (new ConfigJobActivityHazardTypeRepository)->getList($entity->criteria);
                            break;

                        case 'config_job_activity_hazard_description_list':
                            $result['configJobActivityhazardDescriptionList'] = (new ConfigJobActivityHazardDescriptionRepository)->getList($entity->criteria);
                            break;

                        case 'config_job_activity_hazard_effect_list':
                            $result['configJobActivityhazardEffectList'] = (new ConfigJobActivityHazardEffectRepository)->getList($entity->criteria);
                            break;

                        case 'config_special_matrix_hazard_measure_list':
                            $result['configSpecialMatrixHazardMeasureNdList'] = (new ConfigGeneralRepository)->getListND();
                            $result['configSpecialMatrixHazardMeasureNeList'] = (new ConfigGeneralRepository)->getListNE();
                            $result['configSpecialMatrixHazardMeasureNcList'] = (new ConfigGeneralRepository)->getListNC();
                            $result['configSpecialMatrixHazardProbabilityLevelList'] = (new ConfigGeneralRepository)->getListProbabilityLevel();
                            $result['configSpecialMatrixHazardRiskLevelList'] = (new ConfigGeneralRepository)->getListRiskLevel();
                            break;

                        case 'covid_question_list':
                            $result['covidQuestionList'] = (new CovidQuestionRepository)->getList();
                            break;

                        case 'covid_bolivar_question_list':
                            $result['covidPersonalQuestionList'] = (new CovidBolivarQuestionRepository)->getPersonalList();
                            $result['covidFamiliarQuestionList'] = (new CovidBolivarQuestionRepository)->getFamiliarList();
                            break;

                        case 'covid_question_group_list':
                            $result['covidQuestionGroupList'] = (new CovidQuestionRepository)->getGroupList();
                            break;

                        case 'customer_covid_risk_level_list':
                            $result['customerCovidRiskLevelList'] = (new CovidQuestionRepository)->getRiskLevelList();
                            break;

                        case 'customer_covid_bolivar_health_condition_modal':
                            $result['healthCondition'] = (new CustomerCovidBolivarDailyRepository)->getHealthCondition();
                            $result['workType'] = (new CustomerCovidBolivarDailyRepository)->getworkType();
                            $result['diagnosticExam'] = (new CustomerCovidBolivarDailyRepository)->getDiagnosticExam();
                            $result['covidDailySymptomsQuestionList'] = (new CovidBolivarQuestionRepository)->getSymptomsList();
                            $result['covidDailySymptomsBreathingList'] = (new CovidBolivarQuestionRepository)->getBreathingList();
                            $result['covidRiskLevel'] = (new CovidBolivarQuestionRepository)->getRiskLevels();
                            break;

                        case 'customer_covid_bolivar_daily_list':
                            $result['covidDailyList'] = (new CustomerCovidBolivarDailyRepository)->getDailyList($entity->value);
                            break;

                        case 'customer_covid_date_list':
                            $result['customerCovidDateList'] = (new CustomerCovidRepository)->getDateList($entity->criteria);
                            break;

                        case 'customer_covid_period_list':
                            $result['customerCovidPeriodList'] = (new CustomerCovidRepository)->getPeriodList($entity->criteria);
                            break;

                        case 'customer_covid_bolivar_indicators_list':
                            $result['customerCovidBolivarDateList'] = (new CustomerCovidBolivarRepository)->getDateList($entity->criteria);
                            $result['customerCovidBolivarPeriodList'] = (new CustomerCovidBolivarRepository)->getPeriodList($entity->criteria);
                            break;

                        case 'customer_covid_workplace':
                            $result['covidWorkplaceList'] = (new CustomerCovidRepository)->getCovidWorkplaceList($entity->criteria);
                            break;

                        case 'customer_covid_bolivar_workplace':
                            $result['covidWorkplaceList'] = (new CustomerCovidBolivarRepository)->getCovidWorkplaceList($entity->criteria);
                            break;

                        case 'customer_covid_contractor':
                            $result['covidContractorList'] = (new CustomerCovidRepository)->getCovidContractorList($entity->criteria);
                            break;

                        case 'config_job_activity_hazard_classification':
                            $result["dangerTypeList"] = DB::table('wg_config_job_activity_hazard_classification')
                                ->select('id', 'name as item', 'id as value')
                                ->get();
                            break;

                        case 'configuration_help_roles_profile_type':
                            $result["typeList"] = SystemParameter::whereNamespace("wgroup")->whereGroup("configuration_help_roles_profile_type")
                                ->orderBy('id', 'ASC')
                                ->get();
                            break;

                        case "experience_vr":
                            $result["experience_vr"] = SystemParameter::whereNamespace("wgroup")->whereGroup("experience_vr")
                                ->select("id", "item", "value", "item as type")
                                ->get();
                            break;

                        case "customer_vr_employee_list":
                            $options = SystemParameter::whereNamespace("wgroup")->whereGroup("experience_scene_application")
                                ->select("id", "item", "value")
                                ->where("value", "!=", "NU")
                                ->get();

                            $result["applicationOptions"] = $options;
                            $result["experienceList"] = ExperienceRepository::getExperienceList($entity->criteria, $options);
                            break;

                        case "customer_vr_employee_list_options":
                            $options = SystemParameter::whereNamespace("wgroup")->whereGroup("experience_scene_application")
                                ->select("id", "item", "value")
                                ->get();

                            $result["applicationOptions"] = $options;
                            break;

                        case "customer_vr_employee_experience_list":
                            $result["employeeExperienceList"] = ExperienceRepository::getEmployeeExperienceList($entity->criteria);
                            break;

                        case "customer_employee_vr_period_list":
                            $result["employeeVrPeriodList"] = ExperienceRepository::getEmployeeExperiencePeriodList($entity->criteria);
                            break;

                        case "customer_vr_employee_experience_stats":
                            $result["experienceStats"] = ExperienceRepository::getStats($entity->criteria);
                            break;

                        case "customer_vr_employee_filter_field":
                            $result["customerVrEmployeeFilterField"] = CustomerVrEmployeeRepository::getCustomFilters();
                            break;

                        case "customer_vr_employee_observation_options":
                            $result["observationOptions"] = SystemParameter::whereNamespace("wgroup")->whereGroup("experience_scene_observation_type")
                                ->select("id", "item", "value")
                                ->get();
                            break;

                        case "customer_vr_employee_evaluation_options":
                            $result["evaluationOptions"] = SystemParameter::whereNamespace("wgroup")->whereGroup("experience_evaluation")
                                ->select("id", "item", "value")
                                ->get();
                            break;

                        case "customer_vr_employee_historical_period_list":
                            $result["periodList"] = ExperienceAnswerRepository::getPeriodList($entity->criteria);
                            break;

                        case "customer_vr_employee_historical_period_observation_list":
                            $result["periodList"] = ExperienceAnswerRepository::getPeriodObservationsList($entity->criteria);
                            break;

                        case "customer_vr_employee_indicators_period_list":
                            $periods = ExperienceAnswerRepository::getIndicatorsPeriodList($entity->criteria);
                            $result["periodList"] = $periods;
                            if (isset($entity->criteria->period) && !$entity->criteria->period) {
                                $currentPeriod = count($periods) ? $periods[0]->value : null;
                                $entity->criteria->period = $currentPeriod;
                            }
                            $result["experienceFilterList"] = ExperienceRepository::getEmployeeExperienceFilterList($entity->criteria);
                            break;

                        case "customer_config_office_special_exchange_control_filter_field":
                            $result["filterField"] = CustomerConfigOfficeSpecialExchangeControlRepository::getCustomFilters();
                            break;

                        case "customer_config_office_special_exchange_control_clasification":
                            $result["clasificationList"] = SystemParameter::whereNamespace("wgroup")->whereGroup("office_special_exchange_control_clasitication")
                                ->select("id", "item", "value")
                                ->get();
                            break;

                        case "customer_vr_employee_experience_scenes_customer":
                            $result["experienceOptionsList"] = CustomerParameter::whereCustomerId($entity->value)
                                ->join("system_parameters", function ($join) {
                                    $join->on("wg_customer_parameter.item", "=", "system_parameters.id");
                                })
                                ->where("wg_customer_parameter.namespace", "wgroup")
                                ->where("wg_customer_parameter.group", "experienceVR")
                                ->select("wg_customer_parameter.id", "system_parameters.item", "system_parameters.value")
                                ->get();

                            $result["sceneOptionsList"] = SystemParameter::whereNamespace("wgroup")->whereGroup("experience_scene")
                                ->select("id", "item", "value", "code")
                                ->get();

                            break;

                        case "positiva_fgn_consultant_sectional":
                            $result["regionalList"] = ConsultantRepository::getRegionalList();
                            $result["sectionalList"] = ConsultantRepository::getSectionalList($entity->criteria);
                            break;

                        case "positiva_fgn_consultant_all_sectional":
                            $result["sectionalList"] = ConsultantRepository::getAllSectionalList($entity->criteria);
                            break;

                        case "positiva_fgn_all_regional_sectional":
                            $result["sectionalList"] = ConsultantRepository::getAllSectionalList2();
                            break;

                        case "positiva_fgn_consultant_filter_field":
                            $result["filterField"] = ConsultantRepository::getCustomFilters();
                            break;

                        case "positiva_fgn_campus_filter_field":
                            $result["filterField"] = CampusRepository::getCustomFilters();
                            break;

                        case "positiva_fgn_vendor_filter_field":
                            $result["filterField"] = VendorRepository::getCustomFilters();
                            break;

                        case "positiva_fgn_activity_group":
                            $result["groupList"] = $this->getSystemParameter($entity->name);
                            break;

                        case "customer_parameter":
                            $customerId = $entity->criteria->customerId;
                            $group = $entity->criteria->group;
                            $result[$group] = CustomerParameterService::getCustomerParameter($customerId, $group, true);
                            break;

                        case "customer_job_conditions_filter_field":
                            $result["filterField"] = JobConditionRepository::getCustomFilters();
                            break;

                        case 'job_conditions_intervention_plan_responsible':
                            $result["responsible"] = SystemParameter::whereNamespace("wgroup")->whereGroup("wg_occupational_investigation_responsible_type")
                                ->orderBy('id', 'ASC')
                                ->get();
                            break;

                        case 'customer_arl_years':
                            $repository = new CustomerProjectRepository();
                            $result['customerArlYears'] = $repository->getAllYearsContributations($entity->criteria->customerId);
                            break;

                        case 'customer_arl_service_years':
                            $repository = new CustomerArlServiceCostRepository();
                            $result['customerArlServiceYears'] = $repository->getAllYears($entity->criteria->customerId);
                            break;

                        case "customer_vr_employee_satisfaction_indicator_years":
                            $repository = new SatisfactionIndicatorRepository();
                            $result["customerVrSatisfactionIndicatorYears"] =  $repository->getAllYears($entity->criteria->customerId);
                            break;

                        case "customer_vr_employee_satisfactions_answers_types":
                            $repository = new SatisfactionIndicatorRepository();
                            $result["customerVrEmployeeSatisfactionsAnswersTypes"] =  $repository->getAllAnswerTypes();
                            break;

                        case "customer_employee_indicators_years":
                            $result["customerEmployeeIndicatorsYears"] = CustomerEmployeeIndicatorRepository::getYears($entity->criteria->customerId);
                            break;

                        case "customer_comercial_agents";
                            $repository = new LicenseRepository();
                            $result["CustomerComercialAgents"] = $repository->getComercialAgents($entity->criteria->customerId);
                            break;

                        case "customer_contributions_detail_balance_months";
                            $repository = new ContributionRepository();
                            $customerId = $entity->criteria->customerId;
                            $year = $entity->criteria->year;
                            $result["customerContributionsDetailBalanceMonths"] = $repository->getSalesMonth($customerId, $year);
                            $result["customerContributionsDetailBalanceTypes"] = $repository->getSalesTypesByMonth($customerId, $year);
                            break;

                        case 'customer_project_administrators':
                            $type = $entity->criteria->type;
                            $customerId = $entity->criteria->customerId;
                            $year = $entity->criteria->year ?? Carbon::now()->year;
                            $month = $entity->criteria->month ?? Carbon::now()->month;

                            $repository = new CustomerProjectRepository();
                            $result['projectAdministratorList'] = $repository->getAllUserAdministrators($year, $month, $type, $customerId);
                            break;

                        case 'absenteeism_disability_customer_workplace':
                            $customerId = $entity->criteria->customerId;
                            $cause = $entity->criteria->cause;
                            $years = $entity->criteria->years;

                            $repository = new CustomerAbsenteeismDisabilityRepository();
                            $result["workplaceList"] = $repository->getWorkplaceList($customerId, $cause, $years);
                            break;

                        case 'customer_information_relationships':
                            $repository = new CustomerContractorRepository();
                            $result["customerInformationRelationships"] = $repository->getCustomerRelationships($entity->criteria);
                            break;

                        case 'customer_standard_minimal_periods':
                            $customerId = $entity->criteria->customerId;
                            $result["customerStandardMinimalPeriods"] = CustomerEvaluationMinimumStandard0312Service::getPeriodsByCustomer($customerId);
                            break;

                        case 'customer_road_safety_periods':
                            $customerId = $entity->criteria->customerId;
                            $result["customerRoadSafetyPeriods"] = CustomerRoadSafetyService::getYearsByCustomerId($customerId);
                            break;

                        case 'customer_sst_periods':
                            $customerId = $entity->criteria->customerId;

                            $repository = new CustomerDiagnosticRepository();
                            $result["customerSstPeriods"] = $repository->getPeriodsByCustomer($customerId);
                            $result["customerSstPeriodsCompare"] = $repository->getPeriodsByCustomerCompare($customerId);
                            break;

                        case 'dashboard_top_management_periods':
                            $repository = new TopManagementRepository();
                            $result['dashboardTopManagementPeriods'] = $repository->getPeriodList();
                            break;

                        case 'dashboard_improvement_plan':
                            $customerId = $entity->criteria->customerId;

                            $repository = new CustomerImprovementPlanRepository();
                            $result['dashboardImprovementPlanPeriods'] = $repository->getPeriods($customerId);
                            break;

                        case 'dashboard_business_program':
                            $repository = new CustomerManagementRepository();
                            $result['dashboardBusinessProgramWorkplaces'] = $repository->getWorkplaceByYears($entity->criteria);
                            break;

                        case 'dashboard_occupational_investigation_periods':
                            $customerId = $entity->criteria->customerId;

                            $repository = new CustomerOccupationalInvestigationRepository();
                            $result['dashboardOccupationalInvestigationPeriods'] = $repository->getYears($customerId);
                            break;

                        case "dashboard_absenteeism_disability_causes_only":
                            $causes = SystemParameter::whereNamespace("wgroup")->whereGroup('absenteeism_disability_causes')
                                ->orderBy('id', 'ASC')
                                ->whereIn("value", ['AL', 'ELC', 'EG'])
                                ->get()
                                ->toArray();

                            $result["absenteeism_disability_causes_only"] = $causes;
                            break;

                        case "dashboard_absenteeism_disability_indicator_years":
                            $customerId = $entity->criteria->customerId;
                            $causeList = isset($entity->criteria->cause) && $entity->criteria->cause ? [$entity->criteria->cause] : [-1];

                            $result["absenteeism_disability_indicator_years"] = DB::table('wg_customer_absenteeism_disability')
                                ->join('wg_customer_employee', 'wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id')
                                ->select(DB::raw('YEAR(`start`) as item'), DB::raw('YEAR(`start`) as value'))
                                ->where('customer_id', $customerId)
                                ->whereIn('wg_customer_absenteeism_disability.cause', $causeList)
                                ->groupBy(DB::raw('YEAR(`start`)'), 'customer_id')
                                ->orderBy(DB::raw('YEAR(`start`)'), 'DESC')
                                ->get();
                            break;

                        case 'customer_improvement_plan_document_periods':
                            $customerId = $entity->value;
                            $year = $entity->year;

                            $repository = new CustomerImprovementPlanDocumentRepository();
                            $result['customerImprovementPlanDocumentPeriod'] = $repository->getPeriods($customerId, $year);
                            break;

                        case 'customer_document_periods':
                            $customerId = $entity->value;
                            $year = $entity->year;

                            $repository = new CustomerDocumentRepository();
                            $result['customerDocumentPeriod'] = $repository->getPeriods($customerId, $year);
                            break;

                        case 'customer_employee_document_periods':
                            $customerId = $entity->value;
                            $year = $entity->year;

                            $repository = new CustomerEmployeeDocumentRepository();
                            $result['customerEmployeeDocumentPeriod'] = $repository->getPeriods($customerId, $year);
                            break;

                        case 'customer_tracking_document_periods':
                            $customerId = $entity->value;
                            $year = $entity->year;

                            $repository = new CustomerTrackingDocumentRepository();
                            $result['customerTrackingDocumentPeriod'] = $repository->getPeriods($customerId, $year);
                            break;

                        case 'customer_vr_employee_observation':
                            $customerId = $entity->criteria->customerId;

                            $repository = new CustomerParameterRepository();
                            $result['customer_vr_employee_observation'] = $repository->getCustomerVrObservationList($customerId);
                            break;
                            
                        default:
                            $result[$entity->name] = $this->getSystemParameter($entity->name);
                            break;
                    }
                }
            }

            $this->response->setData($result);
            $this->response->setRecordsTotal(0);
            $this->response->setRecordsFiltered(0);
        } catch (Exception $ex) {
            // error on server
            Log::error($ex);
            $this->response->setStatuscode(500);
            $this->response->setMessage($ex->getMessage());
            $this->response->setError($ex->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function states()
    {

        $id = $this->request->get("cid", "0");

        try {
            $states = [];
            if ($model = Country::find($id)) {
                foreach ($model->states as $state) {
                    $states[] = $state;
                }
            }

            $result = $states;

            // set count total ideas
            $this->response->setResult($result);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    public function cities()
    {

        $id = $this->request->get("sid", "0");

        try {
            $result = array();
            if ($model = State::find($id)) {
                //var_dump($model);
                foreach ($model->getCities() as $city) {
                    $result[] = $city;
                }
            }
            // set count total ideas
            $this->response->setResult($result);
        } catch (Exception $exc) {

            // Log the full exception
            Log::error($exc->getMessage());

            // error on server
            $this->response->setStatuscode(500);
            $this->response->setMessage($exc->getMessage());
            $this->response->setError($exc->getMessage());
        }

        return Response::json($this->response, $this->response->getStatuscode());
    }

    private function getCriteriaOperators()
    {
        return [
            ["name" => "Igual", "value" => "eq"],
            ["name" => "Contiene", "value" => "contains"],
            ["name" => "Diferente", "value" => "neq"],
            ["name" => "Mayor o igual que", "value" => "gte"],
            ["name" => "Mayor que", "value" => "gt"],
            ["name" => "Menor o igual que", "value" => "lte"],
            ["name" => "Menor que", "value" => "lt"],
        ];
    }

    private function getCriteriaConditions()
    {
        return [
            ["name" => "Y", "value" => "and"],
            ["name" => "O", "value" => "or"],
        ];
    }

    private function getActiveOptions()
    {
        return [
            ["name" => "Activo", "value" => "1"],
            ["name" => "Inactivo", "value" => "0"],
        ];
    }

    private function getYesNoOptions()
    {
        return [
            ["name" => "Si", "value" => "1"],
            ["name" => "No", "value" => "0"],
        ];
    }

    private function getDocumentManagementOptions()
    {
        return [
            ["item" => "Revisado Aprobado", "value" => 1],
            ["item" => "Revisado Denegado", "value" => 3]
        ];
    }

    private function getDocumentVerifiedOptions()
    {
        return [
            ["name" => "Aprobado", "value" => "Aprobado"],
            ["name" => "Denegado", "value" => "Denegado"],
            ["name" => "Sin verificar", "value" => ""],
        ];
    }

    private function getYesNoLetterOptions()
    {
        return [
            ["name" => "Si", "value" => "Si"],
            ["name" => "No", "value" => "No"],
        ];
    }

    private function monthOptions()
    {
        return [
            ["name" => "Enero", "value" => "1"],
            ["name" => "Febrero", "value" => "2"],
            ["name" => "Marzo", "value" => "3"],
            ["name" => "Abril", "value" => "4"],
            ["name" => "Mayo", "value" => "5"],
            ["name" => "Junio", "value" => "6"],
            ["name" => "Julio", "value" => "7"],
            ["name" => "Agosto", "value" => "8"],
            ["name" => "Septiembre", "value" => "9"],
            ["name" => "Octubre", "value" => "10"],
            ["name" => "Noviembre", "value" => "11"],
            ["name" => "Diciembre", "value" => "12"],
        ];
    }

    private function yearOptions()
    {
        $currentYear = Carbon::now()->year;
        return [
            ["name" => $currentYear - 1, "value" => $currentYear - 1],
            ["name" => $currentYear, "value" => $currentYear],
            ["name" => $currentYear + 1, "value" => $currentYear + 1],
        ];
    }

    private function semesterOptions()
    {
        return [
            ["item" => "Junio", "value" => "6"],
            ["item" => "Diciembre", "value" => "12"],
        ];
    }

    private function trimesterOptions()
    {
        return [
            ["item" => "Marzo", "value" => "3"],
            ["item" => "Junio", "value" => "6"],
            ["item" => "Septiembre", "value" => "9"],
            ["item" => "Diciembre", "value" => "12"],
        ];
    }

    private function getCustomer($currentUser)
    {
        if (!$currentUser) {
            return null;
        }

        return DB::table('wg_customers')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('arl')), function ($join) {
                $join->on('wg_customers.arl', '=', 'arl.value');
            })
            ->select(
                'wg_customers.id',
                'wg_customers.businessName as item',
                'arl.item as arl'
            )
            ->where('wg_customers.id', $currentUser->company)
            ->first();
    }

    private function getSystemParameter($group)
    {
        $repository = new SystemParameter();
        return $repository->whereNamespace("wgroup")->whereGroup($group)
            ->orderBy('id', 'ASC')
            ->get();
    }
}
