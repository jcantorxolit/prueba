'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerGeneralRoadSafetyCtrl', function ($scope, $rootScope, ChartService, ListService, DashboardFilterService) {

    $scope.currentCustomer = null;

    $scope.init = function () {
        $scope.percent = 0;

        $scope.allPeriods = [];
        $scope.periods = [];
        $scope.months = [];
        $scope.compareMonths = [];

        $scope.entity = {
            period: null, month: null, comparePeriod: null, compareMonth: null,
        }

        $scope.chart = {
            bar: {options: null}, data: {
                roadSafetyChartBar: null
            }
        };
    };

    load();

    $scope.$on('onBroadcastChangeFilterCustomer', function () {
        load();
    });

    $scope.onRefresh = function () {
        getCharts();
    };

    $scope.onChangePeriod = function () {
        loadMonths(true);
        $scope.entity.month = null;
    }

    $scope.onChangeComparePeriod = function () {
        loadMonths(false);
        $scope.entity.compareMonth = null;
    }

    function load() {
        $scope.init();
        $scope.currentCustomer = DashboardFilterService.getCurrentCustomer();

        if ($scope.currentCustomer) {
            getList();
            getCharts();
        }
    }


    function extractUniquePeriods() {
        $scope.allPeriods.forEach(function (period) {
            var exists = $scope.periods.some(function (year) {
                return year.value == period.year;
            });

            if (!exists) {
                $scope.periods.push({value: period.year, item: period.year});
            }
        });
    }

    function getList() {
        var entities = [{
            name: 'customer_road_safety_periods', criteria: {
                customerId: $scope.currentCustomer.id
            }
        },];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.allPeriods = response.data.data.customerRoadSafetyPeriods;

                extractUniquePeriods();

                if ($scope.periods.length) {

                    if ($scope.periods[0]) {
                        $scope.entity.period = $scope.periods[0];

                        if ($scope.entity.period) {
                            loadMonths(true);
                            $scope.entity.month = $scope.months[$scope.months.length - 1];
                        }
                    }

                    if ($scope.periods[1]) {
                        $scope.entity.comparePeriod = $scope.periods[1];

                        if ($scope.entity.comparePeriod) {
                            loadMonths(false);
                            $scope.entity.compareMonth = $scope.compareMonths[$scope.compareMonths.length - 1];
                        }
                    }
                }

                getCharts();

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function getCharts() {
        if (!$scope.entity.period || !$scope.entity.month || !$scope.entity.comparePeriod || !$scope.entity.compareMonth) {
            return;
        }

        var $criteria = {
            customerId: $scope.currentCustomer.id,
            period: $scope.entity.period.value,
            month: $scope.entity.month.value,
            comparePeriod: $scope.entity.comparePeriod.value,
            compareMonth: $scope.entity.compareMonth.value,
        };

        var entities = [
            {name: 'chart_bar_options', criteria: null},
            {name: 'dashboard_road_safety', criteria: $criteria}
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.bar.options = response.data.data.chartBarOptions;
                $scope.chart.bar.options.legend.position = "top";
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

                $scope.chart.data.roadSafetyChartBar = response.data.data.dashboardRoadSafetyChartBar;
                $scope.percent = response.data.data.customerRoadSafetyAverage;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    function loadMonths(months) {
        if (months) {
            $scope.months = [];
            var periods = $scope.allPeriods.filter(function (period) {
                return period.year == $scope.entity.period.value;
            });

            $scope.months = periods.map(function (period) {
                return {name: period.monthName, value: period.month}
            })
        } else {
            $scope.compareMonths = [];
            var periods = $scope.allPeriods.filter(function (period) {
                return period.year == $scope.entity.comparePeriod.value;
            });

            $scope.compareMonths = periods.map(function (period) {
                return {name: period.monthName, value: period.month}
            })
        }
    }

});
