'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerGeneralSstCtrl',
    function ($scope, $stateParams, $state, $rootScope, ChartService, ListService, DashboardFilterService, $timeout) {

        $scope.currentCustomer = null;

        $scope.init = function () {
            $scope.currentCycle = null;

            $scope.periods = [];
            $scope.comparePeriods = [];

            $scope.entity = {
                typeIndicator: $scope.typeIndicators[0],
                period: null,
                comparePeriod: null
            }

            $scope.chart = {
                doughnut: { options: null },
                bar: { options: null },
                line: { options: null },
                pie: { options: null },
                rates: {
                    c: null,
                    cp: null,
                    nc: null,
                    na: null,
                },
                data: {
                    progressAll: [],
                    progress: {
                        total_percent: null,
                        cumple_percent: 0,
                        parcial_percent: 0,
                        nocumple_percent: 0,
                        noaplica_percent: 0,
                        nocontesta_percent: 0
                    },
                    minimalStandarComparativeChartLine: null
                }
            };
        };


        $scope.typeIndicators = [
            { value: 0, item: 'Avance Evaluación' },
            { value: 1, item: 'Comparativo' }
        ];

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
            subText: {
                enabled: true,
                text: "Total",
            }
        };

        $scope.optionsTotal = {
            readOnly: true,
            displayPrevious: true,
            barCap: 25,
            trackWidth: 20,
            barWidth: 20,
            trackColor: 'rgba(92,184,92,.1)',
            barColor: '#5BC01E',
            textColor: '#000',
            step: 0.01,
            unit: '%',
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
            getList();
        };

        $scope.onRefresh = function () {
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
            }
        }


        function getList() {
            var entities = [
                { name: 'customer_sst_periods', criteria: { customerId: $scope.currentCustomer.id } }
            ];

            ListService.getDataList(entities)
                .then(function (response) {
                    if ($scope.entity.typeIndicator.value == 0) {
                        $scope.periods = response.data.data.customerSstPeriods;
                    } else {
                        $scope.periods = response.data.data.customerSstPeriodsCompare;
                    }

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
                { name: 'chart_line_options', criteria: null },
            ];

            if ($scope.entity.typeIndicator.value == 0) {
                entities.push({ name: 'dashboard_sst_progress', criteria: $criteria });
            } else {
                entities.push({ name: 'dashboard_sst_compare', criteria: $criteria });
            }

            ChartService.getDataChart(entities)
                .then(function (response) {
                    $scope.currentCycle = null;
                    $scope.chart.line.options = response.data.data.chartLineOptions;

                    $scope.chart.rates.c = getRate(response.data.data.rates, 'c');
                    $scope.chart.rates.cp = getRate(response.data.data.rates, 'cp');
                    $scope.chart.rates.nc = getRate(response.data.data.rates, 'nc');
                    $scope.chart.rates.na = getRate(response.data.data.rates, 'na');

                    if (response.data.data.customerDiagnosticProgress) {
                        $scope.chart.data.progressAll = response.data.data.customerDiagnosticProgress;
                        $scope.chart.data.progress = response.data.data.customerDiagnosticProgress[0];
                        var $total_percent = parseFloat($scope.chart.data.progress.total_percent);
                        $scope.chart.data.progress.total_percent = 0;
                        $scope.chart.data.progressAll.map(function (item) {
                            var options = angular.copy($scope.optionsCycles);
                            options.subText.text = item.name;
                            item.options = options;
                            return item;
                        });
                        $timeout(function () {
                            $scope.chart.data.progress.total_percent = $total_percent
                        }, 100);
                    }

                    if (response.data.data.customerDiagnosticProgressCompareChartLine) {
                        $scope.chart.data.minimalStandarComparativeChartLine = response.data.data.customerDiagnosticProgressCompareChartLine;
                    }

                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getRate(rates, code) {
            var $rate = rates.filter(function (rate) {
                return rate.value == code;
            });

            return $rate.length > 0 ? $rate[0] : null;
        }


    });
