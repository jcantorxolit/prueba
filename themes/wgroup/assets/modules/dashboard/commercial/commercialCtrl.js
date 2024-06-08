'use strict';
/**
 * controller for Dashboard Top Management
 */
app.controller('CommercialDashboardCtrl', function ($scope, $stateParams, $log, DTOptionsBuilder, DTColumnBuilder, DTColumnDefBuilder, $compile, toaster, $state,
                                                    $rootScope, $timeout, $http, SweetAlert, ChartService, $filter, ListService, ngNotify) {

    if(!$rootScope.canShowCommercialDashboard()) {
        $state.go('app.clientes.list', {reload: true});
    }

    $scope.licenseList = $rootScope.parameters("wg_customer_licenses_types");

    $scope.filter = {
        licenseType: null
    }

    $scope.chart = {
        line: {options: null},
        pie: {options: null},
        data: {
            amountLicensesByYearsHistorical: null,
            amountLicensesByTypeAndYearsHistorical: null,
            amountActiveLicensesByType: null,
            amountActiveLicensesByState: null,
        }
    };

    getCharts();


    $scope.dtInstanceCommercialDashboard = {};
    $scope.dtOptionsCommercialDashboard = DTOptionsBuilder.newOptions()
        .withBootstrap()
        .withOption('responsive', true)
        .withOption('ajax', {
            data: function (d) {
                d.license = $scope.filter.licenseType == null ? null : $scope.filter.licenseType.item;
                return JSON.stringify(d);
            },
            url: 'api/dashboard/commercial/next-expired',
            type: 'POST',
        })
        .withDataProp('data')
        .withOption('order', [
            [3, 'desc']
        ])
        .withOption('serverSide', true)
        .withOption('processing', true)
        .withOption('fnPreDrawCallback', function () {
            return true;
        })
        .withPaginationType('full_numbers')
        .withOption('createdRow', function (row) {
            $compile(angular.element(row).contents())($scope);
        });


    $scope.dtColumnsCommercialDashboard = [
        DTColumnBuilder.newColumn('customer').withTitle("Cliente").withOption('width', 200),
        DTColumnBuilder.newColumn('license').withTitle("Tipo de Licencia"),
        DTColumnBuilder.newColumn('startDate').withTitle("Fecha Inicio"),
        DTColumnBuilder.newColumn('finishDate').withTitle("Fecha Finalización"),
        DTColumnBuilder.newColumn('agent').withTitle("Comercial"),
        DTColumnBuilder.newColumn('value').withTitle("Valor")
            .renderWith(function (data) {
                return "$ " + $filter('number')(data, 2);
            }),
        DTColumnBuilder.newColumn('state').withTitle("Estado")
    ];


    $scope.reloadData = function () {
        $scope.dtInstanceCommercialDashboard.reloadData();
    };


    $scope.onConsolidate = function () {
        return $http({
            method: 'POST',
            url: 'api/dashboard/commercial/consolidate',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        }).then(function () {
            $scope.refreshAll();
            SweetAlert.swal("Proceso Exitoso", ".", "success");
        }).catch(function (e) {
            $log.error(e);
            SweetAlert.swal("Error al consolidar", e.data.message, "error");
        });
    };


    $scope.onChangeLicenseTypeFilter = function () {
        $scope.reloadData();
    };


    $scope.onClearLicenseTypeFilter = function () {
        $scope.filter.licenseType = null;
        $scope.reloadData();
    };


    $scope.refreshAll = function () {
        getCharts();
        $scope.reloadData();
    };


    function getCharts() {
        var entities = [
            {name: 'chart_line_options', criteria: null},
            {name: 'chart_pie_options', criteria: null},
            {name: 'dashboard_commercial_summary', criteria: null},
        ];

        ChartService.getDataChart(entities)
            .then(function (response) {

                // Graphics Bar Settings
                $scope.chart.pie.options = response.data.data.chartPieOptions;
                $scope.chart.pie.options.legend.position = 'bottom';

                $scope.chart.line.options = angular.copy(response.data.data.chartLineOptions);
                $scope.chart.line.options.legend.position = 'bottom';


                // set data
                $scope.chart.data.amountLicensesByYearsHistorical = response.data.data.amountLicensesByYearsHistorical;
                $scope.chart.data.amountLicensesByTypeAndYearsHistorical = response.data.data.amountLicensesByTypeAndYearsHistorical;
                $scope.chart.data.amountActiveLicensesByType = response.data.data.amountActiveLicensesByType;
                $scope.chart.data.amountActiveLicensesByState = response.data.data.amountActiveLicensesByState;

            }, function (error) {
                $scope.status = 'Unable to load customer data: ' + error.message;
            });
    }

});
