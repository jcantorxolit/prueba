'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
              $rootScope, $timeout, $http, SweetAlert, ChartService, $aside, $filter, ListService, DashboardFilterService) {

        $scope.isAgent = $rootScope.isAgent();
        $scope.isAdmin = $rootScope.isAdmin();
        $scope.isCustomer = $rootScope.isCustomer();

        var customerId = $scope.isCustomer ? $rootScope.currentUser().company : null;

        $scope.mainCustomerId = $scope.isCustomer ? $rootScope.currentUser().company : null;

        $scope.filter = {
            customer: null,
            contractor: null,
        };

        $scope.chart = {
            doughnut: { options: null },
            bar: { options: null },
            line: { options: null },
            pie: { options: null },
            data: {
                minimalStandarProgressPie: null,
                minimalStandarComparativeLineChart: null,
                roadSafetyChartLine: null
            }
        };

        $scope.customerList = [];
        $scope.contractorList = [];
        $scope.customerInformation = null;
        $scope.isOpenGridDetailCustomerInformation = true;

        getList();
        getCharts();

        $scope.onSelectCustomer = function () {
            if ($scope.filter.customer) {
                $scope.filter.contractor = null;
                $scope.isOpenGridDetailCustomerInformation = true;

                DashboardFilterService.setFirstCustomer($scope.filter.customer);
                DashboardFilterService.setSecondCustomer(null);

                if (!$scope.isCustomer || ($scope.isCustomer && $scope.filter.customer.value == customerId)) {
                    $scope.$broadcast('onBroadcastChangeFilterCustomer');
                }

                getCustomerContractorListOnDemand();

            }
        };

        $scope.onSelectContractor = function () {
            DashboardFilterService.setSecondCustomer($scope.filter.contractor);
            $scope.$broadcast('onBroadcastChangeFilterCustomer');
        };

        $scope.onClearContractor = function () {
            $scope.filter.contractor = null;
            DashboardFilterService.setSecondCustomer(null);
            $scope.$broadcast('onBroadcastChangeFilterCustomer');
        }

        $scope.onShowDetailCustomerInfo = function () {
            $scope.isOpenGridDetailCustomerInformation = !$scope.isOpenGridDetailCustomerInformation;
            $scope.dtInstanceCustomerRelationshipGrid.reloadData();
        };


        $scope.dtInstanceCustomerRelationshipGridCallback = function (instance) {
            $scope.dtInstanceCustomerRelationshipGrid = instance;
        };

        $scope.dtOptionsCustomerRelationshipGrid = DTOptionsBuilder.newOptions()
            .withBootstrap()
            .withOption('responsive', true)
            .withOption('ajax', {
                data: function (d) {
                    d.parentId = $scope.filter.customer.value;
                    return JSON.stringify(d);
                },
                url: 'api/customer-contractor/customer-relationships',
                type: 'POST',
            })
            .withDataProp('data')
            .withOption('order', [
                [0, 'desc']
            ])
            .withOption('serverSide', true)
            .withOption('processing', true)
            .withOption('fnPreDrawCallback', function () {
                return $scope.isOpenGridDetailCustomerInformation == false;
            })
            .withPaginationType('full_numbers')
            .withOption('createdRow', function (row) {
                $compile(angular.element(row).contents())($scope);
            });


        $scope.dtColumnsCustomerRelationshipGrid = [
            DTColumnBuilder.newColumn('documentNumber').withTitle("Nro Documento"),
            DTColumnBuilder.newColumn('businessName').withTitle("Razón Social"),
            DTColumnBuilder.newColumn('relationship').withTitle("Relación"),
            DTColumnBuilder.newColumn('status').withTitle("Estado"),
        ];


        function getList() {
            var $criteria = {
                customerId: customerId,
                isAdmin: $scope.isAdmin,
                isAgent: $scope.isAgent,
                isCustomer: $scope.isCustomer,
                userId: $rootScope.currentUser().id
            }

            var entities = [
                {name: 'customer_employeer', value: null, criteria: $criteria},
            ]

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.customerList = response.data.data.customerEmployeerList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        function getCharts() {
            var entities = [
                {name: 'chart_line_options', criteria: null},
                {name: 'chart_pie_options', criteria: null},
                // {name: 'dashboard_commercial_summary', criteria: null},
            ];

            ChartService.getDataChart(entities)
                .then(function (response) {

                    // Graphics Bar Settings
                    $scope.chart.pie.options = response.data.data.chartPieOptions;
                    $scope.chart.pie.options.legend.position = 'bottom';

                    $scope.chart.line.options = response.data.data.chartLineOptions;
                    $scope.chart.line.options.legend.position = 'bottom';


                    // set data
                    // $scope.chart.data.amountLicensesByYearsHistorical = response.data.data.amountLicensesByYearsHistorical;

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getCustomerContractorListOnDemand() {
            var $criteria = {
                customerId: customerId,
                parentId: $scope.filter.customer.value,
                isAdmin: $scope.isAdmin,
                isAgent: $scope.isAgent,
                isCustomer: $scope.isCustomer
            }

            var entities = [
                {name: 'customer_contractor', value: null, criteria: $criteria},
                {name: 'customer_information_relationships', value: null, criteria: $criteria},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.contractorList = response.data.data.customerContractorList;
                    $scope.contractorList = response.data.data.customerContractorList;
                    $scope.customerInformation = response.data.data.customerInformationRelationships;

                    if ($scope.isCustomer && $scope.filter.customer.value != customerId) {
                        $scope.filter.contractor = $scope.contractorList.length > 0 ? $scope.contractorList[0] : null;
                        DashboardFilterService.setSecondCustomer($scope.filter.contractor);
                        $scope.$broadcast('onBroadcastChangeFilterCustomer');
                    }
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

    });
