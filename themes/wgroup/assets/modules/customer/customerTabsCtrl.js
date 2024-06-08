'use strict';
/**
 * controller for Customers
 */
app.controller('customerTabsCtrl', ['$scope', '$stateParams', '$log', '$compile', '$rootScope',
    '$timeout', '$state', '$filter', 'flowFactory', '$http', 'SupportService', '$analytics', 'ListService', 'SweetAlert',
    function ($scope, $stateParams, $log, $compile, $rootScope, $timeout,
        $state, $filter, flowFactory, $http, SupportService, $analytics, ListService, SweetAlert) {

        $scope.active = 0;
        $scope.profileTabActive = 0;

        $scope.structureOrganizational = false;
        $scope.IsLicenseActive = false;

        $scope.contractorClassification = $rootScope.parameters("wg_contractor_classification_dashboard");

        $scope.contractorClassification = $filter('filter')($scope.contractorClassification, { code: 'contractor' });

        $scope.showContractor = false;

        var gotoSupport = function () {
            switch (SupportService.getCurrentStep()) {

                case 1:
                    $scope.active = 0;
                    $scope.profileTabActive = 0;
                    $scope.profileTab = 'parameter'
                    break;

                case 2:
                    $scope.active = 4;
                    break;

                case 3:
                case 4:
                    $scope.active = 0;
                    $scope.profileTabActive = 9;
                    $scope.profileTab = 'parameter';
                    break;

                default:
                    break;
            }
        }

        var onDestroyHasEconomicGroup$ = $rootScope.$on('hasEconomicGroup', function (event, args) {
            $scope.hasEconomicGroup = args.newValue;
        });

        var onDestroyClassification$ = $rootScope.$on('hasClassification', function (event, args) {
            $scope.showContractor = $scope.contractorClassification.length
                ? $scope.contractorClassification.filter(function (item) {
                    return item.value == args.newValue
                }).length > 0
                : args.newValue == 'Contratante';
        });

        var onDestroySupport$ = $rootScope.$on('navigateToSupport', function (event, args) {
            gotoSupport();
        });

        $scope.$on("$destroy", function () {
            onDestroyHasEconomicGroup$();
            onDestroyClassification$();
            onDestroySupport$();
        });

        if (SupportService.getShouldRedirect()) {
            gotoSupport()
        }

        var log = $log;
        var request = {};

        //Variables globales en el tab
        if ($state.is("app.clientes.view")) {
            $scope.customer_title_tab = "view";
        } else if ($state.is("app.clientes.create")) {
            $scope.customer_title_tab = "create";
        } else {
            $scope.customer_title_tab = "edit";
        }

        $scope.isCreate = $state.is("app.clientes.create");
        $scope.canMatrixShowGTC45 = false;

        $scope.views = [
            { name: 'profile', url: $rootScope.app.views.urlRoot + 'modules/customer/profile/customer_profile_tabs.htm' },
            { name: 'sgsst', url: $rootScope.app.views.urlRoot + 'modules/customer/sgsst/customer_profile_config_sgsst_tabs.htm' },
            { name: 'employee', url: $rootScope.app.views.urlRoot + 'modules/customer/employee/customer_employee.htm' },
            { name: 'tracking', url: $rootScope.app.views.urlRoot + 'modules/customer/tracking/customer_tracking.htm' },
            { name: 'guide', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic-guide/customer_diagnostic_prevention_document.htm' },
            { name: 'diagnostic', url: $rootScope.app.views.urlRoot + 'modules/customer/diagnostic/customer_diagnostic_tabs.htm' },
            { name: 'management', url: $rootScope.app.views.urlRoot + 'modules/customer/management/customer_management_tabs.htm' },
            { name: 'occupational', url: $rootScope.app.views.urlRoot + 'modules/customer/occupational/customer_occupational_medicine_tabs.htm' },
            { name: 'medicine', url: $rootScope.app.views.urlRoot + 'modules/customer/medicine/customer_work_medicine_tabs.htm' },
            { name: 'attachments', url: $rootScope.app.views.urlRoot + 'modules/customer/attachments/customer_attachment.htm' },
            { name: 'poll', url: $rootScope.app.views.urlRoot + 'modules/customer/poll/customer_poll.htm' },
            { name: 'safety', url: $rootScope.app.views.urlRoot + 'modules/customer/safety/customer_safety_inspection_tabs.htm' },
            { name: 'audit', url: $rootScope.app.views.urlRoot + 'modules/customer/log/customer_audit.htm' },
            { name: 'contract', url: $rootScope.app.views.urlRoot + 'modules/customer/contract/customer_contract.htm' },
            { name: 'action-plan', url: $rootScope.app.views.urlRoot + 'modules/customer/action-plan/customer_improvement_plan.htm' },
            { name: 'certificate', url: $rootScope.app.views.urlRoot + 'modules/customer/certificate/certificate_admin_tab.htm' },
            { name: 'matrix', url: $rootScope.app.views.urlRoot + 'modules/customer/matrix/customer_matrix.htm' },
            { name: 'unsafe', url: $rootScope.app.views.urlRoot + 'modules/customer/unsafe/customer_unsafe_act.htm' },
            { name: 'vr_employee', url: $rootScope.app.views.urlRoot + 'modules/customer/vr-employee/customer_vr_employee_tabs.htm' },
            { name: 'reports_cyc', url: $rootScope.app.views.urlRoot + 'modules/customer/reports-cyc/customer_reports_cyc_tabs.htm' },
            { name: 'job_conditions', url: $rootScope.app.views.urlRoot + 'modules/customer/job-conditions/job_condition_tabs.htm' }
        ];

        $scope.section = $scope.views[0];

        var $customerId = $rootScope.currentUser().company;

        $scope.isAgent = $rootScope.currentUser().wg_type == "agent";
        $scope.isAdmin = $rootScope.currentUser().wg_type == "system";
        $scope.isCustomer = $rootScope.currentUser().wg_type == "customerAdmin" || $rootScope.currentUser().wg_type == "customerUser";
        $scope.isCustomerContractor = false;

        $rootScope.canEditRoot = $rootScope.currentUser().wg_type == "customerAdmin" || (!$state.is("app.clientes.view") && ($scope.isAgent || $scope.isAdmin));;

        $scope.customer = {};
        $scope.customer.id = $scope.iscreate ? 0 : $stateParams.customerId;
        $scope.flowConfig = { target: '/api/upload', singleFile: true };
        $scope.uploader = new Flow();
        $scope.noImage = true;

        $scope.tabsloaded = ["profile"];
        $scope.tabname = "profile";
        $scope.titletab = $scope.customer_title_tab;
        $scope.profileTab = "basic";

        $scope.onLoadRecord = function () {

            var onDestroyDataCustomer$ = $rootScope.$on('dataCustomer', function (event, args) {
                $scope.customer = args.newValue;
            });

            var onDestroyCustomerMatrixUpdated$ = $rootScope.$on('customerMatrixUpdated', function (event, args) {
                $scope.canMatrixShowGTC45 = args.newValue == 'G' || args.newValue == "S";
            });

            $scope.$on("$destroy", function () {
                onDestroyDataCustomer$();
                onDestroyCustomerMatrixUpdated$();
            });

            if ($scope.customer.id) {

                // se debe cargar primero la información actual del cliente..
                log.info("editando cliente con código: " + $scope.customer.id);
                var req = {
                    id: $scope.customer.id
                };
                $http({
                    method: 'GET',
                    url: 'api/customer',
                    params: req
                })
                    .catch(function (e, code) {
                        if (code == 403) {
                            var messagered = e.message !== null && e.message !== undefined ? e.message : 'app.clientes.list';
                            // forbbiden
                            // mostramos alerta indincando que no esta authorizado para ver esa cebolla y enviamos al home en 5 segundos
                            SweetAlert.swal("No Autorizado", "No estas autorizado para ver esta información.", "error");
                            $timeout(function () {
                                $state.go(messagered);
                            }, 3000);
                        } else if (code == 404) {
                            SweetAlert.swal("Información no disponible", "Cliente no encontrado", "error");
                            $timeout(function () {
                                $state.go('app.clientes.list');
                            });
                        } else {
                            SweetAlert.swal("Error", "Se ha presentado un error al intentar acceder a la información del cliente", "error");
                        }
                    })
                    .then(function (response) {

                        console.log(response);

                        $timeout(function () {
                            $scope.customer = response.data.result;

                            $scope.canMatrixShowGTC45 = $scope.customer.matrixType == 'G' || $scope.customer.matrixType == "S";

                            if ($scope.isCustomer) {

                                if ($customerId != $stateParams.customerId) {

                                    if ($customerId) {

                                        var req = {
                                            id: $customerId
                                        };
                                        $http({
                                            method: 'GET',
                                            url: 'api/customer',
                                            params: req
                                        })
                                            .catch(function (e, code) {
                                                if (code == 403) {
                                                    $timeout(function () {
                                                        //$state.go(messagered);
                                                    }, 3000);
                                                } else if (code == 404) {
                                                    $timeout(function () {
                                                        $state.go('app.clientes.list');
                                                    });
                                                }
                                            })
                                            .then(function (response) {

                                                $timeout(function () {
                                                    $scope.customerContract = response.data.result;
                                                    if ($scope.customerContract.classification.value == "Contratante" || $scope.customerContract.classification.value == "Empresa") {
                                                        // $state.go("app.clientes.view", {"customerId":$customerId});
                                                        $scope.isCustomerContractor = true;
                                                    } else {

                                                    }
                                                });

                                            }).finally(function () {
                                                $timeout(function () {
                                                    //redirectTo($rootScope.currentUser().company);
                                                }, 400);
                                            });


                                    } else {
                                        //redirectTo($rootScope.currentUser().company);
                                    }
                                }

                            }

                            if ($scope.customer.logo != null && $scope.customer.logo.path != null) {
                                $scope.noImage = false;
                            } else {
                                $scope.noImage = true;
                            }

                            $scope.loading = false;
                        });

                    }).finally(function () { });


            } else {
                //Se creara nuevo cliente
                log.info("creacion de nuevo cliente");
                $scope.loading = false;
            }
        }

        $scope.onLoadRecord();

        $scope.getView = function (viewName) {
            var views = $filter('filter')($scope.views, { name: viewName });
            return views[0];
        };

        $scope.switchTab = function (tab, titletab, Module) {
            $timeout(function () {
                $scope.tabname = tab;
                $scope.titletab = titletab;
                $scope.tabsloaded.push(tab);
                $scope.section = $scope.getView(tab);

                if ($rootScope.app.instance == "bolivar") {
                    console.log("trackea bolivar events")
                    $analytics.eventTrack('Clic módulo', { category: 'Clientes', label: Module, action: 'Abre Pestaña' });
                }
            });
        };

        $scope.switchSubTab = function (subtab) {
            $timeout(function () {
                $scope.subtab = subtab;
            });
        };

        function getList() {
            var entities = [
                { name: 'customer_parameter', criteria: { customerId: $stateParams.customerId, group: 'employeesOrganizationalStructure' } }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    if (response.data.data.employeesOrganizationalStructure) {
                        $scope.structureOrganizational = response.data.data.employeesOrganizationalStructure.value == "1" || false;
                    }
                }, function (error) {
                    $scope.status = "Unable to load customer data: " + error.message;
                });
        }
        getList();

    }
]);
