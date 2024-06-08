'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerGeneralMinimalStandarsCtrl',
    function ($scope, $stateParams, $log, $state,
              $rootScope, $timeout, $http, SweetAlert, ChartService, $aside, $filter, ListService, DashboardFilterService) {

        $scope.currentCustomer = null;

        $scope.typeIndicators = [
            {value: 0, item: 'Avance Evaluación'},
            {value: 1, item: 'Comparativo'}
        ];

        $scope.init = function () {
            $scope.currentCycle = null;
            $scope.periods = [];

            $scope.entity = {
                typeIndicator: $scope.typeIndicators[0],
                period: null,
                comparePeriod: null
            }

            $scope.chart = {
                bar: {options: null},
                line: {options: null},
                data: {
                    all: [],
                    general: {
                        total: 0,
                        accomplish_percent_total: 0,
                        no_accomplish_percent_total: 0,
                        no_apply_with_justification_percent_total: 0,
                        no_checked_percent_total: 0,
                    },
                    minimalStandarComparativeChartLine: null,
                }
            };
        };

        $scope.options = {
            readOnly: true,
            displayPrevious: true,
            barCap: 25,
            trackWidth: 20,
            barWidth: 20,
            trackColor: 'rgba(92,184,92,.1)',
            barColor: '#5BC01E',
            textColor: '#000',
            unit: '%',
            step: 0.01,
            subText: {
                enabled: true,
                text: "Total",
            }
        };

        $scope.optionsCycles = {
            readOnly: true,
            displayPrevious: true,
            barCap: 25,
            trackWidth: 10,
            barWidth: 10,
            trackColor: 'rgba(92,184,92,.1)',
            barColor: '#5BC01E',
            textColor: '#000',
            size: 120,
            unit: '%',
            subText: {
                enabled: true,
                text: "",
            }
        };

        load();

        $scope.$on('onBroadcastChangeFilterCustomer', function () {
            load();
        });

        $scope.onSelecttypeIndicator = function () {
            getCharts();
        };

        $scope.onSelectperiod = function () {
            getCharts();
        };

        $scope.onSelectComparePeriod = function () {
            getCharts();
        };

        $scope.onClearComparePeriod = function () {
            $scope.entity.comparePeriod = null;
            getCharts();
        };

        $scope.onCycle = function (cycle) {
            if ($scope.currentCycle && $scope.currentCycle.name == cycle.name) {
                $scope.currentCycle = null;
                return
            }

            $scope.currentCycle = cycle;
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
                {name: 'customer_standard_minimal_periods', criteria: {customerId: $scope.currentCustomer.id}}
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    $scope.periods = response.data.data.customerStandardMinimalPeriods;

                    if ($scope.periods.length) {
                        $scope.entity.period = $scope.periods[0];
                        getCharts();
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


        function getCharts() {
            if (!$scope.entity.period) {
                return;
            }

            var $criteria = {
                customerId: $scope.currentCustomer.id,
                period: $scope.entity.period.value,
                comparePeriod: $scope.entity.comparePeriod ? $scope.entity.comparePeriod.value : null,
            };


            var entities = [
                {name: 'chart_bar_options', criteria: null},
            ];

            if ($scope.entity.typeIndicator.value == 0) {
                entities.push({name: 'dashboard_minimal_standard_progress', criteria: $criteria});
            } else {
                entities.push({name: 'dashboard_minimal_standard_compare', criteria: $criteria});
            }

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.currentCycle = null;
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.bar.options.legend.display = false;

                    if (response.data.data.dashboardMinimalStandardProgress) {
                        var $stats = response.data.data.dashboardStats;
                        $scope.chart.data.all = response.data.data.dashboardMinimalStandardProgress;
                        $scope.chart.data.general = $scope.chart.data.all.length ? $scope.chart.data.all[0] : {
                            total: 0,
                            accomplish_percent_total: 0,
                            no_accomplish_percent_total: 0,
                            no_apply_with_justification_percent_total: 0,
                            no_checked_percent_total: 0,
                        };

                        $scope.chart.data.general.total = $stats ? parseFloat($stats.total) : $scope.chart.data.general.total;

                        $scope.chart.data.all.map(function (item) {
                            var options = angular.copy($scope.optionsCycles);
                            options.subText.text = item.name;
                            item.options = options;
                            return item;
                        });
                    }

                    if (response.data.data.dashboardMinimalStandardCompareChartLine) {
                        $scope.chart.data.minimalStandarComparativeChartLine = response.data.data.dashboardMinimalStandardCompareChartLine;
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }


    });
