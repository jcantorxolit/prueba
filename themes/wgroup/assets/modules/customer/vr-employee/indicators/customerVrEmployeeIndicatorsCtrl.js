'use strict';
/**
  * controller for Customers
*/
app.controller('customerVrEmployeeIndicatorsCtrl',
    function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
        $rootScope, $timeout, $http, SweetAlert, $document, $filter, $aside, ChartService, ListService) {

        var initialLoaded = false;

        $scope.entity = {
            selectedYear: null,
            customerId: $stateParams.customerId,
            experience: null,
            scene: null,
            periodChart: null,
            selectedRangeDates: null
        };

        $scope.dateRangePicker = {
            picker: null,
            min: moment().startOf('year').format('YYYY-MM-DD'),
            max: moment().endOf('year').format('YYYY-MM-DD'),
            clearable: true,
            options: {
                buttonClasses: 'btn',
                applyButtonClasses: 'btn-primary',
                cancelButtonClasses: 'btn-danger',
                locale: {
                    applyLabel: "Aplicar",
                    cancelLabel: 'Cancelar',
                    clearLabel: "Limpiar",
                    separator: ' - ',
                    format: "DD/MM/YYYY"
                },
                eventHandlers: {
                    'apply.daterangepicker': function (event, picker) {
                        onLoadRecord();
                        getCharts();
                    },
                    'cancel.daterangepicker': function (event, picker) {
                        onLoadRecord();
                        getCharts();
                    }
                }
            }
        };

        $scope.activeTabIndex = 0;
        $scope.periodList = [];
        $scope.experienceList = [];
        $scope.experienceOptionsList = [];
        $scope.sceneOptionsList = [];
        $scope.sceneList = [];
        $scope.experienceFilterList = [];
        $scope.chartLines = {
            customerId: $stateParams.customerId,
            experience: null,
            scene: null,
            period: [],
            data: []
        };

        $scope.chart = {
            bar: { options: null },
            doughnut: { options: null },
            lines: {
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                stepSize: 1
                            }
                        }]
                    }
                }
            },
            genre: null,
            competitorExperience: null,
            genreTotal: null,
        };

        function setDateRangeMinAndMax() {
            var $initialDate = $scope.entity.selectedYear.value + "-01-01";
            var $lastDate = $scope.entity.selectedYear.value + "-12-31";
            $scope.dateRangePicker.min = $initialDate;
            $scope.dateRangePicker.max = $lastDate;
        }

        function getCharts() {
            var chart = [{
                name: "customer_vr_employee_indicators_charts",
                criteria: { 
                    customerId: $stateParams.customerId, 
                    selectedYear: $scope.entity.selectedYear ? $scope.entity.selectedYear.value : null,
                    selectedDateRange: $scope.entity.selectedRangeDates ? $scope.entity.selectedRangeDates : null,
                    selectedExperience: $scope.entity.selectedExperience ? $scope.entity.selectedExperience.value : null,
                }
            },
            { name: "chart_bar_options" },
            ];

            ChartService.getDataChart(chart)
                .then(function (response) {
                    $scope.chart.bar.options = response.data.data.chartBarOptions;
                    $scope.chart.genre = response.data.data.genre;
                    $scope.chart.genreTotal = response.data.data.genreTotal;
                    $scope.chart.competitorExperience = response.data.data.competitorExperience;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        function getParameters() {

            var $selectedYear = $scope.entity.selectedYear ? $scope.entity.selectedYear.value : null;

            var list = [
                { name: 'customer_vr_employee_indicators_period_list', criteria: { customerId: $stateParams.customerId, period: $selectedYear } },
                { name: 'customer_vr_employee_experience_scenes_customer', value: $stateParams.customerId },
            ];

            ListService.getDataList(list)
                .then(function (response) {
                    $scope.periodList = response.data.data.periodList;
                    $scope.experienceOptionsList = response.data.data.experienceOptionsList;
                    $scope.sceneList = response.data.data.sceneOptionsList;
                    $scope.experienceFilterList = response.data.data.experienceFilterList;
                    if ($scope.periodList.length && !initialLoaded) {
                        initialLoaded = true;
                        $scope.entity.selectedYear = $scope.periodList[0];
                        setDateRangeMinAndMax();
                        getCharts();
                        onLoadRecord();
                        $scope.onClearOptions();
                        $scope.entity.periodChart = $scope.periodList[0];
                        $scope.chartLines.period.push($scope.entity.periodChart.value);
                    }
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });

        }

        getParameters();

        var onLoadRecord = function () {

            var req = {};
            var data = JSON.stringify($scope.entity);
            req.data = Base64.encode(data);

            $http({
                method: 'POST',
                url: 'api/customer-vr-employee-experience-answer/get-all',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $scope.experienceList = response.data.result;
                $timeout(function () {
                    angular.element("li.parentTab0 > a.nav-link").triggerHandler('click');
                });
            }).catch(function (e) {
                SweetAlert.swal("Error", "Ocurrió un problema cargar la información.", "error");
            });
        }

        $scope.onConsolidate = function () {

            var req = {};
            req.customerId = $stateParams.customerId;

            $http({
                method: 'POST',
                url: 'api/customer-vr-employee/consolidate',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                data: $.param(req)
            }).then(function (response) {
                $timeout(function () {
                    getParameters();
                });
            }).catch(function (e) {
                SweetAlert.swal("Error", "Ocurrió un problema cargar la información.", "error");
            });
        }

        $scope.onGenerateReport = function () {
            if (!$scope.entity.selectedYear) {
                SweetAlert.swal("Error", "Debe seleccionar el periodo.", "error");
                return;
            }

            var param = {
                customerId: $stateParams.customerId,
                selectedYear: $scope.entity.selectedYear ? $scope.entity.selectedYear.value : null,
                selectedDateRange: $scope.entity.selectedRangeDates ? $scope.entity.selectedRangeDates : null,
                selectedExperience: $scope.entity.selectedExperience ? $scope.entity.selectedExperience.value : null,
            };
            angular.element("#downloadDocument")[0].src = "api/customer-vr-employee/generate-report-pdf?data=" + Base64.encode(JSON.stringify(param));
        }

        $scope.onSelectYear = function () {
            $scope.entity.selectedRangeDates = null;
            $scope.entity.selectedExperience = null;
            if ($scope.entity.selectedYear) {
                setDateRangeMinAndMax();
            }
            getParameters();
            onLoadRecord();
            getCharts();
        }

        $scope.onSelectFilterExperience = function () {
            onLoadRecord();
            getCharts();
        }

        $scope.onClearFilterExperience = function () {
            onLoadRecord();
            getCharts();
        }

        $scope.onClearOptions = function () {
            $scope.entity.experience = null;
            $scope.entity.scene = null;
            $scope.entity.periodChart = null;
            $scope.chartLines.period = [];
            $scope.chartLines.data = [];
        }

        $scope.onSelectPeriod = function () {
            $scope.chartLines.period.push($scope.entity.periodChart.value);
            $scope.onSelectScene();
        }

        $scope.valideSingle = function (item) {
            if ($scope.chartLines.period.indexOf(item.value) > -1) {
                return true;
            }
            return false;
        }

        $scope.removePeriodChart = function (item) {
            $scope.chartLines.period.map(function (value, key) {
                if (item == value) {
                    $scope.chartLines.period.splice(key, 1);
                    return;
                }
            })
        }

        $scope.onSelectExperience = function () {
            var $filterScenes = $filter('filter')($scope.sceneList, { code: $scope.entity.experience.value }, true);
            $scope.sceneOptionsList = $filterScenes;
            $scope.entity.scene = null;
        }

        $scope.onSelectScene = function () {
            $scope.chartLines.data = [];
            $scope.chartLines.experience = $scope.entity.experience.value;
            $scope.chartLines.scene = $scope.entity.scene.value;

            var config = [{
                name: "customer_vr_employee_indicators_period_chart",
                criteria: $scope.chartLines
            }];

            ChartService.getDataChart(config)
                .then(function (response) {
                    $scope.chartLines.data = response.data.data.periodChart;
                }, function (error) {
                    $scope.status = 'Unable to load customer data: ' + error.message;
                });
        }

        $scope.onExportExcel = function () {
            var param = {
                customerId: $stateParams.customerId,
                selectedYear: $scope.entity.selectedYear ? $scope.entity.selectedYear.value : null,
                selectedDateRange: $scope.entity.selectedRangeDates ? $scope.entity.selectedRangeDates : null,
                selectedExperience: $scope.entity.selectedExperience ? $scope.entity.selectedExperience.value : null,
            };

            angular.element("#downloadDocument")[0].src = "api/customer-vr-employee/export-indicators?data=" + Base64.encode(JSON.stringify(param));
        }

    });
