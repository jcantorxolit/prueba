'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerUnsafeConditionsCtrl', function ($scope, $rootScope, ChartService, ListService, DashboardFilterService) {

    $scope.currentCustomer = null;

    $scope.typeIndicators = [
        { value: 0, item: 'Peligros' },
        { value: 1, item: 'Estado' }
    ];

    $scope.init = function () {
        $scope.amount = 0;
        $scope.periods = [];
        $scope.workplaceList = [];

        $scope.entity = {
            typeIndicator: $scope.typeIndicators[0],
            workplace: null,
            period: null
        }

        $scope.chart = {
            bar: { options: null },
            data: {
                customerUnsafeActHazard: {},
                customerUnsafeActStatus: {},
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

    $scope.onRefreshWorkplace = function () {
        getWorkplaceList(true);
    };

    $scope.onClearWorkplace = function () {
        $scope.entity.workplace = null;
        $scope.onRefresh();
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
            {
                name: 'customer_unsafe_act_years', criteria: {
                    customerId: $scope.currentCustomer.id
                }
            }
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.periods = response.data.data.customerUnsafeActYears;
                $scope.workplaceList = response.data.data.customerUnsafeActWorkplace;

                if ($scope.periods.length > 0) {
                    $scope.entity.period = $scope.periods[0];
                    getWorkplaceList(false);
                    $scope.onRefresh();
                }

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

    function getWorkplaceList(refreshChart) {
        var entities = [
            {
                name: 'customer_unsafe_act_workplace', criteria: {
                    customerId: $scope.currentCustomer.id,
                    year: $scope.entity.period ? $scope.entity.period.value : -1,
                }
            }
        ];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.workplaceList = response.data.data.customerUnsafeActWorkplace;
                var previousWorkplace = $scope.entity.workplace;
                $scope.entity.workplace = null;

                if (previousWorkplace) {
                    var workplace = $scope.workplaceList.find(function(workplace) {
                        return workplace.value == previousWorkplace.value;
                    });

                    $scope.entity.workplace = workplace;
                }

                if (refreshChart)
                    getCharts();
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function getCharts() {
        var $criteria = {
            customerId: $scope.currentCustomer.id,
            year: $scope.entity.period.value,
            workplace: $scope.entity.workplace ? $scope.entity.workplace.value : null,
            month: null
        };

        var entities = [
            { name: 'chart_bar_options', criteria: null },
        ];

        if ($scope.entity.typeIndicator.value == 0) {
            entities.push({ name: 'dashboard_unsafe_act_hazard', criteria: $criteria });
        } else {
            entities.push({ name: 'dashboard_unsafe_act_status', criteria: $criteria });
        }

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.bar.options = response.data.data.chartBarOptions;
                $scope.chart.bar.options.legend.display = false;

                if (response.data.data.customerUnsafeActHazard) {
                    $scope.chart.data.customerUnsafeActHazard = response.data.data.customerUnsafeActHazard;
                }

                if (response.data.data.customerUnsafeActStatus) {
                    $scope.chart.data.customerUnsafeActStatus = response.data.data.customerUnsafeActStatus;
                }

                $scope.amount = response.data.data.customerCountUnsafeActHazard;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
