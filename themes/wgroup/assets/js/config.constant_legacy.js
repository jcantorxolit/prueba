'use strict';

/**
 * Config constant
 */
app.constant('APP_MEDIAQUERY', {
    'desktopXL': 1200,
    'desktop': 992,
    'tablet': 768,
    'mobile': 480
});
app.constant('JS_REQUIRES', {
    //*** Scripts
    scripts: {
        //*** Javascript Plugins
        'modernizr': ['themes/wgroup/assets/vendor/modernizr/modernizr.js'],

        'momentwl': ['themes/wgroup/assets/vendor/moment/moment-with-locales.js'],

        'moment': ['themes/wgroup/assets/vendor/moment/moment.min.js'],

        'momentlocale': ['themes/wgroup/assets/vendor/moment/locale/es.js'],

        'spin': 'themes/wgroup/assets/vendor/ladda/spin.min.js',
        'random-color': 'themes/wgroup/assets/vendor/randomcolor/randomColor.js',

        //*** jQuery Plugins
        'datejs': ['themes/wgroup/assets/vendor/datejs/date.js'],
        'bootstrap-datetimepicker': ['themes/wgroup/assets/vendor/bootstrap-datetimepicker/js/bootstrap-datetimepicker.min.js',
            'themes/wgroup/assets/vendor/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css'],
        'perfect-scrollbar-plugin': ['themes/wgroup/assets/vendor/perfect-scrollbar/perfect-scrollbar.min.js', 'themes/wgroup/assets/vendor/perfect-scrollbar/perfect-scrollbar.min.css'],
        'duScroll': ['themes/wgroup/assets/vendor/angular-scroll/angular-scroll.js'],
        'ladda': ['themes/wgroup/assets/vendor/ladda/spin.min.js', 'themes/wgroup/assets/vendor/ladda/ladda.min.js', 'themes/wgroup/assets/vendor/ladda/ladda-themeless.min.css'],
        'sweet-alert': ['themes/wgroup/assets/vendor/sweet-alert/sweet-alert.min.js', 'themes/wgroup/assets/vendor/sweet-alert/sweet-alert.css'],
        'chartjs': 'themes/wgroup/assets/vendor/chartjs/Chart.min.js',
        'jquery-sparkline': 'themes/wgroup/assets/vendor/sparkline/jquery.sparkline.min.js',
        'ckeditor-plugin': 'themes/wgroup/assets/vendor/ckeditor/ckeditor.js',
        'jquery-nestable-plugin': ['themes/wgroup/assets/vendor/ng-nestable/jquery.nestable.js', 'themes/wgroup/assets/vendor/ng-nestable/jquery.nestable.css'],
        'touchspin-plugin': 'themes/wgroup/assets/vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.js',
        'jquery-datatable': [
            //'themes/wgroup/assets/vendor/angular-datatables/jquery.dataTables.min.js',
            'themes/wgroup/assets/vendor/angular-datatables/dataTables.fontAwesome.css'
        ],

        //*** Bootstrap plugins
        'dual-list-box': ['themes/wgroup/assets/vendor/bootstrap-duallistbox/jquery.bootstrap-duallistbox.min.js'
            , 'themes/wgroup/assets/vendor/bootstrap-duallistbox/bootstrap-duallistbox.min.css'],

        'base64': ['themes/wgroup/assets/vendor/utils/base64.js'],
        'json3': ['themes/wgroup/assets/vendor/utils/json3.min.js'],

        //*** Services
        'listService': ['themes/wgroup/assets/js/services/listService.js'],
        'moduleListService': ['themes/wgroup/assets/js/services/moduleListService.js'],
        'geoLocationService': ['themes/wgroup/assets/js/services/geoLocationService.js'],

        //*** Controllers
        'dashboardCtrl': 'themes/wgroup/assets/js/controllers/dashboardCtrl.js',
        'iconsCtrl': 'themes/wgroup/assets/js/controllers/iconsCtrl.js',
        'vAccordionCtrl': 'themes/wgroup/assets/js/controllers/vAccordionCtrl.js',
        'ckeditorCtrl': 'themes/wgroup/assets/js/controllers/ckeditorCtrl.js',
        'laddaCtrl': 'themes/wgroup/assets/js/controllers/laddaCtrl.js',
        'ngTableCtrl': 'themes/wgroup/assets/js/controllers/ngTableCtrl.js',
        'cropCtrl': 'themes/wgroup/assets/js/controllers/cropCtrl.js',
        'asideCtrl': 'themes/wgroup/assets/js/controllers/asideCtrl.js',
        'toasterCtrl': 'themes/wgroup/assets/js/controllers/toasterCtrl.js',
        'sweetAlertCtrl': 'themes/wgroup/assets/js/controllers/sweetAlertCtrl.js',
        'mapsCtrl': 'themes/wgroup/assets/js/controllers/mapsCtrl.js',
        'chartsCtrl': 'themes/wgroup/assets/js/controllers/chartsCtrl.js',
        'nestableCtrl': 'themes/wgroup/assets/js/controllers/nestableCtrl.js',
        'validationCtrl': ['themes/wgroup/assets/js/controllers/validationCtrl.js'],
        'userCtrl': ['themes/wgroup/assets/js/controllers/userCtrl.js'],
        'selectCtrl': 'themes/wgroup/assets/js/controllers/selectCtrl.js',
        'wizardCtrl': 'themes/wgroup/assets/js/controllers/wizardCtrl.js',
        'uploadCtrl': 'themes/wgroup/assets/js/controllers/uploadCtrl.js',
        'treeCtrl': 'themes/wgroup/assets/js/controllers/treeCtrl.js',
        'inboxCtrl': 'themes/wgroup/assets/js/controllers/inboxCtrl.js',
        // Inyectamos el contraldor para la pnatalla de clientes
        'customerTabsCtrl': 'themes/wgroup/assets/js/controllers/customers/customerTabsCtrl.js',

        'customerCtrl': 'themes/wgroup/assets/js/controllers/customers/customerCtrl.js',
        'customerEditCtrl': 'themes/wgroup/assets/js/controllers/customers/customerEditCtrl.js',
        'customerTrackingCtrl': 'themes/wgroup/assets/js/controllers/customers/customerTrackingCtrl.js',
        'customerTrackingListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerTrackingListCtrl.js',
        'customerTrackingEditCtrl': 'themes/wgroup/assets/js/controllers/customers/customerTrackingEditCtrl.js',
        // Inyectamos el contraldor para la pantalla de asesores
        'agentCtrl': 'themes/wgroup/assets/js/controllers/agents/agentCtrl.js',

        'agentEditCtrl': 'themes/wgroup/assets/js/controllers/agents/agentEditCtrl.js',
        'agentTabsCtrl': 'themes/wgroup/assets/js/controllers/agents/agentTabsCtrl.js',
        'agentDocumentCtrl': 'themes/wgroup/assets/js/controllers/agents/agentDocumentCtrl.js',
        'agentDocumentListCtrl': 'themes/wgroup/assets/js/controllers/agents/agentDocumentListCtrl.js',
        'customerDiagnosticEditCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticEditCtrl.js',

        'customerDiagnosticCtrl': [
            'themes/wgroup/assets/js/controllers/customers/customerDiagnosticCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticRiskMatrixCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticRiskMatrixSummaryCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticRiskMatrixPriorizationCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticRiskMatrixHistoricalCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticTabsCtrl.js'
        ],

        'customerEvaluationMinimumStandardCtrl': [
            'themes/wgroup/assets/js/controllers/customers/customerEvaluationMinimumStandardCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEvaluationMinimumStandardSummaryCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEvaluationMinimumStandardEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigurationMinimumStandardItemListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEvaluationMinimumStandardMonthlyReportCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEvaluationMinimumStandardReportCtrl.js'
        ],

        'customerRoadSafetyCtrl': [
            'themes/wgroup/assets/js/controllers/customers-road-safety/customerRoadSafetyCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers-road-safety/customerRoadSafetySummaryCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers-road-safety/customerRoadSafetyEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers-road-safety/customerRoadSafetyMonthlyReportCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers-road-safety/customerRoadSafetyReportCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers-road-safety/customerConfigurationRoadSafetyItemListCtrl.js'
        ],

        'customerImprovementPlanCtrl': [
            'themes/wgroup/assets/js/controllers/customers/customerImprovementPlanCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerImprovementPlanListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerImprovementPlanEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerImprovementPlanActionPlanCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerImprovementPlanTrackingCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerImprovementPlanAuditCtrl.js'
        ],

        'customerDiagnosticListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticListCtrl.js',
        'customerDiagnosticSummaryCtrl': ['themes/wgroup/assets/js/controllers/customers/customerDiagnosticSummaryCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticReportMonthlyCtrl.js'],
        'customerDiagnosticObservationListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticObservationListCtrl.js',

        'customerDiagnosticProcessListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticProcessListCtrl.js',
        'customerDiagnosticRiskFactorListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticRiskFactorListCtrl.js',
        'customerDiagnosticWorkPlaceListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticWorkPlaceListCtrl.js',
        'customerDiagnosticAccidentListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticAccidentListCtrl.js',
        'customerDiagnosticDiseaseListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticDiseaseListCtrl.js',
        'customerDiagnosticRiskTaskListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticRiskTaskListCtrl.js',
        'customerDiagnosticArlListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticArlListCtrl.js',
        'customerDiagnosticArlIntermediaryListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticArlIntermediaryListCtrl.js',
        'customerDiagnosticEnviromentalListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticEnviromentalListCtrl.js',
        'customerDiagnosticEnviromentalIntermediaryListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticEnviromentalIntermediaryListCtrl.js',
        'customerDiagnosticReportCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDiagnosticReportCtrl.js',
        'customerDocumentListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDocumentListCtrl.js',
        'customerDocumentCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDocumentCtrl.js',
        'customerDocumentSettingCtrl': 'themes/wgroup/assets/js/controllers/customers/customerDocumentSettingCtrl.js',
        'customerAuditCtrl': 'themes/wgroup/assets/js/controllers/customers/customerAuditCtrl.js',
        'customerAuditListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerAuditListCtrl.js',
        'customerManagementSettingCtrl': 'themes/wgroup/assets/js/controllers/customers/customerManagementSettingCtrl.js',
        'customerManagementCtrl': 'themes/wgroup/assets/js/controllers/customers/customerManagementCtrl.js',

        'customerManagementEditCtrl': 'themes/wgroup/assets/js/controllers/customers/customerManagementEditCtrl.js',
        'customerManagementListCtrl': 'themes/wgroup/assets/js/controllers/customers/customerManagementListCtrl.js',
        'customerManagementReportCtrl': 'themes/wgroup/assets/js/controllers/customers/customerManagementReportCtrl.js',
        'customerManagementSummaryCtrl': ['themes/wgroup/assets/js/controllers/customers/customerManagementSummaryCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerManagementReportMonthlyCtrl.js'],

        'customerWorkMedicineCtrl': ['themes/wgroup/assets/js/controllers/customers/customerWorkMedicineCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerWorkMedicineListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerWorkMedicineEditCtrl.js'],

        'customerEmployeeOccupationalExaminationCtrl': ['themes/wgroup/assets/js/controllers/customers/customerEmployeeOccupationalExaminationCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeOccupationalExaminationListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeOccupationalExaminationEditCtrl.js'],

        'customerHealthDamageDiagnosticSource': ['themes/wgroup/assets/js/controllers/customers/customerHealthDamageDiagnosticSourceCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageDiagnosticSourceListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageDiagnosticSourceEditCtrl.js'],

        'customerHealthDamageRestriction': ['themes/wgroup/assets/js/controllers/customers/customerHealthDamageRestrictionCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageRestrictionListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageRestrictionEditCtrl.js'],

        'customerHealthDamageDisability': ['themes/wgroup/assets/js/controllers/customers/customerHealthDamageDisabilityCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageDisabilityListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageDisabilityEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageDisabilityPersonCtrl.js'
        ],

        'customerHealthDamageQualificationSource': ['themes/wgroup/assets/js/controllers/customers/customerHealthDamageQualificationSourceCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageQualificationSourceListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageQualificationSourceEditCtrl.js'],

        'customerHealthDamageQualificationLost': ['themes/wgroup/assets/js/controllers/customers/customerHealthDamageQualificationLostCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageQualificationLostListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageQualificationLostEditCtrl.js'],

        'customerHealthDamageAdministrativeProcess': ['themes/wgroup/assets/js/controllers/customers/customerHealthDamageAdministrativeProcessCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageAdministrativeProcessListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageAdministrativeProcessEditCtrl.js'],

        'customerHealthDamageObservation': ['themes/wgroup/assets/js/controllers/customers/customerHealthDamageObservationCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerHealthDamageObservationListCtrl.js'
        ],

        'customerHealthDamageAnalysis': [
            'themes/wgroup/assets/js/controllers/customers/customerHealthDamageAnalysisCtrl.js'
        ],

        'customerContractCtrl': ['themes/wgroup/assets/js/controllers/customers/customerContractCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerContractListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerContractSummaryCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerContractEditCtrl.js'],

        'customerSafetyInspection': [
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionConfigHeaderCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionConfigHeaderListCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionConfigHeaderEditCtrl.js',

            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionConfigListCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionConfigListListCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionConfigListEditCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionConfigListGroupEditCtrl.js',

            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionListCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionSummaryCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionListItemEditCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerSafetyInspectionReportCtrl.js',
            'themes/wgroup/assets/js/controllers/customerscontractorsafety/customerContractorSafetyInspectionListItemEditCtrl.js',

        ],

        'customerConfigSGSSTCtrl': ['themes/wgroup/assets/js/controllers/customers/customerConfigWorkPlaceListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigMacroProcessesCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigProcessesCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigActivityListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigJobCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigJobListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigJobEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigWizardCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigWizardEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigWizardSummaryCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigWizardJobImport.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigWizardMacroProcessesImportCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigWizardProcessesImportCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigWizardWorkPlaceImportCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerConfigWizardJobActivityImportCtrl.js'
        ],

        'customerEnrollmentCtrl': ['themes/wgroup/assets/js/controllers/customers/customerEnrollmentTabsCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEnrollmentEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEnrollmentListCtrl.js'
        ],

        'customerActionPlanCtrl': ['themes/wgroup/assets/js/controllers/customers/customerActionPlanCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerActionPlanListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerActionPlanSummaryCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerActionPlanActivityCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerActionPlanEditCtrl.js'],

        'customerPollCtrl': ['themes/wgroup/assets/js/controllers/customers/customerPollCtrl.js', 'themes/wgroup/assets/js/controllers/customers/customerPollEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerPollListCtrl.js'],

        'customerOccupationalReportALCtrl': ['themes/wgroup/assets/js/controllers/customers/customerOccupationalReportALCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalReportALListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalReportALEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalReportALPreviewCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalReportALAnalysisCtrl.js'],

        'customerOccupationalInvestigationCtrl': ['themes/wgroup/assets/js/controllers/customers/customerOccupationalInvestigationCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalInvestigationEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalInvestigationListCtrl.js'
        ],

        'customerOccupationalReportIncidentCtrl': ['themes/wgroup/assets/js/controllers/customers/customerOccupationalReportIncidentCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalReportIncidentListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalReportIncidentEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalReportIncidentPreviewCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerOccupationalReportIncidentAnalysisCtrl.js'],

        'customerAbsenteeismCtrl': ['themes/wgroup/assets/js/controllers/customers/customerAbsenteeismEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerAbsenteeismDisabilityCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerAbsenteeismAnalysisCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerAbsenteeismBillingCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerAbsenteeismIndicatorsCtrl.js'],

        'customerEmployeeCtrl': ['themes/wgroup/assets/js/controllers/customers/customerEmployeeCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeEditFixCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeDemographicCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeDocumentListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeDocumentExpirationSearchListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeDocumentCriticalListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeDocumentExpirationListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeDocumentImportCtrl.js'
            , 'themes/wgroup/assets/js/controllers/customers/customerEmployeeAuditListCtrl.js'
        ],

        'customerDiagnosticPreventionDocument': [
            'themes/wgroup/assets/js/controllers/customers/customerDiagnosticPreventionDocumentCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerDiagnosticPreventionDocumentListCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerDiagnosticPreventionDocumentEditCtrl.js'
        ],


        'customerInternalCertificate': [
            'themes/wgroup/assets/js/controllers/customerinternalcertificate/customerInternalCertificateAdminGradeCtrl.js',
            'themes/wgroup/assets/js/controllers/customerinternalcertificate/customerInternalCertificateAdminGradeEditCtrl.js',
            'themes/wgroup/assets/js/controllers/customerinternalcertificate/customerInternalCertificateAdminGradeListCtrl.js',
            'themes/wgroup/assets/js/controllers/customerinternalcertificate/customerInternalCertificateAdminGradeParticipantCertificateListCtrl.js',
            'themes/wgroup/assets/js/controllers/customerinternalcertificate/customerInternalCertificateAdminGradeParticipantEditCtrl.js',
            'themes/wgroup/assets/js/controllers/customerinternalcertificate/customerInternalCertificateAdminProgramCtrl.js',
            'themes/wgroup/assets/js/controllers/customerinternalcertificate/customerInternalCertificateAdminProgramEditCtrl.js',
            'themes/wgroup/assets/js/controllers/customerinternalcertificate/customerInternalCertificateAdminProgramListCtrl.js',
            'themes/wgroup/assets/js/controllers/customerinternalcertificate/customerInternalCertificateAdminTabsCtrl.js',
        ],

        'customerMatrix': [
            'themes/wgroup/assets/js/controllers/customers/customerMatrixCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixListCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixConfigWizardTabProjectCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixConfigWizardTabActivityCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixConfigWizardTabImpactCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixConfigWizardTabAspectCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixConfigWizardTabAspectEditCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixConfigWizardTabAspectListCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixConfigWizardTabSummaryCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixDataTabCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixDataTabEditCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerMatrixDataTabListCtrl.js',
        ],

        'unsafeAct': [
            'themes/wgroup/assets/js/controllers/customers/customerUnsafeActCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerUnsafeActListCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerUnsafeActEditCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerUnsafeActObservationListCtrl.js',
            'themes/wgroup/assets/js/controllers/customers/customerUnsafeActIndicatorCtrl.js',
        ],

        'dashboardDiagnosticCtrl': [
            'themes/wgroup/assets/js/controllers/dashboard/dashboardDiagnosticEconomicGroupCtrl.js',
            'themes/wgroup/assets/js/controllers/dashboard/dashboardDiagnosticContractorCtrl.js',
            'themes/wgroup/assets/js/controllers/dashboard/dashboardDiagnosticCustomerCtrl.js'
        ],

        'investigationCtrl': [
            'themes/wgroup/assets/js/controllers/investigation/investigationReviewCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationReviewExpirationCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationListCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationEditFormCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationFormTabAccidentCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationFormTabAnalysisCauseCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationFormTabCheckCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationFormTabCustomerCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationFormTabDeterminateCauseCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationFormTabDocumentCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationFormTabEmployeeCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationFormTabEventCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationMeasureTrackingCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationTracingCtrl.js',
            'themes/wgroup/assets/js/controllers/investigation/investigationFormTabProposedMeasureCtrl.js'
        ],

        'projectCtrl': [
            'themes/wgroup/assets/js/controllers/project/projectCtrl.js',
            'themes/wgroup/assets/js/controllers/project/projectBudgetCtrl.js',
            'themes/wgroup/assets/js/controllers/project/projectBillingCtrl.js',
            'themes/wgroup/assets/js/controllers/project/projectPlanningCtrl.js'],

        'internalProjectCtrl': ['themes/wgroup/assets/js/controllers/internalproject/internalProjectCtrl.js',
            'themes/wgroup/assets/js/controllers/internalproject/internalProjectPlanningCtrl.js'],

        //'planerCalendarCtrl': 'themes/wgroup/assets/js/controllers/planer/planerCalendarCtrl.js',
        'planerCalendarCtrl': 'themes/wgroup/assets/js/controllers/planer/planerCalendarCtrl.js',

        'quoteCtrl': ['themes/wgroup/assets/js/controllers/quote/quoteCtrl.js', 'themes/wgroup/assets/js/controllers/quote/quoteEditCtrl.js'],

        'configurationProgramPreventionDocument': [
            'themes/wgroup/assets/js/controllers/configuration/configurationProgramPreventionDocumentListCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration/configurationProgramPreventionDocumentEditCtrl.js'
        ],

        'configurationMinimumStandard': [
            'themes/wgroup/assets/js/controllers/configuration/configurationMinimumStandardCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration/configurationMinimumStandardListCtrl.js'
        ],

        'configurationMinimumStandardItem': [
            'themes/wgroup/assets/js/controllers/configuration/configurationMinimumStandardItemCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration/configurationMinimumStandardItemListCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration/configurationMinimumStandardItemEditCtrl.js'
        ],


        'configurationRoadSafety': [
            'themes/wgroup/assets/js/controllers/configuration-road-safety/configurationRoadSafetyCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration-road-safety/configurationRoadSafetyListCtrl.js'
        ],

        'configurationRoadSafetyItem': [
            'themes/wgroup/assets/js/controllers/configuration-road-safety/configurationRoadSafetyItemCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration-road-safety/configurationRoadSafetyItemListCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration-road-safety/configurationRoadSafetyItemEditCtrl.js'
        ],


        'configurationComplementaryTest': [
            'themes/wgroup/assets/modules/configuration/complementary-test/configurationComplementaryTestCtrl.js',
            'themes/wgroup/assets/modules/configuration/complementary-test/configurationComplementaryTestFormCtrl.js',
            'themes/wgroup/assets/modules/configuration/complementary-test/configurationComplementaryTestResultCtrl.js',
            'themes/wgroup/assets/modules/configuration/complementary-test/configurationComplementaryTestService.js'
        ],

        'configurationPrioritizationFactor': [
            'themes/wgroup/assets/modules/configuration/prioritization-factor/configurationPrioritizationFactorCtrl.js',
            'themes/wgroup/assets/modules/configuration/prioritization-factor/configurationPrioritizationFactorFormCtrl.js',
        ],

        //-------------------------------------------------START PROFESSOR

        'professorDocumentCtrl': [
            'themes/wgroup/assets/js/controllers/professor/document/professorDocumentListCtrl.js',
        ],

        'professorProviderCtrl': [
            'themes/wgroup/assets/js/controllers/professor/provider/professorProviderTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/provider/professorProviderListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/provider/professorProviderEditCtrl.js',

            'themes/wgroup/assets/js/controllers/professor/provider-service-order/professorProviderServiceOrderCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/provider-service-order/professorProviderServiceOrderListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/provider-service-order/professorProviderServiceOrderEditCtrl.js',

            'themes/wgroup/assets/js/controllers/professor/provider-service-order-event/professorProviderServiceOrderEventListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/provider-service-order-event/professorProviderServiceOrderEventEditCtrl.js',

            'themes/wgroup/assets/js/controllers/professor/provider-invoice/professorProviderInvoiceCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/provider-invoice/professorProviderInvoiceListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/provider-invoice/professorProviderInvoiceEditCtrl.js',

            'themes/wgroup/assets/js/controllers/professor/provider-invoice-event/professorProviderInvoiceEventListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/provider-invoice-event/professorProviderInvoiceEventEditCtrl.js',

        ],

        'professorNewsCtrl': [
            'themes/wgroup/assets/js/controllers/professor/news/professorNewsTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/news/professorNewsListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/news/professorNewsEditCtrl.js',
        ],

        'professorParameterConfigCtrl': [
            'themes/wgroup/assets/js/controllers/professor/parameter-config/professorParameterConfigTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/parameter-config/professorParameterConfigDisciplineListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/parameter-config/professorParameterConfigMainThemeListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/parameter-config/professorParameterConfigProgramListCtrl.js',
        ],

        'professorEventCtrl': [
            'themes/wgroup/assets/js/controllers/professor/event/professorEventTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event/professorEventListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event/professorEventEditCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-segment/professorEventSegmentListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-document/professorEventDocumentListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail/professorEventDetailCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail/professorEventDetailListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail/professorEventDetailEditCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail-actor/professorEventDetailActorListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail-schedule/professorEventDetailScheduleListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail-history/professorEventDetailHistoryListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail-participant/professorEventDetailParticipantListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail-document/professorEventDetailDocumentListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail-observation/professorEventDetailObservationListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-detail-invoice/professorEventDetailInvoiceListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-customer-provider/professorEventCustomerProviderTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-customer-provider/professorEventCustomerProviderNewsListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-customer-provider/professorEventCustomerProviderEventListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-customer-provider-detail/professorEventCustomerProviderDetailListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-customer-provider-document/professorEventCustomerProviderDocumentListCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/event-mass-assign/professorEventMassAssignCtrl.js',
        ],

        'professorReportCtrl': [
            'themes/wgroup/assets/js/controllers/professor/report/reportCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/report/reportTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/report/reportEditCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/report/reportCalculatedCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/report/reportChartCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/report/reportDynamicallyCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/report/reportDynamicallyTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/report/reportGenerateCtrl.js',
            'themes/wgroup/assets/js/controllers/professor/report/reportGenerateTabsCtrl.js'
        ],

        //-------------------------------------------------END PROFESSOR






        'pollCtrl': ['themes/wgroup/assets/js/controllers/poll/pollCtrl.js', 'themes/wgroup/assets/js/controllers/poll/pollTabsCtrl.js'
            , 'themes/wgroup/assets/js/controllers/poll/pollEditCtrl.js', 'themes/wgroup/assets/js/controllers/poll/pollQuestionCtrl.js'
            , 'themes/wgroup/assets/js/controllers/poll/pollCustomerCtrl.js'],

        'reportCtrl': ['themes/wgroup/assets/js/controllers/report/reportCtrl.js', 'themes/wgroup/assets/js/controllers/report/reportTabsCtrl.js'
            , 'themes/wgroup/assets/js/controllers/report/reportEditCtrl.js', 'themes/wgroup/assets/js/controllers/report/reportCalculatedCtrl.js'
            , 'themes/wgroup/assets/js/controllers/report/reportChartCtrl.js', 'themes/wgroup/assets/js/controllers/report/reportDynamicallyCtrl.js'
            , 'themes/wgroup/assets/js/controllers/report/reportDynamicallyTabsCtrl.js', 'themes/wgroup/assets/js/controllers/report/reportGenerateCtrl.js'
            , 'themes/wgroup/assets/js/controllers/report/reportGenerateTabsCtrl.js'],

        'certificateCtrl': ['themes/wgroup/assets/js/controllers/certificate/certificateCtrl.js'],

        'certificateAdminCtrl': ['themes/wgroup/assets/js/controllers/certificateadmin/certificateAdminTabsCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificateadminprogram/certificateAdminProgramCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificateadminprogram/certificateAdminProgramListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificateadminprogram/certificateAdminProgramEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificateadmingrade/certificateAdminGradeCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificateadmingrade/certificateAdminGradeListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificateadmingrade/certificateAdminGradeEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificateadmingrade/certificateAdminGradeParticipantEditCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificateadmingrade/certificateAdminGradeParticipantCertificateListCtrl.js'],

        'certificateReportCtrl': ['themes/wgroup/assets/js/controllers/certificatereport/certificateReportTabsCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificatereport/certificateReportCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificatereport/certificateReportExpirationCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificatereport/certificateReportListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificatereport/certificateReportExpirationListCtrl.js'],

        'certificateLogBookCtrl': ['themes/wgroup/assets/js/controllers/certificatelogbook/certificateLogBookTabsCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificatelogbook/certificateLogBookCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificatelogbook/certificateLogBookCourseCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificatelogbook/certificateLogBookListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/certificatelogbook/certificateLogBookCourseListCtrl.js'],

        'configurationDisabilityDiagnosticCtrl': ['themes/wgroup/assets/js/controllers/configuration/configurationDisabilityDiagnosticListCtrl.js'],

        'configurationProjectTaskTypeCtrl': ['themes/wgroup/assets/js/controllers/configuration/configurationProjectTaskTypeListCtrl.js'],

        'configurationGeneralParameterCtrl': ['themes/wgroup/assets/js/controllers/configuration/configurationGeneralParameterCtrl.js'],

        'configurationTermConditionCtrl': ['themes/wgroup/assets/js/controllers/configuration/configurationTermConditionCtrl.js'],

        'configurationArlCtrl': ['themes/wgroup/assets/js/controllers/configuration/configurationArlCtrl.js'],

        'termConditionCtrl': ['themes/wgroup/assets/js/controllers/common/termConditionCtrl.js'],

        'configurationProgramPrevention': [
            'themes/wgroup/assets/js/controllers/configuration/configurationProgramPreventionTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration/configurationProgramPreventionQuestionListCtrl.js'
        ],

        'configurationManagementCtrl': [
            'themes/wgroup/assets/js/controllers/configuration/configurationManagementTabsCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration/configurationManagementProgramListCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration/configurationManagementCategoryListCtrl.js',
            'themes/wgroup/assets/js/controllers/configuration/configurationManagementQuestionListCtrl.js'
        ],

        'resourceLibraryCtrl': ['themes/wgroup/assets/js/controllers/resource-library/resourceLibraryTabsCtrl.js'
            , 'themes/wgroup/assets/js/controllers/resource-library/resourceLibraryListCtrl.js'
            , 'themes/wgroup/assets/js/controllers/resource-library/resourceLibrarySearchCtrl.js'
            , 'themes/wgroup/assets/js/controllers/resource-library/resourceLibraryCategoryCtrl.js'
        ],

        //*** Filters
        'htmlToPlaintext': 'themes/wgroup/assets/js/filters/htmlToPlaintext.js'
    },
    //*** angularJS Modules
    modules: [{
        name: 'ui.bootstrap.datetimepicker',
        files: [
            'themes/wgroup/assets/vendor/datetimepicker/datetimepicker.js',
            'themes/wgroup/assets/vendor/datetimepicker/datetimepicker-tpls-0.8.js',
            'themes/wgroup/assets/vendor/datetimepicker/datetimepicker.css'
        ]
    },
        {
            name: 'perfect_scrollbar',
            files: ['themes/wgroup/assets/vendor/perfect-scrollbar/angular-perfect-scrollbar.js']
        },
        {
            name: 'pasvaz.bindonce',
            files: ['themes/wgroup/assets/vendor/bindonce/bindonce.min.js']
        }, {
            name: 'infinite-scroll',
            files: ['themes/wgroup/assets/vendor/ngInfiniteScroll/ng-infinite-scroll.min.js']
        }, {
            name: 'ngScrollSpy',
            files: ['themes/wgroup/assets/vendor/ngScrollSpy/ngScrollSpy.js']
        }, {
            name: 'toaster',
            files: ['themes/wgroup/assets/vendor/toaster/toaster.js', 'themes/wgroup/assets/vendor/toaster/toaster.css']
        }, {
            name: 'angularBootstrapNavTree',
            files: ['themes/wgroup/assets/vendor/angular-bootstrap-nav-tree/abn_tree_directive.js', 'themes/wgroup/assets/vendor/angular-bootstrap-nav-tree/abn_tree.css']
        }, {
            name: 'angular-ladda',
            files: ['themes/wgroup/assets/vendor/ladda/angular-ladda.min.js']
        }, {
            name: 'ngTable',
            files: ['themes/wgroup/assets/vendor/ng-table/ng-table.min.js', 'themes/wgroup/assets/vendor/ng-table/ng-table.min.css']
        }, {
            name: 'ui.select',
            files: ['themes/wgroup/assets/vendor/ui-select/select.min.js', 'themes/wgroup/assets/vendor/ui-select/select.min.css', 'themes/wgroup/assets/vendor/ui-select/select2.css', 'themes/wgroup/assets/vendor/ui-select/select2-bootstrap.css', 'themes/wgroup/assets/vendor/ui-select/selectize.bootstrap3.css']
        }, {
            name: 'ui.mask',
            files: ['themes/wgroup/assets/vendor/ui-utils/mask/mask.js']
        }, {
            name: 'angular-bootstrap-touchspin',
            files: ['themes/wgroup/assets/vendor/bootstrap-touchspin/angular.bootstrap-touchspin.js', 'themes/wgroup/assets/vendor/bootstrap-touchspin/jquery.bootstrap-touchspin.min.css']
        }, {
            name: 'ngImgCrop',
            files: ['themes/wgroup/assets/vendor/ngImgCrop/ng-img-crop.js', 'themes/wgroup/assets/vendor/ngImgCrop/ng-img-crop.css']
        }, {
            name: 'angularFileUpload',
            files: ['themes/wgroup/assets/vendor/angular-file-upload/angular-file-upload.min.js', 'themes/wgroup/assets/vendor/angular-file-upload/directives.js']
        }, {
            name: 'ngAside',
            files: ['themes/wgroup/assets/vendor/angular-aside/angular-aside.min.js', 'themes/wgroup/assets/vendor/angular-aside/angular-aside.min.css']
        }, {
            name: 'truncate',
            files: ['themes/wgroup/assets/vendor/angular-truncate/truncate.js']
        }, {
            name: 'oitozero.ngSweetAlert',
            files: ['themes/wgroup/assets/vendor/sweet-alert/ngSweetAlert.min.js']
        }, {
            name: 'monospaced.elastic',
            files: ['themes/wgroup/assets/vendor/angular-elastic/elastic.js']
        }, {
            name: 'ngMap',
            files: ['themes/wgroup/assets/vendor/angular-google-maps/ng-map.min.js']
        }, {
            name: 'tc.chartjs',
            files: ['themes/wgroup/assets/vendor/chartjs/tc-angular-chartjs.min.js']
        }, {
            name: 'sparkline',
            files: ['themes/wgroup/assets/vendor/sparkline/angular-sparkline.js']
        }, {
            name: 'flow',
            files: ['themes/wgroup/assets/vendor/ng-flow/ng-flow-standalone.min.js']
        }, {
            name: 'uiSwitch',
            files: ['themes/wgroup/assets/vendor/angular-ui-switch/angular-ui-switch.min.js', 'themes/wgroup/assets/vendor/angular-ui-switch/angular-ui-switch.min.css']
        }, {
            name: 'ckeditor',
            files: ['themes/wgroup/assets/vendor/ckeditor/angular-ckeditor.min.js']
        }, {
            name: 'mwl.calendar',
            files: ['themes/wgroup/assets/vendor/angular-bootstrap-calendar/angular-bootstrap-calendar.js', 'themes/wgroup/assets/vendor/angular-bootstrap-calendar/angular-bootstrap-calendar-tpls.js', 'themes/wgroup/assets/vendor/angular-bootstrap-calendar/angular-bootstrap-calendar.min.css']
        }, {
            name: 'ng-nestable',
            files: ['themes/wgroup/assets/vendor/ng-nestable/angular-nestable.js']
        }, {
            name: 'vAccordion',
            files: ['themes/wgroup/assets/vendor/v-accordion/v-accordion.min.js', 'themes/wgroup/assets/vendor/v-accordion/v-accordion.min.css']
        }/*, {
            name: 'bootstrapLightbox',
            files: [
                'themes/wgroup/assets/vendor/angular-bootstrap-lightbox/dist/angular-bootstrap-lightbox.js',
                'themes/wgroup/assets/vendor/angular-bootstrap-lightbox/dist/angular-bootstrap-lightbox.css']
        }*/,
        {
            name: 'datatables',
            files: [
               /* 'themes/wgroup/assets/vendor/angular-datatables/angular-datatables.min.js',*/
                'themes/wgroup/assets/vendor/angular-datatables/datatables.bootstrap.min.css'
            ]
        },
        {
            name: 'datatables.bootstrap',
            files: [
                'themes/wgroup/assets/vendor/angular-datatables/plugins/bootstrap/angular-datatables.bootstrap.min.js',
                'https://cdn.datatables.net/responsive/2.1.0/css/responsive.dataTables.min.css',
                'https://cdn.datatables.net/responsive/2.1.0/js/dataTables.responsive.min.js',
            ]
        },
        {
            name: 'datatables.scroller',
            files: [
                'themes/wgroup/assets/vendor/angular-datatables/plugins/scroller/angular-datatables.scroller.min.js',
                'https://cdn.datatables.net/scroller/1.4.2/css/scroller.dataTables.min.css'
            ]
        }
    ]
});
