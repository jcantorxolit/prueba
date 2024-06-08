'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerGeneralHazardMatrixCtrl',
    function ($scope, $stateParams, $state, $rootScope, ChartService, ListService, DashboardFilterService) {

        $scope.currentCustomer = null;

        $scope.typeIndicators = [
            {value: 0, item: 'Peligros'},
            {value: 1, item: 'Aceptabilidad del riesgo'}
        ];

        $scope.init = function () {
            $scope.workplaceList = [];
            $scope.totalHazard = 0;

            $scope.entity = {
                typeIndicator: $scope.typeIndicators[0],
                workplace: null,
            }

            $scope.chart = {
                bar: {options: null},
                data: {
                    roadSafetyChartBar: null
                }
            };
        };

        load();

        $scope.$on('onBroadcastChangeFilterCustomer', function () {
            load();
        });

        $scope.onSelecttypeIndicator = function () {
            getCharts();
        };

        $scope.onSelectworkPlace = function () {
            getCharts();
        };

        $scope.onClearWorkplace = function () {
            $scope.entity.workplace = null;
            getCharts();
        };

        function load() {
            $scope.init();
            $scope.currentCustomer = DashboardFilterService.getCurrentCustomer();

            if ($scope.currentCustomer) {
                getList();
                getCharts();
            }
        }

        function getList() {
            var entities = [
                {name: 'customer_config_acitivty_hazard_workplace_list', criteria: {
                    customerId: $scope.currentCustomer.id
                }},
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.workplaceList = response.data.data.customerConfigAcitivtyHazardWorkplaceList;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        function getCharts() {
            var $criteria = {
                customerId: $scope.currentCustomer.id,
                workplace: $scope.entity.workplace,
            };

            var entities = [
                {name: 'chart_bar_options', criteria: null},
            ];

            if ($scope.entity.typeIndicator.value == 0) {
                entities.push({name: 'dashboard_hazard_matrix', criteria: $criteria});
            } else {
                entities.push({name: 'dashboard_hazard_matrix_acceptability', criteria: $criteria});
            }

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.bar.options.legend.display = false;
                    $scope.chart.bar.options.scales = {
                        xAxes: [{
                            ticks: {
                                autoSkip: false
                            }
                        }],
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }

                    $scope.totalHazard = response.data.data.dashboardHazardMatrixAmountRecords;

                    if (response.data.data.dashboardHazardMatrixChartBar) {
                        $scope.chart.data.roadSafetyChartBar = response.data.data.dashboardHazardMatrixChartBar;
                    }

                    if (response.data.data.dashboardHazardMatrixAcceptabilityChartBar) {
                        $scope.chart.data.roadSafetyChartBar = response.data.data.dashboardHazardMatrixAcceptabilityChartBar;
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


    });
