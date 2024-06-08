'use strict';
/**
 * controller for Customers
 */
app.controller('dashboardDiagnosticCustomerBusinessProgramCtrl', function ($scope, $rootScope, $filter, ChartService, ListService, DashboardFilterService) {

    $scope.currentCustomer = null;

    $scope.init = function () {
        $scope.periods = [];
        $scope.workplaceList = [];
        $scope.allFilters = [];

        $scope.entity = {
            workplace: null,
            period: null
        }

        $scope.chart = {
            bar: { options: null },
            data: {
                programs: null,
            }
        };
    };

    $scope.options = {
        readOnly: true,
        displayPrevious: true,
        barCap: 5,
        trackWidth: 10,
        barWidth: 5,
        trackColor: 'rgba(92,184,92,.1)',
        barColor: '#5BC01E',
        textColor: '#000',
        size: 150,
        unit: '%',
        subText: {
            enabled: true,
            text: ""
        }
    };

    load();

    $scope.$on('onBroadcastChangeFilterCustomer', function () {
        load();
    });

    $scope.onRefresh = function () {
        getCharts();
    };

    $scope.onChangeWorkplace = function () {
        $scope.entity.period = null;
        loadPeriods();
    }

    $scope.getProgramValue = function (rate, program) {
        var result = 0;
        switch (rate.code) {
            case 'c':
                result = program.cumple
                break;

            case 'cp':
                result = program.parcial
                break;

            case 'nc':
                result = program.nocumple
                break;

            case 'na':
                result = program.noaplica
                break;

            default:
                break;
        }
        return result;
    }

    function load() {
        $scope.init();
        $scope.currentCustomer = DashboardFilterService.getCurrentCustomer();

        if ($scope.currentCustomer) {
            getList();
        }
    }


    function getList() {
        var entities = [{
            name: 'dashboard_business_program', criteria: {
                customerId: $scope.currentCustomer.id
            }
        }];

        ListService.getDataList(entities)
            .then(function (response) {
                // $scope.periods = response.data.data.dashboardBusinessProgramPeriods;
                // $scope.workplaceList = response.data.data.dashboardBusinessProgramWorkplaces;

                $scope.allFilters = response.data.data.dashboardBusinessProgramWorkplaces;

                $scope.allFilters.forEach(function (item) {
                    var exists = $scope.workplaceList.some(function (workplace) {
                        return workplace.value == item.id;
                    });

                    if (!exists) {
                        $scope.workplaceList.push({ value: item.id, name: item.name });
                    }
                })

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function getCharts() {
        if (!$scope.entity.period || !$scope.entity.workplace) {
            return;
        }

        var $criteria = {
            customerId: $scope.currentCustomer.id,
            period: $scope.entity.period ? $scope.entity.period.value : null,
            workplaceId: $scope.entity.workplace ? $scope.entity.workplace.value : null
        };

        var entities = [
            { name: 'chart_bar_with_scales_options', criteria: null },
            { name: 'dashboard_occupational_investigation_data', criteria: $criteria }
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                $scope.chart.bar.options = response.data.data.chartBarOptionsWithScales;
                $scope.chart.data.programs = response.data.data.dashboardOccupationalInvestigationData;
                $scope.chart.data.rates = response.data.data.dashboardOccupationalInvestigationRates;

                $scope.chart.data.programs.map(function (item) {
                    item.options = angular.copy($scope.options)
                    return item;
                });

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function loadPeriods() {
        $scope.periods = [];

        $scope.allFilters
            .filter(function (item) {
                return item.id == $scope.entity.workplace.value;
            })
            .forEach(function (item) {
                var exists = $scope.periods.some(function (period) {
                    return period.value == item.period;
                })

                if (!exists) {
                    $scope.periods.push({ value: item.period, item: item.period });
                }
            });
    }

});
