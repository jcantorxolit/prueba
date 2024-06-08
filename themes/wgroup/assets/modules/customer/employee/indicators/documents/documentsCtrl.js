'use strict';
/**
 * controller for Customers Employees
 */
app.controller('customerEmployeeIndicatorsDocumentsCtrl', function ($scope, $stateParams, ListService, ChartService, $http, $log, SweetAlert) {

    $scope.yearList = [];
    $scope.workplaces = [];

    $scope.totalDocuments = 0;

    $scope.filters = {
        year: null,
        workplace: null
    }

    $scope.chart = {
        line: {options: null},
        doughnut: {options: null},
        barWithScales: {options: null},
        data: {
            documentsByStatus: null,
            authorizedDocuments: null,

            documentsByWorkplaces: null,
            statusDocumentsByWorkplaces: null,
            authorizedDocumentsByWorkplaces: null,

            amountDocumentsByPeriod: null,
            amountDocumentsByStatusByPeriod: null,
            amountApprovedVsDeniedDocumentsByPeriod: null,
        }
    };


    getList();
    getWorkplaces();


    $scope.onChangeWorkplace = function () {
        getCharts();
    };

    $scope.onClearFilterWorkplace = function () {
        $scope.filters.workplace = null;
        getCharts();
    };


    $scope.onConsolidate = function () {
        var req = {
            customerId: $stateParams.customerId
        }

        return $http({
            method: 'POST',
            url: 'api/customer-employee-indicators/consolidateSupportDocuments',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function () {
            getList();
            SweetAlert.swal("Proceso Exitoso", ".", "success");
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error al consolidar", e.data.message, "error");
        });
    };


    function getList() {
        var entities = [{
            name: 'customer_employee_indicators_years',
            criteria: {customerId: $stateParams.customerId}
        }];

        ListService.getDataList(entities)
            .then(function (response) {
                $scope.yearList = response.data.data.customerEmployeeIndicatorsYears;
                if ($scope.yearList.length) {
                    $scope.filters.year = $scope.yearList[0];
                    getCharts();
                }

            }, function (error) {
                $scope.status = "Unable to load customer data: " + error.message;
            });
    }


    function getWorkplaces() {
        var req = {customerId: $stateParams.customerId}

        return $http({
            method: 'POST',
            url: 'api/customer/config-sgsst/workplace/listProcess',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            data: $.param(req)
        }).then(function (response) {
            $scope.workplaces = response.data.data;
        });
    }


    function setupCharts(data) {
        $scope.chart.line.options = data.chartLineOptions;
        $scope.chart.line.options.legend.position = 'top';
        $scope.chart.line.options.scales.xAxes = [{
            ticks: {
                autoSkip: false
            }
        }];

        $scope.chart.doughnut.options = data.chartDoughnutOptions;
        $scope.chart.doughnut.options.legend.position = 'top';
        $scope.chart.doughnut.options.maintainAspectRatio = false;
        $scope.chart.doughnut.options.responsive = false;

        $scope.chart.barWithScales.options = data.chartBarOptionsWithScales;
        $scope.chart.barWithScales.options.legend.position = 'top';

        // show all columns
        var xAxes = $scope.chart.barWithScales.options.scales.xAxes;
        xAxes[0].ticks = {
            autoSkip: false
        }
        $scope.chart.barWithScales.options.scales.xAxes = xAxes;
    }


    function getCharts() {
        var entities = [
            {name: 'chart_line_options', criteria: null},
            {name: 'chart_doughnut_options', criteria: null},
            {name: 'chart_bar_with_scales_options', criteria: null},
            {
                name: 'customer_employee_documents_indicators', criteria: {
                    customerId: $stateParams.customerId,
                    year: $scope.filters.year.year,
                    workplace: $scope.filters.workplace == null ? null : $scope.filters.workplace.id
                }
            },
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {
                setupCharts(response.data.data);

                $scope.totalDocuments = response.data.data.customerEmployeeIndicatorsDocumentsTotal;
                $scope.chart.data.documentsByStatus = response.data.data.customerEmployeeIndicatorsDocumentsByStatus;
                $scope.chart.data.authorizedDocuments = response.data.data.customerEmployeeIndicatorsDocumentsAuthorized;

                $scope.chart.data.documentsByWorkplaces = response.data.data.customerEmployeeIndicatorsDocumentsByWorkplaces;
                $scope.chart.data.statusDocumentsByWorkplaces = response.data.data.customerEmployeeIndicatorsStatusDocumentsByWorkplaces;
                $scope.chart.data.authorizedDocumentsByWorkplaces = response.data.data.customerEmployeeIndicatorsAuthorizedByWorkplaces;

                $scope.chart.data.amountDocumentsByPeriod = response.data.data.customerEmployeeIndicatorsDocumentsByPeriod;
                $scope.chart.data.amountDocumentsByStatusByPeriod = response.data.data.customerEmployeeIndicatorsDocumentsByStatusByPeriod;
                $scope.chart.data.amountApprovedVsDeniedDocumentsByPeriod = response.data.data.customerEmployeeIndicatorsAuthorizedDocumentsByPeriod;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
