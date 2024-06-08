'use strict';
/**
  * controller for Customers
*/
app.controller('positivaFgnIndicatorsCtrl',
function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
    $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ChartService, ListService, ModuleListService) {

    $scope.entity = {
        userId: $rootScope.$id,
        selectedYear : null,
        periodChart: null
    };

    var initialize = function() {
        $scope.entity = {
            period: null,
            sectional: null,
            axis: null
        };
    };

    initialize();
    getParameters();
    loadFilters();


    $scope.activeTabIndex = 0;
    $scope.periodList = [];
    $scope.sectionalList = [];
    $scope.axisList = [];
    $scope.actionList = [];


    $scope.experienceOptionsList = [];
    $scope.sceneOptionsList = [];
    $scope.sceneList = [];

    $scope.chart = {
        bar: { options: null },
        doughnut: { options: null },
        genre: null,
        genreTotal: null,
        complianceBySectional: null,
    };


    $scope.form = {
        submit: function (form) {
            $scope.Form = form;

            if (form.$invalid) {
                var field = null, firstError = null;
                for (field in form) {
                    if (field[0] != '$') {
                        if (firstError === null && !form[field].$valid) {
                            firstError = form[field].$name;
                        }

                        if (form[field].$pristine) {
                            form[field].$dirty = true;
                        }
                    }
                }

                angular.element('.ng-invalid[name=' + firstError + ']').focus();
                SweetAlert.swal("El formulario contiene errores!", "Por favor corrige los errores del formulario e intentalo de nuevo.", "error");
                return;
            } else {
                updateIndicators();
            }
        },
        reset: function () {
            if ($scope.Form) {
                $scope.Form.$setPristine(true);
            }
            initialize();
        }
    };


    function updateIndicators() {
        if ($scope.entity.period == null || $scope.entity.sectional == null || $scope.entity.axis == null) {
            return;
        }

        getCharts();
        loadIndicators();
    }


    function getCharts() {
        var data = {
            period: $scope.entity.period.value,
            sectional: $scope.entity.sectional.value,
            axis: $scope.entity.axis.value,
            userId: $rootScope.$id,
        }

        var chart = [
            {
               name: "positiva_fng_indicators_charts",
                criteria: data
            },
            {name: "chart_bar_options"},
            {name: "chart_doughnut_options"},
        ];

        ChartService.getDataChart(chart)
            .then(function (response) {
                // chart dona
                $scope.chart.genre = response.data.data.genre;
                $scope.chart.genreTotal = response.data.data.genreTotal;

                // chart barra
                $scope.chart.bar.options = response.data.data.chartBarOptions;
                $scope.doughnut = response.data.data.chartDoughnutOptions;
                $scope.chart.complianceBySectional = response.data.data.complianceBySectional;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function getParameters() {
        var list = [
            {name: 'positiva_fgn_consultant_all_sectional', criteria: { userId: $rootScope.$id }}
        ];

        ListService.getDataList(list)
            .then(function (response) {
                $scope.sectionalList = response.data.data.sectionalList;
            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }


    function loadFilters() {
        var entities = [
            {name: 'positiva_fgn_period', value: null},
            {name: 'axisByUserIdConsultantList', value: $rootScope.$id },
        ];

        ModuleListService.getDataList("/positiva-fgn-fgn-management/config", entities)
            .then(function (response) {
                $scope.periodList = response.data.result.positivaFgnPeriod;
                $scope.axisList = response.data.result.axisByUserIdConsultantList;
            }, function (error) {
                $scope.status = 'Unable to load activity data: ' + error.message;
            });
    }


    $scope.onConsolidate = function () {
        var data = {
            userId: $rootScope.$id,
        }
        var req = { data: Base64.encode(JSON.stringify(data)) };
        $http({
            method: 'POST',
            url: 'api/positiva-fgn-fgn-indicator/consolidated',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            updateIndicators();
        }).catch(function (e) {
            SweetAlert.swal("Error", "Ocurrió un problema cargar la información.", "error");
        });
    }

    $scope.validateConsolidated = function () {
        if (($scope.entity.period && $scope.entity.period.value) && ($scope.entity.sectional && $scope.entity.sectional.value) && ($scope.entity.axis && $scope.entity.axis.value)) {
            return false;
        }

        return true;
    }


    function loadIndicators() {
        var data = {
            period: $scope.entity.period.value,
            sectional: $scope.entity.sectional.value,
            axis: $scope.entity.axis.value,
            userId: $rootScope.$id,
        }
        var req = { data: Base64.encode(JSON.stringify(data)) };
        $http({
            method: 'POST',
            url: 'api/positiva-fgn-fgn-indicator/indicators',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.actionList = response.data.result;

            $timeout(function () {
                angular.element("li.parentTab0 > a.nav-link").triggerHandler('click');
            });
        }).catch(function (e) {
            SweetAlert.swal("Error", "Ocurrió un problema cargar la información.", "error");
        });
    }

});