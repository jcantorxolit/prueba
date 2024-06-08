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
                resolve: loadSequence('modernizr', 'uiSwitch', 'perfect-scrollbar-plugin', 'knob-d3',
                    'toaster', 'ngAside', 'vAccordion', 'chartjs', 'tc.chartjs', 'truncate', 'qrcode',
                    'listService', 'moduleListService', 'geoLocationService', 'chartService', 'ja.qr',
                    'cp.ngConfirm', 'ngNotify', 'ui.knob', 'moment', 'momentwl', 'momentlocale', 'angularMoment',
                    'angular-notification-icons', 'htmlToPlaintext', 'base64', 'ui.swiper', 'daterangepicker', 'userController'),
                abstract: true
            })
            .state('app.term-condition', {
                url: '/term-condition',
                resolve: loadSequence('termConditionController', 'base64', 'json3', 'ui.swiper', 'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller'),
                templateUrl: "themes/wgroup/assets/modules/term-conditions/terms_conditions.htm",
            })
            .state('app.dashboard', {
                url: '/dasboard',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select',
                    'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angularFileUpload',
                    'dashboardController', 'base64', 'ui.swiper'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'dashboard'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.dashboard.list', {
                url: '/list',
                templateUrl: "themes/wgroup/assets/modules/dashboard/dashboard_list.htm",
                ncyBreadcrumb: {
                    label: 'Lista'
                }/*,
                 controller: 'customerCtrl'*/
            })
            .state('app.dashboard.top-management', {
                url: '/top-management',
                resolve: loadSequence('moment', 'angularMoment', 'mwl.calendar', 'vAccordion', 'configController'),
                templateUrl: "themes/wgroup/assets/modules/dashboard/top-management/top_management_tabs.htm",
                ncyBreadcrumb: {
                    label: 'Alta Gerencia'
                }
            })
            .state('app.dashboard.commercial', {
                url: '/commercial',
                resolve: loadSequence('moment', 'angularMoment', 'mwl.calendar', 'vAccordion', 'configController'),
                templateUrl: "themes/wgroup/assets/modules/dashboard/commercial/commercial.htm",
                ncyBreadcrumb: {
                    label: 'Alta Gerencia'
                }
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START CLLIENT ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            .state('app.clientes', {
                url: '/clientes',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angularFileUpload', 'pdfjs', 'pdf',
                    'customerController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Clientes'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.clientes.list', {
                resolve: loadSequence('jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'customerController'),
                url: '/list',
                ncyBreadcrumb: {
                    label: 'Lista'
                },
                templateUrl: "themes/wgroup/assets/modules/customer/customer_list.htm",
                title: 'Pruebas Complentarias - Resultados',
            })

            .state('app.clientes.create', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/create',
                templateUrl: "themes/wgroup/assets/modules/customer/customer_edit_tab.htm",
                ncyBreadcrumb: {
                    label: 'Creación'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.clientes.edit', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/edit/:customerId',
                templateUrl: "themes/wgroup/assets/modules/customer/customer_edit_tab.htm",
                ncyBreadcrumb: {
                    label: 'Edición'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.clientes.view', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/view/:customerId',
                templateUrl: "themes/wgroup/assets/modules/customer/customer_edit_tab.htm",
                ncyBreadcrumb: {
                    label: 'Visualización'
                }
            })
            .state('app.clientes.group', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/group/:customerId',
                templateUrl: "themes/wgroup/assets/modules/customer/customer_edit_tab.htm",
                ncyBreadcrumb: {
                    label: 'Visualización'
                }
            })
            .state('app.clientes.contract', {
                resolve: loadSequence('base64', 'json3', 'flow', 'angularFileUpload'),
                url: '/contract/:customerId',
                templateUrl: "themes/wgroup/assets/modules/customer/customer_edit_tab.htm",
                ncyBreadcrumb: {
                    label: 'Visualización'
                }
            })

            .state('app.clientes-reports-cyc.protocols-edit', {
                url: '/protocols/edit/:protocolId',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/consultant/consultant_edit.htm"
            })


            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END CLLIENT ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\



            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START CONFIG ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            .state('app.configuration', {
                url: '/configuration',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce', 'mwl.calendar',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment', 'angularMoment',
                    'touchspin-plugin', 'angularFileUpload', 'flow', 'base64', 'configController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Configuración'
                }
            })
            .state('app.configuration.term-condition', {
                url: '/term-condition',
                templateUrl: "themes/wgroup/assets/modules/configuration/terms-conditions/configuration_terms_conditions.htm",
                ncyBreadcrumb: {
                    label: 'Términos y Condiciones'
                }
            })
            .state('app.configuration.privacy-policy', {
                url: '/privacy-policy',
                templateUrl: "themes/wgroup/assets/modules/configuration/privacy-policy/configuration_privacy_policy.htm",
                ncyBreadcrumb: {
                    label: 'Política de tratamiento de datos personales'
                }
            })
            .state('app.configuration.economic-sector-task', {
                url: '/economic-sector-task',
                templateUrl: "themes/wgroup/assets/modules/configuration/economic-sector-task/economic_sector_task.htm",
                ncyBreadcrumb: {
                    label: 'Actividades por Sector Económico'
                }
            })
            .state('app.configuration.parameters', {
                url: '/planning',
                templateUrl: "themes/wgroup/assets/modules/configuration/planning/configuration_general_parameter_list.htm",
                ncyBreadcrumb: {
                    label: 'Parametrización General'
                }
            })
            .state('app.configuration.arl', {
                url: '/arl',
                templateUrl: "themes/wgroup/assets/modules/configuration/arl/configuration_arl_list.htm",
                ncyBreadcrumb: {
                    label: 'Parametrización ARL'
                }
            })
            .state('app.configuration.diagnostic-disability', {
                url: '/diagnostic-disability',
                templateUrl: "themes/wgroup/assets/modules/configuration/diagnostic-disability/configuration_disability_diagnostic_list.htm",
                ncyBreadcrumb: {
                    label: 'Diagnostico Incapacidad'
                }
            })
            .state('app.configuration.project-task-type', {
                url: '/project-task-type',
                templateUrl: "themes/wgroup/assets/modules/configuration/project-task-type/configuration_project_task_type_list.htm",
                ncyBreadcrumb: {
                    label: 'Tipos Tarea Proyecto'
                }
            })
            .state('app.configuration.management-system', {
                url: '/management-system',
                templateUrl: "themes/wgroup/assets/modules/configuration/business-programs/configuration_program_prevention_tabs.htm",
                ncyBreadcrumb: {
                    label: 'Sistema de gestión'
                }
            })
            .state('app.configuration.business-programs', {
                url: '/business-programs',
                templateUrl: "themes/wgroup/assets/modules/configuration/business-programs/configuration_management_tabs.htm",
                ncyBreadcrumb: {
                    label: 'Programas empresariales'
                }
            })

            .state('app.program-prevention-document', {
                url: '/program-prevention-document',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment', 'angularMoment',
                    'touchspin-plugin', 'angularFileUpload', 'flow', 'base64', 'configController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Documento'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.program-prevention-document.list', {
                url: '/list',
                templateUrl: "themes/wgroup/assets/modules/configuration/guide/configuration_program_prevention_document_list.htm",
                ncyBreadcrumb: {
                    label: 'Lista'
                }
                /*,
                                 controller: 'customerCtrl'*/
            })
            .state('app.program-prevention-document.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateUrl: "themes/wgroup/assets/modules/configuration/guide/configuration_program_prevention_document_edit.htm",
                ncyBreadcrumb: {
                    label: 'Creación'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.program-prevention-document.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:id',
                templateUrl: "themes/wgroup/assets/modules/configuration/guide/configuration_program_prevention_document_edit.htm",
                ncyBreadcrumb: {
                    label: 'Edición'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.program-prevention-document.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateUrl: "themes/wgroup/assets/modules/configuration/guide/configuration_program_prevention_document_edit.htm",
                ncyBreadcrumb: {
                    label: 'Visualización'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })


            .state('app.configuration.minimum-standard', {
                url: '/minimum-standard',
                templateUrl: "themes/wgroup/assets/modules/configuration/minimum-standard/configuration_minimum_standard_tabs.htm",
                ncyBreadcrumb: {
                    label: 'Estándares Mínimos 1111'
                }
            })

            .state('app.configuration.minimum-standard-0312', {
                url: '/minimum-standard-0312',
                templateUrl: "themes/wgroup/assets/modules/configuration/minimum-standard-0312/configuration_minimum_standard_tabs.htm",
                ncyBreadcrumb: {
                    label: 'Estándares Mínimos 0312'
                }
            })

            .state('app.configuration.road-safety', {
                url: '/road-safety',
                templateUrl: "themes/wgroup/assets/modules/configuration/road-safety/configuration_road_safety_tabs.htm",
                ncyBreadcrumb: {
                    label: 'Seguridad Vial'
                }
            })
            .state('app.configuration.resource-library', {
                url: '/resource-library',
                templateUrl: "themes/wgroup/assets/modules/configuration/resource-library/resource_library_list.htm",
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
                resolve: loadSequence('moment', 'angularMoment', 'mwl.calendar', 'vAccordion', 'configController')
            })

            .state('app.configuration.prioritization-factor', {
                url: '/prioritization-factor',
                templateUrl: "themes/wgroup/assets/modules/configuration/prioritization-factor/_index.htm",
                title: 'Resultados Factor de Priorización',
                ncyBreadcrumb: {
                    label: 'Resultados Factor de Priorización'
                },
                resolve: loadSequence('moment', 'angularMoment', 'mwl.calendar', 'vAccordion', 'configController')
            })

            .state('app.configuration.help-roles-profiles', {
                url: '/help-roles-profiles',
                templateUrl: "themes/wgroup/assets/modules/configuration/help-roles-profiles/configuration_help_roles_profiles.htm",
                title: 'Ayudas Roles y Perfiles',
                ncyBreadcrumb: {
                    label: 'Ayudas Roles y Perfiles'
                }
            })

            .state('app.configuration.signature-certificate-vr', {
                url: '/signature-certificate-vr',
                templateUrl: "themes/wgroup/assets/modules/configuration/signature-certificate-vr/configuration_signature_certificate_vr.htm",
                title: 'Firma Certificado RV',
                resolve: loadSequence('angularFileUpload'),
                ncyBreadcrumb: {
                    label: 'Firma Certificado RV'
                }
            })

            .state('app.configuration.signature-indicator-vr', {
                url: '/signature-indicator-vr',
                templateUrl: "themes/wgroup/assets/modules/configuration/signature-indicator-vr/configuration_signature_indicator_vr.htm",
                title: 'Firma Informe Indicadores RV',
                resolve: loadSequence('angularFileUpload'),
                ncyBreadcrumb: {
                    label: 'Firma Informe Indicadores RV'
                }
            })            

            .state('app.configuration.template-manage', {
                url: '/template-manage',
                templateUrl: "themes/wgroup/assets/modules/configuration/template-manage/template_manage_list.htm",
                ncyBreadcrumb: {
                    label: 'Administración Plantillas'
                }
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END CONFIG ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START ASESORES ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            .state('app.asesores', {
                url: '/asesores',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angularFileUpload',
                    'agentController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Asesores'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.asesores.list', {
                url: '/list',
                templateUrl: "themes/wgroup/assets/modules/agents/agent_list.htm",
                ncyBreadcrumb: {
                    label: 'Lista'
                }
                /*,
                                 controller: 'customerCtrl'*/
            })
            .state('app.asesores.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateUrl: "themes/wgroup/assets/modules/agents/agent_edit_tab.htm",
                ncyBreadcrumb: {
                    label: 'Creación'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.asesores.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:agentId',
                templateUrl: "themes/wgroup/assets/modules/agents/agent_edit_tab.htm",
                ncyBreadcrumb: {
                    label: 'Edición'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.asesores.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:agentId',
                templateUrl: "themes/wgroup/assets/modules/agents/agent_edit_tab.htm",
                ncyBreadcrumb: {
                    label: 'Visualización'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END ASESORES ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START PLANER ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\


            .state('app.planer', {
                url: '/planer',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce', 'mwl.calendar',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic', 'moment', 'angularMoment',
                    'touchspin-plugin', 'angularFileUpload', 'flow', 'base64',
                    'agentController',
                    'planerCalendarController',
                    'projectController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Planeador'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.planer.calendar', {
                url: '/calendar',
                templateUrl: "themes/wgroup/assets/modules/planer/planer_calendar.htm",
                ncyBreadcrumb: {
                    label: 'Calendario'
                }
                /*,
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
                }
                /*,
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
                }
                /*,
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
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END PLANER ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START REPORT ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            .state('app.report', {
                url: '/reportes',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'monospaced.elastic',
                    'dual-list-box', 'touchspin-plugin', 'angularFileUpload',
                    'reportController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Reportes'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.report.list', {
                url: '/list',
                templateUrl: "themes/wgroup/assets/modules/report/report_list.htm",
                data: {
                    module: 'customer'
                },
                ncyBreadcrumb: {
                    label: 'Lista'
                }
                /*,
                                 controller: 'customerCtrl'*/
            })
            .state('app.report.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateUrl: "themes/wgroup/assets/modules/report/report_form.htm",
                ncyBreadcrumb: {
                    label: 'Creación'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.report.dynamically', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/dynamically',
                templateUrl: "themes/wgroup/assets/modules/report/dynamically/report_dynamically.htm",
                ncyBreadcrumb: {
                    label: 'Creación dinámica'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.report.generate', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/generate/:reportId',
                templateUrl: "themes/wgroup/assets/modules/report/generate/report_generate.htm",
                ncyBreadcrumb: {
                    label: 'Visualización'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.report.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:reportId',
                templateUrl: "themes/wgroup/assets/modules/report/report_form.htm",
                ncyBreadcrumb: {
                    label: 'Edición'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END REPORT ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\


            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ POSITIVA FGN ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            .state('app.positiva-fgn', {
                url: '/positiva-fgn',
                resolve: loadSequence('random-color', 'infinite-scroll',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select', 'ui.mask', 'moment', 'angularFileUpload',
                    'flow', 'base64', 'positivaFgnController')
            })
            .state('app.positiva-fgn.consultants-list', {
                url: '/consultants',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/consultant/consultant_list.htm"
            })
            .state('app.positiva-fgn.consultants-edit', {
                url: '/consultants/edit/:consultantId',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/consultant/consultant_edit.htm"
            })
            .state('app.positiva-fgn.campus-list', {
                url: '/campus',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/campus/campus_list.htm"
            })
            .state('app.positiva-fgn.vendor-list', {
                url: '/vendor',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/vendor/vendor_list.htm"
            })
            .state('app.positiva-fgn.vendor-edit', {
                url: '/vendor/edit/:vendorId',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/vendor/tabs/tabs.htm"
            })
            .state('app.positiva-fgn.gestpos', {
                url: '/gestpos',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/gestpos/tabs.htm"
            })
            .state('app.positiva-fgn.fgn', {
                url: '/fgn',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/fgn/config/config_list.htm"
            })
            .state('app.positiva-fgn.fgn-activity-list', {
                url: '/fgn/activity/:configId',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/fgn/activity/activity_list.htm"
            })
            .state('app.positiva-fgn.fgn-activity-edit', {
                url: '/fgn/activity/:configId/edit/:activityId',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/fgn/activity/activity_edit.htm"
            })
            .state('app.positiva-fgn.fgn-activity-config', {
                url: '/fgn/activity/:configId/config/:activityId',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/fgn/activity-config/activity_config_edit.htm"
            })
            .state('app.positiva-fgn.fgn-activity-config-sectional', {
                url: '/fgn/activity/:configId/config-sectional/:activityId',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/fgn/activity-config-sectional/activity_config_sectional_edit.htm"
            })
            .state('app.positiva-fgn.consultant-assignment', {
                url: '/consultant-assignment',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/consultant-assignment/consultant_assignment_list.htm"
            })
            .state('app.positiva-fgn.fgn-management-axis-programming', {
                url: '/management/axis/programming',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/management/management.htm"
            })
            .state('app.positiva-fgn.fgn-management-axis-execution', {
                url: '/management/axis/execution',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/management/management.htm"
            })
            .state('app.positiva-fgn.fgn-management-activity', {
                url: '/management/activity',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/management/management_list.htm"
            })
            .state('app.positiva-fgn.fgn-indicators', {
                url: '/fgn/indicators',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/indicators/indicators_list.htm"
            })
            .state('app.positiva-fgn.fgn-indicators-report', {
                url: '/fgn/indicators/report/:id',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/indicators/reports/reports_tabs.htm"
            })
            .state('app.positiva-fgn.config-sectionals', {
                url: '/sectionals',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/sectionals/sectional_list.htm"
            })
            .state('app.positiva-fgn.config-professional', {
                url: '/professional',
                templateUrl: "themes/wgroup/assets/modules/positiva-fgn/professional/professional_list.htm"
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ POSITIVA FGN ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\



            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START POLL ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\


            .state('app.poll', {
                url: '/encuestas',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select',
                    'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angularFileUpload',
                    'pollController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Asesores'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.poll.list', {
                url: '/list',
                templateUrl: "themes/wgroup/assets/modules/poll/poll_list.htm",
                ncyBreadcrumb: {
                    label: 'Lista'
                }
                /*,
                                 controller: 'customerCtrl'*/
            })
            .state('app.poll.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateUrl: "themes/wgroup/assets/modules/poll/poll_form.htm",
                ncyBreadcrumb: {
                    label: 'Creación'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.poll.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:id',
                templateUrl: "themes/wgroup/assets/modules/poll/poll_form.htm",
                ncyBreadcrumb: {
                    label: 'Edición'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.poll.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:id',
                templateUrl: "themes/wgroup/assets/modules/poll/poll_form.htm",
                ncyBreadcrumb: {
                    label: 'Visualización'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END POLL ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START QUOTE ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\


            .state('app.cotizaciones', {
                url: '/cotizaciones',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller',
                    'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angularFileUpload',
                    'quoteController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Asesores'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.cotizaciones.list', {
                url: '/list',
                templateUrl: "themes/wgroup/assets/modules/quote/quote_list.htm",
                ncyBreadcrumb: {
                    label: 'Lista'
                }
                /*,
                                 controller: 'customerCtrl'*/
            })
            .state('app.cotizaciones.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateUrl: "themes/wgroup/assets/modules/quote/quote_edit.htm",
                ncyBreadcrumb: {
                    label: 'Creación'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.cotizaciones.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:quoteId',
                templateUrl: "themes/wgroup/assets/modules/quote/quote_edit.htm",
                ncyBreadcrumb: {
                    label: 'Edición'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.cotizaciones.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:quoteId',
                templateUrl: "themes/wgroup/assets/modules/quote/quote_edit.htm",
                ncyBreadcrumb: {
                    label: 'Visualización'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END QUOTE ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START CERTIFICATE ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            .state('app.certificate', {
                url: '/certificados',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller',
                    'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angularFileUpload',
                    'certificateController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Certificados'
                }
                /*controller: 'customerCtrl'*/
            })
            .state('app.certificate.validate', {
                url: '/validate',
                templateUrl: "themes/wgroup/assets/modules/certificate/certificate_dashboard.htm",
                ncyBreadcrumb: {
                    label: 'Dashboard'
                }
            })
            .state('app.certificate.admin', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/admin',
                templateUrl: "themes/wgroup/assets/modules/certificate/certificateadmin/certificate_admin_tab.htm",
                ncyBreadcrumb: {
                    label: 'Administración'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.admin.program', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/program',
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Programa'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.admin.program.create', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/create',
                templateUrl: "themes/wgroup/assets/modules/certificate/certificateadminprogram/certificate_program_form_tabs.htm",
                ncyBreadcrumb: {
                    label: 'Creación'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.admin.program.edit', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/edit/:programId',
                templateUrl: "themes/wgroup/assets/modules/certificate/certificateadminprogram/certificate_program_form_tabs.htm",
                ncyBreadcrumb: {
                    label: 'Edición'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.admin.program.view', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/view/:programId',
                templateUrl: "themes/wgroup/assets/modules/certificate/certificateadminprogram/certificate_program_form_tabs.htm",
                ncyBreadcrumb: {
                    label: 'Visualización'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.management', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/management',
                templateUrl: "themes/wgroup/assets/modules/certificate/certificate_dashboardc.htm",
                ncyBreadcrumb: {
                    label: 'Gestión'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.report', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/search',
                templateUrl: "themes/wgroup/assets/modules/certificate/certificatereport/certificate_report_tab.htm",
                ncyBreadcrumb: {
                    label: 'Consulta'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })
            .state('app.certificate.logbook', {
                resolve: loadSequence('base64', 'json3', 'flow'),
                url: '/logbook',
                templateUrl: "themes/wgroup/assets/modules/certificate/certificatelogbook/certificate_logbook_tab.htm",
                ncyBreadcrumb: {
                    label: 'Consulta'
                }
                /*,
                                 controller: 'customerEditCtrl'*/
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END CERTIFICATE ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START LIBRARY ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\


            .state('app.resource', {
                url: '/resource-library',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller',
                    'ui.select', 'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angularFileUpload'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Biblioteca de Recursos'
                }
            })
            .state('app.resource.library', {
                resolve: loadSequence('base64', 'json3', 'flow', 'resourceLibraryController'),
                url: '/list',
                templateUrl: "themes/wgroup/assets/modules/resource-library/resource_library_tab.htm",
                ncyBreadcrumb: {
                    label: 'Administrar'
                }
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END LIBRARY ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START PROJECTS ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\


            .state('app.projects', {
                url: '/project',
                resolve: loadSequence('random-color', 'uiSwitch', 'infinite-scroll', 'pasvaz.bindonce', 'mwl.calendar',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select',
                    'ui.mask', 'monospaced.elastic', 'moment', 'angularMoment',
                    'touchspin-plugin', 'angularFileUpload', 'flow', 'base64',
                    'agentController',
                    'planerCalendarController',
                    'projectController'),
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
                templateUrl: "themes/wgroup/assets/modules/project/project_planning.htm",
                ncyBreadcrumb: {
                    label: 'Planeación'
                }
                /*,
                                 controller: 'customerCtrl'*/
            })
            .state('app.projects.list', {
                url: '/list',
                templateUrl: "themes/wgroup/assets/modules/project/project_list.htm",
                ncyBreadcrumb: {
                    label: 'Administración'
                }
            })
            .state('app.projects.budget', {
                url: '/budget',
                templateUrl: "themes/wgroup/assets/modules/project/project_budget.htm",
                ncyBreadcrumb: {
                    label: 'Presupuesto'
                }
            })
            .state('app.projects.billing', {
                url: '/billing',
                templateUrl: "themes/wgroup/assets/modules/project/project_billing.htm",
                ncyBreadcrumb: {
                    label: 'Facturacion'
                }
            })
            .state('app.projects.attachments', {
                url: '/attachments',
                templateUrl: "themes/wgroup/assets/modules/project/project_attachment_list.htm",
                ncyBreadcrumb: {
                    label: 'Anexos'
                }
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ END PROJECTS ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START INTERNAL PROJECTS ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\

            .state('app.internal-projects', {
                url: '/internal-project',
                resolve: loadSequence('random-color', 'uiSwitch', 'infinite-scroll', 'pasvaz.bindonce', 'mwl.calendar',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select',
                    'ui.mask', 'monospaced.elastic', 'moment', 'angularMoment',
                    'touchspin-plugin', 'angularFileUpload', 'flow', 'base64',
                    'agentController',
                    'planerCalendarController',
                    'internalProjectController'),
                template: '<div ui-view class="fade-in-right-big "></div>',
                ncyBreadcrumb: {
                    label: 'Proyecto Interno'
                }
            })
            .state('app.internal-projects.planning', {
                url: '/planning',
                templateUrl: "themes/wgroup/assets/modules/internalproject/project_planning.htm",
                ncyBreadcrumb: {
                    label: 'Planeación Proyecto Interno'
                }
            })
            .state('app.internal-projects.list', {
                url: '/list',
                templateUrl: "themes/wgroup/assets/modules/internalproject/project_list.htm",
                ncyBreadcrumb: {
                    label: 'Proyecto Interno'
                }
            })
            .state('app.internal-projects.attachment', {
                url: '/attachment-list',
                templateUrl: "themes/wgroup/assets/modules/internalproject/project_attachment_list.htm",
                ncyBreadcrumb: {
                    label: 'Anexos Proyecto Interno'
                }
            })

            //▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼ START INTERNAL PROJECTS ▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲▼▲\\





            // Rutas para el login
            .state('login', {
                url: '/login',
                template: '<div ui-view class="fade-in-right-big smooth"></div>',
                resolve: loadSequence('base64', 'modernizr', 'uiSwitch', 'perfect-scrollbar-plugin', 'knob-d3',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller',
                    'toaster', 'ngAside', 'vAccordion', 'chartjs', 'tc.chartjs', 'truncate', 'touchspin-plugin',
                    'listService', 'geoLocationService', 'chartService', 'ui.select',
                    'cp.ngConfirm', 'ngNotify', 'ui.knob', 'moment', 'momentwl', 'momentlocale', 'angularMoment',
                    'angular-notification-icons', 'htmlToPlaintext', 'base64', 'ui.swiper', 'userController'),
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
            .state('login.lockscreen', {
                url: '/lock',
                templateProvider: function ($templateCache) {
                    return $templateCache.get('login_lock_screen.htm');
                }
            })
            .state('app.user', {
                url: '/user',
                resolve: loadSequence('random-color', 'infinite-scroll', 'pasvaz.bindonce',
                    'jquery-datatable', 'datatables', 'datatables.bootstrap', 'datatables.scroller', 'ui.select',
                    'ui.mask', 'monospaced.elastic',
                    'touchspin-plugin', 'angularFileUpload'),
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
                }
            })

            .state('app.user.messages', {
                url: '/messages',
                templateUrl: "themes/wgroup/assets/modules/user/message/user_messages.html",
                resolve: loadSequence('truncate', 'htmlToPlaintext')
            }).state('app.user.messages.inbox', {
                url: '/inbox/:inboxID',
                templateUrl: "themes/wgroup/assets/modules/user/message/user_inbox.html",
                controller: 'ViewMessageCrtl'
            })


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
                    }
                ]
            };
        }
    }
]);
