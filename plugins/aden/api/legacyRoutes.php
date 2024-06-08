<?php


use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Flash;
use ReCaptcha\ReCaptcha;
use Alxy\Captcha\Models\Settings;


    Route::get('customer', 'Wgroup\Controllers\CustomerController@get');
        Route::get('customer/unit', 'Wgroup\Controllers\CustomerController@getUnit');
        Route::get('states', 'Wgroup\Controllers\CustomerController@states');
        Route::get('towns', 'Wgroup\Controllers\CustomerController@towns');
        Route::get('tracking', 'Wgroup\Controllers\CustomerTrackingController@get');
        Route::get('contribution', 'Wgroup\Controllers\CustomerContributionController@get');
        Route::get('customer/report', 'Wgroup\Controllers\CustomerController@report');
        Route::get('customer/document', 'Wgroup\Controllers\CustomerDocumentController@get');
        Route::get('customer/poll', 'Wgroup\Controllers\CustomerPollController@get');
        Route::get('customer/certificate-program', 'Wgroup\Controllers\CustomerCertificateProgramController@get');
        Route::get('customer/contractor', 'Wgroup\Controllers\CustomerContractorController@get');
        Route::get('customer/economic-group', 'Wgroup\Controllers\CustomerEconomicGroupController@get');
        Route::get('customer/contractor/export-excel', 'Wgroup\Controllers\CustomerContractorController@export');
        Route::get('customer/periodic-requirement', 'Wgroup\Controllers\CustomerPeriodicRequirementController@get');
        Route::get('customer/user', 'Wgroup\Controllers\CustomerUserController@get');

        Route::get('customer/action-plan/export-activity-excel', 'Wgroup\Controllers\CustomerActionPlanController@exportActivity');
        Route::get('customer/action-plan/export-task-excel', 'Wgroup\Controllers\CustomerActionPlanController@exportActivityTask');

        Route::get('customer-employee', 'Wgroup\Controllers\CustomerEmployeeController@get');
        Route::get('customer-employee-v3', 'Wgroup\Controllers\CustomerEmployeeController@getThree');
        Route::get('customer-employee/export', 'Wgroup\Controllers\CustomerEmployeeController@export');
        Route::get('customer-employee/export-template', 'Wgroup\Controllers\CustomerEmployeeController@exportTemplate');
        Route::get('customer-employee/exportPdf', 'Wgroup\Controllers\CustomerEmployeeController@exportPdf');
        Route::get('customer-employee/document', 'Wgroup\Controllers\CustomerEmployeeDocumentController@get');

        Route::get('customer-parameter', 'Wgroup\Controllers\CustomerParameterController@get');

        Route::get('agent', 'Wgroup\Controllers\AgentController@get');
        Route::get('agent/document', 'Wgroup\Controllers\AgentDocumentController@get');

        Route::get('diagnostic', 'Wgroup\Controllers\CustomerDiagnosticController@get');
        Route::get('diagnostic/reportMonthly', 'Wgroup\Controllers\CustomerDiagnosticController@reportMonthly');
        Route::get('diagnostic/report', 'Wgroup\Controllers\CustomerDiagnosticController@report');
        Route::get('diagnostic/prevention', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@index');
        Route::get('diagnostic/infoprevention', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@getInformation');
        Route::get('diagnostic/infoReport', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@getInformationReport');
        Route::get('diagnostic/infoReport/export-excel', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@export');
        Route::get('diagnostic/export-excel', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@exportAll');
        Route::get('diagnostic/prevention/detail', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@detail');
        Route::get('diagnostic/actionPlan', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@getActionPlan');




        Route::get('absenteeism-disability', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@get');
        Route::get('absenteeism-indicator', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@get');

        //Route::get('management', 'Wgroup\Controllers\CustomerManagementController@get');

        Route::get('management/report', 'Wgroup\Controllers\CustomerManagementController@report');
        Route::get('management/reportMonthly', 'Wgroup\Controllers\CustomerManagementController@reportMonthly');
        //Route::get('management/prevention', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@index');
        Route::get('management/information', 'Wgroup\Controllers\CustomerManagementDetailController@getInformation');
        Route::get('management/infoReport', 'Wgroup\Controllers\CustomerManagementDetailController@getInformationReport');
        Route::get('management/actionPlan', 'Wgroup\Controllers\CustomerManagementDetailController@getActionPlan');
        Route::get('management/export-excel', 'Wgroup\Controllers\CustomerManagementDetailController@exportAll');
        Route::get('management/infoReport/export-excel', 'Wgroup\Controllers\CustomerManagementDetailController@export');
        //Route::get('management/prevention/detail', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@detail');

        Route::get('customer/contract-detail/information', 'Wgroup\Controllers\CustomerContractDetailController@getInformation');
        Route::get('customer/contract-detail/infoReport', 'Wgroup\Controllers\CustomerContractDetailController@getInformationReport');
        Route::get('customer/contract-detail/actionPlan', 'Wgroup\Controllers\CustomerContractDetailController@getActionPlan');

        Route::get('customer/contract-detail-document', 'Wgroup\Controllers\CustomerContractDetailDocumentController@get');
        Route::get('customer/contract-detail-document/download', 'Wgroup\Controllers\CustomerContractDetailDocumentController@download');

        Route::get('project', 'Wgroup\Controllers\CustomerProjectController@get');
        Route::get('project/task', 'Wgroup\Controllers\CustomerProjectController@getTask');
        Route::get('project/report', 'Wgroup\Controllers\CustomerProjectController@report');
        Route::get('project/information', 'Wgroup\Controllers\CustomerProjectController@getInformation');
        Route::get('project/infoReport', 'Wgroup\Controllers\CustomerProjectController@getInformationReport');

        Route::get('internal-project', 'Wgroup\Controllers\CustomerInternalProjectController@get');
        Route::get('internal-project/task', 'Wgroup\Controllers\CustomerInternalProjectController@getTask');
        Route::get('internal-project/report', 'Wgroup\Controllers\CustomerInternalProjectController@report');
        Route::get('internal-project/information', 'Wgroup\Controllers\CustomerInternalProjectController@getInformation');
        Route::get('internal-project/infoReport', 'Wgroup\Controllers\CustomerInternalProjectController@getInformationReport');

        Route::get('diagnostic/disease/get', 'Wgroup\Controllers\CustomerDiagnosticDiseaseController@get');
        Route::get('diagnostic/accident/get', 'Wgroup\Controllers\CustomerDiagnosticAccidentController@get');
        Route::get('diagnostic/workplace/get', 'Wgroup\Controllers\CustomerDiagnosticWorkPlaceController@get');
        Route::get('diagnostic/riskfactor/get', 'Wgroup\Controllers\CustomerDiagnosticRiskFactorController@get');
        Route::get('diagnostic/risktask/get', 'Wgroup\Controllers\CustomerDiagnosticRiskTaskController@get');
        Route::get('diagnostic/arl/get', 'Wgroup\Controllers\CustomerDiagnosticArlController@get');
        Route::get('diagnostic/arlintermediary/get', 'Wgroup\Controllers\CustomerDiagnosticArlIntermediaryController@get');
        Route::get('diagnostic/environmental/get', 'Wgroup\Controllers\CustomerDiagnosticEnvironmentalController@get');
        Route::get('diagnostic/environmentalintermediary/get', 'Wgroup\Controllers\CustomerDiagnosticEnvironmentalIntermediaryController@get');
        Route::get('diagnostic/process/get', 'Wgroup\Controllers\CustomerDiagnosticProcessController@get');
        Route::get('diagnostic/observation/get', 'Wgroup\Controllers\CustomerDiagnosticObservationController@get');

        Route::get('document/download', 'Wgroup\Controllers\CustomerDocumentController@download');

        Route::get('agent/document/download', 'Wgroup\Controllers\AgentDocumentController@download');

        Route::get('customer-employee', 'Wgroup\Controllers\CustomerEmployeeController@get');
        Route::get('customer-employee/document/download', 'Wgroup\Controllers\CustomerEmployeeDocumentController@download');
        Route::get('customer-employee/document/stream', 'Wgroup\Controllers\CustomerEmployeeDocumentController@stream');
        Route::get('customer-employee/document/required-export', 'Wgroup\Controllers\CustomerEmployeeDocumentController@export');

        Route::get('absenteeism-disability-document', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityDocumentController@get');
        Route::get('absenteeism-disability-document/download', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityDocumentController@download');

        Route::get('certificate-grade-participant-document', 'Wgroup\Controllers\CertificateGradeParticipantDocumentController@get');
        Route::get('certificate-grade-participant-document/download', 'Wgroup\Controllers\CertificateGradeParticipantDocumentController@download');

        Route::get('certificate-logbook-document', 'Wgroup\Controllers\CertificateLogBookDocumentController@get');
        Route::get('certificate-logbook-document/download', 'Wgroup\Controllers\CertificateLogBookDocumentController@download');

        Route::get('quote', 'Wgroup\Controllers\QuoteController@get');

        Route::get('report', 'Wgroup\Controllers\ReportController@get');
        Route::get('report/export', 'Wgroup\Controllers\ReportController@export');
        Route::get('report/exportDynamic', 'Wgroup\Controllers\ReportController@exportDynamic');

        Route::get('poll', 'Wgroup\Controllers\PollController@get');
        Route::get('poll/export', 'Wgroup\Controllers\PollController@export');

        Route::get('quote-service', 'Wgroup\Controllers\QuoteServiceController@get');

        Route::get('report-calculated', 'Wgroup\Controllers\ReportCalculatedFieldController@get');

        Route::get('poll-question', 'Wgroup\Controllers\PollQuestionController@get');

        Route::get('certificate-program', 'Wgroup\Controllers\CertificateProgramController@get');
        Route::get('certificate-grade', 'Wgroup\Controllers\CertificateGradeController@get');

        Route::get('certificate-grade-participant', 'Wgroup\Controllers\CertificateGradeParticipantController@get');
        Route::get('certificate-external/download', 'Wgroup\Controllers\CertificateExternalController@download');
        Route::get('certificate-grade-participant-certificate/download', 'Wgroup\Controllers\CertificateGradeParticipantController@downloadCertificate');
        Route::get('certificate-grade-participant-certificate/stream', 'Wgroup\Controllers\CertificateGradeParticipantController@streamCertificate');

        Route::get('disability-diagnostic', 'Wgroup\Controllers\DisabilityDiagnosticController@get');
        Route::get('project-task-type', 'Wgroup\Controllers\ProjectTaskTypeController@get');
        Route::get('system-parameter', 'Wgroup\Controllers\SystemParameterController@get');

        Route::get('diagnostic/summary/export-pdf', 'Wgroup\Controllers\CustomerDiagnosticController@summaryExportPdf');
        Route::get('diagnostic/summary/export-excel', 'Wgroup\Controllers\CustomerDiagnosticController@summaryExportExcel');


        Route::post('diagnostic/disease', 'Wgroup\Controllers\CustomerDiagnosticDiseaseController@index');
        Route::post('diagnostic/accident', 'Wgroup\Controllers\CustomerDiagnosticAccidentController@index');
        Route::post('diagnostic/workplace', 'Wgroup\Controllers\CustomerDiagnosticWorkPlaceController@index');
        Route::post('diagnostic/riskfactor', 'Wgroup\Controllers\CustomerDiagnosticRiskFactorController@index');
        Route::post('diagnostic/risktask', 'Wgroup\Controllers\CustomerDiagnosticRiskTaskController@index');
        Route::post('diagnostic/arl', 'Wgroup\Controllers\CustomerDiagnosticArlController@index');
        Route::post('diagnostic/arlintermediary', 'Wgroup\Controllers\CustomerDiagnosticArlIntermediaryController@index');
        Route::post('diagnostic/environmental', 'Wgroup\Controllers\CustomerDiagnosticEnvironmentalController@index');
        Route::post('diagnostic/environmentalintermediary', 'Wgroup\Controllers\CustomerDiagnosticEnvironmentalIntermediaryController@index');
        Route::post('diagnostic/process', 'Wgroup\Controllers\CustomerDiagnosticProcessController@index');
        Route::post('diagnostic/observation', 'Wgroup\Controllers\CustomerDiagnosticObservationController@index');

        Route::post('customers', 'Wgroup\Controllers\CustomerController@index');
        Route::post('customers-contractor', 'Wgroup\Controllers\CustomerController@indexContractor');
        Route::post('customers-economic-group', 'Wgroup\Controllers\CustomerController@indexEconomicGroup');
        Route::post('customers-contractor-economic-group', 'Wgroup\Controllers\CustomerController@indexContractAndEconomicGroup');
        Route::post('customers/all', 'Wgroup\Controllers\CustomerController@getCustomers');
        Route::post('customer/save', 'Wgroup\Controllers\CustomerController@save');
        Route::post('customer/saveUnit', 'Wgroup\Controllers\CustomerController@saveUnit');
        Route::post('customer/saveContacts', 'Wgroup\Controllers\CustomerController@saveContacts');
        Route::post('customer/saveInfoDetail', 'Wgroup\Controllers\CustomerController@saveInfoDetail');
        Route::post('customer/saveParameters', 'Wgroup\Controllers\CustomerController@saveParameters');
        Route::post('customer/save-document-type', 'Wgroup\Controllers\CustomerController@saveDocumentTypeParameters');
        Route::post('customer/saveQuick', 'Wgroup\Controllers\CustomerController@saveQuick');
        Route::post('customer/insert', 'Wgroup\Controllers\CustomerController@insert');
        Route::post('upload', 'Wgroup\Controllers\CustomerController@upload');
        Route::post('customer/delete', 'Wgroup\Controllers\CustomerController@delete');
        Route::post('customer/agentList', 'Wgroup\Controllers\CustomerController@getAgents');


        Route::post('customer/info-detail/delete', 'Wgroup\Controllers\CustomerController@deleteInfoDetail');
        Route::post('customer/contact/delete', 'Wgroup\Controllers\CustomerController@deleteContact');

        Route::post('agents', 'Wgroup\Controllers\AgentController@index');
        Route::post('agents-customer', 'Wgroup\Controllers\AgentController@getByCustomer');
        Route::post('agent/save', 'Wgroup\Controllers\AgentController@save');
        Route::post('agent/upload', 'Wgroup\Controllers\AgentController@upload');
        Route::post('agent/signature', 'Wgroup\Controllers\AgentController@uploadSignature');
        Route::post('agent/delete', 'Wgroup\Controllers\AgentController@upload');

        // Para traking
        Route::post('tracking', 'Wgroup\Controllers\CustomerTrackingController@index');
        Route::post('contribution', 'Wgroup\Controllers\CustomerContributionController@index');
        Route::post('tracking/save', 'Wgroup\Controllers\CustomerTrackingController@save');
        Route::post('contribution/save', 'Wgroup\Controllers\CustomerContributionController@save');
        Route::post('tracking/delete', 'Wgroup\Controllers\CustomerTrackingController@delete');
        Route::post('tracking/agent', 'Wgroup\Controllers\CustomerTrackingController@agent');
        Route::post('contribution/delete', 'Wgroup\Controllers\CustomerContributionController@delete');

        //Audit
        Route::post('audit', 'Wgroup\Controllers\CustomerAuditController@index');

        //Lista los diagnosticos
        Route::post('diagnostic', 'Wgroup\Controllers\CustomerDiagnosticController@index');
        Route::post('diagnostic/canCreate', 'Wgroup\Controllers\CustomerDiagnosticController@canCreate');
        Route::post('diagnostic/summary', 'Wgroup\Controllers\CustomerDiagnosticController@summary');
        Route::post('diagnostic/save', 'Wgroup\Controllers\CustomerDiagnosticController@save');
        Route::post('diagnostic/update', 'Wgroup\Controllers\CustomerDiagnosticController@update');
        Route::post('diagnostic/delete', 'Wgroup\Controllers\CustomerDiagnosticController@delete');
        Route::post('diagnostic/cancel', 'Wgroup\Controllers\CustomerDiagnosticController@cancel');
        Route::post('diagnostic/report-monthly-filter', 'Wgroup\Controllers\CustomerDiagnosticController@getYearFilter');
        Route::post('diagnostic/summary-indicator', 'Wgroup\Controllers\CustomerDiagnosticController@summaryByIndicator');
        Route::get('diagnostic/summary-indicator/export', 'Wgroup\Controllers\CustomerDiagnosticController@summaryByIndicatorExport');
        Route::post('diagnostic/summary-program', 'Wgroup\Controllers\CustomerDiagnosticController@summaryByProgram');
        Route::get('diagnostic/summary-program/export', 'Wgroup\Controllers\CustomerDiagnosticController@summaryByProgramExport');

        Route::post('diagnostic/economic-group', 'Wgroup\Controllers\CustomerDiagnosticController@listEconomicGroup');
        Route::post('diagnostic/economic-group-customer', 'Wgroup\Controllers\CustomerDiagnosticController@listEconomicGroupCustomer');
        Route::post('diagnostic/report-economic-group', 'Wgroup\Controllers\CustomerDiagnosticController@reportEconomicGroup');
        Route::post('diagnostic/economic-group-summary', 'Wgroup\Controllers\CustomerDiagnosticController@economicGroupSummary');
        Route::post('diagnostic/report-economic-group-indicator', 'Wgroup\Controllers\CustomerDiagnosticController@reportEconomicGroupIndicator');
        Route::post('diagnostic/report-economic-group-customer-indicator', 'Wgroup\Controllers\CustomerDiagnosticController@reportEconomicGroupCustomerIndicator');

        Route::post('diagnostic/customer', 'Wgroup\Controllers\CustomerDiagnosticController@listCustomer');
        Route::post('diagnostic/contracting', 'Wgroup\Controllers\CustomerDiagnosticController@listContracting');
        Route::post('diagnostic/contracting-customer', 'Wgroup\Controllers\CustomerDiagnosticController@listContractingCustomer');
        Route::post('diagnostic/report-contracting', 'Wgroup\Controllers\CustomerDiagnosticController@reportContracting');
        Route::post('diagnostic/contracting-summary', 'Wgroup\Controllers\CustomerDiagnosticController@contractingSummary');
        Route::post('diagnostic/report-contracting-indicator', 'Wgroup\Controllers\CustomerDiagnosticController@reportContractingIndicator');
        Route::post('diagnostic/report-contracting-customer-indicator', 'Wgroup\Controllers\CustomerDiagnosticController@reportContractingCustomerIndicator');

        Route::post('diagnostic/actionPlan/save', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@saveActionPlan');
        Route::post('diagnostic/comment/save', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@saveComment');
        Route::post('diagnostic/comment', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@getComments');


        Route::post('diagnostic/question/save', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@saveQuestion');
        Route::get('diagnostic/question', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@getQuestion');

        //Lista de encuesta
        Route::post('customer/poll', 'Wgroup\Controllers\CustomerPollController@index');
        Route::post('customer/poll/summary', 'Wgroup\Controllers\CustomerPollController@summary');
        Route::post('customer/poll/save', 'Wgroup\Controllers\CustomerPollController@save');
        Route::post('customer/poll/update', 'Wgroup\Controllers\CustomerPollController@update');
        Route::post('customer/poll/delete', 'Wgroup\Controllers\CustomerPollController@delete');
        Route::post('customer/poll/send', 'Wgroup\Controllers\CustomerPollController@send');
        Route::post('customer/poll/generate', 'Wgroup\Controllers\CustomerPollController@generate');

        Route::post('customer/poll/answer', 'Wgroup\Controllers\CustomerPollAnswerController@index');
        Route::post('customer/poll/answer/save', 'Wgroup\Controllers\CustomerPollAnswerController@save');
        Route::post('customer/poll/answer/update', 'Wgroup\Controllers\CustomerPollAnswerController@update');
        Route::post('customer/poll/answer/delete', 'Wgroup\Controllers\CustomerPollAnswerController@delete');

        Route::post('customer/certificate-program', 'Wgroup\Controllers\CustomerCertificateProgramController@index');
        Route::post('customer/certificate-program/save', 'Wgroup\Controllers\CustomerCertificateProgramController@save');
        Route::post('customer/certificate-program/update', 'Wgroup\Controllers\CustomerCertificateProgramController@update');
        Route::post('customer/certificate-program/delete', 'Wgroup\Controllers\CustomerCertificateProgramController@delete');

        Route::post('customer/contractor', 'Wgroup\Controllers\CustomerContractorController@index');
        Route::post('customer/contractor/save', 'Wgroup\Controllers\CustomerContractorController@save');
        Route::post('customer/contractor/update', 'Wgroup\Controllers\CustomerContractorController@update');
        Route::post('customer/contractor/delete', 'Wgroup\Controllers\CustomerContractorController@delete');
        Route::post('customer/contractor/summary', 'Wgroup\Controllers\CustomerContractorController@summary');
        Route::post('customer/contractor/infoSummary', 'Wgroup\Controllers\CustomerContractorController@infoSummary');
        Route::post('customer/contractor/chart', 'Wgroup\Controllers\CustomerContractorController@chart');

        Route::post('customer/economic-group', 'Wgroup\Controllers\CustomerEconomicGroupController@index');
        Route::post('customer/economic-group/save', 'Wgroup\Controllers\CustomerEconomicGroupController@save');
        Route::post('customer/economic-group/update', 'Wgroup\Controllers\CustomerEconomicGroupController@update');
        Route::post('customer/economic-group/delete', 'Wgroup\Controllers\CustomerEconomicGroupController@delete');
        Route::post('customer/economic-group/customer', 'Wgroup\Controllers\CustomerEconomicGroupController@customer');
        Route::post('customer/economic-group/list', 'Wgroup\Controllers\CustomerEconomicGroupController@getList');

        Route::post('customer/action-plan', 'Wgroup\Controllers\CustomerActionPlanController@index');
        Route::post('customer/action-plan/save', 'Wgroup\Controllers\CustomerActionPlanController@save');
        Route::post('customer/action-plan/update', 'Wgroup\Controllers\CustomerActionPlanController@update');
        Route::post('customer/action-plan/delete', 'Wgroup\Controllers\CustomerActionPlanController@delete');
        Route::post('customer/action-plan/summary', 'Wgroup\Controllers\CustomerActionPlanController@summary');
        Route::post('customer/action-plan/activity', 'Wgroup\Controllers\CustomerActionPlanController@activity');

        Route::post('customer/action-plan/summaryActivity', 'Wgroup\Controllers\CustomerActionPlanController@summaryActivity');
        Route::post('customer/action-plan/summaryActivityTask', 'Wgroup\Controllers\CustomerActionPlanController@summaryActivityTask');

        Route::get('customer/action-plan-activity-task', 'Wgroup\Controllers\CustomerActionPlanController@get');
        Route::post('customer/action-plan-activity-task', 'Wgroup\Controllers\CustomerActionPlanController@tasks');
        Route::post('customer/action-plan-activity-task/save', 'Wgroup\Controllers\CustomerActionPlanController@save');
        Route::post('customer/action-plan-activity-task/update', 'Wgroup\Controllers\CustomerActionPlanController@update');
        Route::post('customer/action-plan-activity-task/delete', 'Wgroup\Controllers\CustomerActionPlanController@delete');
        Route::post('customer/action-plan-activity-task/summary', 'Wgroup\Controllers\CustomerActionPlanController@summary');
        Route::post('customer/action-plan-activity-task/activity', 'Wgroup\Controllers\CustomerActionPlanController@activity');


        Route::post('customer/action-plan/infoSummary', 'Wgroup\Controllers\CustomerActionPlanController@infoSummary');
        Route::post('customer/action-plan/chart', 'Wgroup\Controllers\CustomerActionPlanController@chart');

        Route::post('customer/contractor-contract', 'Wgroup\Controllers\CustomerContractorController@getContract');


        Route::post('customer/periodic-requirement', 'Wgroup\Controllers\CustomerPeriodicRequirementController@index');
        Route::post('customer/periodic-requirement/save', 'Wgroup\Controllers\CustomerPeriodicRequirementController@save');
        Route::post('customer/periodic-requirement/update', 'Wgroup\Controllers\CustomerPeriodicRequirementController@update');
        Route::post('customer/periodic-requirement/delete', 'Wgroup\Controllers\CustomerPeriodicRequirementController@delete');

        Route::post('customer/user', 'Wgroup\Controllers\CustomerUserController@index');
        Route::post('customer/user/save', 'Wgroup\Controllers\CustomerUserController@save');
        Route::post('customer/user/update', 'Wgroup\Controllers\CustomerUserController@update');
        Route::post('customer/user/delete', 'Wgroup\Controllers\CustomerUserController@delete');
        Route::get('customer/user/licence-download', 'Wgroup\Controllers\CustomerUserController@download');
        Route::get('customer/user/privacy-download', 'Wgroup\Controllers\CustomerUserController@downloadPrivacy');

        Route::post('customer/user-skill/delete', 'Wgroup\Controllers\CustomerUserController@skillDelete');

        Route::post('customer/contract-detail', 'Wgroup\Controllers\CustomerContractDetailController@index');
        Route::post('customer/contract-detail/save', 'Wgroup\Controllers\CustomerContractDetailController@save');
        Route::post('customer/contract-detail/bulk', 'Wgroup\Controllers\CustomerContractDetailController@bulkInsert');
        Route::post('customer/contract-detail/update', 'Wgroup\Controllers\CustomerContractDetailController@update');
        Route::post('customer/contract-detail/delete', 'Wgroup\Controllers\CustomerContractDetailController@delete');
        Route::post('customer/contract-detail/action-plan/save', 'Wgroup\Controllers\CustomerContractDetailActionPlanController@save');

        Route::post('customer/contract-detail-document', 'Wgroup\Controllers\CustomerContractDetailDocumentController@index');
        Route::post('customer/contract-detail-document/save', 'Wgroup\Controllers\CustomerContractDetailDocumentController@save');
        Route::post('customer/contract-detail-document/delete', 'Wgroup\Controllers\CustomerContractDetailDocumentController@delete');
        Route::post('customer/contract-detail-document/upload', 'Wgroup\Controllers\CustomerContractDetailDocumentController@upload');

        Route::post('customer-parameter', 'Wgroup\Controllers\CustomerParameterController@index');
        Route::post('customer-parameter/save', 'Wgroup\Controllers\CustomerParameterController@save');
        Route::post('customer-parameter/delete', 'Wgroup\Controllers\CustomerParameterController@delete');
        Route::post('customer-parameter/update/covid-register', 'Wgroup\Controllers\CustomerParameterController@updateCovidRegister');


        Route::post('customer/unsafe-act', 'Wgroup\Controllers\CustomerUnsafeActController@index');
        Route::post('customer/unsafe-act/save', 'Wgroup\Controllers\CustomerUnsafeActController@save');
        Route::post('customer/unsafe-act/delete', 'Wgroup\Controllers\CustomerUnsafeActController@delete');
        Route::post('customer/unsafe-act/upload', 'Wgroup\Controllers\CustomerUnsafeActController@upload');
        Route::post('customer/unsafe-act/download', 'Wgroup\Controllers\CustomerUnsafeActController@download');
        Route::post('customer/unsafe-act/filter', 'Wgroup\Controllers\CustomerUnsafeActController@getYearFilter');
        Route::post('customer/unsafe-act/indicator', 'Wgroup\Controllers\CustomerUnsafeActController@getIndicator');
        Route::get('customer/unsafe-act', 'Wgroup\Controllers\CustomerUnsafeActController@get');

        Route::post('customer/unsafe-act-observation', 'Wgroup\Controllers\CustomerUnsafeActObservationController@index');
        Route::post('customer/unsafe-act-observation/save', 'Wgroup\Controllers\CustomerUnsafeActObservationController@save');
        Route::post('customer/unsafe-act-observation/delete', 'Wgroup\Controllers\CustomerUnsafeActObservationController@delete');
        Route::post('customer/unsafe-act-observation/upload', 'Wgroup\Controllers\CustomerUnsafeActObservationController@upload');
        Route::post('customer/unsafe-act-observation/download', 'Wgroup\Controllers\CustomerUnsafeActObservationController@download');
        Route::get('customer/unsafe-act-observation', 'Wgroup\Controllers\CustomerUnsafeActObservationController@get');

        Route::post('employee-demographic', 'Wgroup\Controllers\EmployeeDemographicController@index');
        Route::post('employee-demographic/save', 'Wgroup\Controllers\EmployeeDemographicController@save');
        Route::post('employee-demographic/delete', 'Wgroup\Controllers\EmployeeDemographicController@delete');
        Route::post('employee-demographic/upload', 'Wgroup\Controllers\EmployeeDemographicController@upload');
        Route::get('employee-demographic', 'Wgroup\Controllers\EmployeeDemographicController@upload');

        Route::post('employee-children', 'Wgroup\Controllers\EmployeeChildrenController@index');
        Route::post('employee-children/save', 'Wgroup\Controllers\EmployeeChildrenController@save');
        Route::post('employee-children/delete', 'Wgroup\Controllers\EmployeeChildrenController@delete');
        Route::post('employee-children/upload', 'Wgroup\Controllers\EmployeeChildrenController@upload');
        Route::get('employee-children', 'Wgroup\Controllers\EmployeeChildrenController@upload');

        Route::post('customer-employee/validity/delete', 'Wgroup\Controllers\CustomerEmployeeController@validityDelete');

        Route::post('customer-employee', 'Wgroup\Controllers\CustomerEmployeeController@index');
        Route::post('customer-employee-active', 'Wgroup\Controllers\CustomerEmployeeController@indexActive');
        Route::post('customer-employee/save', 'Wgroup\Controllers\CustomerEmployeeController@save');
        Route::post('customer-employee/delete', 'Wgroup\Controllers\CustomerEmployeeController@delete');
        Route::post('customer-employee/inactive', 'Wgroup\Controllers\CustomerEmployeeController@inactive');
        Route::post('customer-employee/upload', 'Wgroup\Controllers\CustomerEmployeeController@upload');
        Route::post('customer-employee/import', 'Wgroup\Controllers\CustomerEmployeeController@import');
        Route::post('customer-employee/quickSave', 'Wgroup\Controllers\CustomerEmployeeController@quickSave');
        Route::post('customer-employee/save-demographic', 'Wgroup\Controllers\CustomerEmployeeController@saveDemographic');


        Route::post('customer-employee/audit', 'Wgroup\Controllers\CustomerEmployeeAuditController@index');

        Route::post('customer-employee/document', 'Wgroup\Controllers\CustomerEmployeeDocumentController@index');
        Route::post('customer-employee/document/save', 'Wgroup\Controllers\CustomerEmployeeDocumentController@save');
        Route::post('customer-employee/document/import', 'Wgroup\Controllers\CustomerEmployeeDocumentController@import');
        Route::post('customer-employee/document/delete', 'Wgroup\Controllers\CustomerEmployeeDocumentController@delete');
        Route::post('customer-employee/document/upload', 'Wgroup\Controllers\CustomerEmployeeDocumentController@upload');
        Route::post('customer-employee/document/upload-bulk', 'Wgroup\Controllers\CustomerEmployeeDocumentController@uploadBulk');
        Route::post('customer-employee/document/required', 'Wgroup\Controllers\CustomerEmployeeDocumentController@required');
        Route::post('customer-employee/document/required-validate', 'Wgroup\Controllers\CustomerEmployeeDocumentController@requiredValidate');
        Route::post('customer-employee/document/expiration', 'Wgroup\Controllers\CustomerEmployeeDocumentController@filterExpiration');
        Route::post('customer-employee/document/search-expiration', 'Wgroup\Controllers\CustomerEmployeeDocumentController@filterSearchExpiration');
        Route::post('customer-employee/document/denied', 'Wgroup\Controllers\CustomerEmployeeDocumentController@denied');
        Route::post('customer-employee/document/approve', 'Wgroup\Controllers\CustomerEmployeeDocumentController@approve');
        Route::post('customer-employee/document/critical', 'Wgroup\Controllers\CustomerEmployeeDocumentController@indexCritical');
        Route::get('customer-employee/document/search-expiration-export', 'Wgroup\Controllers\CustomerEmployeeDocumentController@exportSearchExpiration');
        Route::get('customer-employee/document/search-expiration-export-pdf', 'Wgroup\Controllers\CustomerEmployeeDocumentController@exportPdfSearchExpiration');

        Route::post('customer-employee/critical-activity', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@index');
        Route::post('customer-employee/critical-activity/save', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@save');
        Route::post('customer-employee/critical-activity/delete', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@delete');
        Route::post('customer-employee/critical-activity/upload', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@upload');
        Route::post('customer-employee/critical-activity/required', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@required');
        Route::post('customer-employee/critical-activity/required-validate', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@requiredValidate');
        Route::post('customer-employee/critical-activity/expiration', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@filterExpiration');
        Route::post('customer-employee/critical-activity/search-expiration', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@filterSearchExpiration');
        Route::post('customer-employee/critical-activity/list', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@listIndex');
        Route::post('customer-employee/critical-activity/duplicate', 'Wgroup\Controllers\CustomerEmployeeCriticalActivityController@duplicate');

        //Lista las gestiones
        Route::post('management', 'Wgroup\Controllers\CustomerManagementController@index');
        Route::post('management/canCreate', 'Wgroup\Controllers\CustomerManagementController@canCreate');
        Route::post('management/setting', 'Wgroup\Controllers\CustomerManagementController@setting');
        Route::post('management/summary', 'Wgroup\Controllers\CustomerManagementController@summary');
        Route::post('management/save', 'Wgroup\Controllers\CustomerManagementController@save');
        Route::post('management/update', 'Wgroup\Controllers\CustomerManagementController@update');
        Route::post('management/delete', 'Wgroup\Controllers\CustomerManagementController@delete');
        Route::post('management/cancel', 'Wgroup\Controllers\CustomerManagementController@cancel');
        Route::post('management/activate', 'Wgroup\Controllers\CustomerManagementController@activate');
        Route::post('management/report-monthly-filter', 'Wgroup\Controllers\CustomerManagementController@getYearFilter');
        Route::post('management/summary-indicator', 'Wgroup\Controllers\CustomerManagementController@summaryByIndicator');
        Route::get('management/summary-indicator/export', 'Wgroup\Controllers\CustomerManagementController@summaryByIndicatorExport');
        Route::post('management/summary-program', 'Wgroup\Controllers\CustomerManagementController@summaryByProgram');
        Route::get('management/summary-program/export', 'Wgroup\Controllers\CustomerManagementController@summaryByProgramExport');
        Route::get('management/summary/export-excel', 'Wgroup\Controllers\CustomerManagementController@summaryExportExcel');

        Route::post('management/actionPlan/save', 'Wgroup\Controllers\CustomerManagementDetailController@saveActionPlan');
        Route::post('management/comment/save', 'Wgroup\Controllers\CustomerManagementDetailController@saveComment');
        Route::post('management/comment', 'Wgroup\Controllers\CustomerManagementDetailController@getComments');

        //Lista las gestiones
        Route::post('projects', 'Wgroup\Controllers\CustomerProjectController@index');
        Route::post('project/setting', 'Wgroup\Controllers\CustomerProjectController@setting');
        Route::post('project/summary', 'Wgroup\Controllers\CustomerProjectController@summary');
        Route::post('project/summaryBilling', 'Wgroup\Controllers\CustomerProjectController@summaryBilling');
        Route::post('project/report', 'Wgroup\Controllers\CustomerProjectController@report');
        Route::post('project/agent', 'Wgroup\Controllers\CustomerProjectController@agent');
        Route::post('project/agent/delete', 'Wgroup\Controllers\CustomerProjectController@agentDelete');
        Route::post('project/customer', 'Wgroup\Controllers\CustomerProjectController@customer');
        Route::post('project/task', 'Wgroup\Controllers\CustomerProjectController@task');
        Route::post('project/tasks', 'Wgroup\Controllers\CustomerProjectController@projectAgentTasks');
        Route::post('project/tasks/all', 'Wgroup\Controllers\CustomerProjectController@projectTasks');
        Route::post('project/save', 'Wgroup\Controllers\CustomerProjectController@save');
        Route::post('project/task/save', 'Wgroup\Controllers\CustomerProjectController@taskSave');
        Route::post('project/task/update', 'Wgroup\Controllers\CustomerProjectController@taskUpdate');
        Route::post('project/event/update', 'Wgroup\Controllers\CustomerProjectController@eventUpdate');
        Route::post('project/update', 'Wgroup\Controllers\CustomerProjectController@update');
        Route::post('project/updateBilling', 'Wgroup\Controllers\CustomerProjectController@updateBilling');
        Route::post('project/delete', 'Wgroup\Controllers\CustomerProjectController@delete');
        Route::post('project/cancel', 'Wgroup\Controllers\CustomerProjectController@cancel');
        Route::post('project/activate', 'Wgroup\Controllers\CustomerProjectController@activate');
        Route::post('project/fillList', 'Wgroup\Controllers\CustomerProjectController@fillList');
        Route::post('project/send-status', 'Wgroup\Controllers\CustomerProjectController@sendAndSaveStatus');
        Route::get('project/gantt', 'Wgroup\Controllers\CustomerProjectController@gantt');
        Route::get('project/gantt-resource', 'Wgroup\Controllers\CustomerProjectController@ganttResource');
        Route::get('project/gantt-resource-assignment', 'Wgroup\Controllers\CustomerProjectController@ganttResourceAssignment');
        Route::post('project/gantt-dependecy', 'Wgroup\Controllers\CustomerProjectController@ganttDependecy');

        Route::post('project/cost/delete', 'Wgroup\Controllers\CustomerProjectController@deleteCost');

        Route::post('internal-projects', 'Wgroup\Controllers\CustomerInternalProjectController@index');
        Route::post('internal-project/setting', 'Wgroup\Controllers\CustomerInternalProjectController@setting');
        Route::post('internal-project/summary', 'Wgroup\Controllers\CustomerInternalProjectController@summary');
        Route::post('internal-project/report', 'Wgroup\Controllers\CustomerInternalProjectController@report');
        Route::post('internal-project/agent', 'Wgroup\Controllers\CustomerInternalProjectController@agent');
        Route::post('internal-project/agent/delete', 'Wgroup\Controllers\CustomerInternalProjectController@agentDelete');
        Route::post('internal-project/customer', 'Wgroup\Controllers\CustomerInternalProjectController@customer');
        Route::post('internal-project/task', 'Wgroup\Controllers\CustomerInternalProjectController@task');
        Route::post('internal-project/tasks', 'Wgroup\Controllers\CustomerInternalProjectController@projectAgentTasks');
        Route::post('internal-project/tasks/all', 'Wgroup\Controllers\CustomerInternalProjectController@projectTasks');
        Route::post('internal-project/save', 'Wgroup\Controllers\CustomerInternalProjectController@save');
        Route::post('internal-project/task/save', 'Wgroup\Controllers\CustomerInternalProjectController@taskSave');
        Route::post('internal-project/task/update', 'Wgroup\Controllers\CustomerInternalProjectController@taskUpdate');
        Route::post('internal-project/event/update', 'Wgroup\Controllers\CustomerInternalProjectController@eventUpdate');
        Route::post('internal-project/update', 'Wgroup\Controllers\CustomerInternalProjectController@update');
        Route::post('internal-project/delete', 'Wgroup\Controllers\CustomerInternalProjectController@delete');
        Route::post('internal-project/cancel', 'Wgroup\Controllers\CustomerInternalProjectController@cancel');
        Route::post('internal-project/activate', 'Wgroup\Controllers\CustomerInternalProjectController@activate');
        Route::post('internal-project/listCustomer', 'Wgroup\Controllers\CustomerInternalProjectController@ListByCustomer');
        Route::post('internal-project/fillList', 'Wgroup\Controllers\CustomerInternalProjectController@fillList');
        Route::post('internal-project/send-status', 'Wgroup\Controllers\CustomerInternalProjectController@sendAndSaveStatus');
        Route::get('internal-project/gantt', 'Wgroup\Controllers\CustomerInternalProjectController@gantt');
        Route::get('internal-project/gantt-resource', 'Wgroup\Controllers\CustomerInternalProjectController@ganttResource');
        Route::get('internal-project/gantt-resource-assignment', 'Wgroup\Controllers\CustomerInternalProjectController@ganttResourceAssignment');

        //Lista
        Route::post('prevention', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@index');

        Route::post('diagnostic/disease/save', 'Wgroup\Controllers\CustomerDiagnosticDiseaseController@save');
        Route::post('diagnostic/accident/save', 'Wgroup\Controllers\CustomerDiagnosticAccidentController@save');
        Route::post('diagnostic/workplace/save', 'Wgroup\Controllers\CustomerDiagnosticWorkPlaceController@save');
        Route::post('diagnostic/riskfactor/save', 'Wgroup\Controllers\CustomerDiagnosticRiskFactorController@save');
        Route::post('diagnostic/risktask/save', 'Wgroup\Controllers\CustomerDiagnosticRiskTaskController@save');
        Route::post('diagnostic/arl/save', 'Wgroup\Controllers\CustomerDiagnosticArlController@save');
        Route::post('diagnostic/arlintermediary/save', 'Wgroup\Controllers\CustomerDiagnosticArlIntermediaryController@save');
        Route::post('diagnostic/environmental/save', 'Wgroup\Controllers\CustomerDiagnosticEnvironmentalController@save');
        Route::post('diagnostic/environmentalintermediary/save', 'Wgroup\Controllers\CustomerDiagnosticEnvironmentalIntermediaryController@save');
        Route::post('diagnostic/process/save', 'Wgroup\Controllers\CustomerDiagnosticProcessController@save');
        Route::post('diagnostic/observation/save', 'Wgroup\Controllers\CustomerDiagnosticObservationController@save');

        Route::post('diagnostic/disease/delete', 'Wgroup\Controllers\CustomerDiagnosticDiseaseController@delete');
        Route::post('diagnostic/accident/delete', 'Wgroup\Controllers\CustomerDiagnosticAccidentController@delete');
        Route::post('diagnostic/workplace/delete', 'Wgroup\Controllers\CustomerDiagnosticWorkPlaceController@delete');
        Route::post('diagnostic/riskfactor/delete', 'Wgroup\Controllers\CustomerDiagnosticRiskFactorController@delete');
        Route::post('diagnostic/risktask/delete', 'Wgroup\Controllers\CustomerDiagnosticRiskTaskController@delete');
        Route::post('diagnostic/arl/delete', 'Wgroup\Controllers\CustomerDiagnosticArlController@delete');
        Route::post('diagnostic/arlintermediary/delete', 'Wgroup\Controllers\CustomerDiagnosticArlIntermediaryController@delete');
        Route::post('diagnostic/environmental/delete', 'Wgroup\Controllers\CustomerDiagnosticEnvironmentalController@delete');
        Route::post('diagnostic/environmentalintermediary/delete', 'Wgroup\Controllers\CustomerDiagnosticEnvironmentalIntermediaryController@delete');
        Route::post('diagnostic/process/delete', 'Wgroup\Controllers\CustomerDiagnosticProcessController@delete');
        Route::post('diagnostic/observation/delete', 'Wgroup\Controllers\CustomerDiagnosticObservationController@delete');

        //Persistir la informaciòn
        Route::post('prevention/save', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@save');
        Route::post('management/detail/save', 'Wgroup\Controllers\CustomerManagementDetailController@save');
        Route::get('prevention/question', 'Wgroup\Controllers\CustomerDiagnosticPreventionController@getQuestion');

        // Para document
        Route::post('document', 'Wgroup\Controllers\CustomerDocumentController@index');
        Route::post('document/save', 'Wgroup\Controllers\CustomerDocumentController@save');
        Route::post('document/delete', 'Wgroup\Controllers\CustomerDocumentController@delete');
        Route::post('document/upload', 'Wgroup\Controllers\CustomerDocumentController@upload');

        Route::post('document/users', 'Wgroup\Controllers\CustomerDocumentController@users');
        Route::post('document/permission', 'Wgroup\Controllers\CustomerDocumentController@savePermission');

        Route::post('agent/documents', 'Wgroup\Controllers\AgentDocumentController@index');
        Route::post('agent/document/save', 'Wgroup\Controllers\AgentDocumentController@save');
        Route::post('agent/document/delete', 'Wgroup\Controllers\AgentDocumentController@delete');
        Route::post('agent/document/upload', 'Wgroup\Controllers\AgentDocumentController@upload');

        Route::post('quote-service', 'Wgroup\Controllers\QuoteServiceController@index');
        Route::post('quote-service/save', 'Wgroup\Controllers\QuoteServiceController@save');
        Route::post('quote-service/delete', 'Wgroup\Controllers\QuoteServiceController@delete');

        Route::post('quote', 'Wgroup\Controllers\QuoteController@index');
        Route::post('quote/save', 'Wgroup\Controllers\QuoteController@save');
        Route::post('quote/delete', 'Wgroup\Controllers\QuoteController@delete');
        Route::post('quote/responsible', 'Wgroup\Controllers\QuoteController@responsible');

        Route::post('report', 'Wgroup\Controllers\ReportController@index');
        Route::post('report/save', 'Wgroup\Controllers\ReportController@save');
        Route::post('report/delete', 'Wgroup\Controllers\ReportController@delete');
        Route::post('report/generate', 'Wgroup\Controllers\ReportController@generate');
        Route::post('report/dynamically', 'Wgroup\Controllers\ReportController@dynamically');


        Route::post('collection-data', 'Wgroup\Controllers\CollectionDataController@index');
        Route::post('collection-data/save', 'Wgroup\Controllers\CollectionDataController@save');
        Route::post('collection-data/delete', 'Wgroup\Controllers\CollectionDataController@delete');
        Route::post('collection-data/generate', 'Wgroup\Controllers\CollectionDataController@delete');

        Route::post('report-calculated', 'Wgroup\Controllers\ReportCalculatedFieldController@index');
        Route::post('report-calculated/save', 'Wgroup\Controllers\ReportCalculatedFieldController@save');
        Route::post('report-calculated/delete', 'Wgroup\Controllers\ReportCalculatedFieldController@delete');
        Route::post('report-calculated/generate', 'Wgroup\Controllers\ReportCalculatedFieldController@delete');

        Route::post('report-chart', 'Wgroup\Controllers\ReportChartFieldController@index');
        Route::post('report-chart/save', 'Wgroup\Controllers\ReportChartFieldController@save');
        Route::post('report-chart/delete', 'Wgroup\Controllers\ReportChartFieldController@delete');
        Route::post('report-chart/generate', 'Wgroup\Controllers\ReportChartFieldController@delete');

        Route::post('poll', 'Wgroup\Controllers\PollController@index');
        Route::post('poll/summary', 'Wgroup\Controllers\PollController@summary');
        Route::post('poll/save', 'Wgroup\Controllers\PollController@save');
        Route::post('poll/delete', 'Wgroup\Controllers\PollController@delete');
        Route::post('poll/dashboard', 'Wgroup\Controllers\PollController@dashboard');

        Route::post('poll/dynamically', 'Wgroup\Controllers\PollController@dynamically');

        Route::post('poll-question', 'Wgroup\Controllers\PollQuestionController@index');
        Route::post('poll-question/save', 'Wgroup\Controllers\PollQuestionController@save');
        Route::post('poll-question/delete', 'Wgroup\Controllers\PollQuestionController@delete');
        Route::post('poll-question/generate', 'Wgroup\Controllers\PollQuestionController@delete');

        Route::post('absenteeism-disability', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@index');
        Route::post('absenteeism-disability/billing', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@getBilling');
        Route::post('absenteeism-disability/save', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@save');
        Route::post('absenteeism-disability/update', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@update');
        Route::post('absenteeism-disability/delete', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@delete');
        Route::post('absenteeism-disability/generate', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@delete');
        Route::post('absenteeism-disability/summary', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@summary');
        Route::post('absenteeism-disability/summaryReport', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@summaryReport');
        Route::post('absenteeism-disability/employee', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@getEmployee');
        Route::post('absenteeism-disability/diagnostic-analysis', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@diagnosticAnalysis');
        Route::post('absenteeism-disability/days-analysis', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@daysAnalysis');
        Route::post('absenteeism-disability/person-analysis', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@personAnalysis');
        Route::get('absenteeism-disability/diagnostic-analysis-export', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@diagnosticAnalysisExport');
        Route::get('absenteeism-disability/days-analysis-export', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@daysAnalysisExport');
        Route::get('absenteeism-disability/person-analysis-export', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityController@personAnalysisExport');

        Route::post('absenteeism-disability-document', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityDocumentController@index');
        Route::post('absenteeism-disability-document/save', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityDocumentController@save');
        Route::post('absenteeism-disability-document/delete', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityDocumentController@delete');
        Route::post('absenteeism-disability-document/upload', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityDocumentController@upload');

        Route::post('absenteeism-disability-indirect-cost', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityIndirectCostController@index');
        Route::post('absenteeism-disability-indirect-cost/save', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityIndirectCostController@save');
        Route::post('absenteeism-disability-indirect-cost/delete', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityIndirectCostController@delete');
        Route::post('absenteeism-disability-indirect-cost/upload', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityIndirectCostController@upload');

        Route::post('absenteeism-disability-report-al', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityReportALController@index');
        Route::post('absenteeism-disability-report-al/available', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityReportALController@available');
        Route::post('absenteeism-disability-report-al/save', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityReportALController@save');
        Route::post('absenteeism-disability-report-al/delete', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityReportALController@delete');
        Route::post('absenteeism-disability-report-al/upload', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityReportALController@upload');

        Route::get('absenteeism-disability/action-plan', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityActionPlanController@get');
        Route::post('absenteeism-disability/action-plan/save', 'Wgroup\Controllers\CustomerAbsenteeismDisabilityActionPlanController@save');

        Route::post('absenteeism-indicator', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@index');
        Route::post('absenteeism-indicator/save', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@save');
        Route::post('absenteeism-indicator/delete', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@delete');
        Route::post('absenteeism-indicator/generate', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@delete');
        Route::post('absenteeism-indicator/summary', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@summary');
        Route::post('absenteeism-indicator/list', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@getWorkPlaces');
        Route::post('absenteeism-indicator/chart', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@getCharts');
        Route::post('absenteeism-indicator/report', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@getReport');
        Route::post('absenteeism-indicator/consolidate', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@consolidate');
        Route::post('absenteeism-indicator/indicators', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorController@indicators');

        Route::post('absenteeism-indicator-target', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorTargetController@index');
        Route::post('absenteeism-indicator-target/save', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorTargetController@save');
        Route::post('absenteeism-indicator-target/delete', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorTargetController@delete');
        Route::post('absenteeism-indicator-target/generate', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorTargetController@delete');
        Route::post('absenteeism-indicator-target/summary', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorTargetController@summary');
        Route::post('absenteeism-indicator-target/list', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorTargetController@getWorkPlaces');
        Route::post('absenteeism-indicator-target/chart', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorTargetController@getCharts');
        Route::post('absenteeism-indicator-target/report', 'Wgroup\Controllers\CustomerAbsenteeismIndicatorTargetController@getReport');

        Route::post('certificate-program', 'Wgroup\Controllers\CertificateProgramController@index');
        Route::post('certificate-program/save', 'Wgroup\Controllers\CertificateProgramController@save');
        Route::post('certificate-program/delete', 'Wgroup\Controllers\CertificateProgramController@delete');
        Route::post('certificate-program/generate', 'Wgroup\Controllers\CertificateProgramController@delete');

        Route::post('certificate-program-speciality/delete', 'Wgroup\Controllers\CertificateProgramController@deleteSpeciality');
        Route::post('certificate-program-requirement/delete', 'Wgroup\Controllers\CertificateProgramController@deleteRequirement');

        Route::post('certificate-grade', 'Wgroup\Controllers\CertificateGradeController@index');
        Route::post('certificate-grade/save', 'Wgroup\Controllers\CertificateGradeController@save');
        Route::post('certificate-grade/delete', 'Wgroup\Controllers\CertificateGradeController@delete');
        Route::post('certificate-grade/generate', 'Wgroup\Controllers\CertificateGradeController@generateCertificate');

        Route::post('certificate-grade-calendar/delete', 'Wgroup\Controllers\CertificateGradeCalendarController@delete');
        Route::post('certificate-grade-agent/delete', 'Wgroup\Controllers\CertificateGradeAgentController@delete');


        Route::post('certificate-grade-participant', 'Wgroup\Controllers\CertificateGradeParticipantController@index');
        Route::post('certificate-grade-participant/search', 'Wgroup\Controllers\CertificateGradeParticipantController@filterIndex');
        Route::post('certificate-grade-participant/expiration', 'Wgroup\Controllers\CertificateGradeParticipantController@filterExpiration');
        Route::post('certificate-grade-participant/save', 'Wgroup\Controllers\CertificateGradeParticipantController@save');
        Route::post('certificate-grade-participant/delete', 'Wgroup\Controllers\CertificateGradeParticipantController@delete');
        Route::post('certificate-grade-participant/upload', 'Wgroup\Controllers\CertificateGradeParticipantController@upload');
        Route::post('certificate-grade-participant/validate', 'Wgroup\Controllers\CertificateGradeParticipantController@validate');
        Route::post('certificate-grade-participant/download', 'Wgroup\Controllers\CertificateGradeParticipantController@download');

        Route::post('certificate-external/save', 'Wgroup\Controllers\CertificateExternalController@save');
        Route::post('certificate-external/delete', 'Wgroup\Controllers\CertificateExternalController@delete');
        Route::post('certificate-external/upload', 'Wgroup\Controllers\CertificateExternalController@upload');
        Route::post('certificate-external/download', 'Wgroup\Controllers\CertificateExternalController@download');

        Route::post('certificate-grade-participant-document', 'Wgroup\Controllers\CertificateGradeParticipantDocumentController@index');
        Route::post('certificate-grade-participant-document/save', 'Wgroup\Controllers\CertificateGradeParticipantDocumentController@save');
        Route::post('certificate-grade-participant-document/delete', 'Wgroup\Controllers\CertificateGradeParticipantDocumentController@delete');
        Route::post('certificate-grade-participant-document/upload', 'Wgroup\Controllers\CertificateGradeParticipantDocumentController@upload');


        Route::post('certificate-logbook-document', 'Wgroup\Controllers\CertificateLogBookDocumentController@index');
        Route::post('certificate-logbook-document/save', 'Wgroup\Controllers\CertificateLogBookDocumentController@save');
        Route::post('certificate-logbook-document/delete', 'Wgroup\Controllers\CertificateLogBookDocumentController@delete');
        Route::post('certificate-logbook-document/upload', 'Wgroup\Controllers\CertificateLogBookDocumentController@upload');

        //Route::post('disability-diagnostic', 'Wgroup\Controllers\DisabilityDiagnosticController@index');
        Route::post('disability-diagnostic/save', 'Wgroup\Controllers\DisabilityDiagnosticController@save');
        Route::post('disability-diagnostic/delete', 'Wgroup\Controllers\DisabilityDiagnosticController@delete');
        Route::post('disability-diagnostic/upload', 'Wgroup\Controllers\DisabilityDiagnosticController@upload');
        Route::post('disability-diagnostic/import', 'Wgroup\Controllers\DisabilityDiagnosticController@import');
        //Route::post('disability-diagnostic-employee', 'Wgroup\Controllers\DisabilityDiagnosticController@indexEmployee');
        //Route::post('disability-diagnostic-source-employee', 'Wgroup\Controllers\DisabilityDiagnosticController@indexDiagnosticEmployee');

        Route::post('project-task-type', 'Wgroup\Controllers\ProjectTaskTypeController@index');
        Route::post('project-task-type/save', 'Wgroup\Controllers\ProjectTaskTypeController@save');
        Route::post('project-task-type/delete', 'Wgroup\Controllers\ProjectTaskTypeController@delete');

        Route::post('system-parameter', 'Wgroup\Controllers\SystemParameterController@index');
        Route::post('system-parameter/relation', 'Wgroup\Controllers\SystemParameterController@indexRelation');
        Route::post('system-parameter/save', 'Wgroup\Controllers\SystemParameterController@save');
        Route::post('system-parameter/delete', 'Wgroup\Controllers\SystemParameterController@delete');
        Route::post('system-parameter/group', 'Wgroup\Controllers\SystemParameterController@getGroupParameter');
        Route::post('system-parameter/agree', 'Wgroup\Controllers\SystemParameterController@agree');
        Route::post('system-parameter/upload', 'Wgroup\Controllers\SystemParameterController@upload');
        Route::get('system-parameter/get', 'Wgroup\Controllers\SystemParameterController@get');
        Route::get('system-parameter/term-condition', 'Wgroup\Controllers\SystemParameterController@terms');
        Route::get('system-parameter/privacy-policy', 'Wgroup\Controllers\SystemParameterController@privacyPolicy');

        Route::get('configuration/management-program', 'Wgroup\Controllers\ProgramManagementController@get');
        Route::post('configuration/management-program', 'Wgroup\Controllers\ProgramManagementController@index');
        Route::post('configuration/management-program/save', 'Wgroup\Controllers\ProgramManagementController@save');
        Route::post('configuration/management-program/delete', 'Wgroup\Controllers\ProgramManagementController@delete');
        Route::post('configuration/management-program/list', 'Wgroup\Controllers\ProgramManagementController@getList');

        Route::get('configuration/management-category', 'Wgroup\Controllers\CategoryManagementController@get');
        Route::post('configuration/management-category', 'Wgroup\Controllers\CategoryManagementController@index');
        Route::post('configuration/management-category/save', 'Wgroup\Controllers\CategoryManagementController@save');
        Route::post('configuration/management-category/delete', 'Wgroup\Controllers\CategoryManagementController@delete');
        Route::post('configuration/management-category/list', 'Wgroup\Controllers\CategoryManagementController@getList');

        Route::get('configuration/management-question', 'Wgroup\Controllers\QuestionManagementController@get');
        Route::post('configuration/management-question', 'Wgroup\Controllers\QuestionManagementController@index');
        Route::post('configuration/management-question/save', 'Wgroup\Controllers\QuestionManagementController@save');
        Route::post('configuration/management-question/delete', 'Wgroup\Controllers\QuestionManagementController@delete');

        Route::post('occupational-report', 'Wgroup\Controllers\CustomerOccupationalReportALController@index');
        Route::post('occupational-report/save', 'Wgroup\Controllers\CustomerOccupationalReportALController@save');
        Route::post('occupational-report/delete', 'Wgroup\Controllers\CustomerOccupationalReportALController@delete');
        Route::post('occupational-report/generate', 'Wgroup\Controllers\CustomerOccupationalReportALController@delete');
        Route::post('occupational-report/summary', 'Wgroup\Controllers\CustomerOccupationalReportALController@summary');
        Route::post('occupational-report/list', 'Wgroup\Controllers\CustomerOccupationalReportALController@getWorkPlaces');
        Route::post('occupational-report/chart', 'Wgroup\Controllers\CustomerOccupationalReportALController@getCharts');
        Route::post('occupational-report/monthly-filter', 'Wgroup\Controllers\CustomerOccupationalReportALController@getYearFilter');
        Route::post('occupational-report/summary-indicator', 'Wgroup\Controllers\CustomerOccupationalReportALController@summaryByIndicator');
        Route::post('occupational-report/summary-lesion', 'Wgroup\Controllers\CustomerOccupationalReportALController@summaryByLesion');
        Route::get('occupational-report/preview', 'Wgroup\Controllers\CustomerOccupationalReportALController@preview');
        Route::get('occupational-report/download', 'Wgroup\Controllers\CustomerOccupationalReportALController@download');
        Route::get('occupational-report', 'Wgroup\Controllers\CustomerOccupationalReportALController@get');
        Route::get('occupational-report/summary-lesion/export', 'Wgroup\Controllers\CustomerOccupationalReportALController@summaryByLesionExport');
        Route::get('occupational-report/summary-indicator/export', 'Wgroup\Controllers\CustomerOccupationalReportALController@summaryByIndicatorExport');

        Route::post('occupational-report-incident', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@index');
        Route::post('occupational-report-incident/save', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@save');
        Route::post('occupational-report-incident/delete', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@delete');
        Route::post('occupational-report-incident/generate', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@delete');
        Route::post('occupational-report-incident/summary', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@summary');
        Route::post('occupational-report-incident/list', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@getWorkPlaces');
        Route::post('occupational-report-incident/chart', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@getCharts');
        Route::post('occupational-report-incident/monthly-filter', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@getYearFilter');
        Route::post('occupational-report-incident/summary-indicator', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@summaryByIndicator');
        Route::post('occupational-report-incident/summary-lesion', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@summaryByLesion');
        Route::get('occupational-report-incident/preview', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@preview');
        Route::get('occupational-report-incident/download', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@download');
        Route::get('occupational-report-incident', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@get');
        Route::get('occupational-report-incident/summary-lesion/export', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@summaryByLesionExport');
        Route::get('occupational-report-incident/summary-indicator/export', 'Wgroup\Controllers\CustomerOccupationalReportIncidentController@summaryByIndicatorExport');

        Route::post('customer/config-sgsst/workplace', 'Wgroup\Controllers\CustomerConfigWorkPlaceController@index');
        Route::post('customer/config-sgsst/workplace/save', 'Wgroup\Controllers\CustomerConfigWorkPlaceController@save');
        Route::post('customer/config-sgsst/workplace/delete', 'Wgroup\Controllers\CustomerConfigWorkPlaceController@delete');
        Route::post('customer/config-sgsst/workplace/import', 'Wgroup\Controllers\CustomerConfigWorkPlaceController@import');
        Route::post('customer/config-sgsst/workplace/list', 'Wgroup\Controllers\CustomerConfigWorkPlaceController@listIndexMacro');
        Route::post('customer/config-sgsst/workplace/listProcess', 'Wgroup\Controllers\CustomerConfigWorkPlaceController@listIndexProcesses');
        Route::get('customer/config-sgsst/workplace/get', 'Wgroup\Controllers\CustomerConfigWorkPlaceController@get');
        Route::get('customer/config-sgsst/workplace/download', 'Wgroup\Controllers\CustomerConfigWorkPlaceController@download');

        Route::post('customer/config-sgsst/macro', 'Wgroup\Controllers\CustomerConfigMacroProcessesController@index');
        Route::post('customer/config-sgsst/macro/save', 'Wgroup\Controllers\CustomerConfigMacroProcessesController@save');
        Route::post('customer/config-sgsst/macro/delete', 'Wgroup\Controllers\CustomerConfigMacroProcessesController@delete');
        Route::post('customer/config-sgsst/macro/import', 'Wgroup\Controllers\CustomerConfigMacroProcessesController@import');
        Route::post('customer/config-sgsst/macro/list', 'Wgroup\Controllers\CustomerConfigMacroProcessesController@listIndex');
        Route::get('customer/config-sgsst/macro/get', 'Wgroup\Controllers\CustomerConfigMacroProcessesController@get');
        Route::get('customer/config-sgsst/macro/download', 'Wgroup\Controllers\CustomerConfigMacroProcessesController@download');

        Route::post('customer/config-sgsst/process', 'Wgroup\Controllers\CustomerConfigProcessesController@index');
        Route::post('customer/config-sgsst/process/save', 'Wgroup\Controllers\CustomerConfigProcessesController@save');
        Route::post('customer/config-sgsst/process/delete', 'Wgroup\Controllers\CustomerConfigProcessesController@delete');
        Route::post('customer/config-sgsst/process/import', 'Wgroup\Controllers\CustomerConfigProcessesController@import');
        Route::post('customer/config-sgsst/process/list', 'Wgroup\Controllers\CustomerConfigProcessesController@listIndex');
        Route::get('customer/config-sgsst/process/get', 'Wgroup\Controllers\CustomerConfigProcessesController@get');
        Route::get('customer/config-sgsst/process/download', 'Wgroup\Controllers\CustomerConfigProcessesController@download');

        Route::post('customer/config-sgsst/job-data', 'Wgroup\Controllers\CustomerConfigJobDataController@index');
        Route::post('customer/config-sgsst/job-data/save', 'Wgroup\Controllers\CustomerConfigJobDataController@save');
        Route::post('customer/config-sgsst/job-data/delete', 'Wgroup\Controllers\CustomerConfigJobDataController@delete');
        Route::post('customer/config-sgsst/job-data/import', 'Wgroup\Controllers\CustomerConfigJobDataController@import');
        Route::post('customer/config-sgsst/job-data/list', 'Wgroup\Controllers\CustomerConfigJobDataController@listIndex');
        Route::post('customer/config-sgsst/job-data/listByWorkPlace', 'Wgroup\Controllers\CustomerConfigJobDataController@listIndexByWorkPlace');
        Route::get('customer/config-sgsst/job-data/get', 'Wgroup\Controllers\CustomerConfigJobDataController@get');
        Route::get('customer/config-sgsst/job-data/download', 'Wgroup\Controllers\CustomerConfigJobDataController@download');

        Route::post('customer/config-sgsst/job', 'Wgroup\Controllers\CustomerConfigJobController@index');
        Route::post('customer/config-sgsst/job/save', 'Wgroup\Controllers\CustomerConfigJobController@save');
        Route::post('customer/config-sgsst/job/delete', 'Wgroup\Controllers\CustomerConfigJobController@delete');
        Route::post('customer/config-sgsst/job/import', 'Wgroup\Controllers\CustomerConfigJobController@import');
        Route::post('customer/config-sgsst/job/list', 'Wgroup\Controllers\CustomerConfigJobController@listIndex');
        Route::post('customer/config-sgsst/job/listByWorkPlace', 'Wgroup\Controllers\CustomerConfigJobController@listIndexByWorkPlace');
        Route::get('customer/config-sgsst/job/get', 'Wgroup\Controllers\CustomerConfigJobController@get');
        Route::get('customer/config-sgsst/job/download', 'Wgroup\Controllers\CustomerConfigJobController@download');

        Route::post('customer/config-sgsst/activity', 'Wgroup\Controllers\CustomerConfigActivityController@index');
        Route::post('customer/config-sgsst/activity/save', 'Wgroup\Controllers\CustomerConfigActivityController@save');
        Route::post('customer/config-sgsst/activity/delete', 'Wgroup\Controllers\CustomerConfigActivityController@delete');
        Route::post('customer/config-sgsst/activity/import', 'Wgroup\Controllers\CustomerConfigActivityController@import');
        Route::post('customer/config-sgsst/activity/list', 'Wgroup\Controllers\CustomerConfigActivityController@listIndex');
        Route::post('customer/config-sgsst/activity/listBy', 'Wgroup\Controllers\CustomerConfigActivityController@listBy');
        Route::get('customer/config-sgsst/activity/get', 'Wgroup\Controllers\CustomerConfigActivityController@get');
        Route::get('customer/config-sgsst/activity/download', 'Wgroup\Controllers\CustomerConfigActivityController@download');


        Route::post('customer/config-sgsst/activity-process', 'Wgroup\Controllers\CustomerConfigActivityProcessController@index');
        Route::post('customer/config-sgsst/activity-process/save', 'Wgroup\Controllers\CustomerConfigActivityProcessController@save');
        Route::post('customer/config-sgsst/activity-process/delete', 'Wgroup\Controllers\CustomerConfigActivityProcessController@delete');
        Route::post('customer/config-sgsst/activity-process/import', 'Wgroup\Controllers\CustomerConfigActivityProcessController@import');
        Route::post('customer/config-sgsst/activity-process/list', 'Wgroup\Controllers\CustomerConfigActivityProcessController@listIndex');
        Route::get('customer/config-sgsst/activity-process/get', 'Wgroup\Controllers\CustomerConfigActivityProcessController@get');
        Route::get('customer/config-sgsst/activity-process/download', 'Wgroup\Controllers\CustomerConfigActivityProcessController@download');


        Route::post('customer/config-sgsst/job-activity', 'Wgroup\Controllers\CustomerConfigJobActivityController@index');
        Route::post('customer/config-sgsst/job-activity/save', 'Wgroup\Controllers\CustomerConfigJobActivityController@save');
        Route::post('customer/config-sgsst/job-activity/delete', 'Wgroup\Controllers\CustomerConfigJobActivityController@delete');
        Route::post('customer/config-sgsst/job-activity/import', 'Wgroup\Controllers\CustomerConfigJobActivityController@import');
        Route::post('customer/config-sgsst/job-activity/list', 'Wgroup\Controllers\CustomerConfigJobActivityController@listIndex');
        Route::post('customer/config-sgsst/job-activity/listBy', 'Wgroup\Controllers\CustomerConfigJobActivityController@listBy');
        Route::get('customer/config-sgsst/job-activity/get', 'Wgroup\Controllers\CustomerConfigJobActivityController@get');
        Route::get('customer/config-sgsst/job-activity/download', 'Wgroup\Controllers\CustomerConfigJobActivityController@download');

        Route::post('customer/config-sgsst/job-activity-hazard', 'Wgroup\Controllers\CustomerConfigJobActivityHazardController@index');
        Route::post('customer/config-sgsst/job-activity-hazard/save', 'Wgroup\Controllers\CustomerConfigJobActivityHazardController@save');
        Route::post('customer/config-sgsst/job-activity-hazard/update', 'Wgroup\Controllers\CustomerConfigJobActivityHazardController@update');
        Route::post('customer/config-sgsst/job-activity-hazard/delete', 'Wgroup\Controllers\CustomerConfigJobActivityHazardController@delete');
        Route::post('customer/config-sgsst/job-activity-hazard/import', 'Wgroup\Controllers\CustomerConfigJobActivityHazardController@import');
        Route::post('customer/config-sgsst/job-activity-hazard/list', 'Wgroup\Controllers\CustomerConfigJobActivityHazardController@listIndex');
        Route::get('customer/config-sgsst/job-activity-hazard/get', 'Wgroup\Controllers\CustomerConfigJobActivityHazardController@get');
        Route::get('customer/config-sgsst/job-activity-hazard/download', 'Wgroup\Controllers\CustomerConfigJobActivityHazardController@download');

        Route::post('customer/config-sgsst/job-activity-document', 'Wgroup\Controllers\CustomerConfigJobActivityDocumentController@index');
        Route::post('customer/config-sgsst/job-activity-document/save', 'Wgroup\Controllers\CustomerConfigJobActivityDocumentController@save');
        Route::post('customer/config-sgsst/job-activity-document/delete', 'Wgroup\Controllers\CustomerConfigJobActivityDocumentController@delete');
        Route::post('customer/config-sgsst/job-activity-document/import', 'Wgroup\Controllers\CustomerConfigJobActivityDocumentController@import');
        Route::post('customer/config-sgsst/job-activity-document/list', 'Wgroup\Controllers\CustomerConfigJobActivityDocumentController@listIndex');
        Route::get('customer/config-sgsst/job-activity-document/get', 'Wgroup\Controllers\CustomerConfigJobActivityDocumentController@get');
        Route::get('customer/config-sgsst/job-activity-document/download', 'Wgroup\Controllers\CustomerConfigJobActivityDocumentController@download');

        Route::post('customer/config-sgsst/job-activity-hazard/intervention', 'Wgroup\Controllers\CustomerConfigJobActivityInterventionController@index');
        Route::post('customer/config-sgsst/job-activity-hazard/intervention-prioritize', 'Wgroup\Controllers\CustomerConfigJobActivityInterventionController@indexPrioritize');
        Route::post('customer/config-sgsst/job-activity-hazard/intervention-historical', 'Wgroup\Controllers\CustomerConfigJobActivityInterventionController@indexHistorical');
        Route::post('customer/config-sgsst/job-activity-hazard/intervention/delete', 'Wgroup\Controllers\CustomerConfigJobActivityInterventionController@delete');
        Route::get('customer/config-sgsst/job-activity-hazard/intervention/export', 'Wgroup\Controllers\CustomerConfigJobActivityInterventionController@exportExcelSummary');
        Route::get('customer/config-sgsst/job-activity-hazard/intervention-prioritize/export', 'Wgroup\Controllers\CustomerConfigJobActivityInterventionController@exportExcelPrioritize');
        Route::get('customer/config-sgsst/job-activity-hazard/intervention-historical/export', 'Wgroup\Controllers\CustomerConfigJobActivityInterventionController@exportExcelHistorical');


        Route::get('customer/config-sgsst/job-activity-hazard/intervention/action-plan', 'Wgroup\Controllers\CustomerConfigJobActivityInterventionActionPlanController@get');
        Route::post('customer/config-sgsst/job-activity-hazard/intervention/action-plan/save', 'Wgroup\Controllers\CustomerConfigJobActivityInterventionActionPlanController@save');

        Route::post('customer/config-sgsst/wizard/list', 'Wgroup\Controllers\CustomerConfigController@listIndex');
        Route::post('customer/config-sgsst/wizard/listClassification', 'Wgroup\Controllers\CustomerConfigController@listIndexHazardClassification');
        Route::post('customer/config-sgsst/wizard/listType', 'Wgroup\Controllers\CustomerConfigController@listIndexHazardType');
        Route::post('customer/config-sgsst/wizard/listDescription', 'Wgroup\Controllers\CustomerConfigController@listIndexHazardDescription');
        Route::post('customer/config-sgsst/wizard/listEffect', 'Wgroup\Controllers\CustomerConfigController@listIndexHazardEffect');
        Route::post('customer/config-sgsst/wizard/listLevel', 'Wgroup\Controllers\CustomerConfigController@listIndexHazardLevel');


        Route::get('customer/safety-inspection-config-header', 'Wgroup\Controllers\CustomerSafetyInspectionConfigHeaderController@get');
        Route::post('customer/safety-inspection-config-header', 'Wgroup\Controllers\CustomerSafetyInspectionConfigHeaderController@index');
        Route::post('customer/safety-inspection-config-header/save', 'Wgroup\Controllers\CustomerSafetyInspectionConfigHeaderController@save');
        Route::post('customer/safety-inspection-config-header/delete', 'Wgroup\Controllers\CustomerSafetyInspectionConfigHeaderController@delete');
        Route::post('customer/safety-inspection-config-header/list', 'Wgroup\Controllers\CustomerSafetyInspectionConfigHeaderController@listIndex');

        Route::get('customer/safety-inspection-config-header-field', 'Wgroup\Controllers\CustomerSafetyInspectionConfigHeaderFieldController@get');
        Route::post('customer/safety-inspection-config-header-field', 'Wgroup\Controllers\CustomerSafetyInspectionConfigHeaderFieldController@index');
        Route::post('customer/safety-inspection-config-header-field/save', 'Wgroup\Controllers\CustomerSafetyInspectionConfigHeaderFieldController@save');
        Route::post('customer/safety-inspection-config-header-field/delete', 'Wgroup\Controllers\CustomerSafetyInspectionConfigHeaderFieldController@delete');

        Route::get('customer/safety-inspection-config-list', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListController@get');
        Route::post('customer/safety-inspection-config-list', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListController@index');
        Route::post('customer/safety-inspection-config-list/save', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListController@save');
        Route::post('customer/safety-inspection-config-list/delete', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListController@delete');
        Route::post('customer/safety-inspection-config-list/list', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListController@listIndex');

        Route::get('customer/safety-inspection-config-list-group', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListGroupController@get');
        Route::post('customer/safety-inspection-config-list-group', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListGroupController@index');
        Route::post('customer/safety-inspection-config-list-group/save', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListGroupController@save');
        Route::post('customer/safety-inspection-config-list-group/delete', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListGroupController@delete');
        Route::post('customer/safety-inspection-config-list-group/list', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListGroupController@listIndex');

        Route::get('customer/safety-inspection-config-list-validation', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListValidationController@get');
        Route::post('customer/safety-inspection-config-list-validation', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListValidationController@index');
        Route::post('customer/safety-inspection-config-list-validation/save', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListValidationController@save');
        Route::post('customer/safety-inspection-config-list-validation/delete', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListValidationController@delete');

        Route::get('customer/safety-inspection-config-list-item', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListItemController@get');
        Route::post('customer/safety-inspection-config-list-item', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListItemController@index');
        Route::post('customer/safety-inspection-config-list-item/save', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListItemController@save');
        Route::post('customer/safety-inspection-config-list-item/delete', 'Wgroup\Controllers\CustomerSafetyInspectionConfigListItemController@delete');

        Route::get('customer/safety-inspection', 'Wgroup\Controllers\CustomerSafetyInspectionController@get');
        Route::get('customer/safety-inspection/summary-export-excel', 'Wgroup\Controllers\CustomerSafetyInspectionController@summaryExportExcel');
        Route::get('customer/safety-inspection/export-excel', 'Wgroup\Controllers\CustomerSafetyInspectionController@summaryExportExcel');
        Route::post('customer/safety-inspection', 'Wgroup\Controllers\CustomerSafetyInspectionController@index');
        Route::post('customer/safety-inspection/save', 'Wgroup\Controllers\CustomerSafetyInspectionController@save');
        Route::post('customer/safety-inspection/delete', 'Wgroup\Controllers\CustomerSafetyInspectionController@delete');
        Route::post('customer/safety-inspection/summary', 'Wgroup\Controllers\CustomerSafetyInspectionController@summaryIndex');
        Route::post('customer/safety-inspection/chart', 'Wgroup\Controllers\CustomerSafetyInspectionController@chart');
        Route::post('customer/safety-inspection/action', 'Wgroup\Controllers\CustomerSafetyInspectionController@actionReport');
        Route::post('customer/safety-inspection/activity', 'Wgroup\Controllers\CustomerSafetyInspectionController@activityReport');
        Route::post('customer/safety-inspection/report', 'Wgroup\Controllers\CustomerSafetyInspectionController@report');

        Route::get('customer/safety-inspection-header-field', 'Wgroup\Controllers\CustomerSafetyInspectionHeaderFieldController@get');
        Route::post('customer/safety-inspection-header-field', 'Wgroup\Controllers\CustomerSafetyInspectionHeaderFieldController@index');
        Route::post('customer/safety-inspection-header-field/save', 'Wgroup\Controllers\CustomerSafetyInspectionHeaderFieldController@save');
        Route::post('customer/safety-inspection-header-field/delete', 'Wgroup\Controllers\CustomerSafetyInspectionHeaderFieldController@delete');

        Route::get('customer/safety-inspection-list-item', 'Wgroup\Controllers\CustomerSafetyInspectionListItemController@get');
        Route::post('customer/safety-inspection-list-item', 'Wgroup\Controllers\CustomerSafetyInspectionListItemController@index');
        Route::post('customer/safety-inspection-list-item/save', 'Wgroup\Controllers\CustomerSafetyInspectionListItemController@save');
        Route::post('customer/safety-inspection-list-item/delete', 'Wgroup\Controllers\CustomerSafetyInspectionListItemController@delete');
        Route::post('customer/safety-inspection-list-item/wizard', 'Wgroup\Controllers\CustomerSafetyInspectionListItemController@wizard');
        Route::post('customer/safety-inspection-list-item/report', 'Wgroup\Controllers\CustomerSafetyInspectionListItemController@report');


        Route::get('customer/safety-inspection-list-item-document', 'Wgroup\Controllers\CustomerSafetyInspectionListItemDocumentController@get');
        Route::post('customer/safety-inspection-list-item-document', 'Wgroup\Controllers\CustomerSafetyInspectionListItemDocumentController@index');
        Route::post('customer/safety-inspection-list-item-document/save', 'Wgroup\Controllers\CustomerSafetyInspectionListItemDocumentController@save');
        Route::post('customer/safety-inspection-list-item-document/delete', 'Wgroup\Controllers\CustomerSafetyInspectionListItemDocumentController@delete');
        Route::post('customer/safety-inspection-list-item-document/upload', 'Wgroup\Controllers\CustomerSafetyInspectionListItemDocumentController@upload');

        Route::get('customer/safety-inspection-list-observation', 'Wgroup\Controllers\CustomerSafetyInspectionListObservationController@get');
        Route::post('customer/safety-inspection-list-observation', 'Wgroup\Controllers\CustomerSafetyInspectionListObservationController@index');
        Route::post('customer/safety-inspection-list-observation/save', 'Wgroup\Controllers\CustomerSafetyInspectionListObservationController@save');
        Route::post('customer/safety-inspection-list-observation/delete', 'Wgroup\Controllers\CustomerSafetyInspectionListObservationController@delete');

        Route::get('customer/safety-inspection-list-item/action-plan', 'Wgroup\Controllers\CustomerSafetyInspectionListItemActionPlanController@get');
        Route::post('customer/safety-inspection-list-item/action-plan', 'Wgroup\Controllers\CustomerSafetyInspectionListItemActionPlanController@index');
        Route::post('customer/safety-inspection-list-item/action-plan/save', 'Wgroup\Controllers\CustomerSafetyInspectionListItemActionPlanController@save');
        Route::post('customer/safety-inspection-list-item/action-plan/delete', 'Wgroup\Controllers\CustomerSafetyInspectionListItemActionPlanController@delete');
        Route::post('customer/safety-inspection-list-item/action-plan/upload', 'Wgroup\Controllers\CustomerSafetyInspectionListItemActionPlanController@upload');

        Route::get('customer/contractor-safety-inspection-list-item', 'Wgroup\Controllers\CustomerContractorSafetyInspectionListItemController@get');
        Route::post('customer/contractor-safety-inspection-list-item', 'Wgroup\Controllers\CustomerContractorSafetyInspectionListItemController@index');
        Route::post('customer/contractor-safety-inspection-list-item/save', 'Wgroup\Controllers\CustomerContractorSafetyInspectionListItemController@save');
        Route::post('customer/contractor-safety-inspection-list-item/delete', 'Wgroup\Controllers\CustomerContractorSafetyInspectionListItemController@delete');
        Route::post('customer/contractor-safety-inspection-list-item/wizard', 'Wgroup\Controllers\CustomerContractorSafetyInspectionListItemController@wizard');
        Route::post('customer/contractor-safety-inspection-list-item/report', 'Wgroup\Controllers\CustomerContractorSafetyInspectionListItemController@report');

        Route::post('customer/work-medicine', 'Wgroup\Controllers\CustomerWorkMedicineController@index');
        Route::post('customer/work-medicine-employee', 'Wgroup\Controllers\CustomerWorkMedicineController@employeeIndex');
        Route::post('customer/work-medicine/save', 'Wgroup\Controllers\CustomerWorkMedicineController@save');
        Route::post('customer/work-medicine/delete', 'Wgroup\Controllers\CustomerWorkMedicineController@delete');
        Route::post('customer/work-medicine/import', 'Wgroup\Controllers\CustomerWorkMedicineController@import');
        Route::get('customer/work-medicine', 'Wgroup\Controllers\CustomerWorkMedicineController@get');
        Route::get('customer/work-medicine/download', 'Wgroup\Controllers\CustomerWorkMedicineController@download');

        Route::post('customer/work-medicine/complementary-test', 'Wgroup\Controllers\CustomerWorkMedicineComplementaryTestController@index');
        Route::post('customer/work-medicine/complementary-test/save', 'Wgroup\Controllers\CustomerWorkMedicineComplementaryTestController@save');
        Route::post('customer/work-medicine/complementary-test/delete', 'Wgroup\Controllers\CustomerWorkMedicineComplementaryTestController@delete');
        Route::post('customer/work-medicine/complementary-test/import', 'Wgroup\Controllers\CustomerWorkMedicineComplementaryTestController@import');
        Route::get('customer/work-medicine/complementary-test', 'Wgroup\Controllers\CustomerWorkMedicineComplementaryTestController@get');
        Route::get('customer/work-medicine/complementary-test/download', 'Wgroup\Controllers\CustomerWorkMedicineComplementaryTestController@download');

        Route::post('customer/work-medicine/sve', 'Wgroup\Controllers\CustomerWorkMedicineSveController@index');
        Route::post('customer/work-medicine/sve/save', 'Wgroup\Controllers\CustomerWorkMedicineSveController@save');
        Route::post('customer/work-medicine/sve/delete', 'Wgroup\Controllers\CustomerWorkMedicineSveController@delete');
        Route::post('customer/work-medicine/sve/import', 'Wgroup\Controllers\CustomerWorkMedicineSveController@import');
        Route::get('customer/work-medicine/sve', 'Wgroup\Controllers\CustomerWorkMedicineSveController@get');
        Route::get('customer/work-medicine/sve/download', 'Wgroup\Controllers\CustomerWorkMedicineSveController@download');

        Route::post('customer/work-medicine/tracking', 'Wgroup\Controllers\CustomerWorkMedicineTrackingController@index');
        Route::post('customer/work-medicine/tracking/save', 'Wgroup\Controllers\CustomerWorkMedicineTrackingController@save');
        Route::post('customer/work-medicine/tracking/delete', 'Wgroup\Controllers\CustomerWorkMedicineTrackingController@delete');
        Route::post('customer/work-medicine/tracking/import', 'Wgroup\Controllers\CustomerWorkMedicineTrackingController@import');
        Route::get('customer/work-medicine/tracking', 'Wgroup\Controllers\CustomerWorkMedicineTrackingController@get');
        Route::get('customer/work-medicine/tracking/download', 'Wgroup\Controllers\CustomerWorkMedicineTrackingController@download');


        Route::post('customer/health-damage/diagnostic-source', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceController@index');
        Route::post('customer/health-damage/diagnostic-source/save', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceController@save');
        Route::post('customer/health-damage/diagnostic-source/delete', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceController@delete');
        Route::post('customer/health-damage/diagnostic-source/import', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceController@import');
        Route::get('customer/health-damage/diagnostic-source', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceController@get');
        Route::get('customer/health-damage/diagnostic-source/download', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceController@download');


        Route::post('customer/health-damage/diagnostic-source-detail', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@index');
        Route::post('customer/health-damage/diagnostic-source-detail/save', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@save');
        Route::post('customer/health-damage/diagnostic-source-detail/delete', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@delete');
        Route::post('customer/health-damage/diagnostic-source-detail/import', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@import');
        Route::get('customer/health-damage/diagnostic-source-detail', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@get');
        Route::get('customer/health-damage/diagnostic-source-detail/download', 'Wgroup\Controllers\CustomerHealthDamageDiagnosticSourceDetailController@download');


        Route::post('customer/health-damage/restriction', 'Wgroup\Controllers\CustomerHealthDamageRestrictionController@index');
        Route::post('customer/health-damage/restriction/save', 'Wgroup\Controllers\CustomerHealthDamageRestrictionController@save');
        Route::post('customer/health-damage/restriction/delete', 'Wgroup\Controllers\CustomerHealthDamageRestrictionController@delete');
        Route::post('customer/health-damage/restriction/import', 'Wgroup\Controllers\CustomerHealthDamageRestrictionController@import');
        Route::get('customer/health-damage/restriction', 'Wgroup\Controllers\CustomerHealthDamageRestrictionController@get');
        Route::get('customer/health-damage/restriction/download', 'Wgroup\Controllers\CustomerHealthDamageRestrictionController@download');


        Route::post('customer/health-damage/restriction-detail', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDetailController@index');
        Route::post('customer/health-damage/restriction-detail/save', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDetailController@save');
        Route::post('customer/health-damage/restriction-detail/delete', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDetailController@delete');
        Route::post('customer/health-damage/restriction-detail/import', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDetailController@import');
        Route::post('customer/health-damage/restriction-detail/upload', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDetailController@upload');
        Route::get('customer/health-damage/restriction-detail', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDetailController@get');
        Route::get('customer/health-damage/restriction-detail/download', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDetailController@download');

        Route::post('customer/health-damage/restriction-observation', 'Wgroup\Controllers\CustomerHealthDamageRestrictionObservationController@index');
        Route::post('customer/health-damage/restriction-observation-all', 'Wgroup\Controllers\CustomerHealthDamageRestrictionObservationController@indexAll');
        Route::post('customer/health-damage/restriction-observation/save', 'Wgroup\Controllers\CustomerHealthDamageRestrictionObservationController@save');
        Route::post('customer/health-damage/restriction-observation/delete', 'Wgroup\Controllers\CustomerHealthDamageRestrictionObservationController@delete');
        Route::post('customer/health-damage/restriction-observation/import', 'Wgroup\Controllers\CustomerHealthDamageRestrictionObservationController@import');
        Route::post('customer/health-damage/restriction-observation/upload', 'Wgroup\Controllers\CustomerHealthDamageRestrictionObservationController@upload');
        Route::get('customer/health-damage/restriction-observation', 'Wgroup\Controllers\CustomerHealthDamageRestrictionObservationController@get');
        Route::get('customer/health-damage/restriction-observation/download', 'Wgroup\Controllers\CustomerHealthDamageRestrictionObservationController@download');

        Route::post('customer/health-damage/restriction-document', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDocumentController@index');
        Route::post('customer/health-damage/restriction-document/save', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDocumentController@save');
        Route::post('customer/health-damage/restriction-document/delete', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDocumentController@delete');
        Route::post('customer/health-damage/restriction-document/import', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDocumentController@import');
        Route::post('customer/health-damage/restriction-document/upload', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDocumentController@upload');
        Route::get('customer/health-damage/restriction-document', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDocumentController@get');
        Route::get('customer/health-damage/restriction-document/download', 'Wgroup\Controllers\CustomerHealthDamageRestrictionDocumentController@download');

        //--------------------------------------------------------------------------------------------------------------------------Qs
        Route::post('customer/health-damage/qs', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceController@index');
        Route::post('customer/health-damage/qs/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceController@save');
        Route::post('customer/health-damage/qs/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceController@delete');
        Route::post('customer/health-damage/qs/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceController@import');
        Route::get('customer/health-damage/qs', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceController@get');
        Route::get('customer/health-damage/qs/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceController@download');

        Route::post('customer/health-damage/qs/document', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDocumentController@index');
        Route::post('customer/health-damage/qs/document-all', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDocumentController@indexAll');
        Route::post('customer/health-damage/qs/document/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDocumentController@save');
        Route::post('customer/health-damage/qs/document/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDocumentController@delete');
        Route::post('customer/health-damage/qs/document/upload', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDocumentController@upload');
        Route::get('customer/health-damage/qs/document', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDocumentController@get');
        Route::get('customer/health-damage/qs/document/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDocumentController@download');
        Route::get('customer/health-damage/qs/document/download-all', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDocumentController@downloadAll');

        Route::post('customer/health-damage/qs/diagnostic', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticController@index');
        Route::post('customer/health-damage/qs/diagnostic/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticController@save');
        Route::post('customer/health-damage/qs/diagnostic/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticController@delete');
        Route::post('customer/health-damage/qs/diagnostic/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticController@import');
        Route::get('customer/health-damage/qs/diagnostic', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticController@get');
        Route::get('customer/health-damage/qs/diagnostic/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticController@download');

        Route::post('customer/health-damage/qs/diagnostic-support', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticSupportController@index');
        Route::post('customer/health-damage/qs/diagnostic-support/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticSupportController@save');
        Route::post('customer/health-damage/qs/diagnostic-support/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticSupportController@delete');
        Route::post('customer/health-damage/qs/diagnostic-support/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticSupportController@import');
        Route::get('customer/health-damage/qs/diagnostic-support', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticSupportController@get');
        Route::get('customer/health-damage/qs/diagnostic-support/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticSupportController@download');

        Route::post('customer/health-damage/qs/diagnostic-document', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticDocumentController@index');
        Route::post('customer/health-damage/qs/diagnostic-document/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticDocumentController@save');
        Route::post('customer/health-damage/qs/diagnostic-document/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticDocumentController@delete');
        Route::post('customer/health-damage/qs/diagnostic-document/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticDocumentController@import');
        Route::get('customer/health-damage/qs/diagnostic-document', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticDocumentController@get');
        Route::get('customer/health-damage/qs/diagnostic-document/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceDiagnosticDocumentController@download');

        Route::post('customer/health-damage/qs/opportunity', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityController@index');
        Route::post('customer/health-damage/qs/opportunity/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityController@save');
        Route::post('customer/health-damage/qs/opportunity/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityController@delete');
        Route::post('customer/health-damage/qs/opportunity/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityController@import');
        Route::get('customer/health-damage/qs/opportunity', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityController@get');
        Route::get('customer/health-damage/qs/opportunity/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityController@download');

        Route::post('customer/health-damage/qs/opportunity-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailController@index');
        Route::post('customer/health-damage/qs/opportunity-detail/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailController@save');
        Route::post('customer/health-damage/qs/opportunity-detail/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailController@delete');
        Route::post('customer/health-damage/qs/opportunity-detail/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailController@import');
        Route::get('customer/health-damage/qs/opportunity-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailController@get');
        Route::get('customer/health-damage/qs/opportunity-detail/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceOpportunityDetailController@download');

        Route::post('customer/health-damage/qs/regional', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalController@index');
        Route::post('customer/health-damage/qs/regional/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalController@save');
        Route::post('customer/health-damage/qs/regional/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalController@delete');
        Route::post('customer/health-damage/qs/regional/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalController@import');
        Route::get('customer/health-damage/qs/regional', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalController@get');
        Route::get('customer/health-damage/qs/regional/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalController@download');

        Route::post('customer/health-damage/qs/regional-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailController@index');
        Route::post('customer/health-damage/qs/regional-detail/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailController@save');
        Route::post('customer/health-damage/qs/regional-detail/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailController@delete');
        Route::post('customer/health-damage/qs/regional-detail/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailController@import');
        Route::get('customer/health-damage/qs/regional-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailController@get');
        Route::get('customer/health-damage/qs/regional-detail/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceRegionalDetailController@download');

        Route::post('customer/health-damage/qs/national', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalController@index');
        Route::post('customer/health-damage/qs/national/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalController@save');
        Route::post('customer/health-damage/qs/national/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalController@delete');
        Route::post('customer/health-damage/qs/national/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalController@import');
        Route::get('customer/health-damage/qs/national', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalController@get');
        Route::get('customer/health-damage/qs/national/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalController@download');

        Route::post('customer/health-damage/qs/national-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalDetailController@index');
        Route::post('customer/health-damage/qs/national-detail/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalDetailController@save');
        Route::post('customer/health-damage/qs/national-detail/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalDetailController@delete');
        Route::post('customer/health-damage/qs/national-detail/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalDetailController@import');
        Route::get('customer/health-damage/qs/national-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalDetailController@get');
        Route::get('customer/health-damage/qs/national-detail/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceNationalDetailController@download');


        Route::post('customer/health-damage/qs/justice', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeController@index');
        Route::post('customer/health-damage/qs/justice/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeController@save');
        Route::post('customer/health-damage/qs/justice/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeController@delete');
        Route::post('customer/health-damage/qs/justice/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeController@import');
        Route::get('customer/health-damage/qs/justice', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeController@get');
        Route::get('customer/health-damage/qs/justice/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeController@download');

        Route::post('customer/health-damage/qs/justice-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@index');
        Route::post('customer/health-damage/qs/justice-first-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@first');
        Route::post('customer/health-damage/qs/justice-second-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@second');
        Route::post('customer/health-damage/qs/justice-third-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@third');
        Route::post('customer/health-damage/qs/justice-detail/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@save');
        Route::post('customer/health-damage/qs/justice-detail/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@delete');
        Route::post('customer/health-damage/qs/justice-detail/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@import');
        Route::get('customer/health-damage/qs/justice-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@get');
        Route::get('customer/health-damage/qs/justice-detail/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationSourceJusticeDetailController@download');

        //--------------------------------------------------------------------------------------------------------------------------Ql
        Route::post('customer/health-damage/ql', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostController@index');
        Route::post('customer/health-damage/ql/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostController@save');
        Route::post('customer/health-damage/ql/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostController@delete');
        Route::post('customer/health-damage/ql/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostController@import');
        Route::get('customer/health-damage/ql', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostController@get');
        Route::get('customer/health-damage/ql/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostController@download');

        Route::post('customer/health-damage/ql/document', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostDocumentController@index');
        Route::post('customer/health-damage/ql/document-all', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostDocumentController@indexAll');
        Route::post('customer/health-damage/ql/document/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostDocumentController@save');
        Route::post('customer/health-damage/ql/document/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostDocumentController@delete');
        Route::post('customer/health-damage/ql/document/upload', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostDocumentController@upload');
        Route::get('customer/health-damage/ql/document', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostDocumentController@get');
        Route::get('customer/health-damage/ql/document/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostDocumentController@download');
        Route::get('customer/health-damage/ql/document/download-all', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostDocumentController@downloadAll');

        Route::post('customer/health-damage/ql/opportunity', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityController@index');
        Route::post('customer/health-damage/ql/opportunity/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityController@save');
        Route::post('customer/health-damage/ql/opportunity/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityController@delete');
        Route::post('customer/health-damage/ql/opportunity/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityController@import');
        Route::get('customer/health-damage/ql/opportunity', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityController@get');
        Route::get('customer/health-damage/ql/opportunity/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityController@download');

        Route::post('customer/health-damage/ql/opportunity-diagnostic', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityDiagnosticController@index');
        Route::post('customer/health-damage/ql/opportunity-diagnostic/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityDiagnosticController@save');
        Route::post('customer/health-damage/ql/opportunity-diagnostic/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityDiagnosticController@delete');
        Route::post('customer/health-damage/ql/opportunity-diagnostic/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityDiagnosticController@import');
        Route::get('customer/health-damage/ql/opportunity-diagnostic', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityDiagnosticController@get');
        Route::get('customer/health-damage/ql/opportunity-diagnostic/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostOpportunityDiagnosticController@download');

        Route::post('customer/health-damage/ql/regional', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalController@index');
        Route::post('customer/health-damage/ql/regional/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalController@save');
        Route::post('customer/health-damage/ql/regional/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalController@delete');
        Route::post('customer/health-damage/ql/regional/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalController@import');
        Route::get('customer/health-damage/ql/regional', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalController@get');
        Route::get('customer/health-damage/ql/regional/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalController@download');

        Route::post('customer/health-damage/qs/regional-diagnostic', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalDiagnosticController@index');
        Route::post('customer/health-damage/qs/regional-diagnostic/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalDiagnosticController@save');
        Route::post('customer/health-damage/qs/regional-diagnostic/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalDiagnosticController@delete');
        Route::post('customer/health-damage/qs/regional-diagnostic/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalDiagnosticController@import');
        Route::get('customer/health-damage/qs/regional-diagnostic', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalDiagnosticController@get');
        Route::get('customer/health-damage/qs/regional-diagnostic/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostRegionalDiagnosticController@download');

        Route::post('customer/health-damage/ql/national', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalController@index');
        Route::post('customer/health-damage/ql/national/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalController@save');
        Route::post('customer/health-damage/ql/national/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalController@delete');
        Route::post('customer/health-damage/ql/national/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalController@import');
        Route::get('customer/health-damage/ql/national', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalController@get');
        Route::get('customer/health-damage/ql/national/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalController@download');

        Route::post('customer/health-damage/ql/national-diagnostic', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalDiagnosticController@index');
        Route::post('customer/health-damage/ql/national-diagnostic/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalDiagnosticController@save');
        Route::post('customer/health-damage/ql/national-diagnostic/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalDiagnosticController@delete');
        Route::post('customer/health-damage/ql/national-diagnostic/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalDiagnosticController@import');
        Route::get('customer/health-damage/ql/national-diagnostic', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalDiagnosticController@get');
        Route::get('customer/health-damage/ql/national-diagnostic/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostNationalDiagnosticController@download');


        Route::post('customer/health-damage/ql/justice', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeController@index');
        Route::post('customer/health-damage/ql/justice/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeController@save');
        Route::post('customer/health-damage/ql/justice/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeController@delete');
        Route::post('customer/health-damage/ql/justice/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeController@import');
        Route::get('customer/health-damage/ql/justice', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeController@get');
        Route::get('customer/health-damage/ql/justice/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeController@download');

        Route::post('customer/health-damage/ql/justice-detail', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeDiagnosticController@index');
        Route::post('customer/health-damage/ql/justice-diagnostic/save', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeDiagnosticController@save');
        Route::post('customer/health-damage/ql/justice-diagnostic/delete', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeDiagnosticController@delete');
        Route::post('customer/health-damage/ql/justice-diagnostic/import', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeDiagnosticController@import');
        Route::get('customer/health-damage/ql/justice-diagnostic', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeDiagnosticController@get');
        Route::get('customer/health-damage/ql/justice-diagnostic/download', 'Wgroup\Controllers\CustomerHealthDamageQualificationLostJusticeDiagnosticController@download');


        //--------------------------------------------------------------------------------------------------------------------------ADMINISTRATIVE PROCESS
        Route::post('customer/health-damage/administrative-process', 'Wgroup\Controllers\CustomerHealthDamageAdministrativeProcessController@index');
        Route::post('customer/health-damage/administrative-process/save', 'Wgroup\Controllers\CustomerHealthDamageAdministrativeProcessController@save');
        Route::post('customer/health-damage/administrative-process/delete', 'Wgroup\Controllers\CustomerHealthDamageAdministrativeProcessController@delete');
        Route::post('customer/health-damage/administrative-process/import', 'Wgroup\Controllers\CustomerHealthDamageAdministrativeProcessController@import');
        Route::get('customer/health-damage/administrative-process', 'Wgroup\Controllers\CustomerHealthDamageAdministrativeProcessController@get');
        Route::get('customer/health-damage/administrative-process/download', 'Wgroup\Controllers\CustomerHealthDamageAdministrativeProcessController@download');


        //--------------------------------------------------------------------------------------------------------------------------ANALYSIS
        Route::post('customer/health-damage/analysis', 'Wgroup\Controllers\CustomerHealthDamageAnalysisController@index');
        Route::post('customer/health-damage/analysis/filter-year', 'Wgroup\Controllers\CustomerHealthDamageAnalysisController@getYearFilter');
        Route::get('customer/health-damage/analysis/download', 'Wgroup\Controllers\CustomerHealthDamageAnalysisController@download');


        Route::post('configuration/program-prevention-question', 'Wgroup\Controllers\ProgramPreventionQuestionController@index');
        Route::post('configuration/program-prevention-question/save', 'Wgroup\Controllers\ProgramPreventionQuestionController@save');
        Route::post('configuration/program-prevention-question/delete', 'Wgroup\Controllers\ProgramPreventionQuestionController@delete');
        Route::post('configuration/program-prevention-question/upload', 'Wgroup\Controllers\ProgramPreventionQuestionController@delete');
        Route::get('configuration/program-prevention-question', 'Wgroup\Controllers\ProgramPreventionQuestionController@get');
        Route::get('configuration/program-prevention-question/download', 'Wgroup\Controllers\ProgramPreventionQuestionController@get');

        Route::post('configuration/program-prevention-question-classification', 'Wgroup\Controllers\ProgramPreventionQuestionClassificationController@index');
        Route::post('configuration/program-prevention-question-classification/save', 'Wgroup\Controllers\ProgramPreventionQuestionClassificationController@save');
        Route::post('configuration/program-prevention-question-classification/delete', 'Wgroup\Controllers\ProgramPreventionQuestionClassificationController@delete');
        Route::post('configuration/program-prevention-question-classification/upload', 'Wgroup\Controllers\ProgramPreventionQuestionClassificationController@delete');
        Route::get('configuration/program-prevention-question-classification', 'Wgroup\Controllers\ProgramPreventionQuestionClassificationController@get');
        Route::get('configuration/program-prevention-question-classification/download', 'Wgroup\Controllers\ProgramPreventionQuestionClassificationController@get');

        Route::post('configuration/program-prevention-document', 'Wgroup\Controllers\ProgramPreventionDocumentController@index');
        Route::post('configuration/program-prevention-document/save', 'Wgroup\Controllers\ProgramPreventionDocumentController@save');
        Route::post('configuration/program-prevention-document/delete', 'Wgroup\Controllers\ProgramPreventionDocumentController@delete');
        Route::post('configuration/program-prevention-document/upload', 'Wgroup\Controllers\ProgramPreventionDocumentController@upload');
        Route::post('configuration/program-prevention-document/question', 'Wgroup\Controllers\ProgramPreventionDocumentController@filterQuestion');
        Route::get('configuration/program-prevention-document', 'Wgroup\Controllers\ProgramPreventionDocumentController@get');
        Route::get('configuration/program-prevention-document/download', 'Wgroup\Controllers\ProgramPreventionDocumentController@download');


        Route::post('configuration/program-prevention-document-question', 'Wgroup\Controllers\ProgramPreventionDocumentQuestionController@index');
        Route::post('configuration/program-prevention-document-question/selected', 'Wgroup\Controllers\ProgramPreventionDocumentQuestionController@filterSelected');
        Route::post('configuration/program-prevention-document-question/save', 'Wgroup\Controllers\ProgramPreventionDocumentQuestionController@save');
        Route::post('configuration/program-prevention-document-question/delete', 'Wgroup\Controllers\ProgramPreventionDocumentQuestionController@delete');
        Route::post('configuration/program-prevention-document-question/upload', 'Wgroup\Controllers\ProgramPreventionDocumentQuestionController@upload');
        Route::get('configuration/program-prevention-document-question', 'Wgroup\Controllers\ProgramPreventionDocumentQuestionController@get');
        Route::get('configuration/program-prevention-document-question/download', 'Wgroup\Controllers\ProgramPreventionDocumentQuestionController@get');


        Route::post('customer/diagnostic-prevention-document', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentController@index');
        Route::post('customer/diagnostic-prevention-document/save', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentController@save');
        Route::post('customer/diagnostic-prevention-document/delete', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentController@delete');
        Route::post('customer/diagnostic-prevention-document/upload', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentController@upload');
        Route::post('customer/diagnostic-prevention-document/question', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentController@filterQuestion');
        Route::get('customer/diagnostic-prevention-document', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentController@get');
        Route::get('customer/diagnostic-prevention-document/download', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentController@download');

        Route::post('customer/diagnostic-prevention-document-question', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentQuestionController@index');
        Route::post('customer/diagnostic-prevention-document-question/selected', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentQuestionController@filterSelected');
        Route::post('customer/diagnostic-prevention-document-question/save', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentQuestionController@save');
        Route::post('customer/diagnostic-prevention-document-question/delete', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentQuestionController@delete');
        Route::post('customer/diagnostic-prevention-document-question/upload', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentQuestionController@upload');
        Route::get('customer/diagnostic-prevention-document-question', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentQuestionController@get');
        Route::get('customer/diagnostic-prevention-document-question/download', 'Wgroup\Controllers\CustomerDiagnosticPreventionDocumentQuestionController@download');


        Route::post('nephos-integration', 'Wgroup\Controllers\NephosIntegrationController@index');
        Route::post('nephos-integration/install', 'Wgroup\Controllers\NephosIntegrationController@install');
        Route::post('nephos-integration/remove', 'Wgroup\Controllers\NephosIntegrationController@remove');
        Route::post('nephos-integration/configure', 'Wgroup\Controllers\NephosIntegrationController@configure');
        Route::post('nephos-integration/disable', 'Wgroup\Controllers\NephosIntegrationController@disable');
        Route::post('nephos-integration/enable', 'Wgroup\Controllers\NephosIntegrationController@enable');

        Route::post('nephos-integration/install-customer', 'Wgroup\Controllers\NephosIntegrationController@onInstall');
        Route::post('nephos-integration/remove-customer', 'Wgroup\Controllers\NephosIntegrationController@onRemove');
        Route::post('nephos-integration/configure-customer', 'Wgroup\Controllers\NephosIntegrationController@onConfigure');
        Route::post('nephos-integration/disable-customer', 'Wgroup\Controllers\NephosIntegrationController@onDisable');
        Route::post('nephos-integration/enable-customer', 'Wgroup\Controllers\NephosIntegrationController@onEnable');


        Route::post('budget', 'Wgroup\Controllers\BudgetController@index');
        Route::post('budget/save', 'Wgroup\Controllers\BudgetController@save');
        Route::post('budget/delete', 'Wgroup\Controllers\BudgetController@delete');
        Route::post('budget/import', 'Wgroup\Controllers\BudgetController@import');
        Route::post('budget/classification', 'Wgroup\Controllers\BudgetController@getBudgets');
        Route::get('budget', 'Wgroup\Controllers\BudgetController@get');
        Route::get('budget/download', 'Wgroup\Controllers\BudgetController@download');

        /*Route::post('budget-detail', 'Wgroup\Controllers\BudgetDetailController@index');
        Route::post('budget-detail/save', 'Wgroup\Controllers\BudgetDetailController@save');
        Route::post('budget-detail/delete', 'Wgroup\Controllers\BudgetDetailController@delete');
        Route::post('budget-detail/import', 'Wgroup\Controllers\BudgetDetailController@import');
        Route::get('budget-detail', 'Wgroup\Controllers\BudgetDetailController@get');
        Route::get('budget-detail/download', 'Wgroup\Controllers\BudgetDetailController@download');*/


        Route::post('customer/investigation-al', 'Wgroup\Controllers\CustomerInvestigationAlController@index');
        Route::post('customer/investigation-al/save', 'Wgroup\Controllers\CustomerInvestigationAlController@save');
        Route::post('customer/investigation-al/saveCustomer', 'Wgroup\Controllers\CustomerInvestigationAlController@saveCustomer');
        Route::post('customer/investigation-al/saveEmployee', 'Wgroup\Controllers\CustomerInvestigationAlController@saveEmployee');
        Route::post('customer/investigation-al/saveAccident', 'Wgroup\Controllers\CustomerInvestigationAlController@saveAccident');
        Route::post('customer/investigation-al/saveSummary', 'Wgroup\Controllers\CustomerInvestigationAlController@saveSummary');
        Route::post('customer/investigation-al/saveEvent', 'Wgroup\Controllers\CustomerInvestigationAlController@saveEvent');
        Route::post('customer/investigation-al/saveAnalysis', 'Wgroup\Controllers\CustomerInvestigationAlController@saveAnalysis');
        Route::post('customer/investigation-al/saveCause', 'Wgroup\Controllers\CustomerInvestigationAlController@saveCause');
        Route::post('customer/investigation-al/activate', 'Wgroup\Controllers\CustomerInvestigationAlController@activate');
        Route::post('customer/investigation-al/update', 'Wgroup\Controllers\CustomerInvestigationAlController@update');
        Route::post('customer/investigation-al/delete', 'Wgroup\Controllers\CustomerInvestigationAlController@delete');
        Route::post('customer/investigation-al/upload', 'Wgroup\Controllers\CustomerInvestigationAlController@upload');
        Route::get('customer/investigation-al', 'Wgroup\Controllers\CustomerInvestigationAlController@get');
        Route::get('customer/investigation-al/get-cause', 'Wgroup\Controllers\CustomerInvestigationAlController@getCause');
        Route::get('customer/investigation-al/download-certificate', 'Wgroup\Controllers\CustomerInvestigationAlController@download');
        Route::get('customer/investigation-al/download-pdf', 'Wgroup\Controllers\CustomerInvestigationAlController@download');
        Route::get('customer/investigation-al/download-letter', 'Wgroup\Controllers\CustomerInvestigationAlController@downloadLetter');

        Route::post('customer/investigation-al/review-injury', 'Wgroup\Controllers\CustomerInvestigationAlController@reviewInjury');
        Route::post('customer/investigation-al/review-pivot', 'Wgroup\Controllers\CustomerInvestigationAlController@reviewPivot');
        Route::post('customer/investigation-al/review-filters', 'Wgroup\Controllers\CustomerInvestigationAlController@getReviewFilters');
        Route::post('customer/investigation-al/review-charts', 'Wgroup\Controllers\CustomerInvestigationAlController@getReviewCharts');
        Route::get('customer/investigation-al/review-injury/export', 'Wgroup\Controllers\CustomerInvestigationAlController@reviewInjuryExport');

        Route::post('customer/investigation-al/tracing', 'Wgroup\Controllers\CustomerInvestigationAlController@tracing');
        Route::get('customer/investigation-al/tracing/export', 'Wgroup\Controllers\CustomerInvestigationAlController@tracingExport');

        Route::post('customer/investigation-al/factor', 'Wgroup\Controllers\CustomerInvestigationAlFactorController@index');
        Route::post('customer/investigation-al/factor/save', 'Wgroup\Controllers\CustomerInvestigationAlFactorController@save');
        Route::post('customer/investigation-al/factor/delete', 'Wgroup\Controllers\CustomerInvestigationAlFactorController@delete');
        Route::post('customer/investigation-al/factor/upload', 'Wgroup\Controllers\CustomerInvestigationAlFactorController@upload');
        Route::get('customer/investigation-al/factor', 'Wgroup\Controllers\CustomerInvestigationAlFactorController@get');
        Route::get('customer/investigation-al/factor/download-certificate', 'Wgroup\Controllers\CustomerInvestigationAlFactorController@download');
        Route::get('customer/investigation-al/factor/download-pdf', 'Wgroup\Controllers\CustomerInvestigationAlFactorController@download');
        Route::get('customer/investigation-al/factor/fish-bone', 'Wgroup\Controllers\CustomerInvestigationAlFactorController@fishBone');

        Route::post('customer/investigation-al/control', 'Wgroup\Controllers\CustomerInvestigationAlControlController@index');
        Route::post('customer/investigation-al/control/save', 'Wgroup\Controllers\CustomerInvestigationAlControlController@save');
        Route::post('customer/investigation-al/control/delete', 'Wgroup\Controllers\CustomerInvestigationAlControlController@delete');
        Route::post('customer/investigation-al/control/upload', 'Wgroup\Controllers\CustomerInvestigationAlControlController@upload');
        Route::post('customer/investigation-al/control/next-control', 'Wgroup\Controllers\CustomerInvestigationAlControlController@getNextControl');
        Route::post('customer/investigation-al/control/update', 'Wgroup\Controllers\CustomerInvestigationAlControlController@update');
        Route::post('customer/investigation-al/control/analysis', 'Wgroup\Controllers\CustomerInvestigationAlControlController@analysis');
        Route::get('customer/investigation-al/control', 'Wgroup\Controllers\CustomerInvestigationAlControlController@get');
        Route::get('customer/investigation-al/control/export', 'Wgroup\Controllers\CustomerInvestigationAlControlController@export');
        Route::get('customer/investigation-al/control/download-pdf', 'Wgroup\Controllers\CustomerInvestigationAlControlController@download');


        Route::post('customer/investigation-al/measure', 'Wgroup\Controllers\CustomerInvestigationAlMeasureController@index');
        Route::post('customer/investigation-al/measure/save', 'Wgroup\Controllers\CustomerInvestigationAlMeasureController@save');
        Route::post('customer/investigation-al/measure/delete', 'Wgroup\Controllers\CustomerInvestigationAlMeasureController@delete');
        Route::post('customer/investigation-al/measure/upload', 'Wgroup\Controllers\CustomerInvestigationAlMeasureController@upload');
        Route::post('customer/investigation-al/measure/action-plan/save', 'Wgroup\Controllers\CustomerInvestigationAlMeasureActionPlanController@save');
        Route::get('customer/investigation-al/measure', 'Wgroup\Controllers\CustomerInvestigationAlMeasureController@get');
        Route::get('customer/investigation-al/measure/download-certificate', 'Wgroup\Controllers\CustomerInvestigationAlMeasureController@download');
        Route::get('customer/investigation-al/measure/download-pdf', 'Wgroup\Controllers\CustomerInvestigationAlMeasureController@download');
        Route::get('customer/investigation-al/measure/action-plan', 'Wgroup\Controllers\CustomerInvestigationAlMeasureActionPlanController@get');

        Route::post('customer/investigation-al/measure-tracking', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingController@index');
        Route::post('customer/investigation-al/measure-tracking-index', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingController@indexInvestigation');
        Route::post('customer/investigation-al/measure-tracking/save', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingController@save');
        Route::post('customer/investigation-al/measure-tracking/delete', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingController@delete');
        Route::post('customer/investigation-al/measure-tracking/upload', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingController@upload');
        Route::get('customer/investigation-al/measure-tracking', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingController@get');

        Route::post('customer/investigation-al/measure-tracking-evidence', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingEvidenceController@index');
        Route::post('customer/investigation-al/measure-tracking-evidence/save', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingEvidenceController@save');
        Route::post('customer/investigation-al/measure-tracking-evidence/delete', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingEvidenceController@delete');
        Route::post('customer/investigation-al/measure-tracking-evidence/upload', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingEvidenceController@upload');
        Route::get('customer/investigation-al/measure-tracking-evidence', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingEvidenceController@get');
        Route::get('customer/investigation-al/measure-tracking-evidence-download', 'Wgroup\Controllers\CustomerInvestigationAlMeasureTrackingEvidenceController@download');

        Route::post('customer/investigation-al/cause', 'Wgroup\Controllers\CustomerInvestigationAlCauseController@index');
        Route::post('customer/investigation-al/cause/save', 'Wgroup\Controllers\CustomerInvestigationAlCauseController@save');
        Route::post('customer/investigation-al/cause/delete', 'Wgroup\Controllers\CustomerInvestigationAlCauseController@delete');
        Route::post('customer/investigation-al/cause/upload', 'Wgroup\Controllers\CustomerInvestigationAlCauseController@upload');
        Route::get('customer/investigation-al/cause', 'Wgroup\Controllers\CustomerInvestigationAlCauseController@get');
        Route::get('customer/investigation-al/cause/download-certificate', 'Wgroup\Controllers\CustomerInvestigationAlCauseController@download');
        Route::get('customer/investigation-al/cause/download-pdf', 'Wgroup\Controllers\CustomerInvestigationAlCauseController@download');

        Route::post('customer/investigation-al/document', 'Wgroup\Controllers\CustomerInvestigationAlDocumentController@index');
        Route::post('customer/investigation-al/document/save', 'Wgroup\Controllers\CustomerInvestigationAlDocumentController@save');
        Route::post('customer/investigation-al/document/delete', 'Wgroup\Controllers\CustomerInvestigationAlDocumentController@delete');
        Route::post('customer/investigation-al/document/upload', 'Wgroup\Controllers\CustomerInvestigationAlDocumentController@upload');
        Route::get('customer/investigation-al/document', 'Wgroup\Controllers\CustomerInvestigationAlDocumentController@get');
        Route::get('customer/investigation-al/document/download', 'Wgroup\Controllers\CustomerInvestigationAlDocumentController@download');
        Route::get('customer/investigation-al/document/download-all', 'Wgroup\Controllers\CustomerInvestigationAlDocumentController@downloadAll');

        Route::post('customer/investigation-al/event', 'Wgroup\Controllers\CustomerInvestigationAlEventController@index');
        Route::post('customer/investigation-al/event/save', 'Wgroup\Controllers\CustomerInvestigationAlEventController@save');
        Route::post('customer/investigation-al/event/delete', 'Wgroup\Controllers\CustomerInvestigationAlEventController@delete');
        Route::post('customer/investigation-al/event/upload', 'Wgroup\Controllers\CustomerInvestigationAlEventController@upload');
        Route::get('customer/investigation-al/event', 'Wgroup\Controllers\CustomerInvestigationAlEventController@get');
        Route::get('customer/investigation-al/event/download', 'Wgroup\Controllers\CustomerInvestigationAlEventController@download');

        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN CAUSE
        Route::post('customer/investigation-al-factor-cause', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseController@index');
        Route::post('customer/investigation-al-factor-cause/save', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseController@save');
        Route::post('customer/investigation-al-factor-cause/delete', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseController@delete');
        Route::post('customer/investigation-al-factor-cause/list-data', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseController@listData');
        Route::get('customer/investigation-al-factor-cause', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseController@get');
        Route::get('customer/investigation-al-factor-cause/fish-bone', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseController@fishBone');

        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN CAUSE SUB CAUSE
        Route::post('customer/investigation-al-factor-cause-sub-cause', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseSubCauseController@index');
        Route::post('customer/investigation-al-factor-cause-sub-cause/save', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseSubCauseController@save');
        Route::post('customer/investigation-al-factor-cause-sub-cause/delete', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseSubCauseController@delete');
        Route::post('customer/investigation-al-factor-cause-sub-cause/list-data', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseSubCauseController@listData');
        Route::get('customer/investigation-al-factor-cause-sub-cause', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseCauseController@get');

        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN CAUSE ROOT CAUSE
        Route::post('customer/investigation-al-factor-cause-root-cause', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseRootCauseController@index');
        Route::post('customer/investigation-al-factor-cause-root-cause/save', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseRootCauseController@save');
        Route::post('customer/investigation-al-factor-cause-root-cause/delete', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseRootCauseController@delete');
        Route::post('customer/investigation-al-factor-cause-root-cause/list-data', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseRootCauseController@listData');
        Route::get('customer/investigation-al-factor-cause-root-cause', 'Wgroup\Controllers\CustomerInvestigationAlFactorCauseRootCauseController@get');







        Route::post('investigation-al/cause', 'Wgroup\Controllers\InvestigationAlCauseController@index');
        Route::post('investigation-al/cause/save', 'Wgroup\Controllers\InvestigationAlCauseController@save');
        Route::post('investigation-al/cause/delete', 'Wgroup\Controllers\InvestigationAlCauseController@delete');
        Route::post('investigation-al/cause/upload', 'Wgroup\Controllers\InvestigationAlCauseController@upload');
        Route::get('investigation-al/cause', 'Wgroup\Controllers\InvestigationAlCauseController@get');

        Route::post('investigation-al/economic-activity', 'Wgroup\Controllers\InvestigationAlEconomicActivityController@index');
        Route::post('investigation-al/economic-activity/save', 'Wgroup\Controllers\InvestigationAlEconomicActivityController@save');
        Route::post('investigation-al/economic-activity/delete', 'Wgroup\Controllers\InvestigationAlEconomicActivityController@delete');
        Route::post('investigation-al/economic-activity/upload', 'Wgroup\Controllers\InvestigationAlEconomicActivityController@upload');
        Route::get('investigation-al/economic-activity', 'Wgroup\Controllers\InvestigationAlEconomicActivityController@get');




        Route::post('customer-internal-certificate-program', 'Wgroup\Controllers\CustomerInternalCertificateProgramController@index');
        Route::post('customer-internal-certificate-program/save', 'Wgroup\Controllers\CustomerInternalCertificateProgramController@save');
        Route::post('customer-internal-certificate-program/delete', 'Wgroup\Controllers\CustomerInternalCertificateProgramController@delete');
        Route::post('customer-internal-certificate-program/generate', 'Wgroup\Controllers\CustomerInternalCertificateProgramController@delete');
		Route::get('customer-internal-certificate-program', 'Wgroup\Controllers\CustomerInternalCertificateProgramController@get');

        Route::post('customer-internal-certificate-program-speciality/delete', 'Wgroup\Controllers\CustomerInternalCertificateProgramController@deleteSpeciality');
        Route::post('customer-internal-certificate-program-requirement/delete', 'Wgroup\Controllers\CustomerInternalCertificateProgramController@deleteRequirement');

        Route::post('customer-internal-certificate-grade', 'Wgroup\Controllers\CustomerInternalCertificateGradeController@index');
        Route::post('customer-internal-certificate-grade/save', 'Wgroup\Controllers\CustomerInternalCertificateGradeController@save');
        Route::post('customer-internal-certificate-grade/delete', 'Wgroup\Controllers\CustomerInternalCertificateGradeController@delete');
        Route::post('customer-internal-certificate-grade/generate', 'Wgroup\Controllers\CustomerInternalCertificateGradeController@generateCertificate');
		Route::get('customer-internal-certificate-grade', 'Wgroup\Controllers\CustomerInternalCertificateGradeController@get');

        Route::post('customer-internal-certificate-grade-calendar/delete', 'Wgroup\Controllers\CustomerInternalCertificateGradeCalendarController@delete');
        Route::post('customer-internal-certificate-grade-agent/delete', 'Wgroup\Controllers\CustomerInternalCertificateGradeAgentController@delete');

        Route::post('customer-internal-certificate-grade-participant', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@index');
        Route::post('customer-internal-certificate-grade-participant/search', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@filterIndex');
        Route::post('customer-internal-certificate-grade-participant/expiration', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@filterExpiration');
        Route::post('customer-internal-certificate-grade-participant/save', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@save');
        Route::post('customer-internal-certificate-grade-participant/delete', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@delete');
        Route::post('customer-internal-certificate-grade-participant/upload', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@upload');
        Route::post('customer-internal-certificate-grade-participant/validate', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@validate');
        Route::post('customer-internal-certificate-grade-participant/download', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@download');
		Route::get('customer-internal-certificate-grade-participant', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@get');
		Route::get('customer-internal-certificate-grade-participant-certificate/download', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@downloadCertificate');
		Route::get('customer-internal-certificate-grade-participant-certificate/stream', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantController@streamCertificate');

        Route::post('customer-internal-certificate-grade-participant-document', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantDocumentController@index');
        Route::post('customer-internal-certificate-grade-participant-document/save', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantDocumentController@save');
        Route::post('customer-internal-certificate-grade-participant-document/delete', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantDocumentController@delete');
        Route::post('customer-internal-certificate-grade-participant-document/upload', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantDocumentController@upload');
        Route::get('customer-internal-certificate-grade-participant-document', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantDocumentController@get');
        Route::get('customer-internal-certificate-grade-participant-document/download', 'Wgroup\Controllers\CustomerInternalCertificateGradeParticipantDocumentController@download');

        Route::post('customer/matrix', 'Wgroup\Controllers\CustomerMatrixController@index');
        Route::post('customer/matrix/save', 'Wgroup\Controllers\CustomerMatrixController@save');
        Route::post('customer/matrix/delete', 'Wgroup\Controllers\CustomerMatrixController@delete');
        Route::post('customer/matrix/upload', 'Wgroup\Controllers\CustomerMatrixController@upload');
        Route::post('customer/matrix/chart-list', 'Wgroup\Controllers\CustomerMatrixController@getChartList');
        Route::get('customer/matrix', 'Wgroup\Controllers\CustomerMatrixController@get');
        Route::get('customer/matrix/download', 'Wgroup\Controllers\CustomerMatrixController@download');

        Route::post('customer/matrix-project', 'Wgroup\Controllers\CustomerMatrixProjectController@index');
        Route::post('customer/matrix-project/save', 'Wgroup\Controllers\CustomerMatrixProjectController@save');
        Route::post('customer/matrix-project/delete', 'Wgroup\Controllers\CustomerMatrixProjectController@delete');
        Route::post('customer/matrix-project/upload', 'Wgroup\Controllers\CustomerMatrixProjectController@upload');
        Route::get('customer/matrix-project', 'Wgroup\Controllers\CustomerMatrixProjectController@get');
        Route::get('customer/matrix-project/download', 'Wgroup\Controllers\CustomerMatrixProjectController@download');

        Route::post('customer/matrix-activity', 'Wgroup\Controllers\CustomerMatrixActivityController@index');
        Route::post('customer/matrix-activity/save', 'Wgroup\Controllers\CustomerMatrixActivityController@save');
        Route::post('customer/matrix-activity/delete', 'Wgroup\Controllers\CustomerMatrixActivityController@delete');
        Route::post('customer/matrix-activity/upload', 'Wgroup\Controllers\CustomerMatrixActivityController@upload');
        Route::get('customer/matrix-activity', 'Wgroup\Controllers\CustomerMatrixActivityController@get');
        Route::get('customer/matrix-activity/download', 'Wgroup\Controllers\CustomerMatrixActivityController@download');

        Route::post('customer/matrix-impact', 'Wgroup\Controllers\CustomerMatrixEnvironmentalImpactController@index');
        Route::post('customer/matrix-impact/save', 'Wgroup\Controllers\CustomerMatrixEnvironmentalImpactController@save');
        Route::post('customer/matrix-impact/delete', 'Wgroup\Controllers\CustomerMatrixEnvironmentalImpactController@delete');
        Route::post('customer/matrix-impact/upload', 'Wgroup\Controllers\CustomerMatrixEnvironmentalImpactController@upload');
        Route::post('customer/matrix-impact/list', 'Wgroup\Controllers\CustomerMatrixEnvironmentalImpactController@getList');
        Route::get('customer/matrix-impact', 'Wgroup\Controllers\CustomerMatrixEnvironmentalImpactController@get');
        Route::get('customer/matrix-impact/download', 'Wgroup\Controllers\CustomerMatrixEnvironmentalImpactController@download');

        Route::post('customer/matrix-aspect', 'Wgroup\Controllers\CustomerMatrixEnvironmentalAspectController@index');
        Route::post('customer/matrix-aspect/save', 'Wgroup\Controllers\CustomerMatrixEnvironmentalAspectController@save');
        Route::post('customer/matrix-aspect/delete', 'Wgroup\Controllers\CustomerMatrixEnvironmentalAspectController@delete');
        Route::post('customer/matrix-aspect/upload', 'Wgroup\Controllers\CustomerMatrixEnvironmentalAspectController@upload');
        Route::get('customer/matrix-aspect', 'Wgroup\Controllers\CustomerMatrixEnvironmentalAspectController@get');
        Route::get('customer/matrix-aspect/download', 'Wgroup\Controllers\CustomerMatrixEnvironmentalAspectController@download');


        Route::post('customer/matrix-data', 'Wgroup\Controllers\CustomerMatrixDataController@index');
        Route::post('customer/matrix-data/save', 'Wgroup\Controllers\CustomerMatrixDataController@save');
        Route::post('customer/matrix-data/delete', 'Wgroup\Controllers\CustomerMatrixDataController@delete');
        Route::post('customer/matrix-data/upload', 'Wgroup\Controllers\CustomerMatrixDataController@upload');
        Route::post('customer/matrix-data/chart-list', 'Wgroup\Controllers\CustomerMatrixDataController@getChartList');
        Route::post('customer/matrix-data/list', 'Wgroup\Controllers\CustomerMatrixDataController@getList');
        Route::get('customer/matrix-data', 'Wgroup\Controllers\CustomerMatrixDataController@get');
        Route::get('customer/matrix-data/download', 'Wgroup\Controllers\CustomerMatrixDataController@download');



        Route::post('customer/occupational-investigation-al', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@index');
        Route::post('customer/occupational-investigation-al/save', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@save');
        Route::post('customer/occupational-investigation-al/saveCustomer', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@saveCustomer');
        Route::post('customer/occupational-investigation-al/saveEmployee', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@saveEmployee');
        Route::post('customer/occupational-investigation-al/saveAccident', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@saveAccident');
        Route::post('customer/occupational-investigation-al/saveSummary', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@saveSummary');
        Route::post('customer/occupational-investigation-al/saveEvent', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@saveEvent');
        Route::post('customer/occupational-investigation-al/saveAnalysis', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@saveAnalysis');
        Route::post('customer/occupational-investigation-al/update', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@update');
        Route::post('customer/occupational-investigation-al/delete', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@delete');
        Route::post('customer/occupational-investigation-al/upload', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@upload');
        Route::get('customer/occupational-investigation-al', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@get');
        Route::get('customer/occupational-investigation-al/download-certificate', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@download');
        Route::get('customer/occupational-investigation-al/download-pdf', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@download');
        Route::get('customer/occupational-investigation-al/download-letter', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlController@downloadLetter');

        Route::post('customer/occupational-investigation-al-measure', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlMeasureController@index');
        Route::post('customer/occupational-investigation-al-measure/save', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlMeasureController@save');
        Route::post('customer/occupational-investigation-al-measure/delete', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlMeasureController@delete');
        Route::post('customer/occupational-investigation-al-measure/upload', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlMeasureController@upload');
        Route::post('customer/occupational-investigation-al-measure/action-plan/save', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlMeasureController@save');
        Route::get('customer/occupational-investigation-al-measure', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlMeasureController@get');
        Route::get('customer/occupational-investigation-al-measure/download-certificate', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlMeasureController@download');
        Route::get('customer/occupational-investigation-al-measure/download-pdf', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlMeasureController@download');
        Route::get('customer/occupational-investigation-al-measure/action-plan', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlMeasureController@get');

        Route::post('customer/occupational-investigation-al-cause', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlCauseController@index');
        Route::post('customer/occupational-investigation-al-cause/immediate', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlCauseController@immediate');
        Route::post('customer/occupational-investigation-al-cause/basic', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlCauseController@basic');
        Route::post('customer/occupational-investigation-al-cause/save', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlCauseController@save');
        Route::post('customer/occupational-investigation-al-cause/delete', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlCauseController@delete');
        Route::post('customer/occupational-investigation-al-cause/upload', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlCauseController@upload');
        Route::get('customer/occupational-investigation-al-cause', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlCauseController@get');
        Route::get('customer/occupational-investigation-al-cause/download-certificate', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlCauseController@download');
        Route::get('customer/occupational-investigation-al-cause/download-pdf', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlCauseController@download');

        Route::post('customer/occupational-investigation-al-witness/delete', 'Wgroup\Controllers\CustomerOccupationalInvestigationAlWitnessController@delete');



        //-------------------------------------------------------------------------------------//EVALUATION MINIMUM STANDARD
        Route::get('customer/evaluation-minimum-standard', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@get');
        Route::get('customer/evaluation-minimum-standard/summary-export-excel', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@summaryExportExcel');
        Route::get('customer/evaluation-minimum-standard/summary-indicator-export', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@summaryByIndicatorExport');
        Route::get('customer/evaluation-minimum-standard/summary-program-export', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@summaryByProgramExport');

        Route::post('customer/evaluation-minimum-standard', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@index');
        Route::post('customer/evaluation-minimum-standard/summary', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@summary');
        Route::post('customer/evaluation-minimum-standard/can-create', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@canCreate');
        Route::post('customer/evaluation-minimum-standard/save', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@save');
        Route::post('customer/evaluation-minimum-standard/update', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@update');
        Route::post('customer/evaluation-minimum-standard/delete', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@delete');
        Route::post('customer/evaluation-minimum-standard/cancel', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@cancel');
        Route::post('customer/evaluation-minimum-standard/list-data', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@listData');
        Route::post('customer/evaluation-minimum-standard/chart-report', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@chartReport');
        Route::post('customer/evaluation-minimum-standard/monthly-report', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@monthlyReport');
        Route::post('customer/evaluation-minimum-standard/summary-indicator', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@summaryByIndicator');
        Route::post('customer/evaluation-minimum-standard/summary-program', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardController@summaryByProgram');


        //-------------------------------------------------------------------------------------//EVALUATION MINIMUM STANDARD ITEM
        Route::post('customer/evaluation-minimum-standard-item', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemController@index');
        Route::post('customer/evaluation-minimum-standard-item-report', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemController@report');
        Route::post('customer/evaluation-minimum-standard-item/save', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemController@save');
        Route::get('customer/evaluation-minimum-standard-item/export-excel', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemController@export');
        Route::get('customer/evaluation-minimum-standard-item/export-pdf', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemController@exportPdf');
        Route::get('customer/evaluation-minimum-standard-item/', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemController@get');


        //-------------------------------------------------------------------------------------//EVALUATION MINIMUM STANDARD ITEM COMMENT
        Route::post('customer/evaluation-minimum-standard-item-comment', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemCommentController@index');
        Route::post('customer/evaluation-minimum-standard-item-comment/save', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemCommentController@save');

        //-------------------------------------------------------------------------------------//CONFIG MINIMUM STANDARD ITEM DETAIL
        Route::post('customer/evaluation-minimum-standard-item-detail', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemDetailController@index');
        Route::post('customer/evaluation-minimum-standard-item-detail/save', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemDetailController@save');
        Route::post('customer/evaluation-minimum-standard-item-detail/insert', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemDetailController@insert');
        Route::post('customer/evaluation-minimum-standard-item-detail/delete', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemDetailController@delete');
        Route::post('customer/evaluation-minimum-standard-item-detail/list-data', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemDetailController@listData');
        Route::get('customer/evaluation-minimum-standard-item-detail/', 'Wgroup\Controllers\CustomerEvaluationMinimumStandardItemDetailController@get');



        //-------------------------------------------------------------------------------------//ROAD SAFETY
        Route::get('customer/road-safety', 'Wgroup\Controllers\CustomerRoadSafetyController@get');
        Route::get('customer/road-safety/summary-export-excel', 'Wgroup\Controllers\CustomerRoadSafetyController@summaryExportExcel');
        Route::get('customer/road-safety/summary-indicator-export', 'Wgroup\Controllers\CustomerRoadSafetyController@summaryByIndicatorExport');
        Route::get('customer/road-safety/summary-program-export', 'Wgroup\Controllers\CustomerRoadSafetyController@summaryByProgramExport');

        Route::post('customer/road-safety', 'Wgroup\Controllers\CustomerRoadSafetyController@index');
        Route::post('customer/road-safety/summary', 'Wgroup\Controllers\CustomerRoadSafetyController@summary');
        Route::post('customer/road-safety/summary-weighted', 'Wgroup\Controllers\CustomerRoadSafetyController@summaryWeighted');
        Route::post('customer/road-safety/can-create', 'Wgroup\Controllers\CustomerRoadSafetyController@canCreate');
        Route::post('customer/road-safety/save', 'Wgroup\Controllers\CustomerRoadSafetyController@save');
        Route::post('customer/road-safety/update', 'Wgroup\Controllers\CustomerRoadSafetyController@update');
        Route::post('customer/road-safety/delete', 'Wgroup\Controllers\CustomerRoadSafetyController@delete');
        Route::post('customer/road-safety/cancel', 'Wgroup\Controllers\CustomerRoadSafetyController@cancel');
        Route::post('customer/road-safety/list-data', 'Wgroup\Controllers\CustomerRoadSafetyController@listData');
        Route::post('customer/road-safety/chart-report', 'Wgroup\Controllers\CustomerRoadSafetyController@chartReport');
        Route::post('customer/road-safety/monthly-report', 'Wgroup\Controllers\CustomerRoadSafetyController@monthlyReport');
        Route::post('customer/road-safety/summary-indicator', 'Wgroup\Controllers\CustomerRoadSafetyController@summaryByIndicator');
        Route::post('customer/road-safety/summary-program', 'Wgroup\Controllers\CustomerRoadSafetyController@summaryByProgram');


        //-------------------------------------------------------------------------------------//ROAD SAFETY ITEM
        Route::post('customer/road-safety-item', 'Wgroup\Controllers\CustomerRoadSafetyItemController@index');
        Route::post('customer/road-safety-item/parent', 'Wgroup\Controllers\CustomerRoadSafetyItemController@indexParent');
        Route::post('customer/road-safety-item/item', 'Wgroup\Controllers\CustomerRoadSafetyItemController@indexItem');
        Route::post('customer/road-safety-item-report', 'Wgroup\Controllers\CustomerRoadSafetyItemController@report');
        Route::post('customer/road-safety-item/save', 'Wgroup\Controllers\CustomerRoadSafetyItemController@save');
        Route::get('customer/road-safety-item/export-excel', 'Wgroup\Controllers\CustomerRoadSafetyItemController@export');
        Route::get('customer/road-safety-item/export-pdf', 'Wgroup\Controllers\CustomerRoadSafetyItemController@exportPdf');
        Route::get('customer/road-safety-item/', 'Wgroup\Controllers\CustomerRoadSafetyItemController@get');


        //-------------------------------------------------------------------------------------//ROAD SAFETY ITEM COMMENT
        Route::post('customer/road-safety-item-comment', 'Wgroup\Controllers\CustomerRoadSafetyItemCommentController@index');
        Route::post('customer/road-safety-item-comment/save', 'Wgroup\Controllers\CustomerRoadSafetyItemCommentController@save');

        //-------------------------------------------------------------------------------------//CONFIG ROAD SAFETY ITEM DETAIL
        Route::post('customer/road-safety-item-detail', 'Wgroup\Controllers\CustomerRoadSafetyItemDetailController@index');
        Route::post('customer/road-safety-item-detail/save', 'Wgroup\Controllers\CustomerRoadSafetyItemDetailController@save');
        Route::post('customer/road-safety-item-detail/insert', 'Wgroup\Controllers\CustomerRoadSafetyItemDetailController@insert');
        Route::post('customer/road-safety-item-detail/delete', 'Wgroup\Controllers\CustomerRoadSafetyItemDetailController@delete');
        Route::post('customer/road-safety-item-detail/list-data', 'Wgroup\Controllers\CustomerRoadSafetyItemDetailController@listData');
        Route::get('customer/road-safety-item-detail/', 'Wgroup\Controllers\CustomerRoadSafetyItemDetailController@get');




        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN
        Route::post('customer/improvement-plan', 'Wgroup\Controllers\CustomerImprovementPlanController@index');
        Route::post('customer/improvement-plan/save', 'Wgroup\Controllers\CustomerImprovementPlanController@save');
        Route::post('customer/improvement-plan/delete', 'Wgroup\Controllers\CustomerImprovementPlanController@delete');
        Route::post('customer/improvement-plan/list-data', 'Wgroup\Controllers\CustomerImprovementPlanController@listData');
        Route::get('customer/improvement-plan', 'Wgroup\Controllers\CustomerImprovementPlanController@get');
        Route::get('customer/improvement-plan-entity', 'Wgroup\Controllers\CustomerImprovementPlanController@getEntity');


        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN CAUSE
        Route::post('customer/improvement-plan-cause', 'Wgroup\Controllers\CustomerImprovementPlanCauseController@index');
        Route::post('customer/improvement-plan-cause/save', 'Wgroup\Controllers\CustomerImprovementPlanCauseController@save');
        Route::post('customer/improvement-plan-cause/delete', 'Wgroup\Controllers\CustomerImprovementPlanCauseController@delete');
        Route::post('customer/improvement-plan-cause/list-data', 'Wgroup\Controllers\CustomerImprovementPlanCauseController@listData');
        Route::get('customer/improvement-plan-cause', 'Wgroup\Controllers\CustomerImprovementPlanCauseController@get');
        Route::get('customer/improvement-plan-cause/fish-bone', 'Wgroup\Controllers\CustomerImprovementPlanCauseController@fishBone');

        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN CAUSE SUB CAUSE
        Route::post('customer/improvement-plan-cause-sub-cause', 'Wgroup\Controllers\CustomerImprovementPlanCauseSubCauseController@index');
        Route::post('customer/improvement-plan-cause-sub-cause/save', 'Wgroup\Controllers\CustomerImprovementPlanCauseSubCauseController@save');
        Route::post('customer/improvement-plan-cause-sub-cause/delete', 'Wgroup\Controllers\CustomerImprovementPlanCauseSubCauseController@delete');
        Route::post('customer/improvement-plan-cause-sub-cause/list-data', 'Wgroup\Controllers\CustomerImprovementPlanCauseSubCauseController@listData');
        Route::get('customer/improvement-plan-cause-sub-cause', 'Wgroup\Controllers\CustomerImprovementPlanCauseSubCauseController@get');

        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN CAUSE ROOT CAUSE
        Route::post('customer/improvement-plan-cause-root-cause', 'Wgroup\Controllers\CustomerImprovementPlanCauseRootCauseController@index');
        Route::post('customer/improvement-plan-cause-root-cause/save', 'Wgroup\Controllers\CustomerImprovementPlanCauseRootCauseController@save');
        Route::post('customer/improvement-plan-cause-root-cause/delete', 'Wgroup\Controllers\CustomerImprovementPlanCauseRootCauseController@delete');
        Route::post('customer/improvement-plan-cause-root-cause/list-data', 'Wgroup\Controllers\CustomerImprovementPlanCauseRootCauseController@listData');
        Route::get('customer/improvement-plan-cause-root-cause', 'Wgroup\Controllers\CustomerImprovementPlanCauseRootCauseController@get');


        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN ACTION PLAN
        Route::post('customer/improvement-plan-action-plan', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanController@index');
        Route::post('customer/improvement-plan-action-plan/save', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanController@save');
        Route::post('customer/improvement-plan-action-plan/delete', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanController@delete');
        Route::post('customer/improvement-plan-action-plan/list-data', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanController@listData');
        Route::get('customer/improvement-plan-action-plan', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanController@get');

        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN ACTION PLAN NOTIFIED
        Route::post('customer/improvement-plan-action-plan-notified', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanNotifiedController@index');
        Route::post('customer/improvement-plan-action-plan-notified/save', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanNotifiedController@save');
        Route::post('customer/improvement-plan-action-plan-notified/delete', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanNotifiedController@delete');
        Route::get('customer/improvement-plan-action-plan-notified', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanNotifiedController@get');

        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN ACTION PLAN TASK
        Route::post('customer/improvement-plan-action-plan-task', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanTaskController@index');
        Route::post('customer/improvement-plan-action-plan-task/save', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanTaskController@save');
        Route::post('customer/improvement-plan-action-plan-task/update', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanTaskController@update');
        Route::post('customer/improvement-plan-action-plan-task/delete', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanTaskController@delete');
        Route::post('customer/improvement-plan-action-plan-task/list-data', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanTaskController@listData');
        Route::get('customer/improvement-plan-action-plan-task', 'Wgroup\Controllers\CustomerImprovementPlanActionPlanTaskController@get');

        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN TRACKING
        Route::post('customer/improvement-plan-tracking', 'Wgroup\Controllers\CustomerImprovementPlanTrackingController@index');
        Route::post('customer/improvement-plan-tracking/save', 'Wgroup\Controllers\CustomerImprovementPlanTrackingController@save');
        Route::post('customer/improvement-plan-tracking/delete', 'Wgroup\Controllers\CustomerImprovementPlanTrackingController@delete');
        Route::post('customer/improvement-plan-tracking/list-data', 'Wgroup\Controllers\CustomerImprovementPlanTrackingController@listData');
        Route::get('customer/improvement-plan-tracking', 'Wgroup\Controllers\CustomerImprovementPlanTrackingController@get');

        //-------------------------------------------------------------------------------------//IMPROVEMENT PLAN ALERT
        Route::post('customer/improvement-plan-alert', 'Wgroup\Controllers\CustomerImprovementPlanAlertController@index');
        Route::post('customer/improvement-plan-alert/save', 'Wgroup\Controllers\CustomerImprovementPlanAlertController@save');
        Route::post('customer/improvement-plan-alert/delete', 'Wgroup\Controllers\CustomerImprovementPlanAlertController@delete');
        Route::post('customer/improvement-plan-alert/list-data', 'Wgroup\Controllers\CustomerImprovementPlanAlertController@listData');
        Route::get('customer/improvement-plan-alert', 'Wgroup\Controllers\CustomerImprovementPlanAlertController@get');


        //-------------------------------------------------------------------------------------//CONFIG MINIMUM STANDARD ITEM DETAIL
        Route::post('customer/configuration-minimum-standard-item-detail', 'Wgroup\Controllers\CustomerConfigMinimumStandardItemDetailController@index');
        Route::post('customer/configuration-minimum-standard-item-detail/save', 'Wgroup\Controllers\CustomerConfigMinimumStandardItemDetailController@save');
        Route::post('customer/configuration-minimum-standard-item-detail/insert', 'Wgroup\Controllers\CustomerConfigMinimumStandardItemDetailController@insert');
        Route::post('customer/configuration-minimum-standard-item-detail/delete', 'Wgroup\Controllers\CustomerConfigMinimumStandardItemDetailController@delete');
        Route::post('customer/configuration-minimum-standard-item-detail/list-data', 'Wgroup\Controllers\CustomerConfigMinimumStandardItemDetailController@listData');
        Route::get('customer/configuration-minimum-standard-item-detail/', 'Wgroup\Controllers\CustomerConfigMinimumStandardItemDetailController@get');


        //-------------------------------------------------------------------------------------//MINIMUM STANDARD
        Route::post('minimum-standard', 'Wgroup\Controllers\MinimumStandardController@index');
        Route::post('minimum-standard/save', 'Wgroup\Controllers\MinimumStandardController@save');
        Route::post('minimum-standard/delete', 'Wgroup\Controllers\MinimumStandardController@delete');
        Route::post('minimum-standard/list-data', 'Wgroup\Controllers\MinimumStandardController@listData');
        Route::get('minimum-standard', 'Wgroup\Controllers\MinimumStandardController@get');

        Route::post('minimum-standard-item', 'Wgroup\Controllers\MinimumStandardItemController@index');
        Route::post('minimum-standard-item/save', 'Wgroup\Controllers\MinimumStandardItemController@save');
        Route::post('minimum-standard-item/delete', 'Wgroup\Controllers\MinimumStandardItemController@delete');
        Route::get('minimum-standard-item', 'Wgroup\Controllers\MinimumStandardItemController@get');

        Route::post('minimum-standard-item-detail', 'Wgroup\Controllers\MinimumStandardItemDetailController@index');
        Route::post('minimum-standard-item-detail/save', 'Wgroup\Controllers\MinimumStandardItemDetailController@save');
        Route::post('minimum-standard-item-detail/delete', 'Wgroup\Controllers\MinimumStandardItemDetailController@delete');
        Route::get('minimum-standard-item-detail', 'Wgroup\Controllers\MinimumStandardItemDetailController@get');

        Route::post('minimum-standard-item-question', 'Wgroup\Controllers\MinimumStandardItemQuestionController@index');
        Route::post('minimum-standard-item-question-available', 'Wgroup\Controllers\MinimumStandardItemQuestionController@filterAvailableQuestionsIndex');
        Route::post('minimum-standard-item-question/save', 'Wgroup\Controllers\MinimumStandardItemQuestionController@save');
        Route::post('minimum-standard-item-question/insert', 'Wgroup\Controllers\MinimumStandardItemQuestionController@insert');
        Route::post('minimum-standard-item-question/delete', 'Wgroup\Controllers\MinimumStandardItemQuestionController@delete');
        Route::get('minimum-standard-item-question', 'Wgroup\Controllers\MinimumStandardItemQuestionController@get');


        //-------------------------------------------------------------------------------------//CONFIG ROAD SAFETY ITEM DETAIL
        Route::post('customer/configuration-road-safety-item-detail', 'Wgroup\Controllers\CustomerConfigRoadSafetyItemDetailController@index');
        Route::post('customer/configuration-road-safety-item-detail/save', 'Wgroup\Controllers\CustomerConfigRoadSafetyItemDetailController@save');
        Route::post('customer/configuration-road-safety-item-detail/insert', 'Wgroup\Controllers\CustomerConfigRoadSafetyItemDetailController@insert');
        Route::post('customer/configuration-road-safety-item-detail/delete', 'Wgroup\Controllers\CustomerConfigRoadSafetyItemDetailController@delete');
        Route::post('customer/configuration-road-safety-item-detail/list-data', 'Wgroup\Controllers\CustomerConfigRoadSafetyItemDetailController@listData');
        Route::get('customer/configuration-road-safety-item-detail/', 'Wgroup\Controllers\CustomerConfigRoadSafetyItemDetailController@get');


        //-------------------------------------------------------------------------------------//ROAD SAFETY
        Route::post('road-safety', 'Wgroup\Controllers\RoadSafetyController@index');
        Route::post('road-safety/save', 'Wgroup\Controllers\RoadSafetyController@save');
        Route::post('road-safety/delete', 'Wgroup\Controllers\RoadSafetyController@delete');
        Route::post('road-safety/list-data', 'Wgroup\Controllers\RoadSafetyController@listData');
        Route::get('road-safety', 'Wgroup\Controllers\RoadSafetyController@get');

        Route::post('road-safety-item', 'Wgroup\Controllers\RoadSafetyItemController@index');
        Route::post('road-safety-item/save', 'Wgroup\Controllers\RoadSafetyItemController@save');
        Route::post('road-safety-item/delete', 'Wgroup\Controllers\RoadSafetyItemController@delete');
        Route::get('road-safety-item', 'Wgroup\Controllers\RoadSafetyItemController@get');

        Route::post('road-safety-item-detail', 'Wgroup\Controllers\RoadSafetyItemDetailController@index');
        Route::post('road-safety-item-detail/save', 'Wgroup\Controllers\RoadSafetyItemDetailController@save');
        Route::post('road-safety-item-detail/delete', 'Wgroup\Controllers\RoadSafetyItemDetailController@delete');
        Route::get('road-safety-item-detail', 'Wgroup\Controllers\RoadSafetyItemDetailController@get');

        Route::post('road-safety-item-question', 'Wgroup\Controllers\RoadSafetyItemQuestionController@index');
        Route::post('road-safety-item-question-available', 'Wgroup\Controllers\RoadSafetyItemQuestionController@filterAvailableQuestionsIndex');
        Route::post('road-safety-item-question/save', 'Wgroup\Controllers\RoadSafetyItemQuestionController@save');
        Route::post('road-safety-item-question/insert', 'Wgroup\Controllers\RoadSafetyItemQuestionController@insert');
        Route::post('road-safety-item-question/delete', 'Wgroup\Controllers\RoadSafetyItemQuestionController@delete');
        Route::get('road-safety-item-question', 'Wgroup\Controllers\RoadSafetyItemQuestionController@get');


        //-------------------------------------------------------------------------------------//RESOURCE LIBRARY
        //Route::post('resource-library', 'Wgroup\Controllers\ResourceLibraryController@index');
        Route::post('resource-library-category', 'Wgroup\Controllers\ResourceLibraryController@indexCategory');
        Route::post('resource-library/save', 'Wgroup\Controllers\ResourceLibraryController@save');
        Route::post('resource-library/delete', 'Wgroup\Controllers\ResourceLibraryController@delete');
        Route::post('resource-library/upload', 'Wgroup\Controllers\ResourceLibraryController@upload');
        Route::post('resource-library/upload-cover', 'Wgroup\Controllers\ResourceLibraryController@uploadCover');
        Route::get('resource-library', 'Wgroup\Controllers\ResourceLibraryController@get');
        Route::get('resource-library/download', 'Wgroup\Controllers\ResourceLibraryController@download');
