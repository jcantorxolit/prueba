'use strict';

/**
 * Config for the router
 */
app.config(['$stateProvider', '$urlRouterProvider', '$controllerProvider', '$compileProvider', '$filterProvider',
    '$provide', '$ocLazyLoadProvider', 'JS_REQUIRES', '$interpolateProvider', '$locationProvider',
    function ($stateProvider, $urlRouterProvider, $controllerProvider, $compileProvider, $filterProvider, $provide,
              $ocLazyLoadProvider, jsRequires, $interpolateProvider, $locationProvider) {
        $provide.decorator('$state', function ($delegate, $stateParams) {
            $delegate.forceReload = function () {
                return $delegate.go($delegate.current, $stateParams, {
                    reload: true,
                    inherit: false,
                    notify: true
                });
            };
            return $delegate;
        });
        /*
         $provide.decorator( "uiSelect", function( $delegate ) {
         var directive = $delegate[ 0 ];

         directive.compile = function compile( tElement, tAttrs ) {
         return {
         pre: function preLink( scope, iElement, iAttrs, controller ) {
         scope.$watchGroup( [ "$select.open", "$select.focus" ], function( val ) {
         scope.$parent.$broadcast( "uiSelect:events", val );
         });
         },
         post: directive.link
         }
         };

         return $delegate;
         });
         */
        $interpolateProvider.startSymbol('[[').endSymbol(']]');

        // use the HTML5 History API
        $locationProvider.html5Mode({
            enabled: true
        });

        app.controller = $controllerProvider.register;
        app.directive = $compileProvider.directive;
        app.filter = $filterProvider.register;
        app.factory = $provide.factory;
        app.service = $provide.service;
        app.constant = $provide.constant;
        app.value = $provide.value;

        // LAZY MODULES

        $ocLazyLoadProvider.config({
            debug: false,
            events: true,
            modules: jsRequires.modules
        });

        // APPLICATION ROUTES
        // -----------------------------------
        // For any unmatched url, redirect to /dashboard
        $urlRouterProvider.otherwise("/login/signin");

        //
        // Set up the states
        $stateProvider

            // Rutas para el dashboard
            .state('app', {
                url: '/app',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app.htm');
                },
                resolve: loadSequence('modernizr', 'uiSwitch', 'perfect-scrollbar-plugin', 'perfect_scrollbar',
                    'toaster', 'ngAside', 'vAccordion', 'sweet-alert', 'chartjs', 'tc.chartjs',
                    'oitozero.ngSweetAlert', 'listService', 'moduleListService', 'geoLocationService'),
                abstract: true
            })
            .state('app.term-condition', {
                url: '/term-condition',
                resolve: loadSequence('termConditionCtrl', 'base64', 'json3'),
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_terms_conditions.htm');
                }
            })
            .state('app.dashboard', {
                url: '/dasboard',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload',
                    'dashboardDiagnosticCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'dashboard'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.clientes', {
                url: '/clientes',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'customerTabsCtrl', 'customerCtrl', 'customerEditCtrl',
                    'customerTrackingCtrl', 'customerTrackingListCtrl', 'customerTrackingEditCtrl',
                    'customerDiagnosticEditCtrl', 'customerDiagnosticCtrl', 'customerDiagnosticListCtrl',
                    'customerDiagnosticSummaryCtrl', 'customerDiagnosticObservationListCtrl', 'customerDiagnosticProcessListCtrl',
                    'customerDiagnosticRiskFactorListCtrl', 'customerDiagnosticWorkPlaceListCtrl', 'customerDiagnosticAccidentListCtrl',
                    'customerDiagnosticDiseaseListCtrl', 'customerDiagnosticRiskTaskListCtrl', 'customerDiagnosticArlListCtrl',
                    'customerDiagnosticEnviromentalListCtrl', 'customerDiagnosticArlIntermediaryListCtrl', 'customerDiagnosticEnviromentalIntermediaryListCtrl',
                    'customerDiagnosticReportCtrl', 'customerDocumentListCtrl', 'customerDocumentCtrl', 'customerDocumentSettingCtrl', 'customerAuditCtrl', 'customerAuditListCtrl', 'customerManagementSettingCtrl'
                    , 'customerManagementCtrl', 'customerManagementEditCtrl', 'customerManagementListCtrl', 'customerManagementReportCtrl', 'customerManagementSummaryCtrl', 'customerPollCtrl'
                    , 'customerAbsenteeismCtrl', 'customerContractCtrl', 'customerEmployeeCtrl', 'customerActionPlanCtrl',
                    'customerOccupationalReportALCtrl', 'customerConfigSGSSTCtrl', 'customerSafetyInspection', 'customerWorkMedicineCtrl',
                    'customerEmployeeOccupationalExaminationCtrl', 'customerHealthDamageDiagnosticSource', 'customerHealthDamageRestriction',
                    'customerHealthDamageQualificationSource',
                    'customerHealthDamageQualificationLost', 'customerHealthDamageAdministrativeProcess', 'customerHealthDamageObservation',
                    'customerHealthDamageAnalysis',
                    'customerDiagnosticPreventionDocument',
                    'customerHealthDamageDisability', 'customerInternalCertificate', 'customerOccupationalReportIncidentCtrl',
                    'customerMatrix', 'customerOccupationalInvestigationCtrl', 'customerEvaluationMinimumStandardCtrl', 'customerRoadSafetyCtrl', 'customerImprovementPlanCtrl', 'unsafeAct'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Clientes'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.dashboard.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_dashboards.htm');
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.clientes.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_clientes.htm');
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.clientes.create', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_clientes_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.clientes.edit', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/edit/:customerId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_clientes_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.clientes.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:customerId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_clientes_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            .state('app.enrollment', {
                url: '/enrollment',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'customerEnrollmentCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Pre-registro'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.enrollment.list', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_enrollment_list.htm');
                },
                ncyBreadcrumb: {
                    label: 'Estadistica'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.enrollment.create', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_enrollment_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            .state('app.asesores', {
                url: '/asesores',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'agentCtrl', 'agentEditCtrl', 'agentTabsCtrl',
                    'agentDocumentCtrl', 'agentDocumentListCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Asesores'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.asesores.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_asesores.htm');
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.asesores.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_asesores_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.asesores.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:agentId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_asesores_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.asesores.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:agentId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_asesores_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })


            .state('app.investigation', {
                url: '/investigation',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'investigationCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Investigación AL / IL'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.investigation.list', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_investigation_list.htm');
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.investigation.create', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_investigation_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.investigation.edit', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/edit/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_investigation_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.investigation.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_investigation_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.investigation.review', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/review/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_investigation_review.htm');
                },
                ncyBreadcrumb: {
                    label: 'Analisis'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            .state('app.investigation.review-expiration', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/review-expiration',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_investigation_review_expiration.htm');
                },
                ncyBreadcrumb: {
                    label: 'Analisis de vencimientos'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            .state('app.investigation.tracing', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/tracing',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_investigation_tracing.htm');
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })

            .state('app.planer', {
                url: '/planer',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce', 'mwl.calendar',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'agentCtrl', 'agentEditCtrl', 'agentTabsCtrl',
                    'agentDocumentCtrl', 'agentDocumentListCtrl', 'flow', 'base64', 'planerCalendarCtrl', 'projectCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Planeador'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.planer.calendar', {
                url: '/calendar',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_planer.htm');
                },
                ncyBreadcrumb: {
                    label: 'Calendario'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.planer.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_planer_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.planer.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:agentId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_planer_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.planer.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:agentId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_planer_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.projects', {
                url: '/project',
                resolve: loadSequence('datejs', 'random-color', 'uiSwitch', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce', 'mwl.calendar',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'agentCtrl', 'agentEditCtrl', 'agentTabsCtrl',
                    'agentDocumentCtrl', 'agentDocumentListCtrl', 'planerCalendarCtrl', 'flow', 'base64', 'projectCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Proyecto'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.projects.planning', {
                url: '/planning',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_project_planning.htm');
                },
                ncyBreadcrumb: {
                    label: 'Planeación'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.projects.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_project.htm');
                },
                ncyBreadcrumb: {
                    label: 'Administración'
                }
            })
            .state('app.projects.budget', {
                url: '/budget',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_project_budget.htm');
                },
                ncyBreadcrumb: {
                    label: 'Presupuesto'
                }
            })
            .state('app.projects.billing', {
                url: '/billing',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_project_billing.htm');
                },
                ncyBreadcrumb: {
                    label: 'Facturacion'
                }
            })
            .state('app.internal-projects', {
                url: '/internal-project',
                resolve: loadSequence('datejs', 'random-color', 'uiSwitch', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce', 'mwl.calendar',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'agentCtrl', 'agentEditCtrl', 'agentTabsCtrl',
                    'agentDocumentCtrl', 'agentDocumentListCtrl', 'planerCalendarCtrl', 'flow', 'base64', 'internalProjectCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Proyecto Interno'
                }
            })
            .state('app.internal-projects.planning', {
                url: '/planning',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_internal_project_planning.htm');
                },
                ncyBreadcrumb: {
                    label: 'Planeación Proyecto Interno'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.internal-projects.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_internal_project.htm');
                },
                ncyBreadcrumb: {
                    label: 'Proyecto Interno'
                }
            })
            .state('app.cotizaciones', {
                url: '/cotizaciones',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'quoteCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Asesores'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.cotizaciones.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_cotizaciones.htm');
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.cotizaciones.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_cotizaciones_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.cotizaciones.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:quoteId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_cotizaciones_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.cotizaciones.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:quoteId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_cotizaciones_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.poll', {
                url: '/encuestas',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'pollCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Asesores'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.poll.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_encuestas.htm');
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.poll.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_encuestas_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.poll.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_encuestas_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.poll.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_encuestas_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.report', {
                url: '/reportes',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'dual-list-box', 'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'reportCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Reportes'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.report.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_reportes.htm');
                },
                data: {
                    module: 'customer'
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.report.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_reportes_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.report.dynamically', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/dynamically',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_reportes_dynamically.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación dinámica'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.report.generate', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/generate/:reportId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_reportes_generate.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.report.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:reportId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_reportes_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate', {
                url: '/certificados',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'certificateCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Certificados'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.certificate.validate', {
                url: '/validate',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_certificates.htm');
                },
                ncyBreadcrumb: {
                    label: 'Dashboard'
                }
            })
            .state('app.certificate.admin', {
                resolve: loadSequence('base64', 'json3', 'flow', 'certificateAdminCtrl'),
                url: '/admin',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_certificates_admin.htm');
                },
                ncyBreadcrumb: {
                    label: 'Administración'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.admin.program', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/program',
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Programa'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.admin.program.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_certificates_program_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.admin.program.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:programId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_certificates_program_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.admin.program.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:programId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_certificates_program_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.management', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/management',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_certificates.htm');
                },
                ncyBreadcrumb: {
                    label: 'Gestión'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.report', {
                resolve: loadSequence('base64', 'json3', 'flow', 'certificateReportCtrl'),
                url: '/search',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_certificates_report.htm');
                },
                ncyBreadcrumb: {
                    label: 'Consulta'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.logbook', {
                resolve: loadSequence('base64', 'json3', 'flow', 'certificateLogBookCtrl'),
                url: '/logbook',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_certificates_logbook.htm');
                },
                ncyBreadcrumb: {
                    label: 'Consulta'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            .state('app.resource', {
                url: '/resource-library',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Biblioteca de Recursos'
                }
            })
            .state('app.resource.library', {
                resolve: loadSequence('base64', 'json3', 'flow', 'resourceLibraryCtrl'),
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_resource_library.htm');
                },
                ncyBreadcrumb: {
                    label: 'Administrar'
                }
            })

            .state('app.configuration', {
                url: '/configuration',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce', 'mwl.calendar',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'flow', 'base64', 'configurationDisabilityDiagnosticCtrl',
                    'configurationProjectTaskTypeCtrl', 'configurationGeneralParameterCtrl', 'configurationManagementCtrl',
                    'configurationProgramPrevention', 'configurationTermConditionCtrl', 'configurationArlCtrl', 'configurationMinimumStandard',
                    'configurationMinimumStandardItem', 'resourceLibraryCtrl', 'configurationRoadSafety', 'configurationRoadSafetyItem'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Configuración'
                }
            })
            .state('app.configuration.term-condition', {
                url: '/term-condition',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_terms_conditions.htm');
                },
                ncyBreadcrumb: {
                    label: 'Términos y Condiciones'
                }
            })
            .state('app.configuration.parameters', {
                url: '/planning',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_general_parameter.htm');
                },
                ncyBreadcrumb: {
                    label: 'Parametrización General'
                }
            })
            .state('app.configuration.arl', {
                url: '/arl',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_arl.htm');
                },
                ncyBreadcrumb: {
                    label: 'Parametrización ARL'
                }
            })
            .state('app.configuration.diagnostic-disability', {
                url: '/diagnostic-disability',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_disability_diagnostic.htm');
                },
                ncyBreadcrumb: {
                    label: 'Diagnostico Incapacidad'
                }
            })
            .state('app.configuration.project-task-type', {
                url: '/project-task-type',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_project_task_type.htm');
                },
                ncyBreadcrumb: {
                    label: 'Tipos Tarea Proyecto'
                }
            })
            .state('app.configuration.management-system', {
                url: '/business-programs',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_program_prevention.htm');
                },
                ncyBreadcrumb: {
                    label: 'Sistema de gestión'
                }
            })
            .state('app.configuration.business-programs', {
                url: '/business-programs',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_management.htm');
                },
                ncyBreadcrumb: {
                    label: 'Programas empresariales'
                }
            })

            .state('app.configuration.minimum-standard', {
                url: '/minimum-standard',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_minimum_standard.htm');
                },
                ncyBreadcrumb: {
                    label: 'Estándares Mínimos'
                }
            })

            .state('app.configuration.road-safety', {
                url: '/road-safety',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_configuration_road_safety.htm');
                },
                ncyBreadcrumb: {
                    label: 'Seguridad Vial'
                }
            })

            .state('app.configuration.resource-library', {
                url: '/resource-library',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_resource_library.htm');
                },
                ncyBreadcrumb: {
                    label: 'Biblioteca de recursos'
                }
            })


            .state('app.configuration.complementary-test', {
                url: '/complementary-test',
                templateUrl: "themes/wgroup/assets/modules/configuration/complementary-test/_index.htm",
                title: 'Pruebas Complentarias - Resultados',
                ncyBreadcrumb: {
                    label: 'Pruebas Complentarias - Resultados'
                },
                resolve: loadSequence('moment', 'mwl.calendar', 'vAccordion', 'configurationComplementaryTest')
            })

            .state('app.configuration.prioritization-factor', {
                url: '/prioritization-factor',
                templateUrl: "themes/wgroup/assets/modules/configuration/prioritization-factor/_index.htm",
                title: 'Resultados Factor de Priorización',
                ncyBreadcrumb: {
                    label: 'Resultados Factor de Priorización'
                },
                resolve: loadSequence('moment', 'mwl.calendar', 'vAccordion', 'configurationPrioritizationFactor')
            })

            .state('app.configuration.help-roles-profiles', {
                url: '/help-roles-profiles',
                templateUrl: "themes/wgroup/assets/modules/configuration/help-roles-profiles/configuration_help_roles_profiles.htm",
                title: 'Ayudas Roles y Perfiles',
                ncyBreadcrumb: {
                    label: 'Ayudas Roles y Perfiles'
                }
            })

            //-----------------------------------------------------START PROFESSOR
            .state('app.professor', {
                url: '/professor',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce', 'mwl.calendar',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'flow', 'base64', 'professorDocumentCtrl', 'professorParameterConfigCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Profe'
                }
            })
            .state('app.professor.document', {
                url: '/document',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_document.htm');
                },
                ncyBreadcrumb: {
                    label: 'Repositorio de Documentos'
                }
            })
            .state('app.professor.program', {
                url: '/program',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_parameter_config.htm');
                },
                ncyBreadcrumb: {
                    label: 'Programas'
                }
            })
            .state('app.professor-event', {
                url: '/professor-event',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'flow', 'base64', 'professorEventCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Evento'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.professor-event.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event.htm');
                },
                ncyBreadcrumb: {
                    label: 'Eventos'
                }
            })
            .state('app.professor-event.mass-assign-cover', {
                url: '/mass-assign-cover',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_mass_assign.htm');
                },
                ncyBreadcrumb: {
                    label: 'Asignación masiva de imagen a Eventos'
                }
            })
            .state('app.professor-event.mass-assign-provider', {
                url: '/mass-assign-provider',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_mass_assign.htm');
                },
                ncyBreadcrumb: {
                    label: 'Asignación masiva de proveedor a Eventos por ciudad'
                }
            })
            .state('app.professor-event.mass-assign-sponsor', {
                url: '/mass-assign-sponsor',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_mass_assign.htm');
                },
                ncyBreadcrumb: {
                    label: 'Asignación masiva de padrino / madrina a Eventos por ciudad'
                }
            })
            .state('app.professor-event.mass-assign-director', {
                url: '/mass-assign-director',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_mass_assign.htm');
                },
                ncyBreadcrumb: {
                    label: 'Asignación masiva de director a Eventos por ciudad'
                }
            })
            .state('app.professor-event.mass-assign-agent', {
                url: '/mass-assign-agent',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_mass_assign.htm');
                },
                ncyBreadcrumb: {
                    label: 'Asignación masiva de asesores a Eventos por ciudad'
                }
            })
            .state('app.professor-event.mass-assign-schedule', {
                url: '/mass-assign-schedule',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_mass_assign.htm');
                },
                ncyBreadcrumb: {
                    label: 'Asignación masiva de horarios a Eventos por ciudad'
                }
            })
            .state('app.professor-event.mass-assign-document', {
                url: '/mass-assign-document',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_mass_assign.htm');
                },
                ncyBreadcrumb: {
                    label: 'Asignación masiva de documentos a Eventos'
                }
            })
            .state('app.professor-event.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-event.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-event.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })



            .state('app.professor-event-customer', {
                url: '/professor-event-customer',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'flow', 'base64', 'professorEventCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Evento'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.professor-event-customer.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_customer_provider.htm');
                },
                ncyBreadcrumb: {
                    label: 'Eventos'
                }
            })
            .state('app.professor-event-customer.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-event-customer.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-event-customer.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            .state('app.professor-event-provider', {
                url: '/professor-event-provider',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'flow', 'base64', 'professorEventCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Evento'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.professor-event-provider.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_customer_provider.htm');
                },
                ncyBreadcrumb: {
                    label: 'Eventos'
                }
            })
            .state('app.professor-event-provider.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-event-provider.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-event-provider.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_event_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            .state('app.professor-provider', {
                url: '/professor-provider',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'flow', 'base64', 'professorProviderCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Evento'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.professor-provider.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_provider.htm');
                },
                ncyBreadcrumb: {
                    label: 'Eventos'
                }
            })
            .state('app.professor-provider.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_provider_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-provider.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_provider_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-provider.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_provider_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            .state('app.professor-news', {
                url: '/professor-news',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'flow', 'base64', 'professorNewsCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Noticias'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.professor-news.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_news.htm');
                },
                ncyBreadcrumb: {
                    label: 'Noticias'
                }
            })
            .state('app.professor-news.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_news_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-news.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_news_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-news.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_news_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            .state('app.professor-report', {
                url: '/professor-report',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'dual-list-box', 'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'professorReportCtrl'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Reportes'
                }
            })
            .state('app.professor-report.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_reportes.htm');
                },
                data: {
                    module: 'professor'
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.professor-report.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_reportes_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-report.dynamically', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/dynamically',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_reportes_dynamically.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación dinámica'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-report.generate', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/generate/:reportId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_reportes_generate.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.professor-report.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:reportId',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_professor_reportes_form.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            //------------------------------------------------------------END PROFESSOR

            .state('app.program-prevention-document', {
                url: '/program-prevention-document',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload', 'flow', 'base64', 'configurationProgramPreventionDocument'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Documento'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.program-prevention-document.list', {
                url: '/list',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_program_prevention_document_list.htm');
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.program-prevention-document.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_program_prevention_document_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Creación'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.program-prevention-document.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_program_prevention_document_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Edición'
                }/*,
                 controller: 'customerEditCtrl'*/
            })
            .state('app.program-prevention-document.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_program_prevention_document_edit.htm');
                },
                ncyBreadcrumb: {
                    label: 'Visualización'
                }/*,
                 controller: 'customerEditCtrl'*/
            })

            // Rutas para el login
            .state('login', {
                url: '/login',
                template: '<div ui-view class="fade-in-right-big smooth"></div>',
                abstract: true
            })
            .state('login.signin', {
                url: '/signin',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('login_login.htm');
                }
            })
            .state('login.forgot', {
                url: '/forgot',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('login_forgot.htm');
                }
            })
            .state('login.certificate', {
                url: '/certificate',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('login_certificate.htm');
                }
            })
            .state('login.registration', {
                url: '/registration',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('login_registration.htm');
                }
            })
            .state('login.lockscreen', {
                url: '/lock',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('login_lock_screen.htm');
                }
            })
            .state('app.user', {
                url: '/user',
                resolve: loadSequence('datejs', 'random-color', 'duScroll', 'infinite-scroll', 'pasvaz.bindonce',
                    'bootstrap-datetimepicker', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angular-bootstrap-touchspin', 'angularFileUpload'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Cuenta'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.user.account', {
                url: '/account',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('app_user.htm');
                },
                ncyBreadcrumb: {
                    label: 'Cuenta'
                }/*,
                 controller: 'customerCtrl'*/
            })



            /* .state('app', {
             url: "^/login",
             templateProvider: function ($templateCache) {
             // simplified, expecting that the cache is filled
             // there should be some checking... and async $http loading if not found
             return $templateCache.get('login.htm');
             },
             resolve: loadSequence('modernizr', 'moment', 'uiSwitch', 'perfect-scrollbar-plugin', 'perfect_scrollbar', 'toaster', 'ngAside', 'vAccordion', 'sweet-alert', 'chartjs', 'tc.chartjs', 'oitozero.ngSweetAlert'),
             abstract: true
             })
             .state('app.dashboard', {
             url: "^/dashboard",
             templateProvider: function ($templateCache) {
             // simplified, expecting that the cache is filled
             // there should be some checking... and async $http loading if not found
             return $templateCache.get('dashboard.htm');
             },
             resolve: loadSequence('jquery-sparkline', 'sparkline', 'dashboardCtrl'),
             title: 'Dashboard',
             ncyBreadcrumb: {
             label: 'Dashboard'
             }
             }).state('app.ui', {
             url: '/ui',
             template: '<div ui-view class="fade-in-up"></div>',
             title: 'UI Elements',
             ncyBreadcrumb: {
             label: 'UI Elements'
             }
             }).state('app.ui.elements', {
             url: '/elements',
             templateUrl: "assets/views/ui_elements.html",
             title: 'Elements',
             icon: 'ti-layout-media-left-alt',
             ncyBreadcrumb: {
             label: 'Elements'
             }
             }).state('app.ui.buttons', {
             url: '/buttons',
             templateUrl: "assets/views/ui_buttons.html",
             title: 'Buttons',
             resolve: loadSequence('spin', 'ladda', 'angular-ladda', 'laddaCtrl'),
             ncyBreadcrumb: {
             label: 'Buttons'
             }
             }).state('app.ui.links', {
             url: '/links',
             templateUrl: "assets/views/ui_links.html",
             title: 'Link Effects',
             ncyBreadcrumb: {
             label: 'Link Effects'
             }
             }).state('app.ui.icons', {
             url: '/icons',
             templateUrl: "assets/views/ui_icons.html",
             title: 'Font Awesome Icons',
             ncyBreadcrumb: {
             label: 'Font Awesome Icons'
             },
             resolve: loadSequence('iconsCtrl')
             }).state('app.ui.lineicons', {
             url: '/line-icons',
             templateUrl: "assets/views/ui_line_icons.html",
             title: 'Linear Icons',
             ncyBreadcrumb: {
             label: 'Linear Icons'
             },
             resolve: loadSequence('iconsCtrl')
             }).state('app.ui.modals', {
             url: '/modals',
             templateUrl: "assets/views/ui_modals.html",
             title: 'Modals',
             ncyBreadcrumb: {
             label: 'Modals'
             },
             resolve: loadSequence('asideCtrl')
             }).state('app.ui.toggle', {
             url: '/toggle',
             templateUrl: "assets/views/ui_toggle.html",
             title: 'Toggle',
             ncyBreadcrumb: {
             label: 'Toggle'
             }
             }).state('app.ui.tabs_accordions', {
             url: '/accordions',
             templateUrl: "assets/views/ui_tabs_accordions.html",
             title: "Tabs & Accordions",
             ncyBreadcrumb: {
             label: 'Tabs & Accordions'
             },
             resolve: loadSequence('vAccordionCtrl')
             }).state('app.ui.panels', {
             url: '/panels',
             templateUrl: "assets/views/ui_panels.html",
             title: 'Panels',
             ncyBreadcrumb: {
             label: 'Panels'
             }
             }).state('app.ui.notifications', {
             url: '/notifications',
             templateUrl: "assets/views/ui_notifications.html",
             title: 'Notifications',
             ncyBreadcrumb: {
             label: 'Notifications'
             },
             resolve: loadSequence('toasterCtrl', 'sweetAlertCtrl')
             }).state('app.ui.treeview', {
             url: '/treeview',
             templateUrl: "assets/views/ui_tree.html",
             title: 'TreeView',
             ncyBreadcrumb: {
             label: 'Treeview'
             },
             resolve: loadSequence('angularBootstrapNavTree', 'treeCtrl')
             }).state('app.ui.media', {
             url: '/media',
             templateUrl: "assets/views/ui_media.html",
             title: 'Media',
             ncyBreadcrumb: {
             label: 'Media'
             }
             }).state('app.ui.nestable', {
             url: '/nestable2',
             templateUrl: "assets/views/ui_nestable.html",
             title: 'Nestable List',
             ncyBreadcrumb: {
             label: 'Nestable List'
             },
             resolve: loadSequence('jquery-nestable-plugin', 'ng-nestable', 'nestableCtrl')
             }).state('app.ui.typography', {
             url: '/typography',
             templateUrl: "assets/views/ui_typography.html",
             title: 'Typography',
             ncyBreadcrumb: {
             label: 'Typography'
             }
             }).state('app.table', {
             url: '/table',
             template: '<div ui-view class="fade-in-up"></div>',
             title: 'Tables',
             ncyBreadcrumb: {
             label: 'Tables'
             }
             }).state('app.table.basic', {
             url: '/basic',
             templateUrl: "assets/views/table_basic.html",
             title: 'Basic Tables',
             ncyBreadcrumb: {
             label: 'Basic'
             }
             }).state('app.table.responsive', {
             url: '/responsive',
             templateUrl: "assets/views/table_responsive.html",
             title: 'Responsive Tables',
             ncyBreadcrumb: {
             label: 'Responsive'
             }
             }).state('app.table.data', {
             url: '/data',
             templateUrl: "assets/views/table_data.html",
             title: 'ngTable',
             ncyBreadcrumb: {
             label: 'ngTable'
             },
             resolve: loadSequence('ngTable', 'ngTableCtrl')
             }).state('app.table.export', {
             url: '/export',
             templateUrl: "assets/views/table_export.html",
             title: 'Table'
             }).state('app.form', {
             url: '/form',
             template: '<div ui-view class="fade-in-up"></div>',
             title: 'Forms',
             ncyBreadcrumb: {
             label: 'Forms'
             }
             }).state('app.form.elements', {
             url: '/elements',
             templateUrl: "assets/views/form_elements.html",
             title: 'Forms Elements',
             ncyBreadcrumb: {
             label: 'Elements'
             },
             resolve: loadSequence('ui.select', 'ui.mask', 'monospaced.elastic', 'touchspin-plugin', 'angular-bootstrap-touchspin', 'selectCtrl')
             }).state('app.form.texteditor', {
             url: '/editor',
             templateUrl: "assets/views/form_text_editor.html",
             title: 'Text Editor',
             ncyBreadcrumb: {
             label: 'Text Editor'
             },
             resolve: loadSequence('ckeditor-plugin', 'ckeditor', 'ckeditorCtrl')
             }).state('app.form.wizard', {
             url: '/wizard',
             templateUrl: "assets/views/form_wizard.html",
             title: 'Form Wizard',
             ncyBreadcrumb: {
             label: 'Wizard'
             },
             resolve: loadSequence('wizardCtrl')
             }).state('app.form.validation', {
             url: '/validation',
             templateUrl: "assets/views/form_validation.html",
             title: 'Form Validation',
             ncyBreadcrumb: {
             label: 'Validation'
             },
             resolve: loadSequence('validationCtrl')
             }).state('app.form.cropping', {
             url: '/image-cropping',
             templateUrl: "assets/views/form_image_cropping.html",
             title: 'Image Cropping',
             ncyBreadcrumb: {
             label: 'Image Cropping'
             },
             resolve: loadSequence('ngImgCrop', 'cropCtrl')
             }).state('app.form.upload', {
             url: '/file-upload',
             templateUrl: "assets/views/form_file_upload.html",
             title: 'Multiple File Upload',
             ncyBreadcrumb: {
             label: 'File Upload'
             },
             resolve: loadSequence('angularFileUpload', 'uploadCtrl')
             }).state('app.pages', {
             url: '/pages',
             template: '<div ui-view class="fade-in-up"></div>',
             title: 'Pages',
             ncyBreadcrumb: {
             label: 'Pages'
             }
             }).state('app.pages.user', {
             url: '/user',
             templateUrl: "assets/views/pages_user_profile.html",
             title: 'User Profile',
             ncyBreadcrumb: {
             label: 'User Profile'
             },
             resolve: loadSequence('flow', 'userCtrl')
             }).state('app.pages.invoice', {
             url: '/invoice',
             templateUrl: "assets/views/pages_invoice.html",
             title: 'Invoice',
             ncyBreadcrumb: {
             label: 'Invoice'
             }
             }).state('app.pages.timeline', {
             url: '/timeline',
             templateUrl: "assets/views/pages_timeline.html",
             title: 'Timeline',
             ncyBreadcrumb: {
             label: 'Timeline'
             },
             resolve: loadSequence('ngMap')
             }).state('app.pages.calendar', {
             url: '/calendar',
             templateUrl: "assets/views/pages_calendar.html",
             title: 'Calendar',
             ncyBreadcrumb: {
             label: 'Calendar'
             },
             resolve: loadSequence('moment', 'mwl.calendar', 'calendarCtrl')
             }).state('app.pages.messages', {
             url: '/messages',
             templateUrl: "assets/views/pages_messages.html",
             resolve: loadSequence('truncate', 'htmlToPlaintext', 'inboxCtrl')
             }).state('app.pages.messages.inbox', {
             url: '/inbox/:inboxID',
             templateUrl: "assets/views/pages_inbox.html",
             controller: 'ViewMessageCrtl'
             }).state('app.pages.blank', {
             url: '/blank',
             templateUrl: "assets/views/pages_blank_page.html",
             ncyBreadcrumb: {
             label: 'Starter Page'
             }
             }).state('app.utilities', {
             url: '/utilities',
             template: '<div ui-view class="fade-in-up"></div>',
             title: 'Utilities',
             ncyBreadcrumb: {
             label: 'Utilities'
             }
             }).state('app.utilities.search', {
             url: '/search',
             templateUrl: "assets/views/utility_search_result.html",
             title: 'Search Results',
             ncyBreadcrumb: {
             label: 'Search Results'
             }
             }).state('app.utilities.pricing', {
             url: '/pricing',
             templateUrl: "assets/views/utility_pricing_table.html",
             title: 'Pricing Table',
             ncyBreadcrumb: {
             label: 'Pricing Table'
             }
             }).state('app.maps', {
             url: "/maps",
             templateUrl: "assets/views/maps.html",
             resolve: loadSequence('ngMap', 'mapsCtrl'),
             title: "Maps",
             ncyBreadcrumb: {
             label: 'Maps'
             }
             }).state('app.charts', {
             url: "/charts",
             templateUrl: "assets/views/charts.html",
             resolve: loadSequence('chartjs', 'tc.chartjs', 'chartsCtrl'),
             title: "Charts",
             ncyBreadcrumb: {
             label: 'Charts'
             }
             }).state('app.documentation', {
             url: "/documentation",
             templateUrl: "assets/views/documentation.html",
             title: "Documentation",
             ncyBreadcrumb: {
             label: 'Documentation'
             }
             }).state('error', {
             url: '/error',
             template: '<div ui-view class="fade-in-up"></div>'
             }).state('error.404', {
             url: '/404',
             templateUrl: "assets/views/utility_404.html",
             }).state('error.500', {
             url: '/500',
             templateUrl: "assets/views/utility_500.html",
             })

             // Login routes

             .state('login', {
             url: '/login',
             template: '<div ui-view class="fade-in-right-big smooth"></div>',
             abstract: true
             }).state('login.signin', {
             url: '/signin',
             templateUrl: "assets/views/login_login.html"
             }).state('login.forgot', {
             url: '/forgot',
             templateUrl: "assets/views/login_forgot.html"
             }).state('login.registration', {
             url: '/registration',
             templateUrl: "assets/views/login_registration.html"
             }).state('login.lockscreen', {
             url: '/lock',
             templateUrl: "assets/views/login_lock_screen.html"
             })*/;

        // Generates a resolve object previously configured in constant.JS_REQUIRES (config.constant.js)
        function loadSequence() {
            var _args = arguments;
            return {
                deps: ['$ocLazyLoad', '$q',
                    function ($ocLL, $q) {
                        var promise = $q.when(1);
                        for (var i = 0, len = _args.length; i < len; i++) {
                            promise = promiseThen(_args[i]);
                        }
                        return promise;

                        function promiseThen(_arg) {
                            if (typeof _arg == 'function')
                                return promise.then(_arg);
                            else
                                return promise.then(function () {
                                    var nowLoad = requiredData(_arg);
                                    if (!nowLoad)
                                        return $.error('Route resolve: Bad resource name [' + _arg + ']');
                                    return $ocLL.load(nowLoad);
                                });
                        }

                        function requiredData(name) {
                            if (jsRequires.modules)
                                for (var m in jsRequires.modules)
                                    if (jsRequires.modules[m].name && jsRequires.modules[m].name === name)
                                        return jsRequires.modules[m];
                            return jsRequires.scripts && jsRequires.scripts[name];
                        }
                    }]
            };
        }
    }]);